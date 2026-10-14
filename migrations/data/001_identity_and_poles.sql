-- Identité du groupement et coordonnées des trois pôles (source : .claude/rules/[projet].md).
INSERT INTO settings (key, value) VALUES
  ('raison_sociale', 'Groupement Territorial ESS Finistère - Penn Ar Bed'),
  ('nom_court', 'ESS Finistère'),
  ('siret', '880 705 041 00015'),
  ('email_contact', 'bonjour@ess29.org'),
  ('site_url', 'https://ess29.org'),
  ('promesse', 'L''économie de demain est là'),
  ('baseline', 'Pour une économie humaine et inventive.'),
  ('directeur_publication', '__À_REMPLIR__'),
  ('hebergeur', '__À_REMPLIR__'),
  ('gogocarto_url', '');

INSERT INTO poles (slug, name, address_lines, postal_code, city, phones, email, is_head_office, position) VALUES
  ('pays-de-morlaix', 'Pôle ESS Pays de Morlaix', 'Centre Afpa - Bat. 8' || char(10) || '39 Bellevue de la Madeleine', '29600', 'Morlaix', '09 81 71 87 49', 'paysdemorlaix@ess29.org', 0, 1),
  ('pays-de-brest', 'Pôle ESS Pays de Brest', 'Pépinière Poul Ar Bachet, Bat E.' || char(10) || '1 rue Louis Pidoux', '29200', 'Brest', '09 72 60 58 88', 'paysdebrest@ess29.org', 1, 2),
  ('pays-de-cornouaille', 'Pôle ESS Pays de Cornouaille', 'Maison Pierre Waldeck Rousseau' || char(10) || '1 allée Monseigneur Jean-René Calloc''h', '29000', 'Quimper', '02 98 60 78 87' || char(10) || '07 60 97 29 29', 'paysdecornouaille@ess29.org', 0, 3);
