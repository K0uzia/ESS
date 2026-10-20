-- Page TAg29 : cinquième chiffre publié sur tag.bzh, l'espace de coworking (décision du 20 octobre 2026).
UPDATE page_contents SET value = replace(value, '- 21 communes *concernées*.', '- 21 communes *concernées*.
- 1 espace *de coworking en Bretagne*.'), updated_at = CURRENT_TIMESTAMP
WHERE page_key = 'tag29' AND section_key = 'chiffres' AND field_key = 'body' AND value NOT LIKE '%coworking%';
