-- Fixtures dossiers test - Version finale corrigée
-- Date : 2026-01-26
-- Objectif : 10 dossiers représentatifs pour tests interactions JS

-- ============================================================================
-- NETTOYAGE
-- ============================================================================

DELETE dn FROM dossiernature dn
INNER JOIN dossier d ON dn.ID_DOSSIER = d.ID_DOSSIER
WHERE d.OBJET_DOSSIER LIKE '[TEST-JS]%';

DELETE FROM dossier WHERE OBJET_DOSSIER LIKE '[TEST-JS]%';

-- ============================================================================
-- CRÉATION DOSSIERS DE TEST
-- ============================================================================

-- Test 1: Type 1 (Étude), Nature 1 (PC) - Complet avec avis favorable
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER, AVIS_DOSSIER_COMMISSION, DATECOMM_DOSSIER)
VALUES (1, '[TEST-JS] PC standard', NOW(), 0, 1, '2026-02-20');
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 1);

-- Test 2: Type 1, Nature 2 (AT) - Complet
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER)
VALUES (1, '[TEST-JS] AT - Autorisation travaux', NOW(), 0);
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 2);

-- Test 3: Type 1, Nature 3 (Dérogation) - Complet
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER)
VALUES (1, '[TEST-JS] Dérogation article R123-45', NOW(), 0);
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 3);

-- Test 4: Type 1, Nature 19 (Levée réserves) - AVIS DÉFAVORABLE (ID=2)
-- FACTDANGE_DOSSIER = 4 (facteur dangérosité élevé)
-- ECHEANCIERTRAV_DOSSIER = date échéance travaux
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER, AVIS_DOSSIER_COMMISSION, FACTDANGE_DOSSIER, ECHEANCIERTRAV_DOSSIER)
VALUES (1, '[TEST-JS] Levée réserves - AVIS DÉFAVORABLE', NOW(), 0, 2, 4, '2026-06-30');
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 19);

-- Test 5: Type 1, Nature 4 (Cahier charges SSI) - INCOMPLET
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER, DATEINCOMPLET_DOSSIER)
VALUES (1, '[TEST-JS] Cahier charges SSI - INCOMPLET', NOW(), 1, CURDATE());
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 4);

-- Test 6: Type 2 (Visite commission), Nature 21 (Périodique) - Avec dates visite + commission
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER, DATEVISITE_DOSSIER, DATECOMM_DOSSIER, AVIS_DOSSIER_COMMISSION)
VALUES (2, '[TEST-JS] Visite périodique', NOW(), 0, '2026-02-15', '2026-02-28', 1);
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 21);

-- Test 7: Type 2, Nature 20 (Réception travaux) - Avec date visite uniquement
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER, DATEVISITE_DOSSIER)
VALUES (2, '[TEST-JS] Réception travaux', NOW(), 0, '2026-02-16');
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 20);

-- Test 8: Type 3 (Groupe visite), Nature 26 (Périodique) - Avec date visite
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER, DATEVISITE_DOSSIER)
VALUES (3, '[TEST-JS] Groupe visite périodique', NOW(), 0, '2026-02-17');
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 26);

-- Test 9: Type 5 (Courrier), Nature 52 (Lettre) - Minimal
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER)
VALUES (5, '[TEST-JS] Lettre demande renseignements', NOW(), 0);
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 52);

-- Test 10: Type 1, Nature 1 (PC) - PLATAU (détecté par mot-clé dans objet)
INSERT INTO dossier (TYPE_DOSSIER, OBJET_DOSSIER, DATEINSERT_DOSSIER, INCOMPLET_DOSSIER, AVIS_DOSSIER_COMMISSION)
VALUES (1, '[TEST-JS] PC via PLATAU - Test intégration', NOW(), 0, 1);
INSERT INTO dossiernature (ID_DOSSIER, ID_NATURE) VALUES (LAST_INSERT_ID(), 1);

-- ============================================================================
-- VÉRIFICATION
-- ============================================================================

-- Compter dossiers créés
SELECT 'INFO: Dossiers de test créés' as Message, COUNT(*) as Total 
FROM dossier WHERE OBJET_DOSSIER LIKE '[TEST-JS]%';

-- Lister dossiers créés
SELECT 
    d.ID_DOSSIER as ID,
    dt.LIBELLE_DOSSIERTYPE as Type,
    dnl.LIBELLE_DOSSIERNATURE as Nature,
    LEFT(d.OBJET_DOSSIER, 50) as Objet,
    CASE 
        WHEN d.INCOMPLET_DOSSIER = 1 THEN 'INCOMPLET'
        ELSE 'COMPLET'
    END as Etat,
    CASE 
        WHEN d.AVIS_DOSSIER_COMMISSION = 1 THEN 'Favorable'
        WHEN d.AVIS_DOSSIER_COMMISSION = 2 THEN 'Défavorable'
        WHEN d.AVIS_DOSSIER_COMMISSION = 3 THEN 'Avec réserves'
        WHEN d.AVIS_DOSSIER_COMMISSION = 4 THEN 'Différé'
        ELSE 'Aucun'
    END as Avis
FROM dossier d
JOIN dossiertype dt ON d.TYPE_DOSSIER = dt.ID_DOSSIERTYPE
LEFT JOIN dossiernature dn ON d.ID_DOSSIER = dn.ID_DOSSIER
LEFT JOIN dossiernatureliste dnl ON dn.ID_NATURE = dnl.ID_DOSSIERNATURE
WHERE d.OBJET_DOSSIER LIKE '[TEST-JS]%'
ORDER BY d.ID_DOSSIER;

-- ============================================================================
-- NOTES
-- ============================================================================

/*
COUVERTURE :
- Types testés : 5/7 (Types 1, 2, 3, 5)
- Natures testées : 10 représentatives
- Cas limites : Incomplet, Avis défavorable, Plat'AU

CAS D'USAGE POUR TESTS :
1. Test visibilité conditionnelle : Tous les dossiers (changement type/nature)
2. Test avis défavorable : Dossier #4 (affiche facteur + échéancier)
3. Test incomplet : Dossier #5 (masque avis/commission)
4. Test calendrier : Dossiers #6, #7, #8 (types 2 et 3 avec dates)
5. Test Plat'AU : Dossier #10 (détection mot-clé)

POUR SUPPRIMER :
DELETE dn FROM dossiernature dn
INNER JOIN dossier d ON dn.ID_DOSSIER = d.ID_DOSSIER
WHERE d.OBJET_DOSSIER LIKE '[TEST-JS]%';
DELETE FROM dossier WHERE OBJET_DOSSIER LIKE '[TEST-JS]%';
*/
