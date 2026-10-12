# Script local helper
LOCAL ?= bash setup-local.sh
PORT ?= 8080

.PHONY: \
  help \
  version info deps init reset dev db-shell bin open \
  d-init d-setup vendor-clean dump \
  phpstan test

# ---------- Aide ----------
help: ## Affiche cette aide
	@awk 'BEGIN {FS = ":.*##"; printf "\nTargets disponibles:\n\n"} /^[a-zA-Z0-9_.-]+:.*##/ { printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@echo

# =====================================================================
#                       Local (sans Docker)
# =====================================================================

version:      ## Infos PHP & SQLite (host)
	@php -v
	@sqlite3 --version || true

info:         ## Etat local (versions + chemins)
	$(LOCAL) info

deps:         ## Installe extensions PHP nécessaires (dnf/apt)
	$(LOCAL) deps

init:         ## Crée data/, applique migration SQLite si absente, génère bin/ si manquants
	$(LOCAL) init

reset:        ## Réinitialise la DB SQLite (ATTENTION: destructive)
	$(LOCAL) reset

dev:          ## Lance serveur PHP intégré (Ctrl+C pour arrêter)
	PORT=$(PORT) $(LOCAL) dev

db-shell:     ## Ouvre un shell sqlite
	$(LOCAL) db.shell

bin:          ## (Re)génère bin/dev et bin/db si absents
	$(LOCAL) bin.install

open:         ## Ouvre http://localhost:PORT en local
	xdg-open http://localhost:$(PORT)

vendor-clean: ## Réinstalle vendor proprement (host)
	rm -rf vendor composer.lock
	composer install

dump:         ## (Re)génère l'autoload Composer
	composer dump-autoload

# ---------- Qualité / Tests ----------
phpstan:      ## Analyse statique
	vendor/bin/phpstan analyse app src --level=6

test:         ## Tests unitaires
	vendor/bin/phpunit --testdox

# ---------- Bootstrap projet (host) ----------
d-init:       ## One-shot: installe les outils dev (host)
	composer require --dev phpstan/phpstan phpunit/phpunit
	@echo "✔ Tools added to composer.json (dev). Next runs will use 'install' only."

d-setup:      ## Installe les deps à partir du lock (host)
	composer install --no-interaction --prefer-dist --optimize-autoloader

db.init: ; bin/db init
db.reset: ; bin/db reset
