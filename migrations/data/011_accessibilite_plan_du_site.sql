-- Plan du site ajouté : critère RGAA 12.1 conforme, taux porté à 98 % (grille : content/audit/rgaa-2026-10-15.tsv).
UPDATE page_contents SET value = replace(value, '97 % des critères applicables sont respectés (66 sur 68).', '98 % des critères applicables sont respectés (69 sur 70).'), updated_at = CURRENT_TIMESTAMP WHERE page_key = 'accessibilite' AND field_key = 'body';
UPDATE page_contents SET value = replace(value, 'Non-conformités relevées lors de l''audit :

- **Navigation** (critère 12.1) : le site propose un seul système de navigation, le menu, repris en pied de page ; il n''a ni plan du site ni moteur de recherche.
- **Documents PDF** (critère 13.3) : 22 documents PDF sur 35 (rapports d''activité, bulletins d''adhésion, documents des pôles repris de l''ancien site) ne sont pas balisés pour les technologies d''assistance.', 'Non-conformité relevée lors de l''audit :

- **Documents PDF** (critère 13.3) : 22 documents PDF sur 35 (rapports d''activité, bulletins d''adhésion, documents des pôles repris de l''ancien site) ne sont pas balisés pour les technologies d''assistance.'), updated_at = CURRENT_TIMESTAMP WHERE page_key = 'accessibilite' AND field_key = 'body';
UPDATE page_contents SET value = replace(value, 'Tag29, Mentions légales, Confidentialité, Accessibilité, Lettres d''information, page d''erreur.', 'Tag29, Mentions légales, Confidentialité, Accessibilité, Plan du site, Lettres d''information, page d''erreur.'), updated_at = CURRENT_TIMESTAMP WHERE page_key = 'accessibilite' AND field_key = 'body';
