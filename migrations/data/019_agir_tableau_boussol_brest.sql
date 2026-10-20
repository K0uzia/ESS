-- Page Agir : tableau des salles de la Boussol' Brest retourné (tarifs en lignes, salles en colonnes) pour tenir
-- dans sa carte (décision du 20 octobre 2026). Mêmes montants.
UPDATE page_contents SET value = replace(value, '| Salle | Journée, adhérent | Journée, non adhérent | Demi-journée, adhérent | Demi-journée, non adhérent |
|---|---|---|---|---|
| Salle solidaire (15 m²) | 20 € | 30 € | 10 € | 15 € |
| Salle démocratie (40 m²) | 40 € | 50 € | 25 € | 30 € |', '| Location | Salle solidaire (15 m²) | Salle démocratie (40 m²) |
|---|---|---|
| Journée, adhérent | 20 € | 40 € |
| Journée, non adhérent | 30 € | 50 € |
| Demi-journée, adhérent | 10 € | 25 € |
| Demi-journée, non adhérent | 15 € | 30 € |'), updated_at = CURRENT_TIMESTAMP
WHERE page_key = 'agir' AND section_key = 'boussol' AND field_key = 'body';
