-- Fixtures dossiers de test pour migration JavaScript
-- Date : 2026-01-26
-- Objectif : Créer 20 dossiers représentatifs couvrant tous les types et natures principales
-- Usage : docker exec -i prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 < docs/tech/dossier/fixtures_dossiers_test.sql

-- ============================================================================
-- PRÉPARATION : Vérifier données de référence
-- ============================================================================

-- Vérifier types disponibles (doit retourner 7 types)
SELECT 'Types disponibles:' as info;
SELECT ID_DOSSIERTYPE, LIBELLE_DOSSIERTYPE FROM dossiertype ORDER BY ID_DOSSIERTYPE;

-- Vérifier natures disponibles (doit retourner 62 natures)
SELECT 'Natures disponibles:' as info;
SELECT COUNT(*) as total_natures FROM dossiernatureliste;

-- ============================================================================
-- NETTOYAGE : Supprimer anciens dossiers de test (si existants)
-- ============================================================================

DELETE FROM dossier WHERE OBJET_DOSSIER LIKE '[TEST-JS]%';

-- ============================================================================
-- FIXTURES : Création dossiers de test (20 dossiers)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- TYPE 1 : ÉTUDE (10 dossiers)
-- ----------------------------------------------------------------------------

-- Test 1.1 : PC (Permis de construire) - Nature 1
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATEVISITE_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    1, -- Type Étude
    1, -- Nature PC
    '[TEST-JS] PC - Permis de construire standard',
    '2026-01-15',
    '2026-02-10',
    '2026-02-20',
    0, -- Complet
    1  -- Avis favorable
);

-- Test 1.2 : AT (Autorisation de travaux) - Nature 2
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATEVISITE_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    1,
    2,
    '[TEST-JS] AT - Autorisation travaux ERP',
    '2026-01-16',
    '2026-02-11',
    '2026-02-21',
    0,
    1
);

-- Test 1.3 : Dérogation - Nature 3
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION,
    TEXTEAPPLICABLE_DOSSIER
) VALUES (
    1,
    3,
    '[TEST-JS] Dérogation article R123-45',
    '2026-01-17',
    '2026-02-22',
    0,
    1,
    'Article R123-45 du CCH'
);

-- Test 1.4 : Cahier charges SSI - Nature 4
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    INCOMPLET_DOSSIER
) VALUES (
    1,
    4,
    '[TEST-JS] Cahier charges fonctionnel SSI',
    '2026-01-18',
    1  -- Incomplet (doit masquer avis/commission)
);

-- Test 1.5 : Levée prescriptions - Nature 7
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION,
    TEXTEAPPLICABLE_DOSSIER
) VALUES (
    1,
    7,
    '[TEST-JS] Levée prescriptions dossier PC2025001',
    '2026-01-19',
    '2026-02-23',
    0,
    1,
    'Prescriptions du 15/01/2025'
);

-- Test 1.6 : Utilisation exceptionnelle - Nature 18
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    1,
    18,
    '[TEST-JS] Utilisation exceptionnelle salle spectacle',
    '2026-01-20',
    '2026-02-24',
    0,
    1
);

-- Test 1.7 : Levée réserves avis défavorable - Nature 19 + AVIS DÉFAVORABLE
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION,
    FACTEURDANGEROSITE_DOSSIER,
    ECHEANCIER_DOSSIER
) VALUES (
    1,
    19,
    '[TEST-JS] Levée réserves avis défavorable',
    '2026-01-21',
    '2026-02-25',
    0,
    2,  -- AVIS DÉFAVORABLE (doit afficher champs facteur + échéancier)
    3,
    '2026-06-30'
);

-- Test 1.8 : Déclaration préalable - Nature 30
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    1,
    30,
    '[TEST-JS] DP - Déclaration préalable',
    '2026-01-22',
    0,
    1
);

-- Test 1.9 : ICPE - Nature 61
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    1,
    61,
    '[TEST-JS] Autorisation ICPE usine',
    '2026-01-23',
    '2026-02-26',
    0,
    1
);

-- Test 1.10 : Déclassement - Nature 66
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    1,
    66,
    '[TEST-JS] Déclassement ERP 5ème catégorie',
    '2026-01-24',
    '2026-02-27',
    0,
    1
);

-- ----------------------------------------------------------------------------
-- TYPE 2 : VISITE DE COMMISSION (5 dossiers)
-- ----------------------------------------------------------------------------

-- Test 2.1 : Réception travaux - Nature 20
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATEVISITE_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    2, -- Type Visite commission
    20, -- Nature Réception travaux
    '[TEST-JS] Réception travaux suite AT2025042',
    '2026-01-25',
    '2026-02-12',
    '2026-02-28',
    0,
    1
);

-- Test 2.2 : Périodique - Nature 21
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATEVISITE_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    2,
    21,
    '[TEST-JS] Visite périodique ERP type L',
    '2026-01-26',
    '2026-02-13',
    '2026-03-01',
    0,
    1
);

-- Test 2.3 : Chantier - Nature 22
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATEVISITE_DOSSIER,
    INCOMPLET_DOSSIER
) VALUES (
    2,
    22,
    '[TEST-JS] Visite chantier travaux en cours',
    '2026-01-27',
    '2026-02-14',
    1  -- Incomplet (doit masquer avis/commission)
);

-- Test 2.4 : Contrôle - Nature 23
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATEVISITE_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    2,
    23,
    '[TEST-JS] Visite contrôle suite avis défavorable',
    '2026-01-28',
    '2026-02-15',
    '2026-03-02',
    0,
    2  -- AVIS DÉFAVORABLE
);

-- Test 2.5 : Avant ouverture - Nature 47
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATEVISITE_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    2,
    47,
    '[TEST-JS] Visite avant ouverture nouveau commerce',
    '2026-01-29',
    '2026-02-16',
    '2026-03-03',
    0,
    1
);

-- ----------------------------------------------------------------------------
-- TYPE 3 : GROUPE DE VISITE (2 dossiers)
-- ----------------------------------------------------------------------------

-- Test 3.1 : Réception travaux groupe - Nature 25
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATEVISITE_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    3, -- Type Groupe visite
    25, -- Nature Réception travaux
    '[TEST-JS] Groupe visite réception',
    '2026-01-30',
    '2026-02-17',
    0,
    1
);

-- Test 3.2 : Périodique groupe - Nature 26
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATEVISITE_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    3,
    26,
    '[TEST-JS] Groupe visite périodique',
    '2026-01-31',
    '2026-02-18',
    0,
    1
);

-- ----------------------------------------------------------------------------
-- TYPE 5 : COURRIER / COURRIEL (2 dossiers)
-- ----------------------------------------------------------------------------

-- Test 5.1 : Lettre - Nature 52
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    INCOMPLET_DOSSIER
) VALUES (
    5, -- Type Courrier
    52, -- Nature Lettre
    '[TEST-JS] Lettre demande renseignements',
    '2026-02-01',
    0
);

-- Test 5.2 : Mise en demeure - Nature 55
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    INCOMPLET_DOSSIER
) VALUES (
    5,
    55,
    '[TEST-JS] Mise en demeure suite non-conformité',
    '2026-02-02',
    0
);

-- ----------------------------------------------------------------------------
-- TYPE 7 : ARRÊTÉ (1 dossier)
-- ----------------------------------------------------------------------------

-- Test 7.1 : Arrêté ouverture - Nature 40
INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    INCOMPLET_DOSSIER
) VALUES (
    7, -- Type Arrêté
    40, -- Nature Ouverture
    '[TEST-JS] Arrêté ouverture ERP',
    '2026-02-03',
    0
);

-- ----------------------------------------------------------------------------
-- DOSSIER SPÉCIAL : PLAT'AU (Type 1 avec détection Plat'AU)
-- ----------------------------------------------------------------------------

INSERT INTO dossier (
    ID_DOSSIERTYPE,
    ID_DOSSIERNATURE,
    OBJET_DOSSIER,
    DATEINSERT_DOSSIER,
    DATECOMM_DOSSIER,
    INCOMPLET_DOSSIER,
    AVIS_DOSSIER_COMMISSION
) VALUES (
    1,
    1,
    '[TEST-JS] PC via PLATAU - Test intégration',
    '2026-02-04',
    '2026-03-04',
    0,
    1
);

-- ============================================================================
-- VÉRIFICATION FINALE
-- ============================================================================

-- Compter dossiers de test créés (doit retourner 21)
SELECT 'Dossiers de test créés:' as info;
SELECT COUNT(*) as total_test FROM dossier WHERE OBJET_DOSSIER LIKE '[TEST-JS]%';

-- Afficher tous les dossiers de test créés
SELECT 'Liste complète des dossiers de test:' as info;
SELECT 
    d.ID_DOSSIER,
    dt.LIBELLE_DOSSIERTYPE as Type,
    dnl.LIBELLE_DOSSIERNATURE as Nature,
    d.OBJET_DOSSIER as Objet,
    d.DATEINSERT_DOSSIER as DateInsert,
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
JOIN dossiertype dt ON d.ID_DOSSIERTYPE = dt.ID_DOSSIERTYPE
JOIN dossiernatureliste dnl ON d.ID_DOSSIERNATURE = dnl.ID_DOSSIERNATURE
WHERE d.OBJET_DOSSIER LIKE '[TEST-JS]%'
ORDER BY d.ID_DOSSIERTYPE, d.ID_DOSSIERNATURE;

-- ============================================================================
-- NOTES D'USAGE
-- ============================================================================

/*
EXÉCUTION :
-----------
cd /home/dev/prevarisc-infra
docker exec -i prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 < docs/tech/dossier/fixtures_dossiers_test.sql

NETTOYAGE (si besoin recommencer) :
------------------------------------
DELETE FROM dossier WHERE OBJET_DOSSIER LIKE '[TEST-JS]%';

VÉRIFICATION POST-EXÉCUTION :
-----------------------------
1. Ouvrir http://localhost:7080/dossier
2. Filtrer par objet "[TEST-JS]"
3. Vérifier 21 dossiers listés
4. Tester édition de chaque dossier (clic sur ID)

COUVERTURE :
------------
- Types testés : 5/7 (71%) - Types 4 et 6 omis (peu utilisés)
- Natures testées : 21/62 (34%) - Représentativité 80/20
- Cas limites : Incomplet, Avis défavorable, Plat'AU

POINTS DE VIGILANCE :
---------------------
- IDs auto-incrémentés : récupérer ID_DOSSIER après INSERT pour tests API
- Dates cohérentes : dateInsert < dateVisite < dateCommission
- Contraintes FK : Vérifier que types/natures existent avant INSERT
- Établissements : Dossiers non liés à établissements (optionnel pour tests JS)
*/
