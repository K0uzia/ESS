-- Page TAg29 (décision du 20 octobre 2026) : pastilles des chiffres comme sur tag.bzh (dernier mot en minuscules)
-- et logos des partenaires sous ceux des financeurs.
UPDATE page_contents SET value = replace(replace(replace(replace(value,
  '- 33 projets d''entreprises propulsés.', '- 33 projets d''entreprises *propulsés*.'),
  '- 146 personnes accompagnées.', '- 146 personnes *accompagnées*.'),
  '- 38 emplois créés.', '- 38 emplois *créés*.'),
  '- 21 communes concernées.', '- 21 communes *concernées*.'),
  updated_at = CURRENT_TIMESTAMP
WHERE page_key = 'tag29' AND section_key = 'chiffres' AND field_key = 'body';
UPDATE page_contents SET value = replace(value, '@tag29-logos:financeurs', '@tag29-logos:financeurs

TAg29 travaille aussi avec ces partenaires :

@tag29-logos:partenaires'), updated_at = CURRENT_TIMESTAMP
WHERE page_key = 'tag29' AND section_key = 'partenaires' AND field_key = 'body' AND value NOT LIKE '%@tag29-logos:partenaires%';
