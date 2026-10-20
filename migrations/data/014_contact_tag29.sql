-- Formulaire de contact : destinataire TAg29, en plus d'ESS29 et des trois pôles (décision du 19 octobre 2026).
INSERT INTO settings (key, value) VALUES ('email_contact_tag29', 'bonjour@ess29.org');
UPDATE page_contents SET value = replace(value, 'l''équipe du pôle choisi dans le formulaire, ou celle du groupement à l''adresse bonjour@ess29.org si aucun pôle n''est choisi ;', 'l''équipe choisie dans le formulaire (un des trois pôles, ESS29 ou TAg29) ;'), updated_at = CURRENT_TIMESTAMP WHERE page_key = 'confidentialite' AND field_key = 'body';
