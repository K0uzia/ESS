-- Formulaire de contact : chaque message va au pôle choisi, l'adresse générique reçoit les autres (décision du 19 octobre 2026).
ALTER TABLE poles ADD COLUMN contact_email TEXT NOT NULL DEFAULT '';
UPDATE poles SET contact_email = 'paysdemorlaix@ess29.org' WHERE slug = 'pays-de-morlaix';
UPDATE poles SET contact_email = 'paysdebrest@ess29.org' WHERE slug = 'pays-de-brest';
UPDATE poles SET contact_email = 'paysdecornouaille@ess29.org' WHERE slug = 'pays-de-cornouaille';
UPDATE page_contents SET value = replace(value, '| Destinataires | l''équipe du groupement, à l''adresse bonjour@ess29.org ; le prestataire d''envoi des e-mails, pour l''acheminement |', '| Destinataires | l''équipe du pôle choisi dans le formulaire, ou celle du groupement à l''adresse bonjour@ess29.org si aucun pôle n''est choisi ; le prestataire d''envoi des e-mails, pour l''acheminement |'), updated_at = CURRENT_TIMESTAMP WHERE page_key = 'confidentialite' AND field_key = 'body';
