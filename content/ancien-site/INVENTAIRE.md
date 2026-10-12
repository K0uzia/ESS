# Inventaire du site actuel ess29.org

Relevé le 12 octobre 2026, en lecture seule, à partir de `https://ess29.org/wp-sitemap.xml` (index vide) puis des sous-sitemaps `wp-sitemap-posts-page-1.xml` (57 pages) et `wp-sitemap-posts-post-1.xml` (81 articles), recoupés avec `page-sitemap.xml` et `post-sitemap.xml` (mêmes listes). Aucun sitemap pour l'agenda (`wp-sitemap-posts-tribe_events-1.xml` répond 404).

Fichiers :

- `pages/<slug>.md` : 56 pages extraites (tout le type « page » sauf `/actualites/`, hors périmètre). Contenu de `div.entry-content` seulement, puis listes des liens, PDF, images et iframes.
- `elements-communs.md` : éléments répétés sur toutes les pages (widget « Contacts » de l'en-tête, pied de page : newsletters, Qualiopi, réseaux sociaux, partenaires). Ils ne figurent pas dans les fichiers de page.
- `redirections.tsv` : 153 lignes (57 pages, 12 URL secondaires trouvées dans les liens internes, 3 entrées agenda dont 2 motifs, 81 articles). Les lignes dont l'ancienne et la nouvelle URL sont identiques (`/`, `/mentions-legales/`) ne sont pas des redirections.

Copie fidèle : orthographe, majuscules et coquilles du site conservées. Rien n'a été réécrit ni complété.

## Pages → destination

| Ancienne page | Destination | Résumé |
|---|---|---|
| `/` (accueil) | `/` | Quatre profils, encart daté « permanences juridiques », trois missions, services, appel au contact. |
| `/actualites/` | `/` | Hors périmètre, non extraite. |
| `/contacts/` | `/contact/` | Coordonnées des trois pôles (déjà en `@ess29.org`), un nom par pôle pour Morlaix et Brest. |
| `/mentions-legales/` | `/mentions-legales/` | Trois associations ADESS et anciennes adresses, licence CC BY-NC-SA, liens, données personnelles, réalisation. |
| `/faire-reseau/` | `/les-poles/` | Présentation courte des pôles, vidéo YouTube, liens (cassés) vers les trois pôles. |
| `/faire-reseau/pourquoi-et-comment-adherer/` | `/les-poles/` | Ce qu'est l'ESS, qui peut adhérer, ce qu'apporte l'adhésion, liens d'adhésion 2025 des trois pôles. |
| `/faire-reseau/adessbrest/` | `/les-poles/pays-de-brest/` | Présentation, trois missions, valeurs, partenaires du Pôle Pays de Brest. |
| `/faire-reseau/adessbrest/historique-de-ladess-pays-de-brest/` | `/les-poles/pays-de-brest/` | Création 2009, frise (image), chiffres de l'ESS sur le Pays de Brest. |
| `/faire-reseau/adessbrest/brest-vie-associative/` | `/les-poles/pays-de-brest/` | Plaquette (3 images), statuts, rapports 2023 en PDF. |
| `/faire-reseau/adessbrest/liste-des-adherents-de-ladess-du-pays-de-brest/` | `/les-poles/pays-de-brest/` | Carte GoGoCarto des adhérents en iframe, aucune liste en texte. |
| `/faire-reseau/adessbrest/lequipe-de-ladess-pays-de-brest/` | `/equipe/` | Deux salariées et onze membres du conseil d'administration. |
| `/faire-reseau/adesscornouaille/` | `/les-poles/pays-de-cornouaille/` | Page vide (titre seul). |
| `/faire-reseau/adesscornouaille/historiquecornouaille/` | `/les-poles/pays-de-cornouaille/` | Historique 2009-2013 de l'ADESS Cornouaille. |
| `/faire-reseau/adesscornouaille/documents-de-ladess-cornouaille/` | `/les-poles/pays-de-cornouaille/` | Renvoi vers le site AssoConnect de l'ADESS Cornouaille. |
| `/faire-reseau/adesscornouaille/liste-des-adherents-de-ladess-cornouaille/` | `/les-poles/pays-de-cornouaille/` | Tableau des 29 adhérents 2023 avec leur statut. |
| `/faire-reseau/adesscornouaille/equipeadesscornouaille/` | `/equipe/` | Référent territorial, CA 2025, lien AssoConnect. |
| `/faire-reseau/le-pole-du-pays-de-morlaix/` | `/les-poles/pays-de-morlaix/` | Présentation, missions, valeurs, actions, plaquette PDF, soutiens. |
| `/faire-reseau/le-pole-du-pays-de-morlaix/historique-adess-pays-de-morlaix/` | `/les-poles/pays-de-morlaix/` | Débuts 2007, 17 structures adhérentes, mention « En cours de construction ». |
| `/faire-reseau/le-pole-du-pays-de-morlaix/documents-adess-pays-de-morlaix/` | `/les-poles/pays-de-morlaix/` | Statuts, rapports d'activité 2017 à 2024, bulletin d'adhésion 2024, QR code. |
| `/faire-reseau/le-pole-du-pays-de-morlaix/liste-des-adherents-de-ladess-du-pays-de-morlaix/` | `/les-poles/pays-de-morlaix/` | Carte GoGoCarto en iframe et logos d'adhérents sans texte. |
| `/faire-reseau/le-pole-du-pays-de-morlaix/equipe-pays-de-morlaix/` | `/equipe/` | Deux salariées et CA 2021 avec structures représentées. |
| `/decouvrir-less/` | `/decouvrir-l-ess/` | Sommaire de rubrique (un lien 404, deux entrées sans lien). |
| `/decouvrir-less/less-cest-quoi/` | `/decouvrir-l-ess/` | Introduction et sommaire de liens. |
| `/decouvrir-less/less-cest-quoi/definitions-de-leconomie-sociale-et-solidaire/` | `/decouvrir-l-ess/` | Définitions et histoire de l'économie sociale et solidaire, deux vidéos YouTube. |
| `/decouvrir-less/less-cest-quoi/definitions-de-leconomie-sociale-et-solidaire/ateliers-decoder-less-a-morlaix/` | `/decouvrir-l-ess/` | Atelier « Décoder l'ESS » à Morlaix, inscription par téléphone ou e-mail. |
| `/decouvrir-less/less-cest-quoi/la-loi-ess/` | `/decouvrir-l-ess/` | Loi du 31 juillet 2014 et ses cinq objectifs, deux PDF externes. |
| `/decouvrir-less/less-cest-quoi/less-sur-les-territoires/` | `/les-poles/` | Chiffres ESS Bretagne et Finistère, réseau des pôles bretons, liens vers les trois pôles. |
| `/decouvrir-less/less-cest-quoi/less-sur-les-territoires/less-sur-le-pays-de-brest/` | `/les-poles/pays-de-brest/` | Liens Data'ESS 2018 (404) et ateliers Décoder l'ESS, réseaux sociaux Brest. |
| `/decouvrir-less/less-cest-quoi/less-sur-les-territoires/less-sur-le-pays-de-morlaix/` | `/les-poles/pays-de-morlaix/` | Lien vers le Data'ESS Morlaix 2018 (CRESS). |
| `/decouvrir-less/less-cest-quoi/less-sur-les-territoires/esscornouaille/` | `/les-poles/pays-de-cornouaille/` | Présentation du Pays de Cornouaille et chiffres ESS (source 2015). |
| `/decouvrir-less/sensibilisation-2/` | `/decouvrir-l-ess/` | Offre de sensibilisation, trois exemples d'ateliers. |
| `/decouvrir-less/ressources/` | `/decouvrir-l-ess/` | Liens Data'ESS 2018, fiches pratiques, article LEADER, plaquette recycleries. |
| `/decouvrir-less/ressources/fiches-pratiques/` | `/decouvrir-l-ess/` | Six fiches pratiques PDF (recrutement, écogestes, communication, bien-être, fournisseurs, égalité). |
| `/decouvrir-less/se-former/` | `/se-former/` | Offre de formation de l'Adess Pays de Brest, Qualiopi, indicateurs, témoignages, formations sur mesure. |
| `/decouvrir-less/se-former/cooperative-restauration/` | `/se-former/` | Formation « Coopérative de restauration » : public, contenu, lieu, accès, coût. |
| `/agir-dans-less/` | `/agir/` | Sommaire de rubrique. |
| `/agir-dans-less/devenir-ambassadeur/` | `/agir/` | Une ligne de notes internes et un PDF de mission de service civique 2021. |
| `/agir-dans-less/engager-son-territoire-dans-less/` | `/agir/` | Introduction collectivités, deux liens de sous-pages. |
| `/agir-dans-less/engager-son-territoire-dans-less/developper-une-politique-publique-de-less/` | `/agir/` | Arguments pour une politique ESS, offre de diagnostic de territoire des trois pôles. |
| `/agir-dans-less/engager-son-territoire-dans-less/developper-une-politique-publique-de-less/exemple-de-diagnostic-ess-en-aulne-maritime-presquile-de-crozon/` | `/agir/` | Diagnostic ESS réalisé en 2016 pour la Presqu'île de Crozon. |
| `/agir-dans-less/engager-son-territoire-dans-less/soutenir-impulser-des-projets-ess/` | `/agir/` | Projets portés au Pays de Morlaix : monnaie locale Buzuk, Cigales, PTCE économie circulaire, foncière, ruralités. |
| `/agir-dans-less/engager-son-territoire-dans-less/soutenir-impulser-des-projets-ess/sensibilisation-a-la-transmission-dentreprises-en-scop/` | `/agir/` | Historique de la démarche transmission en SCOP en Cornouaille (2014). |
| `/agir-dans-less/entreprendre-autrement/` | `/agir/` | Porteurs de projets et entrepreneurs, sommaire de liens. |
| `/agir-dans-less/entreprendre-autrement/faire-le-point-sur-mon-projet/` | `/agir/` | Rendez-vous individuel avec un pôle, objectifs, vidéo portrait. |
| `/agir-dans-less/entreprendre-autrement/soutien-a-la-creation-et-a-la-reprise-dactivites-en-ess/` | `/agir/` | Deux paragraphes sur le soutien à la création. |
| `/agir-dans-less/entreprendre-autrement/soutien-a-la-creation-et-a-la-reprise-dactivites-en-ess/collectif-dacteurs-de-laccompagnement/` | `/agir/` | Parcours local d'accompagnement, membres du collectif, plaquette 2015. |
| `/agir-dans-less/entreprendre-autrement/soutien-a-la-creation-et-a-la-reprise-dactivites-en-ess/tag-29/` | `/tag29/` | TAg29 : révélateur, idéateur, incubateur, contacts nominatifs, Facebook. |
| `/agir-dans-less/entreprendre-autrement/lieu-de-travail/` | `/agir/` | Espaces de travail partagé Boussol' Brest et Morlaix, services. |
| `/agir-dans-less/entreprendre-autrement/lieu-de-travail/boussolbrest/` | `/agir/` | Boussol' Brest : formules et tarifs, salles, adresse, carte Google intégrée. |
| `/agir-dans-less/entreprendre-autrement/lieu-de-travail/boussolmorlaix/` | `/agir/` | Boussol Morlaix : formules et tarifs, adresse périmée, carte Google intégrée. |
| `/agir-dans-less/sengager-dans-le-reseau/` | `/agir/` | Introduction et sommaire de liens. |
| `/agir-dans-less/sengager-dans-le-reseau/achatsresponsables/` | `/agir/` | Guide des achats professionnels responsables en Cornouaille (lien cassé). |
| `/agir-dans-less/sengager-dans-le-reseau/achats-responsables-et-economie-circulaire/` | `/agir/` | Économie circulaire, projet CIPEC du Pays de Morlaix, contact. |
| `/agir-dans-less/sengager-dans-le-reseau/rencontres-entre-acteurs-de-less/` | `/agir/` | Réseau d'affaires ESS (Brest) et Mois de l'ESS. |
| `/agir-dans-less/travailler-dans-less/` | `/agir/emploi/` | Introduction, liens vers réseau d'affaires, vivier et offres. |
| `/agir-dans-less/travailler-dans-less/le-vivier-de-competences/` | `/agir/emploi/` | Rencontres pour les personnes en recherche d'emploi dans l'ESS (Brest, Morlaix). |
| `/agir-dans-less/travailler-dans-less/offres-demploi/` | `/agir/emploi/` | Quatre offres d'emploi d'avril et mai 2023 en PDF. |

Agenda (`/evenements/`, `/evenement/<slug>/`) et 81 articles : vers `/`, listés dans `redirections.tsv`.

## Données à vérifier

### Anciennes coordonnées périmées

| Donnée | Où |
|---|---|
| `paysdemorlaix@adess29.fr`, `paysdebrest@adess29.fr`, `adesscornouaille@gmail.com`, « 52 Route de Garlan », « 1 Alez Ar Waremm », libellés « ADESS Pays de … » | `elements-communs.md` (widget Contacts de l'en-tête, sur toutes les pages) |
| `adesscornouaille@gmail.com` | `pages/equipeadesscornouaille.md` |
| « 52 route de Garlan » (« à Kérozar »), `eleduff@adess29.fr`, `ecariou@adess29.fr` | `pages/equipe-pays-de-morlaix.md` |
| « 52 route de Garlan », `paysdemorlaix@adess29.fr`, carte Google pointant sur cette adresse | `pages/boussolmorlaix.md` |
| `paysdebrest@adess29.fr` | `pages/boussolbrest.md` |
| `mblondy [a] adess29.fr`, `csola [a] adess29.fr` | `pages/lequipe-de-ladess-pays-de-brest.md` |
| « 52 Route de Garlan », « 7 rue de Vendée, 29200 Brest », « 71 avenue Jacques Le Viol, 29000 Quimper », site `http://adess29.fr` | `pages/mentions-legales.md` |
| Autres anciens e-mails : `cultureess@adessdupaysdemorlaix.org` | `pages/ateliers-decoder-less-a-morlaix.md` |
| `monnaielocale@adessdupaysdemorlaix.org`, `alemetais@cress-bretagne.org` | `pages/soutenir-impulser-des-projets-ess.md` |
| `cipec@adessdupaysdemorlaix.org` | `pages/achats-responsables-et-economie-circulaire.md` |
| Liens et images vers `adess29.infini.fr` | `pages/soutenir-impulser-des-projets-ess.md` |

La page `/contacts/` utilise déjà les adresses `@ess29.org`, mais diffère de la fiche projet : Morlaix sans « Centre Afpa - Bat. 8 », Brest sans « Bat E. », Cornouaille avec le seul 07 60 97 29 29 et l'adresse « 1 allée Msgr J-R Calloc'h ».

### Liens cassés ou internes au poste de rédaction

- `file:///D:/Téléchargements/…` : bulletin d'adhésion Brest 2025 (`pourquoi-et-comment-adherer.md`), plaquette de formation (`cooperative-restauration.md`).
- `http://wordpress.local/…` : Data'ESS Cornouaille 2015 (`esscornouaille.md`), guide des achats responsables (`achatsresponsables.md`).
- Réponses 404 au relevé : `/faire-reseau/le-pole-du-pays-de-brest/`, `/faire-reseau/le-pole-du-pays-de-cornouailles/` (`faire-reseau.md`, `less-sur-les-territoires.md`), `/decouvrir-less/formez-vous/` (`decouvrir-less.md`), `…/less-sur-le-pays-de-cornouailles/` (`less-sur-les-territoires.md`), `…/dataess-pays-de-brest_2018/` (`less-sur-le-pays-de-brest.md`), `/?page_id=1219` (`less-cest-quoi.md`).
- `collectif-dacteurs-de-laccompagnement.md` : shortcode non rendu `[slideshare id=111909003&doc=pla-lectureweb-180828100128]`.
- Pied de page : les logos « Pays de Landivisiau » et « Morlaix Communauté » pointent vers `brest.fr`.
- `documents-adess-pays-de-morlaix.md` : « Synthétique 2019 » pointe sur le même PDF que « Bilan d'activités TAG 29 » ; « Support AG 2023 » pointe sur `Support-AG-2024.pdf`.

### Contenus datés publiés hors agenda (hors périmètre selon la fiche projet)

- Accueil : permanences juridiques « jeudi 25 septembre et jeudi 14 novembre ».
- `se-former.md` : session « du 15 janvier au 15 juillet 2024 », « prochaine session … automne 2025 ».
- `cooperative-restauration.md` : réunions d'information des 7 et 8 décembre 2023.
- `offres-demploi.md` : offres de 2023.
- Chiffres : Data'ESS 2018 (Brest, Morlaix, Bretagne), 2015 (Cornouaille), « En 2018, l'Adess Pays de Brest compte 14 adhérents et 2 salariés ».

### Contenus en double

- Présentation, trois missions, paragraphe « Créée en 2009 … » et cinq valeurs : identiques entre `adessbrest.md` et `le-pole-du-pays-de-morlaix.md`. « Créée en 2009 » est aussi dans `historique-de-ladess-pays-de-brest.md`. Les trois missions figurent aussi sur l'accueil.
- Boussol' : textes, formules et tarifs quasi identiques entre `boussolbrest.md` et `boussolmorlaix.md`, résumés dans `lieu-de-travail.md`.
- Premier paragraphe identique entre `engager-son-territoire-dans-less.md` et `developper-une-politique-publique-de-less.md`.
- Mêmes trois liens de fin de sommaire (Documents Cornouaille, Fiches pratiques, Coopérative de restauration) dans `entreprendre-autrement.md` et `less-cest-quoi.md`.
- Lien AssoConnect Cornouaille dans `documents-de-ladess-cornouaille.md` et `equipeadesscornouaille.md`.
- Data'ESS Morlaix 2018 : `ressources.md` et `less-sur-le-pays-de-morlaix.md`.
- Adhésion : `pourquoi-et-comment-adherer.md` (bulletin Morlaix 2025) et `documents-adess-pays-de-morlaix.md` (bulletin Morlaix 2024 et QR code).
- Formation Coopérative de restauration : `se-former.md` et `cooperative-restauration.md`.
- Qualiopi : pied de page et `se-former.md`.
- Coordonnées des pôles : en-tête commun, `contacts.md`, pages Boussol', pages équipe.
- Ateliers « Décoder l'ESS » : page Morlaix, et article Brest (`/1435-ateliers-decoder-less-a-brest/`) lié depuis `definitions-…md` et `less-sur-le-pays-de-brest.md`.

### Personnes publiées, par pôle (nom et rôle tels qu'écrits)

À valider avant toute reprise : les pages ne sont pas datées de façon cohérente et se contredisent pour Morlaix.

**Pôle ESS Pays de Brest** (`lequipe-de-ladess-pays-de-brest.md`)

- Équipe salariée : Myriam Blondy, « Coordinatrice » ; Carine Sola, « Responsable formation – Référente handicap ».
- Conseil d'administration : Michel Beaugeois, « co-président – référent formation » (MGEN) ; Nicolas Lunven, « co-président » (Les Papillons Blancs) ; Solène Lagathu, « co-présidente » (Ara Hotel) ; Philippe Barbeoch, « co-président – référent formation » (AB Services Création) ; David Marzin, « co-président » (Don Bosco) ; Camille Tabeling, « co-présidente » (Ti Coop) ; Tangi Le Bot, « co-président » (Skravik) ; Jérôme Sawtschuk, « co-président » (Lieu-Dit) ; Cécile Le Maître, « co-présidente » (CAE29) ; David Levaufre, « co-président » (Groupe Sato) ; Vincent Brisard, « co-président » (« adhérent à titre indivuel »).
- Aussi : Carine SOLA en contact Brest (`contacts.md`) ; « Conception graphique & technique : Myriam Blondy » (`mentions-legales.md`).

**Pôle ESS Pays de Morlaix**

- `equipe-pays-de-morlaix.md`, équipe salariée : Eliane LE DUFF, « Coordinatrice du Pôle ESS du Pays de Morlaix » ; Emilie CARIOU-MENES, « Chargée de mission ».
- `contacts.md` : Chloé MOULARD en contact Morlaix (contradiction avec la page équipe).
- `achats-responsables-et-economie-circulaire.md` : Baptiste BERTRAND, « Chargé de mission Economie circulaire ».
- Conseil d'administration 2021 : co-présidence Chloé JAGUIN (Le Repair) et Yann MOYOU (les Genêts d'Or, site de Landivisiau) ; trésorier Jean-Pol CAROFF (Cellaouate) ; secrétaire Elisabeth MAST (adhérente individuelle) ; co-secrétaire Gérard BAU (adhérent individuel) ; administrateurs Catherine ROUSSEAU (Fondation Massé-Trévidy), Anne-Claire LEPITRE (Askoria), Jean-Pierre QUERE (Sevel Services), Fanny ALLAIS-KERRIEN (RESAM), Matthieu CIROU (RESAM, suppléant), Marie-Emmanuelle GRIGNON (Eco Bretons), Laurence MERMET (Eco Bretons, suppléant), Justine NOLL (Luska), Maurice YERLES (Projets Echanges et Développement).

**Pôle ESS Pays de Cornouaille** (`equipeadesscornouaille.md`)

- Samuel GENY, « référent territorial ».
- Conseil d'administration 2025 : ANTONI Eric (Harmonie Mutuelle) ; BUKIET Hélène (Kan Ar Mor) ; JODOCIUS Xavier (Ateliers Fouesnantais) ; HUNTZINGER France (MAIF), Présidente ; LE DOZE Delphine (GEAI groupement employeurs 29) ; PETIT Martine (ACTES en Cornouaille), secrétaire ; PETIT Pascal (Espace Associatif Quimper) ; TRESSARD Roger (MGEN), Trésorier ; LES CIGALES.

**TAg29** (`tag-29.md`) : Sindy Quere, « responsable Revelateur TAg29 » ; Flavie de Jaurias, « responsable Incubateur TAg29 » (e-mails `@tag29.bzh`).

### Adhérents par pôle

- **Pays de Brest** : aucune liste en texte. Carte GoGoCarto en iframe (`https://adess29.gogocarto.fr`), chargée en JavaScript chez un tiers, non extraite.
- **Pays de Cornouaille** : liste « Adhérents 2023 », 29 lignes avec statut, dans `liste-des-adherents-de-ladess-cornouaille.md` (dont trois personnes qualifiées nommées).
- **Pays de Morlaix** : carte GoGoCarto en iframe (`https://adherentspolessmx.gogocarto.fr`), non extraite, puis logos sans texte alternatif suivis de « Et bien d'autres … ». Les structures ne sont identifiables que par les noms de fichiers et les liens : Sans Transition, Buzuk, Les Genêts d'Or, Icoopa, Le Lok'All, La Maison du Bâtiment, Fondation Massé-Trévidy, Mutualité française de Bretagne, Les Temps Bouilles, URCPIE, ULAMIR CPIE, RESAM, Sevel Services, Luska (sans lien), mpPSY, Les Jeannettes, Le Repair, Le Mélar Dit, Fondation Ildys, Cigales de Bretagne, CAE 29, Cellaouate. Liste déduite, à confirmer par le pôle.

### Partenaires et réseaux cités

- Pied de page « NOS PARTENAIRES sur les territoires » : Région Bretagne, Brest Métropole, Pays de Landivisiau, Morlaix Communauté (logos).
- Pôle Pays de Brest : « Conseil Régional de Bretagne », « Brest métropole océane » (partenaires techniques et financiers).
- Pôle Pays de Morlaix « soutenue par » : La Région Bretagne, Morlaix Communauté, La Communauté de Communes Pays de Landivisiau.
- Formation Coopérative de restauration : financée par la région Bretagne et le Contrat de Ville de Brest Métropole ; lieu Centre Social Horizons Léo Lagrange.
- Collectif d'acteurs de l'accompagnement : les ADESS, RESAM, Espace associatif de Quimper, Sema'For, Entreprendre au Féminin 29, CIVAM 29, CAE 29, URSCOP de l'Ouest, CIGALES de Bretagne, Bretagne Active.
- Réseaux et sources cités : CRESS Bretagne, CNCRES, ORESS (Observatoire de l'ESS en Bretagne), Jeun'ESS, ADEME, Pôle Innovation du Pays de Morlaix, PETR Pays de Morlaix, Juristes engagées (accueil), La Boussol' (`laboussol.fr`).

### Newsletters et réseaux sociaux

- Newsletters : un seul formulaire MailPoet en pied de page (envoi vers `wp-admin/admin-post.php`), trois listes : Pôle ESS Pays de Brest, Pôle ESS Pays de Cornouaille, Pôle ESS Pays de Morlaix. Aucun lien d'archive ni d'inscription externe.
- Également cité : « La lettre de l'économie circulaire en pays de Morlaix » (`achats-responsables-et-economie-circulaire.md`), sans lien.
- Réseaux sociaux : LinkedIn et Facebook Morlaix et Brest (pied de page) ; YouTube Brest (`less-sur-le-pays-de-brest.md`) ; Facebook TAg29 (`tag-29.md`).

### Qualiopi

- Pied de page : « L'ADESS Pays de Brest est certifié Qualiopi », « actions de formations », certificat `Certificat-Qualiopi-ADESS-Pays-Brest-Mise-a-jour-le-07.06.2022.pdf`. Validité actuelle à confirmer.
- `se-former.md` : « a entrepris depuis 2022 une démarche de certification … Qualiopi », indicateurs de satisfaction et de sortie.

### PDF liés

41 PDF hébergés sur `ess29.org/wp-content/uploads/` répondent tous 200 au relevé. Le détail par page figure dans la section « PDF liés » de chaque fichier.

- Pôle Pays de Brest : statuts (`StatutsAJour.pdf`), rapports 2023 (complet et synthétique), certificat Qualiopi.
- Pôle Pays de Morlaix : statuts, rapports d'activité et synthèses 2017 à 2024, bilan TAg29 2019, rapport des pôles ESS Bretagne 2020, support AG, bulletins d'adhésion 2024 et 2025, plaquette `MorlaixWebR.pdf`.
- Découvrir : six fiches pratiques 2020, Data ESS Bretagne 2018, article LEADER, livret des recycleries du Pays de Brest 2022.
- Agir et emploi : mission service civique 2021 (`FDM-SCV-2021.pdf`), quatre offres d'emploi 2023, dossier de candidature Coopérative de restauration.
- Externes : Data'ESS Brest et Morlaix 2018 (`ess-bretagne.org`), brochure loi ESS (CRESS Bretagne), synthèse loi ESS (CNCRES).

### Autres constats

- `adesscornouaille.md` : page vide sur le site.
- `historique-adess-pays-de-morlaix.md` : « En cours de construction ».
- `devenir-ambassadeur.md` : une ligne qui ressemble à une note de travail (« Services civiques / actions de sensibilisation => Ressources / Kit Cournouaille »).
- Mentions légales sans SIRET, sans directeur de publication ni hébergeur.
- Une seule image à la une (`loi-ess.png`, page loi ESS). Les noms d'images sont listés par page ; les fichiers n'ont pas été téléchargés.
