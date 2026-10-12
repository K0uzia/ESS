PRAGMA foreign_keys = ON;

CREATE TABLE schema_migrations (
    name       TEXT PRIMARY KEY,
    applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Identité du groupement : source unique pour pied de page, mentions légales et JSON-LD.
CREATE TABLE settings (
    key        TEXT PRIMARY KEY,
    value      TEXT NOT NULL,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE poles (
    id             INTEGER PRIMARY KEY,
    slug           TEXT NOT NULL UNIQUE,
    name           TEXT NOT NULL,
    address_lines  TEXT NOT NULL,
    postal_code    TEXT NOT NULL,
    city           TEXT NOT NULL,
    phones         TEXT NOT NULL,
    email          TEXT NOT NULL,
    is_head_office INTEGER NOT NULL DEFAULT 0 CHECK (is_head_office IN (0, 1)),
    position       INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE users (
    id            INTEGER PRIMARY KEY,
    username      TEXT NOT NULL UNIQUE COLLATE NOCASE,
    email         TEXT UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'admin' CHECK (role IN ('admin', 'editor')),
    pole_id       INTEGER REFERENCES poles(id) ON DELETE SET NULL,
    last_login_at TEXT,
    created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
    id           INTEGER PRIMARY KEY,
    ip           TEXT NOT NULL,
    attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX login_attempts_ip ON login_attempts (ip, attempted_at);

-- Limite de débit des formulaires publics.
CREATE TABLE rate_limits (
    id         INTEGER PRIMARY KEY,
    bucket     TEXT NOT NULL,
    ip         TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX rate_limits_bucket ON rate_limits (bucket, ip, created_at);

CREATE TABLE audit_log (
    id         INTEGER PRIMARY KEY,
    user_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action     TEXT NOT NULL,
    target     TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Blocs de contenu éditables (nom et colonnes attendus par setup-local.sh reset).
CREATE TABLE page_contents (
    page_key     TEXT NOT NULL,
    section_key  TEXT NOT NULL,
    field_key    TEXT NOT NULL,
    content_type TEXT NOT NULL DEFAULT 'markdown' CHECK (content_type IN ('text', 'markdown', 'url')),
    value        TEXT NOT NULL,
    position     INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (page_key, section_key, field_key)
);

CREATE TABLE team_members (
    id           INTEGER PRIMARY KEY,
    name         TEXT NOT NULL,
    role         TEXT NOT NULL,
    pole_id      INTEGER REFERENCES poles(id) ON DELETE SET NULL,
    team         TEXT NOT NULL DEFAULT 'salaries' CHECK (team IN ('salaries', 'ca', 'tag29')),
    structure    TEXT,
    email        TEXT,
    phone        TEXT,
    position     INTEGER NOT NULL DEFAULT 0,
    is_published INTEGER NOT NULL DEFAULT 1 CHECK (is_published IN (0, 1)),
    created_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Adhérents des pôles.
CREATE TABLE members (
    id           INTEGER PRIMARY KEY,
    pole_id      INTEGER NOT NULL REFERENCES poles(id) ON DELETE CASCADE,
    name         TEXT NOT NULL,
    status       TEXT,
    url          TEXT,
    address      TEXT,
    latitude     REAL,
    longitude    REAL,
    position     INTEGER NOT NULL DEFAULT 0,
    is_published INTEGER NOT NULL DEFAULT 1 CHECK (is_published IN (0, 1))
);

CREATE TABLE job_offers (
    id           INTEGER PRIMARY KEY,
    pole_id      INTEGER REFERENCES poles(id) ON DELETE SET NULL,
    title        TEXT NOT NULL,
    body         TEXT NOT NULL,
    contract     TEXT,
    location     TEXT,
    apply_url    TEXT,
    apply_email  TEXT,
    closes_on    TEXT,
    is_published INTEGER NOT NULL DEFAULT 0 CHECK (is_published IN (0, 1)),
    created_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE documents (
    id           INTEGER PRIMARY KEY,
    pole_id      INTEGER REFERENCES poles(id) ON DELETE SET NULL,
    category     TEXT NOT NULL CHECK (category IN ('document', 'fiche_pratique', 'ressource', 'certificat')),
    title        TEXT NOT NULL,
    description  TEXT,
    year         INTEGER,
    source_url   TEXT,
    file_path    TEXT NOT NULL,
    mime         TEXT NOT NULL,
    size         INTEGER NOT NULL,
    position     INTEGER NOT NULL DEFAULT 0,
    is_published INTEGER NOT NULL DEFAULT 1 CHECK (is_published IN (0, 1)),
    created_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE partners (
    id           INTEGER PRIMARY KEY,
    kind         TEXT NOT NULL CHECK (kind IN ('partenaire', 'reseau')),
    pole_id      INTEGER REFERENCES poles(id) ON DELETE SET NULL,
    name         TEXT NOT NULL,
    url          TEXT,
    logo_path    TEXT,
    position     INTEGER NOT NULL DEFAULT 0,
    is_published INTEGER NOT NULL DEFAULT 1 CHECK (is_published IN (0, 1))
);

CREATE TABLE media (
    id         INTEGER PRIMARY KEY,
    path       TEXT NOT NULL UNIQUE,
    variants   TEXT NOT NULL DEFAULT '{}',
    alt        TEXT NOT NULL DEFAULT '',
    width      INTEGER NOT NULL,
    height     INTEGER NOT NULL,
    mime       TEXT NOT NULL,
    size       INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE contact_messages (
    id         INTEGER PRIMARY KEY,
    pole_id    INTEGER REFERENCES poles(id) ON DELETE SET NULL,
    name       TEXT NOT NULL,
    email      TEXT NOT NULL,
    message    TEXT NOT NULL,
    mail_sent  INTEGER NOT NULL DEFAULT 0 CHECK (mail_sent IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Newsletters : une liste par pôle, plusieurs modèles par pôle.
CREATE TABLE newsletter_subscribers (
    id              INTEGER PRIMARY KEY,
    pole_id         INTEGER NOT NULL REFERENCES poles(id) ON DELETE CASCADE,
    email           TEXT NOT NULL COLLATE NOCASE,
    status          TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'unsubscribed')),
    token           TEXT NOT NULL UNIQUE,
    confirm_token   TEXT,
    source          TEXT NOT NULL DEFAULT 'formulaire',
    consent_at      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at    TEXT,
    unsubscribed_at TEXT,
    UNIQUE (pole_id, email)
);

CREATE TABLE newsletter_templates (
    id         INTEGER PRIMARY KEY,
    pole_id    INTEGER NOT NULL REFERENCES poles(id) ON DELETE CASCADE,
    name       TEXT NOT NULL,
    header     TEXT NOT NULL DEFAULT '',
    footer     TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE newsletter_campaigns (
    id          INTEGER PRIMARY KEY,
    pole_id     INTEGER NOT NULL REFERENCES poles(id) ON DELETE CASCADE,
    template_id INTEGER REFERENCES newsletter_templates(id) ON DELETE SET NULL,
    subject     TEXT NOT NULL,
    body        TEXT NOT NULL,
    status      TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'queued', 'sending', 'sent')),
    created_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    sent_at     TEXT,
    created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE newsletter_deliveries (
    id            INTEGER PRIMARY KEY,
    campaign_id   INTEGER NOT NULL REFERENCES newsletter_campaigns(id) ON DELETE CASCADE,
    subscriber_id INTEGER NOT NULL REFERENCES newsletter_subscribers(id) ON DELETE CASCADE,
    status        TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed')),
    error         TEXT,
    sent_at       TEXT,
    UNIQUE (campaign_id, subscriber_id)
);
