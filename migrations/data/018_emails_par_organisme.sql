-- E-mails par organisme et rôles (décision du 20 octobre 2026).
-- Organismes : les trois pôles (clé = slug), ESS29 (« ess29 ») et TAg29 (« tag29 »).
-- Chacun a une adresse qui reçoit le formulaire de contact, une adresse de réponse, et deux newsletters
-- (adhérents, inscrits via le site) avec chacune son adresse d'expédition.

-- Adresses de réception et de réponse.
CREATE TABLE mail_addresses (
    org     TEXT NOT NULL,
    purpose TEXT NOT NULL CHECK (purpose IN ('contact', 'reponse')),
    email   TEXT NOT NULL DEFAULT '',
    PRIMARY KEY (org, purpose)
);
INSERT INTO mail_addresses (org, purpose, email) SELECT slug, 'contact', contact_email FROM poles;
INSERT INTO mail_addresses (org, purpose, email) SELECT slug, 'reponse', email FROM poles;
INSERT INTO mail_addresses (org, purpose, email) VALUES
    ('ess29', 'contact', COALESCE((SELECT value FROM settings WHERE key = 'email_contact'), '')),
    ('ess29', 'reponse', COALESCE((SELECT value FROM settings WHERE key = 'email_contact'), '')),
    ('tag29', 'contact', COALESCE((SELECT value FROM settings WHERE key = 'email_contact_tag29'), '')),
    ('tag29', 'reponse', COALESCE((SELECT value FROM settings WHERE key = 'email_contact_tag29'), ''));

-- Adresses qui envoient : une boîte Hostinger (mot de passe chiffré avec APP_KEY) ou un alias d'une boîte (« box »).
CREATE TABLE mail_senders (
    email      TEXT PRIMARY KEY COLLATE NOCASE,
    box        TEXT NOT NULL DEFAULT '' COLLATE NOCASE,
    password   TEXT NOT NULL DEFAULT '',
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO mail_senders (email, password)
    SELECT u.value, COALESCE((SELECT value FROM settings WHERE key = 'smtp_password'), '')
    FROM settings u WHERE u.key = 'smtp_user' AND u.value <> '';
-- Expéditeur des e-mails automatiques du site (contact, confirmation d'inscription) ; celui des comptes reste email_comptes.
INSERT INTO settings (key, value) SELECT 'email_envoi', COALESCE((SELECT value FROM settings WHERE key = 'smtp_user'), '');
DELETE FROM settings WHERE key IN ('smtp_user', 'smtp_password', 'smtp_host', 'smtp_port', 'email_contact_tag29');

-- Listes de newsletters : deux par organisme. Celles d'ESS29 et de TAg29 existent mais restent fermées tant qu'un
-- administrateur ne les ouvre pas dans E-mails.
CREATE TABLE newsletter_lists (
    id           INTEGER PRIMARY KEY,
    org          TEXT NOT NULL,
    audience     TEXT NOT NULL CHECK (audience IN ('adherents', 'inscrits')),
    sender_email TEXT NOT NULL DEFAULT '',
    is_open      INTEGER NOT NULL DEFAULT 1 CHECK (is_open IN (0, 1)),
    UNIQUE (org, audience)
);
INSERT INTO newsletter_lists (org, audience, sender_email) SELECT slug, 'adherents', newsletter_email FROM poles ORDER BY position;
INSERT INTO newsletter_lists (org, audience, sender_email) SELECT slug, 'inscrits', '' FROM poles ORDER BY position;
INSERT INTO newsletter_lists (org, audience, is_open) VALUES ('ess29', 'adherents', 0), ('ess29', 'inscrits', 0), ('tag29', 'adherents', 0), ('tag29', 'inscrits', 0);

-- Abonnés et lettres rattachés à une liste. Les livraisons sont mises de côté le temps de recréer les tables
-- (clés étrangères actives : supprimer une table référencée effacerait les livraisons en cascade).
CREATE TABLE newsletter_deliveries_copie AS SELECT * FROM newsletter_deliveries;
DROP TABLE newsletter_deliveries;

-- Inscrits par le formulaire du site → liste « inscrits » ; imports et ajouts manuels → liste « adhérents ».
CREATE TABLE newsletter_subscribers_new (
    id              INTEGER PRIMARY KEY,
    list_id         INTEGER NOT NULL REFERENCES newsletter_lists(id) ON DELETE CASCADE,
    email           TEXT NOT NULL COLLATE NOCASE,
    status          TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'unsubscribed')),
    token           TEXT NOT NULL UNIQUE,
    confirm_token   TEXT,
    source          TEXT NOT NULL DEFAULT 'formulaire',
    consent_at      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at    TEXT,
    unsubscribed_at TEXT,
    UNIQUE (list_id, email)
);
INSERT INTO newsletter_subscribers_new (id, list_id, email, status, token, confirm_token, source, consent_at, confirmed_at, unsubscribed_at)
    SELECT s.id, l.id, s.email, s.status, s.token, s.confirm_token, s.source, s.consent_at, s.confirmed_at, s.unsubscribed_at
    FROM newsletter_subscribers s JOIN poles p ON p.id = s.pole_id
    JOIN newsletter_lists l ON l.org = p.slug AND l.audience = CASE WHEN s.source = 'formulaire' THEN 'inscrits' ELSE 'adherents' END;
DROP TABLE newsletter_subscribers;
ALTER TABLE newsletter_subscribers_new RENAME TO newsletter_subscribers;

-- Les lettres déjà écrites l'ont été pour les adhérents (expéditeur adherents-…).
CREATE TABLE newsletter_campaigns_new (
    id          INTEGER PRIMARY KEY,
    list_id     INTEGER NOT NULL REFERENCES newsletter_lists(id) ON DELETE CASCADE,
    template_id INTEGER REFERENCES newsletter_templates(id) ON DELETE SET NULL,
    subject     TEXT NOT NULL,
    body        TEXT NOT NULL,
    status      TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'queued', 'sending', 'sent')),
    created_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    sent_at     TEXT,
    created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO newsletter_campaigns_new (id, list_id, template_id, subject, body, status, created_by, sent_at, created_at, updated_at)
    SELECT c.id, l.id, c.template_id, c.subject, c.body, c.status, c.created_by, c.sent_at, c.created_at, c.updated_at
    FROM newsletter_campaigns c JOIN poles p ON p.id = c.pole_id JOIN newsletter_lists l ON l.org = p.slug AND l.audience = 'adherents';
DROP TABLE newsletter_campaigns;
ALTER TABLE newsletter_campaigns_new RENAME TO newsletter_campaigns;

CREATE TABLE newsletter_deliveries (
    id            INTEGER PRIMARY KEY,
    campaign_id   INTEGER NOT NULL REFERENCES newsletter_campaigns(id) ON DELETE CASCADE,
    subscriber_id INTEGER NOT NULL REFERENCES newsletter_subscribers(id) ON DELETE CASCADE,
    status        TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed')),
    error         TEXT,
    sent_at       TEXT,
    UNIQUE (campaign_id, subscriber_id)
);
INSERT INTO newsletter_deliveries SELECT * FROM newsletter_deliveries_copie
    WHERE campaign_id IN (SELECT id FROM newsletter_campaigns) AND subscriber_id IN (SELECT id FROM newsletter_subscribers);
DROP TABLE newsletter_deliveries_copie;

ALTER TABLE poles DROP COLUMN contact_email;
ALTER TABLE poles DROP COLUMN newsletter_email;

-- Rôles : un éditeur est rattaché à un pôle (pole_id) ou à ESS29 / TAg29 (org).
ALTER TABLE users ADD COLUMN org TEXT CHECK (org IN ('ess29', 'tag29'));
UPDATE users SET org = 'tag29' WHERE role = 'editor' AND pole_id IS NULL;
