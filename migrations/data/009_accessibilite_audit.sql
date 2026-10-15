-- Déclaration d'accessibilité après l'audit RGAA 4.1 interne du 15 octobre 2026 (grille : content/audit/rgaa-2026-10-15.tsv).
UPDATE page_contents SET value = 'Le site ess29.org est **partiellement conforme** au référentiel général d''amélioration de l''accessibilité (RGAA 4.1) : 97 % des critères applicables sont respectés (66 sur 68).

Le site vise le niveau AA des règles WCAG 2.2 : navigation au clavier, contrastes suffisants, textes redimensionnables, structure de titres, formulaires étiquetés.', updated_at = CURRENT_TIMESTAMP WHERE page_key = 'accessibilite' AND section_key = 'etat' AND field_key = 'body';
UPDATE page_contents SET value = 'Non-conformités relevées lors de l''audit :

- **Navigation** (critère 12.1) : le site propose un seul système de navigation, le menu, repris en pied de page ; il n''a ni plan du site ni moteur de recherche.
- **Documents PDF** (critère 13.3) : 22 documents PDF sur 35 (rapports d''activité, bulletins d''adhésion, documents des pôles repris de l''ancien site) ne sont pas balisés pour les technologies d''assistance.

Si vous ne pouvez pas lire un document, demandez-en une version adaptée : nous vous la fournirons.', updated_at = CURRENT_TIMESTAMP WHERE page_key = 'accessibilite' AND section_key = 'non-accessibles' AND field_key = 'body';
UPDATE page_contents SET value = 'Déclaration établie le 15 octobre 2026, à la suite d''un audit interne réalisé le même jour par La Capsule.

- **Technologies utilisées** : HTML, CSS, JavaScript (menu mobile, filtres, onglets et carte, en amélioration progressive).
- **Pages auditées** : accueil (avec les formulaires de contact et de newsletter), Les pôles, L''équipe, Découvrir l''ESS, Agir, Se former, Tag29, Mentions légales, Confidentialité, Accessibilité, Lettres d''information, page d''erreur.
- **Environnement de test** : Chrome sur ordinateur (1280 px) et sur mobile (375 et 320 px), navigation au clavier, zoom du texte à 200 %, espacement du texte augmenté, mouvement réduit.
- **Outils** : axe-core 4.10, validateur HTML Nu (W3C), mesure des contrastes sur le rendu réel des pages.
- **Limite** : aucun lecteur d''écran n''a été utilisé lors de cet audit.', updated_at = CURRENT_TIMESTAMP WHERE page_key = 'accessibilite' AND section_key = 'etablissement' AND field_key = 'body';
