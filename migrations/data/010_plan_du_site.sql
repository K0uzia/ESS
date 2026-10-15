-- Page « Plan du site » (RGAA 12.1, second système de navigation), décision du 15 octobre 2026 ; contenu repris de content/seed/pages/plan-du-site.md.
INSERT OR IGNORE INTO page_contents (page_key, section_key, field_key, content_type, value, position) VALUES
    ('plan-du-site', '_meta', 'title', 'text', 'Plan du site', 0),
    ('plan-du-site', '_meta', 'description', 'text', 'Plan du site ess29.org : toutes les pages des Pôles ESS du Finistère et leurs sections, pour trouver directement une information ou une rubrique.', 0),
    ('plan-du-site', '_meta', 'h1', 'text', 'Plan du site', 0),
    ('plan-du-site', 'pages', 'title', 'text', 'Toutes les pages', 1),
    ('plan-du-site', 'pages', 'variant', 'text', '', 1),
    ('plan-du-site', 'pages', 'image', 'text', '', 1),
    ('plan-du-site', 'pages', 'body', 'markdown', '@plan', 1);
