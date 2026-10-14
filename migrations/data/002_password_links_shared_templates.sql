-- Décisions du 14 octobre 2026.

-- Création et changement de mot de passe par lien envoyé par e-mail : seul le hachage du jeton est conservé.
CREATE TABLE password_tokens (
    id         INTEGER PRIMARY KEY,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash TEXT NOT NULL UNIQUE,
    purpose    TEXT NOT NULL CHECK (purpose IN ('invitation', 'reinitialisation')),
    expires_at TEXT NOT NULL,
    used_at    TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX password_tokens_user ON password_tokens (user_id);

-- Modèles de newsletter communs aux trois pôles ; chaque lettre reste rattachée à un pôle.
-- La suppression de l'ancienne table remet template_id à NULL (ON DELETE SET NULL) : on le restaure ensuite.
CREATE TEMP TABLE campaign_templates AS SELECT id, template_id FROM newsletter_campaigns WHERE template_id IS NOT NULL;
CREATE TABLE newsletter_templates_new (
    id         INTEGER PRIMARY KEY,
    name       TEXT NOT NULL,
    header     TEXT NOT NULL DEFAULT '',
    footer     TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO newsletter_templates_new (id, name, header, footer, created_at, updated_at)
    SELECT id, name, header, footer, created_at, updated_at FROM newsletter_templates;
DROP TABLE newsletter_templates;
ALTER TABLE newsletter_templates_new RENAME TO newsletter_templates;
UPDATE newsletter_campaigns
    SET template_id = (SELECT t.template_id FROM campaign_templates t WHERE t.id = newsletter_campaigns.id)
    WHERE id IN (SELECT id FROM campaign_templates);
DROP TABLE campaign_templates;

-- Hébergeur : La Capsule (serveur à Morlaix). Téléphone non fourni : reste à compléter.
UPDATE settings
    SET value = 'La Capsule, chantier d''insertion numérique de l''ULAMIR CPIE' || char(10)
             || 'Centre Afpa, 39 Bellevue de la Madeleine, 29600 Morlaix' || char(10)
             || 'Téléphone : __À_REMPLIR__' || char(10)
             || 'contact@lacapsule.org',
        updated_at = CURRENT_TIMESTAMP
    WHERE key = 'hebergeur' AND value = '__À_REMPLIR__';
