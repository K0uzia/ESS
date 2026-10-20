-- État de la connexion de chaque boîte d'envoi (décision du 20 octobre 2026) : vérifiée à l'enregistrement du mot de passe,
-- puis à chaque envoi. checked_at NULL : jamais vérifiée ; check_error vide : connexion acceptée par Hostinger.
ALTER TABLE mail_senders ADD COLUMN checked_at TEXT;
ALTER TABLE mail_senders ADD COLUMN check_error TEXT NOT NULL DEFAULT '';
