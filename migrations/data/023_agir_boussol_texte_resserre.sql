-- Page Agir, section Boussol' : introduction resserrée (mêmes faits, deux paragraphes au lieu de quatre, liste courte)
-- pour une mise en page plus lisible (décision du 20 octobre 2026).
UPDATE page_contents SET value = replace(value, 'Vous cherchez un espace pour travailler, échanger et rencontrer des personnes qui partagent vos valeurs ? Le Pôle ESS Pays de Brest et le Pôle ESS Pays de Morlaix vous accueillent dans les espaces de travail partagé Boussol'', à Brest et à Morlaix.

Ces lieux sont ouverts aux acteurs de l''ESS, aux porteurs de projet, aux salariés d''association sans locaux, aux indépendants et aux télétravailleurs. Dans l''esprit des tiers-lieux, conviviaux et accessibles, ils favorisent la mise en commun de moyens et de compétences, le tissage de liens et l''accès aux ressources de l''ESS. Le mobilier, créé à partir de matériaux de récupération, offre un cadre propice à la créativité.

Un abonnement donne accès aux deux espaces, à Brest comme à Morlaix. Les coworkeurs bénéficient :

- d''un accès wifi ;
- d''un espace de reprographie ;
- d''une cuisine partagée ;
- d''un espace pause et détente ;
- de documentation sur l''ESS ;
- de l''ensemble des animations proposées ;
- de salles de réunion ;
- sous conditions, d''un badge personnel et d''un casier sécurisé.

Votre première journée de coworking est offerte : passez nous voir.', 'Les Pôles ESS des Pays de Brest et de Morlaix vous accueillent dans leurs espaces de travail partagé Boussol'', pour travailler, échanger et rencontrer des personnes qui partagent vos valeurs. Ils sont ouverts aux acteurs de l''ESS, aux porteurs de projet, aux salariés d''association sans locaux, aux indépendants et aux télétravailleurs, dans un cadre convivial au mobilier de récupération.

Un abonnement donne accès aux deux espaces. Il comprend :

- le wifi ;
- la reprographie ;
- une cuisine partagée ;
- un espace pause et détente ;
- de la documentation sur l''ESS ;
- toutes les animations proposées ;
- des salles de réunion ;
- sous conditions, un badge et un casier sécurisé.

**Votre première journée de coworking est offerte : passez nous voir.**'), updated_at = CURRENT_TIMESTAMP
WHERE page_key = 'agir' AND section_key = 'boussol' AND field_key = 'body';
