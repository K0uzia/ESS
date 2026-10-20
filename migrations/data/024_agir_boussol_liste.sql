-- Page Agir, section Boussol' : équipements inclus en éléments courts (affichés en grille), sans ponctuation de phrase.
UPDATE page_contents SET value = replace(value, 'Un abonnement donne accès aux deux espaces. Il comprend :

- le wifi ;
- la reprographie ;
- une cuisine partagée ;
- un espace pause et détente ;
- de la documentation sur l''ESS ;
- toutes les animations proposées ;
- des salles de réunion ;
- sous conditions, un badge et un casier sécurisé.', 'Inclus dans l''abonnement, à Brest comme à Morlaix :

- Wifi
- Reprographie
- Cuisine partagée
- Espace pause et détente
- Documentation sur l''ESS
- Toutes les animations
- Salles de réunion
- Badge et casier sécurisé, sous conditions'), updated_at = CURRENT_TIMESTAMP
WHERE page_key = 'agir' AND section_key = 'boussol' AND field_key = 'body';
