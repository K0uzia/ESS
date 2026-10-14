-- Identité complétée le 14 octobre 2026 (saisie de l'utilisateur, reprise dans .claude/rules/[projet].md).
UPDATE settings SET value = 'Éliane Le Duff', updated_at = CURRENT_TIMESTAMP WHERE key = 'directeur_publication';
UPDATE settings SET value = replace(value, 'Téléphone : __À_REMPLIR__', 'Téléphone : 07 86 44 77 52'), updated_at = CURRENT_TIMESTAMP WHERE key = 'hebergeur';
