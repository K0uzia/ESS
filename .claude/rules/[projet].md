# ESS 29 (propre à ce dépôt)

Refonte de [ess29.org](https://ess29.org/). Les règles `web-*` s'appliquent en plus ; ici seulement ce qui est propre à ce groupement.

Décisions verrouillées le 12 octobre 2026. Toute nouvelle page, fonctionnalité ou dépendance demande une validation explicite.

## Identité

- Raison sociale : Groupement Territorial ESS Finistère - Penn Ar Bed.
- SIRET : 880 705 041 00015.
- Site : ess29.org. Promesse de la charte, en Viga et jamais collée au logo : « L'économie de demain est là ». Baseline déjà publiée, à conserver telle quelle : « Pour une économie humaine et inventive. »
- Trois pôles, nommés ainsi (plus « ADESS » dans la navigation ni sur les coordonnées ; « ADESS » reste seulement dans les textes historiques repris) :
  - Pôle ESS Pays de Morlaix
  - Pôle ESS Pays de Brest
  - Pôle ESS Pays de Cornouaille
- Siège social : même adresse que le Pôle ESS Pays de Brest.
- Contact générique : bonjour@ess29.org.
- Zéro fiction : aucun membre, adhérent, partenaire, logo, chiffre, certification ou photo inventé. Une donnée manquante masque la section, ou un marqueur `__À_REMPLIR__` centralisé et listé. Jamais de texte de démo.
- Source unique des coordonnées, du SIRET et des e-mails : ce fichier, puis la base (tables `settings`, `poles`, `mail_addresses`, `mail_senders`, `newsletter_lists`), modifiée seulement par La Capsule via `migrations/data/`, jamais depuis le tableau de bord (décision du 14 octobre 2026), sauf les adresses e-mail, modifiables par les administrateurs dans E-mails (décisions du 15 et du 20 octobre 2026). Le HTML, le pied de page, les mentions légales et le JSON-LD en sont dérivés.

## Coordonnées

Celles du site en ligne sont périmées. Ne jamais republier les anciennes adresses ni les anciens e-mails (`@adess29.fr`, `adesscornouaille@gmail.com`, 52 Route de Garlan, 1 Alez Ar Waremm).

**Pôle ESS Pays de Morlaix**
Centre Afpa - Bat. 8
39 Bellevue de la Madeleine
29600 Morlaix
09 81 71 87 49
paysdemorlaix@ess29.org

**Pôle ESS Pays de Brest** (siège social)
Pépinière Poul Ar Bachet, Bat E.
1 rue Louis Pidoux
29200 Brest
09 72 60 58 88
paysdebrest@ess29.org

**Pôle ESS Pays de Cornouaille**
Maison Pierre Waldeck Rousseau
1 allée Monseigneur Jean-René Calloc'h
29000 Quimper
02 98 60 78 87 / 07 60 97 29 29
paysdecornouaille@ess29.org

## Périmètre

Reprendre le contenu durable du site actuel, le regrouper et le restructurer. L'arborescence WordPress et ses slugs ne sont pas la navigation cible. Une même information n'apparaît qu'une fois.

Hors périmètre, à ne pas reconstruire : les actualités (page `/actualites/` et tous les articles) et l'agenda (calendrier d'événements). Pas de blog, pas de flux RSS d'articles, pas de dates d'événements reprises depuis l'accueil (permanences juridiques datées, mois de l'ESS, etc.).

Contenu à conserver, puis regrouper :

- Accueil : quatre profils (porteur de projet, acteur de l'ESS, collectivité, curieux), trois missions (entrepreneuriat et emploi, projets collectifs, culture ESS), services, newsletters des trois pôles, certification Qualiopi du seul Pôle ESS Pays de Brest, partenaires et réseaux déjà publiés.
- Les pôles : présentation, historiques, listes d'adhérents, documents, vie associative, « l'ESS sur le territoire » (Brest, Morlaix, Cornouaille), adhésion.
- Découvrir l'ESS : définition, loi, sensibilisation, ressources, fiches pratiques.
- Agir : entreprendre, lieux de travail (dont Boussol), ambassadeur, collectivités, réseau, achats responsables, emploi (vivier, offres). Les offres ne vivent que sur leur page, pas dans un fil d'actualités.
- Se former.
- Contact, mentions légales (raison sociale, SIRET, siège, e-mail générique).

Entrées cibles, à figer dans ce fichier avant d'ouvrir les gabarits :

1. Accueil
2. Les pôles
3. L'équipe
4. Découvrir l'ESS
5. Agir
6. Se former
7. Tag29
8. Contact
9. Mentions légales

Pages figées (validées le 12 octobre 2026), multipage :

| Entrée | URL |
|--------|-----|
| Accueil | `/` |
| Les pôles | `/les-poles/` (un onglet par pôle : `#pays-de-morlaix`, `#pays-de-brest`, `#pays-de-cornouaille` ; adhérents `#adherents`) |
| L'équipe | `/equipe/` |
| Découvrir l'ESS | `/decouvrir-l-ess/` |
| Agir | `/agir/` (emploi en section `#emploi`) |
| Se former | `/se-former/` |
| Tag29 | `/tag29/` |
| Contact | section `/#contact` de l'accueil (formulaire) ; `POST /contact/` reste le point d'envoi, `/contact/` redirige |
| Mentions légales | `/mentions-legales/` |
| Hors navigation | `/confidentialite/`, `/accessibilite/`, `/plan-du-site/` (généré depuis les pages et leurs sections, RGAA 12.1, décision du 15 octobre 2026), page 404, `/newsletter/` (inscription, confirmation, désinscription) |

Toutes les pages durables de l'ancien site sont reprises dans ces gabarits ; chaque ancienne URL redirige en 301 vers sa nouvelle place.

Priorité aux pages uniques (décision du 12 octobre 2026) : une entrée = une page longue en sections ancrées, pas de sous-page. Les pôles sont des onglets de `/les-poles/`, l'emploi une section de `/agir/`, le contact une section de l'accueil. Une nouvelle sous-page demande une validation explicite.

Interface : niveau professionnel attendu. Chaque page porte des visuels de la charte (photos de `5-Photos`, pictogrammes, deux-points, guillemets, dégradé Glaz). Partenaires et réseaux s'affichent en logos (fichiers réels dans `content/logos/`), jamais en liste de noms seule.

## Architecture

- PHP 8.3 + SQLite, rendu serveur, sans framework ni Composer. Front controller `public/index.php`.
- Hébergement : serveur La Capsule (Morlaix). Mentions légales : La Capsule, chantier d'insertion numérique de l'ULAMIR CPIE, Centre Afpa, 39 Bellevue de la Madeleine, 29600 Morlaix, 07 86 44 77 52, contact@lacapsule.org. Directrice de la publication : Éliane Le Duff (décision du 14 octobre 2026).
- JavaScript public limité au filtre Équipe et au menu mobile (amélioration progressive).
- Crédit (décisions du 15 et du 23 octobre 2026) : « Conception et réalisation : Kouzia, Alexandre Kouziaeff EI, au sein de La Capsule » (lien sur « Kouzia ») en petit dans le bas du pied de page du site, centré en bas de chaque page du tableau de bord, et dans la section Crédits des mentions légales avec le SIRET ; liens https://kouzia.com/ (suivi, sans `nofollow`) et https://lacapsule.org/. Le JSON-LD de l'accueil déclare Kouzia `creator` du nœud `WebSite`, avec les `@id` publiés par kouzia.com (`#entreprise`, `#fondateur`).
- Tableau de bord `/admin/`, `noindex`, interdit dans `robots.txt`. Contenu édité par blocs structurés, pas de constructeur de pages : l'admin ne modifie que les textes (titres, paragraphes, cartes, témoignages, libellés de boutons) et la photo d'une section, dans une fenêtre, avec un éditeur gras/italique/lien/listes ; mise en page, pictogrammes, liens des cartes et ordre des sections restent dans `content/seed/` (décision du 13 octobre 2026). Ni ajout ni suppression de section, ni texte pour Google (titre, description) dans l'admin : gérés par La Capsule dans `content/seed/` (décision du 14 octobre 2026). La médiathèque liste toutes les images du site ; photos de section et logos de partenaires s'y choisissent ou s'y ajoutent. Liens et boutons : destination choisie dans la liste des pages et sections (ou autre site / e-mail), jamais une adresse à taper. Équipe et Adhérents : un onglet par pôle, puis par groupe, chacun avec son bouton « Ajouter ». Promesse et baseline ne sont pas modifiables (charte).

## Tableau de bord

Référence d'interface : le tableau de bord d'Ulamir (`/mnt/team/#TEAM/#INTERNE/#WEB/Ulamir`). Aucune logique héritée de WordPress, aucun réglage technique exposé (décision du 14 octobre 2026), sauf la boîte d'envoi des e-mails (voir E-mails).

Menu, sans groupes : Tableau de bord, Pages, Équipe, Adhérents, Offres d'emploi, Documents, Partenaires, Médiathèque, Newsletters, E-mails (administrateurs) ; en bas : Comptes (administrateurs) ou « Changer mon mot de passe » (éditeurs), Voir le site, Se déconnecter. Pas de module Identité ni Coordonnées des pôles.

- Accueil : « Bonjour », raccourcis (publier une offre, écrire une newsletter), quatre chiffres cliquables, offres en ligne, dernières newsletters avec avancement de l'envoi. Pas d'alerte technique, sauf le compte provisoire `lacapsule`.
- Listes en lignes (nom, détails, état, « Modifier ») ; ordre réglé par flèches Monter/Descendre (Équipe dans un pôle et un groupe, Partenaires par type), jamais par un numéro. Documents triés par année.
- Accès (décision du 20 octobre 2026) : administrateur (tout, sans pôle, seul à gérer les comptes et les e-mails) ou éditeur rattaché à un seul organisme : « ESS29 » (pages du groupement : Accueil, Les pôles, L'équipe, Découvrir l'ESS, Agir, Se former ; équipe ESS29 ; offres et documents sans pôle ; `users.org = 'ess29'`), « TAg29 » (page Tag29, équipe TAg29 ; `users.org = 'tag29'`) ou un pôle (fiches, page et newsletters du pôle ; `users.pole_id`). Un éditeur voit les newsletters de son organisme seulement si un administrateur les a ouvertes. Communs à tous les comptes : partenaires, médiathèque, modèles de newsletters (décision du 14 octobre 2026). Un éditeur ne change pas lui-même son mot de passe : l'administrateur lui envoie le lien depuis Comptes (décision du 14 octobre 2026).

- Comptes : création, suppression, changement d'e-mail depuis le tableau de bord. Mot de passe jamais saisi par un administrateur : un nouveau compte reçoit une invitation par e-mail et choisit le sien ; un changement se fait par lien envoyé par l'administrateur à l'adresse du compte (invitation 7 jours, changement 2 heures, lien à usage unique), ou depuis « Mot de passe oublié » sur la page de connexion (décision du 14 octobre 2026). Champs mot de passe avec bouton œil. Compte initial temporaire `lacapsule` (mot de passe `lacapsule`) : P0 bloquant la mise en production tant qu'il existe avec ce mot de passe.
- E-mails (administrateurs seulement, décisions du 15 et du 20 octobre 2026), une page en trois parties : 1) boîtes et alias d'envoi (`mail_senders` : une boîte Hostinger avec son mot de passe chiffré avec `APP_KEY`, jamais réaffiché, ou un alias rattaché à une boîte ; bouton « Tester » par adresse ; connexion à Hostinger vérifiée à l'enregistrement du mot de passe (refusée : rien n'est enregistré), puis à chaque envoi, et état « connectée / non connectée » affiché (`mail_senders.checked_at`, `check_error`) ; serveur et port non exposés, Hostinger 465 ; le `.env` ne sert qu'à défaut) ; 2) envois du site : e-mails automatiques (contact transmis, confirmations d'inscription, `settings.email_envoi`) et comptes (invitations, mots de passe, `settings.email_comptes` ; boîte réelle : mail@ess29.com) ; 3) pour chaque organisme (trois pôles, ESS29, TAg29) : adresse qui reçoit le formulaire de contact et adresse de réponse (`mail_addresses`), adresse affichée sur le site (`poles.email`, `settings.email_contact` pour ESS29), et ses deux newsletters avec ouverture et expéditeur (`newsletter_lists`). Une adresse d'expédition se choisit parmi les boîtes et alias, jamais tapée ; une adresse non reliée à une boîte est signalée en haut de page.
- Newsletters : rédaction et envoi depuis le tableau de bord. Deux listes par organisme, chacune avec ses abonnés et ses lettres (décision du 20 octobre 2026) : « Adhérents » (gérée dans le tableau de bord : ajout, import) et « Inscrits via le site » (double opt-in, proposée sur le site). Listes des pôles ouvertes ; celles d'ESS29 et de TAg29 prévues mais fermées tant qu'un administrateur ne les ouvre pas. Lettres rangées en un onglet par organisme ; modèles (introduction, signature) communs à tous (décision du 14 octobre 2026). Lien de désinscription dans chaque envoi, envoi par lots via la boîte de l'adresse d'expédition de la liste.
- Newsletters, envoi (décision du 20 octobre 2026, susceptible d'évoluer) : chaque liste a un mode. Mode Sympa (`newsletter_lists.sympa_address`) : la lettre part une seule fois à l'adresse de la liste de diffusion Sympa (`sympa.ess29.com`), qui la distribue à ses abonnés, gérés dans Sympa (pas d'abonnés ni d'import dans le tableau de bord, pas de lien de désinscription du site) ; listes « adhérents » des pôles au départ : adherents-poleess-morlaix@ess29.com, adherents-paysdebrest@ess29.com, adherents-cornouaille@ess29.com. Mode site : une lettre par abonné géré par le site (inscrits via le site), envoi par lots. Dans les deux cas l'expéditeur (`newsletter_lists.sender_email`) est choisi parmi les boîtes et alias ; en mode Sympa, il doit être autorisé à écrire sur la liste. Réponses vers l'adresse de réponse de l'organisme (Reply-To). Envoi par le SMTP Hostinger (`smtp.hostinger.com`), quota quotidien respecté. Abonnés existants : import du CSV fourni (plus de 1 200 contacts) et des bases du site actuel, avec la date et l'origine du consentement de chaque contact ; un contact sans consentement connu n'est pas importé comme actif. Migration du 20 octobre 2026 : inscrits par le formulaire → « Inscrits via le site », imports et ajouts manuels → « Adhérents » (inutilisés tant que cette liste est en mode Sympa).
- Adhérents : une seule section `/les-poles/#adherents`, construite depuis la base (nom, pôle, lien, adresse, coordonnées), filtre par pôle, liste complète lisible sans JavaScript, et carte SVG du Finistère servie par le site (aucun service tiers) dont les points suivent le même filtre. Données de départ : cartes GoGoCarto publiques des pôles de Brest et de Morlaix (ODbL, mention de la source sous la carte) ; Cornouaille sans coordonnées connues, listée seulement. Dans l'admin, on saisit l'adresse ; latitude et longitude sont calculées à l'enregistrement par le géocodage de la Géoplateforme IGN (`data.geopf.fr`, appel serveur depuis l'admin seulement). Pas de carte GoGoCarto embarquée ni d'export JSON (décision du 14 octobre 2026).
- Imports : les dumps SQL et CSV du site actuel contiennent des données personnelles. Ils vont dans `data/import/`, jamais versionnés, et sont supprimés après import.
- Contact : formulaire public (nom, e-mail, destinataire, message, tous obligatoires), envoyé par e-mail à l'adresse de réception de l'organisme choisi (`mail_addresses`, décisions du 19 et du 20 octobre 2026) ; un organisme sans adresse de réception n'est pas proposé ; destinataire indiqué dans l'objet, réponse directe au visiteur (Reply-To), envoi depuis `settings.email_envoi`. Pas de messagerie dans le tableau de bord, aucune copie gardée par le site ; si l'envoi échoue, le visiteur voit une erreur et l'adresse à utiliser (décision du 14 octobre 2026).

## Reprise du contenu

- Inventaire du site actuel : `content/ancien-site/` (pages, `redirections.tsv`, `INVENTAIRE.md`).
- Équipe, forces vives et CA : tout ce qui est publié est repris, y compris Morlaix (Eliane LE DUFF, Emilie CARIOU-MENES, Chloé MOULARD) et la liste du CA de 2021 (décision du 12 octobre 2026).
- Qualiopi (Pôle ESS Pays de Brest seulement) : certificat n° 127412-2 (Certifopac), actions de formation, valide du 7 juin 2025 au 6 juin 2028, fichier `Charte/Certificat Qualiopi - Mise à jour le 08.04.2025.pdf`. Ne pas reprendre le certificat de 2022. À renouveler avant le 6 juin 2028 (catégorie « Certificat » du module Documents).

## Équipe

Une seule page Équipe, construite depuis une source de données (une fiche par personne : nom, rôle, pôle, contact seulement s'il est déjà public). Ajouter une personne se fait dans les données, pas dans le gabarit.

Elle remplace les trois pages statiques (équipe Brest, forces vives Morlaix, équipe Cornouaille). Filtre par pôle ; la liste complète reste lisible sans JavaScript. Pas de photo tant qu'un portrait réel n'est pas fourni.

## Tag29

Page à part entière. Le contenu utile de l'ancienne page enterrée `/tag-29/` peut être repris ; l'URL et le gabarit WordPress ne le sont pas.

Tag29 a sa propre identité, appliquée à tout le contenu de cette page (décision du 20 octobre 2026), plus son logo sur les cartes de l'équipe TAg29 de `/equipe/` (groupes « ESS29 » et « TAg29 » séparés, décision du 19 octobre 2026), comme sur l'organigramme (décision du 15 octobre 2026). Le reste du site reste à la charte ESS Finistère. Logos : `Charte/tag29/3. LOGOS TAG/29/bureautique/` (fichiers RVB). Police : Quicksand, fichiers dans `Charte/tag29/Outils graphiques/POLICES/`, uniquement sur cette page.

Charte TAg29 de référence : `Charte/tag29/3. LOGOS TAG/TAg29_KIT_COMMUNICATION_salarie_stagiaire/TAg29_CHARTE-GRAPHIQUE.pdf` ; rendu inspiré de https://tag.bzh/. Sur `/tag29/` : Quicksand Book et Bold (texte et titres), couleurs du logo seulement (jaune `#f8ae3c`, orange foncé `#ea5b25`, noir, blanc) plus le bleu nuit TAgBZH `#212937` pour les bandeaux, tokens `--color-tag29-*` et `--color-tagbzh-nuit`. Le hashtag (`public/assets/img/tag29/diese.svg`, tracé du logo vectoriel de la charte) sert de repère devant les titres, de puce de liste, d'icône des blocs (`### !diese`) et de découpe des photos (comme le bandeau de tag.bzh). En-tête bleu nuit uni, fil d'Ariane en blanc. Sections claires sur le fond topographique de tag.bzh (`content/tag29/fond-topographie.png`), sans aplat teinté. Chiffres en pastilles de tag.bzh (disque jaune au coin décollé ; dernier mot du libellé en italique dans le contenu, affiché en minuscules sous les capitales). Logos des financeurs et des partenaires : `content/tag29/logos.tsv` ; ceux absents de la charte sont repris de tag.bzh dans `content/tag29/logos/` (sources dans `SOURCES.tsv`). Logo jaune posé directement sur le fond, jamais dans un encadré. Le jaune ne porte jamais de texte blanc ni ne sert de texte sur blanc (contraste) : texte noir sur jaune. Pas de deux-points ni de pictogrammes ESS sur cette page. En-tête et pied du site restent à la charte ESS.

## Charte (règle d'or)

`Charte/` est la seule source visuelle. Interdit d'inventer un logo, une couleur, une police ou un pictogramme de marque, d'en recolorer un, ou d'utiliser l'identité d'un autre territoire (Armor, Bretagne Sud, Centre Bretagne, Ille-et-Vilaine, Bretagne générique).

Référence des usages : `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/ESS-Bretagne_CharteGraphique.pdf`.

- Logo du site : uniquement `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/1-Logotype/1-Logotype final/ESS-Finistere/Web-RVB/PNG/`. Choisir la déclinaison prévue pour le fond (couleur sur blanc, cartouche sur aplat glaz, blanc ou blanc-or sur photo sombre, noir sur fond clair ou en gris). Ne pas étirer, recomposer, changer la typo du logo, ni inverser ses couleurs.
- Logos de pôles, fichiers RVB ou PNG nommés au territoire : Brest dans `Charte/logoPoleESS/PoleEssBrest/PaysdeBrest/`, Morlaix dans `Charte/logoPoleESS/PaysdeMorlaix/`, Cornouaille dans `Charte/logoPoleESS/PoleEssCornouaille/` (fournis le 13 octobre 2026).
- Couleur de chaque pôle, relevée sur son logo (décision du 13 octobre 2026) : Morlaix `#157270`, Brest `#9f1251`, Cornouaille `#b22760`. Tokens `--color-pole-*`, réservés aux éléments propres à un pôle (bandeau et onglet du pôle, filtres, points de la carte).
- Polices du site : Poppins pour le texte, les titres et l'interface (`Charte/CHARTE GRAPHIQUE ESS BRETAGNE/2-Polices-de-caracteres/Poppins/`). Viga pour la promesse et les exergues courtes seulement, jamais à côté du logo. Verdana est la police bureautique, pas celle du site. Auto-hébergées à partir de ces fichiers (woff2 au build). Aucun CDN, pas de Google Fonts.
- Couleurs, seules valeurs brutes, en tokens : blanc `#ffffff`, Glaz 1 `#78c7c9`, Glaz `#2d8a87`, Glaz 3 `#005154`, Glaz 4 `#00393b`, or `#f7a600`, plus les trois couleurs de pôle ci-dessus.
- Ingrédients (deux-points, guillemets) : `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/3-Ingredients-graphiques/`, versions Web-RVB. Les deux-points restent un signe de collectif, placés comme dans la charte, pas redessinés.
- Pictogrammes de marque : `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/4-Pictogrammes/Web-RVB/PNG/`. Les icônes d'interface (menu, lien externe) suivent `web-design.md` ; elles ne remplacent pas un picto de marque.
- Web seulement : PNG et polices. Pas les dossiers Print-CMJN, AI, EPS, JPG d'impression, ni `__MACOSX`.
- Photos : fichiers réels de `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/5-Photos/`, fournis ensuite, ou libres de droits sans attribution (CC0) rangés dans `content/photos-libres/` avec leur source dans `SOURCES.tsv` (décision du 13 octobre 2026). Liste unique : `App\Site\Section::PHOTOS`. Une photo n'a qu'un seul emploi sur tout le site.
