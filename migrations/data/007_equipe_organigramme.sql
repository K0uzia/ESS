-- Équipe d'après l'organigramme du 15 octobre 2026 : un groupe « ESS Finistère » (salariés du groupement, sans pôle)
-- et un portrait par personne (photo_path). SQLite ne modifie pas un CHECK : la table est reconstruite.
CREATE TABLE team_members_new (
    id           INTEGER PRIMARY KEY,
    name         TEXT NOT NULL,
    role         TEXT NOT NULL,
    pole_id      INTEGER REFERENCES poles(id) ON DELETE SET NULL,
    team         TEXT NOT NULL DEFAULT 'salaries' CHECK (team IN ('groupement', 'salaries', 'ca', 'tag29')),
    structure    TEXT,
    email        TEXT,
    phone        TEXT,
    photo_path   TEXT,
    position     INTEGER NOT NULL DEFAULT 0,
    is_published INTEGER NOT NULL DEFAULT 1 CHECK (is_published IN (0, 1)),
    created_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO team_members_new (id, name, role, pole_id, team, structure, email, phone, position, is_published, created_at, updated_at)
    SELECT id, name, role, pole_id, team, structure, email, phone, position, is_published, created_at, updated_at FROM team_members;
DROP TABLE team_members;
ALTER TABLE team_members_new RENAME TO team_members;

-- Salariés des pôles et de TAg29 : seules les personnes de l'organigramme restent.
DELETE FROM team_members WHERE team IN ('salaries', 'tag29')
    AND name NOT IN ('Chloé MOULARD', 'Carine Sola', 'Samuel GENY', 'Deborah Hellequin', 'Eliane LE DUFF', 'Emilie CARIOU-MENES');
UPDATE team_members SET role = 'Chargée de Développement Territorial', photo_path = '/assets/img/equipe/chloe-moulard.webp', position = 1, updated_at = CURRENT_TIMESTAMP WHERE name = 'Chloé MOULARD' AND team = 'salaries';
UPDATE team_members SET name = 'Carine SOLA', role = 'Chargée de Développement Territorial', photo_path = '/assets/img/equipe/carine-sola.webp', position = 1, updated_at = CURRENT_TIMESTAMP WHERE name = 'Carine Sola' AND team = 'salaries';
UPDATE team_members SET role = 'Chargé de Développement Territorial', photo_path = '/assets/img/equipe/samuel-geny.webp', position = 1, updated_at = CURRENT_TIMESTAMP WHERE name = 'Samuel GENY' AND team = 'salaries';
UPDATE team_members SET name = 'Déborah HELLEQUIN', role = 'Référente Incubateur', photo_path = '/assets/img/equipe/deborah-hellequin.webp', position = 1, updated_at = CURRENT_TIMESTAMP WHERE name = 'Deborah Hellequin' AND team = 'tag29';

-- Salariés du groupement (organigramme), sans pôle ni téléphone de pôle.
UPDATE team_members SET name = 'Eliane LE DUFF', role = 'Directrice', pole_id = NULL, team = 'groupement', phone = NULL, photo_path = '/assets/img/equipe/eliane-le-duff.webp', position = 1, updated_at = CURRENT_TIMESTAMP WHERE name = 'Eliane LE DUFF' AND team = 'salaries';
UPDATE team_members SET name = 'Emilie CARIOU MENES', role = 'Référente Coopérations Économiques', pole_id = NULL, team = 'groupement', phone = NULL, photo_path = '/assets/img/equipe/emilie-cariou-menes.webp', position = 2, updated_at = CURRENT_TIMESTAMP WHERE name = 'Emilie CARIOU-MENES' AND team = 'salaries';
INSERT INTO team_members (name, role, pole_id, team, position)
    SELECT 'Julie POURRIER-FRÉNET', 'Assistante Comptable', NULL, 'groupement', 3
    WHERE EXISTS (SELECT 1 FROM team_members) AND NOT EXISTS (SELECT 1 FROM team_members WHERE name = 'Julie POURRIER-FRÉNET');

UPDATE page_contents SET value = 'L''équipe salariée du groupement ESS Finistère, des trois Pôles ESS (Morlaix, Brest, Cornouaille) et de TAg29, et les conseils d''administration des pôles.', updated_at = CURRENT_TIMESTAMP
    WHERE page_key = 'equipe' AND section_key = '_meta' AND field_key = 'description';
UPDATE page_contents SET value = 'L''équipe salariée du groupement, des trois pôles et de TAg29, et les administrateurs bénévoles des pôles.', updated_at = CURRENT_TIMESTAMP
    WHERE page_key = 'equipe' AND section_key = '_meta' AND field_key = 'intro';

-- Page TAg29, section Contacts : même équipe que l'organigramme.
UPDATE page_contents SET value = replace(value, 'L''équipe de TAg29 : Flavie de Jaurias, coordinatrice et responsable de l''Incubateur, et Deborah Hellequin, chargée de mission Incubateur et Idéateur.', 'L''équipe de TAg29 : Déborah Hellequin, référente Incubateur.'), updated_at = CURRENT_TIMESTAMP
    WHERE page_key = 'tag29' AND section_key = 'contacts' AND field_key = 'body';
