-- Page TAg29 à la charte TAg29 (décision du 20 octobre 2026) : le hashtag remplace les pictogrammes ESS des blocs.
UPDATE page_contents SET value = replace(replace(replace(replace(replace(replace(replace(replace(value,
  '### !loupe ', '### !diese '), '### !ampoule ', '### !diese '), '### !cible ', '### !diese '), '### !bulles ', '### !diese '),
  '### !localisation ', '### !diese '), '### !engrenage ', '### !diese '), '### !fleche2 ', '### !diese '), '### !reseau ', '### !diese '),
  updated_at = CURRENT_TIMESTAMP
WHERE page_key = 'tag29' AND field_key = 'body';
