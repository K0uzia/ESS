-- Boîte Hostinger réelle : mail@ess29.com, qui envoie les e-mails des comptes (décision du 20 octobre 2026).
-- Son mot de passe se saisit dans E-mails. Les expéditeurs des newsletters « adhérents » des pôles sont rattachés
-- comme alias de cette boîte (à confirmer chez Hostinger ; modifiable dans E-mails).
INSERT OR IGNORE INTO mail_senders (email, box, password) VALUES ('mail@ess29.com', '', '');
INSERT OR IGNORE INTO mail_senders (email, box, password)
    SELECT sender_email, 'mail@ess29.com', '' FROM newsletter_lists WHERE sender_email LIKE 'adherents-%@ess29.com';
UPDATE settings SET value = 'mail@ess29.com', updated_at = CURRENT_TIMESTAMP WHERE key = 'email_comptes';
