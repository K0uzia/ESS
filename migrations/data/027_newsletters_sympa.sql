-- Newsletters « adhérents » des pôles : listes de diffusion Sympa (sympa.ess29.com), décision du 20 octobre 2026.
-- Une liste Sympa reçoit la lettre une seule fois, à son adresse, et la distribue à ses abonnés (gérés dans Sympa).
-- sympa_address vide : abonnés gérés par le site, une lettre par abonné (inscrits via le site).
ALTER TABLE newsletter_lists ADD COLUMN sympa_address TEXT NOT NULL DEFAULT '';
-- Les adresses adherents-…@ess29.com étaient prises pour des expéditeurs : ce sont les adresses des listes.
UPDATE newsletter_lists SET sympa_address = sender_email, sender_email = ''
WHERE audience = 'adherents' AND sender_email LIKE 'adherents-%@ess29.com';
-- Alias supposés de mail@ess29.com (migration 021) : ce n'en sont pas, retirés s'ils n'ont pas été modifiés depuis.
DELETE FROM mail_senders WHERE email LIKE 'adherents-%@ess29.com' AND box = 'mail@ess29.com' AND password = '';
