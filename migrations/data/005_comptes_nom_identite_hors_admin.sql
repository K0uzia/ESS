-- Décisions du 14 octobre 2026 : comptes avec « Prénom et nom », connexion par e-mail ; identité et coordonnées
-- gérées hors du tableau de bord (données modifiées par La Capsule par migration) ; carte GoGoCarto retirée.
ALTER TABLE users ADD COLUMN name TEXT NOT NULL DEFAULT '';
UPDATE users SET name = username WHERE name = '';
DELETE FROM settings WHERE key = 'gogocarto_url';
UPDATE page_contents SET value = replace(value,
    char(10) || char(10) || 'Sur la page des adhérents, la carte GoGoCarto n''est chargée que si vous cliquez sur « Afficher la carte ». GoGoCarto reçoit alors votre adresse IP, comme tout site visité.', ''),
    updated_at = CURRENT_TIMESTAMP
    WHERE page_key = 'confidentialite' AND section_key = 'cookies' AND field_key = 'body';
