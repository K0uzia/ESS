-- Page Agir : espace insécable entre le nombre et l'unité des surfaces de salles (le tableau ne coupe plus « 15 / m² »).
UPDATE page_contents SET value = replace(replace(value, '(15 m²)', '(15 m²)'), '(40 m²)', '(40 m²)'), updated_at = CURRENT_TIMESTAMP
WHERE page_key = 'agir' AND section_key = 'boussol' AND field_key = 'body';
