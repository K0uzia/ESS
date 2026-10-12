# ESS Finistère

Refonte de [ess29.org](https://ess29.org/) : site public et tableau de bord (`/admin/`) du Groupement Territorial ESS Finistère - Penn Ar Bed.

Décisions et périmètre : `.claude/rules/[projet].md`. Format des contenus : `content/seed/FORMAT.md`.

## Démarrer en local

PHP 8.3 avec `pdo_sqlite`, `gd` (WebP), `intl`, `iconv`, `fileinfo`, `openssl`.

```sh
make init               # crée data/database.sqlite (schéma, identité, compte initial)
php bin/seed --documents # contenus, équipe, adhérents, partenaires, documents (télécharge les PDF de l'ancien site)
make dev                # http://localhost:8080 ; admin : /admin/ (compte temporaire lacapsule / lacapsule)
make db.reset           # repart d'une base vide (destructif)
```

Visuels régénérés depuis `Charte/` : `php bin/build-images.php` ; polices : `bin/build-fonts.mjs` (voir l'en-tête du fichier).

## Production (Hostinger)

- Racine web : `public/` uniquement (`app/`, `config/`, `data/`, `content/` hors web). `public/.htaccess` gère HTTPS, redirections, blocages et cache.
- `.env` à partir de `.env.example` : `APP_ENV=production`, `APP_KEY` (64 caractères hexadécimaux), SMTP Hostinger, quotas de newsletter.
- Tâche planifiée pour l'envoi des newsletters par lots : `*/10 * * * * cd /chemin/du/site && php bin/newsletter-send >> data/newsletter.log 2>&1`
- Bloquant avant mise en ligne : supprimer le compte temporaire `lacapsule` ou changer son mot de passe, compléter les `__À_REMPLIR__` (Identité du site).
