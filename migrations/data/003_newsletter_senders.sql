-- Expéditeur des newsletters, distinct de l'e-mail de contact du pôle (décision du 14 octobre 2026).
ALTER TABLE poles ADD COLUMN newsletter_email TEXT NOT NULL DEFAULT '';
UPDATE poles SET newsletter_email = 'adherents-poleess-morlaix@ess29.com' WHERE slug = 'pays-de-morlaix';
UPDATE poles SET newsletter_email = 'adherents-paysdebrest@ess29.com' WHERE slug = 'pays-de-brest';
UPDATE poles SET newsletter_email = 'adherents-cornouaille@ess29.com' WHERE slug = 'pays-de-cornouaille';
