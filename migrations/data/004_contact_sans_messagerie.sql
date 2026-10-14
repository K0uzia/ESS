-- Formulaire de contact envoyé uniquement par e-mail à l'adresse générique ; plus de messagerie dans le tableau de bord
-- (décision du 14 octobre 2026). Les messages conservés sont supprimés avec la table.
DROP TABLE IF EXISTS contact_messages;

-- Politique de confidentialité alignée, sauf si le texte a déjà été modifié dans l'admin (replace sans effet).
UPDATE page_contents SET value = replace(value,
    '| Destinataires | l''équipe du pôle concerné ; l''hébergeur du site et le prestataire d''envoi des e-mails, pour l''acheminement |',
    '| Destinataires | l''équipe du groupement, à l''adresse bonjour@ess29.org ; le prestataire d''envoi des e-mails, pour l''acheminement |'),
    updated_at = CURRENT_TIMESTAMP
    WHERE page_key = 'confidentialite' AND section_key = 'contact' AND field_key = 'body';
UPDATE page_contents SET value = replace(value,
    '| Durée | 12 mois après l''envoi du message, puis suppression automatique |',
    '| Durée | le site ne garde pas le message ; il est conservé dans la messagerie du groupement 12 mois au plus |'),
    updated_at = CURRENT_TIMESTAMP
    WHERE page_key = 'confidentialite' AND section_key = 'contact' AND field_key = 'body';
UPDATE page_contents SET value = replace(value, 'e-mail facultatif', 'adresse e-mail pour l''invitation et les changements de mot de passe'),
    updated_at = CURRENT_TIMESTAMP
    WHERE page_key = 'confidentialite' AND section_key = 'comptes' AND field_key = 'body';
UPDATE page_contents SET value = replace(value, 'Hébergeur du site : __À_REMPLIR__',
    'Hébergeur du site : La Capsule, chantier d''insertion numérique de l''ULAMIR CPIE, serveur situé à Morlaix'),
    updated_at = CURRENT_TIMESTAMP
    WHERE page_key = 'confidentialite' AND section_key = 'hebergement' AND field_key = 'body';
