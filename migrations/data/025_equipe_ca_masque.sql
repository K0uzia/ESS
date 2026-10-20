-- Équipe : les membres des conseils d'administration restent dans le tableau de bord mais ne s'affichent plus sur le
-- site (décision du 20 octobre 2026) ; « Afficher sur le site » se rallume fiche par fiche dans Équipe.
UPDATE team_members SET is_published = 0, updated_at = CURRENT_TIMESTAMP WHERE team = 'ca';
