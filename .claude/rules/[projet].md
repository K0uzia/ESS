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
- Source unique des coordonnées, du SIRET et des e-mails : ce fichier, puis le fichier de données du site quand il existera. Le HTML, le pied de page, les mentions légales et le JSON-LD en sont dérivés.

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
| Hors navigation | `/confidentialite/`, `/accessibilite/`, page 404, `/newsletter/` (inscription, confirmation, désinscription) |

Toutes les pages durables de l'ancien site sont reprises dans ces gabarits ; chaque ancienne URL redirige en 301 vers sa nouvelle place.

Priorité aux pages uniques (décision du 12 octobre 2026) : une entrée = une page longue en sections ancrées, pas de sous-page. Les pôles sont des onglets de `/les-poles/`, l'emploi une section de `/agir/`, le contact une section de l'accueil. Une nouvelle sous-page demande une validation explicite.

Interface : niveau professionnel attendu. Chaque page porte des visuels de la charte (photos de `5-Photos`, pictogrammes, deux-points, guillemets, dégradé Glaz). Partenaires et réseaux s'affichent en logos (fichiers réels dans `content/logos/`), jamais en liste de noms seule.

## Architecture

- PHP 8.3 + SQLite, rendu serveur, sans framework ni Composer. Front controller `public/index.php`.
- Hébergement : serveur La Capsule (Morlaix).
- JavaScript public limité au filtre Équipe et au menu mobile (amélioration progressive).
- Tableau de bord `/admin/`, `noindex`, interdit dans `robots.txt`. Contenu édité par blocs structurés, pas de constructeur de pages.

## Tableau de bord

Modules : identité et coordonnées, contenus, équipe, adhérents, offres d'emploi, documents et fiches pratiques, partenaires, médias, messages de contact, newsletters, comptes.

- Comptes : création, suppression, changement de mot de passe et d'e-mail depuis le tableau de bord. Compte initial temporaire `lacapsule` (mot de passe `lacapsule`) : P0 bloquant la mise en production tant qu'il existe avec ce mot de passe.
- Newsletters : rédaction et envoi depuis le tableau de bord. Une liste d'abonnés et plusieurs modèles par pôle (Morlaix, Brest, Cornouaille). Double opt-in, lien de désinscription dans chaque envoi, envoi par lots via un relais SMTP configuré dans `.env`.
- Newsletters, expéditeurs : un alias par pôle, identique à l'e-mail du pôle (paysdebrest@ess29.org, paysdemorlaix@ess29.org, paysdecornouaille@ess29.org ; donnée : `poles.email`), envoi par le SMTP Hostinger (`smtp.hostinger.com`) en lots étalés pour respecter le quota quotidien du forfait. Abonnés existants : import du CSV fourni (plus de 1 200 contacts) et des bases du site actuel, avec la date et l'origine du consentement de chaque contact ; un contact sans consentement connu n'est pas importé comme actif.
- Adhérents : une seule section `/les-poles/#adherents`, construite depuis la base (nom, pôle, lien, adresse), filtre par pôle, liste complète lisible sans JavaScript. Carte GoGoCarto en plus, nourrie par un export JSON public de cette même base. Exception au « aucun script tiers » : l'iframe GoGoCarto ne se charge qu'au clic du visiteur, et `frame-src` est limité au domaine GoGoCarto.
- Imports : les dumps SQL et CSV du site actuel contiennent des données personnelles. Ils vont dans `data/import/`, jamais versionnés, et sont supprimés après import.
- Contact : formulaire public (nom, e-mail, pôle concerné, message), envoyé à l'adresse du pôle choisi, ou à bonjour@ess29.org par défaut. Le message est aussi conservé dans l'admin, avec une durée de conservation limitée.

## Reprise du contenu

- Inventaire du site actuel : `content/ancien-site/` (pages, `redirections.tsv`, `INVENTAIRE.md`).
- Équipe, forces vives et CA : tout ce qui est publié est repris, y compris Morlaix (Eliane LE DUFF, Emilie CARIOU-MENES, Chloé MOULARD) et la liste du CA de 2021 (décision du 12 octobre 2026).
- Qualiopi (Pôle ESS Pays de Brest seulement) : certificat n° 127412-2 (Certifopac), actions de formation, valide du 7 juin 2025 au 6 juin 2028, fichier `Charte/Certificat Qualiopi - Mise à jour le 08.04.2025.pdf`. Ne pas reprendre le certificat de 2022. À renouveler avant le 6 juin 2028 (catégorie « Certificat » du module Documents).

## Équipe

Une seule page Équipe, construite depuis une source de données (une fiche par personne : nom, rôle, pôle, contact seulement s'il est déjà public). Ajouter une personne se fait dans les données, pas dans le gabarit.

Elle remplace les trois pages statiques (équipe Brest, forces vives Morlaix, équipe Cornouaille). Filtre par pôle ; la liste complète reste lisible sans JavaScript. Pas de photo tant qu'un portrait réel n'est pas fourni.

## Tag29

Page à part entière. Le contenu utile de l'ancienne page enterrée `/tag-29/` peut être repris ; l'URL et le gabarit WordPress ne le sont pas.

Tag29 a sa propre identité, limitée à cette page (logo et titres). Le reste du site reste à la charte ESS Finistère. Logos : `Charte/tag29/3. LOGOS TAG/29/bureautique/` (fichiers RVB). Police : Quicksand, fichiers dans `Charte/tag29/Outils graphiques/POLICES/`, uniquement sur cette page.

## Charte (règle d'or)

`Charte/` est la seule source visuelle. Interdit d'inventer un logo, une couleur, une police ou un pictogramme de marque, d'en recolorer un, ou d'utiliser l'identité d'un autre territoire (Armor, Bretagne Sud, Centre Bretagne, Ille-et-Vilaine, Bretagne générique).

Référence des usages : `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/ESS-Bretagne_CharteGraphique.pdf`.

- Logo du site : uniquement `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/1-Logotype/1-Logotype final/ESS-Finistere/Web-RVB/PNG/`. Choisir la déclinaison prévue pour le fond (couleur sur blanc, cartouche sur aplat glaz, blanc ou blanc-or sur photo sombre, noir sur fond clair ou en gris). Ne pas étirer, recomposer, changer la typo du logo, ni inverser ses couleurs.
- Logos de pôles, fichiers RVB ou PNG nommés au territoire : Brest dans `Charte/logoPoleESS/PoleEssBrest/PaysdeBrest/`, Morlaix dans `Charte/logoPoleESS/PaysdeMorlaix/`. `__À_REMPLIR__` : aucun logo Cornouaille ; le dossier `Charte/logoPoleESS/Pôle ESS Cornouaille/` ne contient que des fichiers Pays de Brest, ne pas les afficher comme logo Cornouaille.
- Polices du site : Poppins pour le texte, les titres et l'interface (`Charte/CHARTE GRAPHIQUE ESS BRETAGNE/2-Polices-de-caracteres/Poppins/`). Viga pour la promesse et les exergues courtes seulement, jamais à côté du logo. Verdana est la police bureautique, pas celle du site. Auto-hébergées à partir de ces fichiers (woff2 au build). Aucun CDN, pas de Google Fonts.
- Couleurs, seules valeurs brutes, en tokens : blanc `#ffffff`, Glaz 1 `#78c7c9`, Glaz `#2d8a87`, Glaz 3 `#005154`, Glaz 4 `#00393b`, or `#f7a600`.
- Ingrédients (deux-points, guillemets) : `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/3-Ingredients-graphiques/`, versions Web-RVB. Les deux-points restent un signe de collectif, placés comme dans la charte, pas redessinés.
- Pictogrammes de marque : `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/4-Pictogrammes/Web-RVB/PNG/`. Les icônes d'interface (menu, lien externe) suivent `web-design.md` ; elles ne remplacent pas un picto de marque.
- Web seulement : PNG et polices. Pas les dossiers Print-CMJN, AI, EPS, JPG d'impression, ni `__MACOSX`.
- Photos : seulement des fichiers réels de `Charte/CHARTE GRAPHIQUE ESS BRETAGNE/5-Photos/` ou fournis ensuite. Sinon, pas d'image.
