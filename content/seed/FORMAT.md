# Format des fichiers de contenu initial (seed)

Chaque page cible = un fichier `content/seed/pages/<page_key>.md`, en UTF-8 :

```
---
title: Titre SEO, unique, moins de 60 caractères
description: Description unique de 140 à 160 caractères, fidèle au contenu affiché.
h1: Titre affiché de la page
intro: Une ou deux phrases de chapeau (facultatif, une seule ligne).
actions: Texte du bouton | /lien-ou-#ancre ; Second bouton | #ancre   (facultatif, deux boutons au plus dans l'en-tête)
---

## Titre de section {#cle-de-section}

Paragraphes en Markdown…

## Autre section {#autre-cle .cartes}

- [Titre de carte](/lien/) : texte court de la carte.
- [Autre carte](/autre/) : texte.

## Section composant {#equipe-liste}

@equipe
```

Règles du Markdown accepté (le moteur ne connaît que ça) :
- Titres de section : `## Titre {#cle}` (clé en minuscules, chiffres, tirets, unique dans la page). Sous-titres : `###` et `####` seulement. Pas de `#` simple (le h1 vient du front matter).
- Paragraphes, listes `- ` et `1. `, `**gras**`, `*italique*`, liens `[texte](url)`, tableaux simples à barres `| a | b |` avec ligne `|---|---|`. Pas de HTML brut, pas d'images, pas d'iframe, pas de vidéo intégrée (une vidéo YouTube devient un simple lien texte « Voir la vidéo … (YouTube) »).
- Bouton : une ligne seule `→ [Texte](url)` devient un bouton.
- Mise en page d'une section (après la clé, cumulables) :
  - `.cartes` : liste de cartes (voir plus bas), pictogramme optionnel `- !picto [Titre](url) : texte` ou `- !picto **Titre** : texte` ;
  - `.blocs` : chaque sous-partie `### [!picto] Titre` devient une carte ; le texte avant la première sert d'introduction ;
  - `.frise` : chaque sous-partie `### Repère : titre` devient une étape de frise chronologique ;
  - `.etapes` : la liste numérotée `1.` devient des étapes numérotées ;
  - `.temoignages` : les citations `> « … » (Prénom, âge)` se placent en grille, avec les guillemets de la charte ;
  - `.encart` : bandeau sombre centré, pour un appel à l'action ;
  - `.split image=<photo>` (`.inverse` pour la photo à gauche) : texte et photo côte à côte ; noms de photos : `App\Site\Section::PHOTOS`. Une photo n'a qu'un seul emploi sur tout le site (en-têtes de page compris, `SiteController::HEAD_PHOTO`) ;
  - `.fond` (teinté) ou `.sombre` (dégradé Glaz) : fond forcé ; sinon alternance automatique.
- Pictogrammes (`!nom`) : ampoule, avionenpapier, bulles, calendrier, camembert, cible, client, engrenage, enveloppe, fleche1, fleche2, horloge, liste, localisation, loupe, megaphone, reseau.
- Liste dont chaque élément commence par un nombre : affichée en chiffres clés.
- `.cartes` après la clé : la section doit contenir une liste de lignes `- [Titre](url) : texte`, rendue en grille de cartes.
- Une section dont le corps est une seule ligne `@composant` insère un bloc dynamique tiré de la base. Composants disponibles :
  - `@poles` : les 3 pôles (nom, adresse, téléphone, e-mail, lien vers leur page)
  - `@coordonnees` : coordonnées complètes des 3 pôles + e-mail générique
  - `@equipe` : toute l'équipe, regroupée et filtrable par pôle
  - `@equipe:<pole-slug>` : l'équipe d'un pôle
  - `@adherents` : tous les adhérents, filtrables par pôle, avec la carte du Finistère
  - `@documents:<pole-slug>` : documents d'un pôle ; `@documents:fiche_pratique` ; `@documents:ressource`
  - `@offres` : offres d'emploi en cours
  - `@partenaires` : partenaires et réseaux
  - `@newsletter` : formulaire d'inscription aux newsletters des pôles
  - `@contact` : formulaire de contact
  - `@qualiopi` : certification Qualiopi du Pôle Brest (masqué tant que le certificat n'est pas déposé)
  - `@identite` : raison sociale, SIRET, siège, e-mail (mentions légales)
- Liens internes : uniquement vers les URL cibles : `/`, `/les-poles/`, `/les-poles/#pays-de-morlaix` (onglets des pôles), `/equipe/`, `/decouvrir-l-ess/`, `/agir/`, `/se-former/`, `/tag29/`, `/#contact`, `/mentions-legales/`, `/confidentialite/`, `/accessibilite/`, éventuellement avec ancre `#cle-de-section`. Jamais d'ancienne URL ess29.org.
- PDF : ne pas lier les PDF dans le texte ; ils passent par `documents.tsv` et un composant `@documents:…`. Exception : PDF externes (CRESS, CNCRES…) qui restent des liens texte.

Fichiers de données (TSV, tabulation, première ligne = en-têtes, UTF-8) dans `content/seed/` :
- `equipe.tsv` : `nom	role	pole	groupe	structure	email	telephone	photo	ordre` — pole = slug (`pays-de-morlaix`, `pays-de-brest`, `pays-de-cornouaille`), `tag29`, ou vide pour le groupement ; groupe = `groupement` (salariés du groupement, sans pôle), `salaries` ou `ca` ; photo = nom du portrait réel dans `content/equipe/` (sans extension), vide sinon ; recrutement = 1 pour un poste ouvert (nom « En recrutement », rôle = intitulé du poste), vide sinon ; email/téléphone seulement s'ils sont publiés et pas périmés (aucun @adess29.fr, @adessdupaysdemorlaix.org, gmail ancien).
- `adherents.tsv` : `nom	pole	statut	url	publie	adresse	latitude	longitude` — publie = 1 ou 0 (0 si le nom est déduit d'un fichier et non écrit sur le site). Brest et Morlaix : export des cartes GoGoCarto publiques des pôles (adess29 et adherentspolessmx, licence ODbL, fiches validées seulement) ; adresse, latitude et longitude facultatives (sans coordonnées, l'adhérent est listé mais absent de la carte).
- `documents.tsv` : `titre	pole	categorie	annee	url_source` — categorie = `document`, `fiche_pratique`, `ressource` ; url_source = URL complète du PDF sur ess29.org.
- `partenaires.tsv` : `nom	type	url	pole` — type = `partenaire` ou `reseau` ; pole vide si global.

Règles éditoriales (obligatoires) :
- Zéro fiction : aucune information qui n'est pas dans `content/ancien-site/` ou `.claude/rules/[projet].md`. Tu peux couper, regrouper, réordonner, corriger l'orthographe et les coquilles, écrire des titres de section et des phrases de liaison neutres ; tu n'ajoutes ni chiffre, ni nom, ni date, ni promesse.
- Une information n'apparaît qu'une fois sur tout le site : supprime les doublons signalés dans `INVENTAIRE.md` et renvoie par un lien vers l'endroit unique.
- Noms : « Pôle ESS Pays de Morlaix / de Brest / de Cornouaille ». « ADESS » seulement dans les historiques.
- Hors périmètre, à retirer : actualités, agenda, dates d'événements (permanences datées, réunions d'info datées, sessions datées passées), offres d'emploi de 2023, notes de travail internes.
- Coordonnées : jamais les anciennes (voir INVENTAIRE). Pour contacter un pôle, renvoyer vers `/contact/` ou le composant `@poles`/`@coordonnees`.
- Chiffres datés (Data'ESS 2018, 2015) : garder seulement en indiquant l'année source dans la phrase.
- Lien cassé (file:///, wordpress.local, 404) : retirer le lien, garder le texte s'il reste utile.
- Pas de texte de démo, pas de « lorem », pas de « à venir ». Donnée manquante : section omise, ou `__À_REMPLIR__` si la section est obligatoire.
- Style : français clair, phrases courtes, tutoiement interdit (vouvoiement), pas d'emoji.
