-- Poste en recrutement affiché comme sur l'organigramme du 15 octobre 2026 (carte « En recrutement », sans portrait).
ALTER TABLE team_members ADD COLUMN is_vacant INTEGER NOT NULL DEFAULT 0 CHECK (is_vacant IN (0, 1));
INSERT INTO team_members (name, role, pole_id, team, is_vacant, position)
    SELECT 'En recrutement', 'Assistance administrative et RH', NULL, 'groupement', 1, 4
    WHERE EXISTS (SELECT 1 FROM team_members) AND NOT EXISTS (SELECT 1 FROM team_members WHERE is_vacant = 1);
