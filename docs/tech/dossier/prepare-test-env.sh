#!/bin/bash
# Script de préparation environnement de test - Interactions JavaScript dossier
# Date : 2026-01-26
# Usage : ./docs/tech/dossier/prepare-test-env.sh

set -e  # Exit on error

# Couleurs pour output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}Préparation environnement de test${NC}"
echo -e "${BLUE}Migration JavaScript - Formulaire dossier${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""

# ============================================================================
# 1. VÉRIFICATION PRÉ-REQUIS
# ============================================================================

echo -e "${YELLOW}[1/6] Vérification pré-requis...${NC}"

# Vérifier Docker
if ! docker ps > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker n'est pas démarré${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker actif${NC}"

# Vérifier conteneurs
if ! docker ps | grep -q "prevarisc-infra-db-1"; then
    echo -e "${RED}❌ Conteneur MySQL non trouvé (prevarisc-infra-db-1)${NC}"
    echo "   Lancer : cd /home/dev/prevarisc-infra && docker compose -f compose.dev.yaml up -d"
    exit 1
fi
echo -e "${GREEN}✅ Conteneur MySQL actif${NC}"

if ! docker ps | grep -q "prevarisc-infra-app-1"; then
    echo -e "${RED}❌ Conteneur App non trouvé (prevarisc-infra-app-1)${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Conteneur App actif${NC}"

# Vérifier base de données accessible
if ! docker exec prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 -e "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${RED}❌ Base de données PRV_prevarisc_v2 inaccessible${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Base de données accessible${NC}"

# Vérifier serveur web
if ! curl -s http://localhost:7080 > /dev/null; then
    echo -e "${RED}❌ Serveur web non accessible (http://localhost:7080)${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Serveur web accessible${NC}"

echo ""

# ============================================================================
# 2. NETTOYAGE FIXTURES PRÉCÉDENTES (optionnel)
# ============================================================================

echo -e "${YELLOW}[2/6] Nettoyage fixtures précédentes...${NC}"

read -p "Supprimer les anciens dossiers de test [TEST-JS] ? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    docker exec prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 \
        -e "DELETE FROM dossier WHERE OBJET_DOSSIER LIKE '[TEST-JS]%';" 2>/dev/null
    
    COUNT=$(docker exec prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 \
        -se "SELECT COUNT(*) FROM dossier WHERE OBJET_DOSSIER LIKE '[TEST-JS]%';" 2>/dev/null)
    
    if [ "$COUNT" -eq 0 ]; then
        echo -e "${GREEN}✅ Anciens dossiers supprimés${NC}"
    else
        echo -e "${RED}❌ Erreur lors de la suppression${NC}"
        exit 1
    fi
else
    echo -e "${BLUE}⏭️  Nettoyage ignoré${NC}"
fi

echo ""

# ============================================================================
# 3. CHARGEMENT FIXTURES SQL
# ============================================================================

echo -e "${YELLOW}[3/6] Chargement fixtures dossiers de test...${NC}"

FIXTURES_FILE="docs/tech/dossier/fixtures_dossiers_test.sql"

if [ ! -f "$FIXTURES_FILE" ]; then
    echo -e "${RED}❌ Fichier fixtures non trouvé : $FIXTURES_FILE${NC}"
    exit 1
fi

# Exécuter le fichier SQL
docker exec -i prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 < "$FIXTURES_FILE" 2>/dev/null

# Vérifier création
COUNT=$(docker exec prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 \
    -se "SELECT COUNT(*) FROM dossier WHERE OBJET_DOSSIER LIKE '[TEST-JS]%';" 2>/dev/null)

if [ "$COUNT" -ge 20 ]; then
    echo -e "${GREEN}✅ $COUNT dossiers de test créés${NC}"
else
    echo -e "${RED}❌ Erreur : seulement $COUNT dossiers créés (attendu: 21)${NC}"
    exit 1
fi

echo ""

# ============================================================================
# 4. VÉRIFICATION DONNÉES RÉFÉRENCE
# ============================================================================

echo -e "${YELLOW}[4/6] Vérification données de référence...${NC}"

# Vérifier types
TYPES_COUNT=$(docker exec prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 \
    -se "SELECT COUNT(*) FROM dossiertype;" 2>/dev/null)
echo -e "   Types dossier : ${GREEN}$TYPES_COUNT${NC} (attendu: 7)"

# Vérifier natures
NATURES_COUNT=$(docker exec prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 \
    -se "SELECT COUNT(*) FROM dossiernatureliste;" 2>/dev/null)
echo -e "   Natures dossier : ${GREEN}$NATURES_COUNT${NC} (attendu: 62)"

# Vérifier commissions (pour tests calendrier)
COMMISSIONS_COUNT=$(docker exec prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 \
    -se "SELECT COUNT(*) FROM commission;" 2>/dev/null)

if [ "$COMMISSIONS_COUNT" -eq 0 ]; then
    echo -e "   ${YELLOW}⚠️  Aucune commission trouvée (tests calendrier limités)${NC}"
else
    echo -e "   Commissions : ${GREEN}$COMMISSIONS_COUNT${NC}"
fi

echo ""

# ============================================================================
# 5. LISTE DOSSIERS DE TEST CRÉÉS
# ============================================================================

echo -e "${YELLOW}[5/6] Dossiers de test créés :${NC}"
echo ""

docker exec prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2 \
    -e "SELECT 
            d.ID_DOSSIER as ID,
            dt.LIBELLE_DOSSIERTYPE as Type,
            dnl.LIBELLE_DOSSIERNATURE as Nature,
            LEFT(d.OBJET_DOSSIER, 50) as Objet,
            CASE 
                WHEN d.INCOMPLET_DOSSIER = 1 THEN 'INCOMPLET'
                ELSE 'COMPLET'
            END as Etat
        FROM dossier d
        JOIN dossiertype dt ON d.ID_DOSSIERTYPE = dt.ID_DOSSIERTYPE
        JOIN dossiernatureliste dnl ON d.ID_DOSSIERNATURE = dnl.ID_DOSSIERNATURE
        WHERE d.OBJET_DOSSIER LIKE '[TEST-JS]%'
        ORDER BY d.ID_DOSSIERTYPE, d.ID_DOSSIERNATURE;" 2>/dev/null

echo ""

# ============================================================================
# 6. RÉCAPITULATIF ET PROCHAINES ÉTAPES
# ============================================================================

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}✅ Environnement de test prêt !${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""

echo -e "${BLUE}📋 Prochaines étapes :${NC}"
echo ""
echo "1. Ouvrir navigateur : http://localhost:7080"
echo "2. Se connecter (identifiants admin)"
echo "3. Naviguer vers : Liste dossiers > Filtrer par '[TEST-JS]'"
echo "4. Commencer tests manuels selon guide :"
echo "   📄 docs/tech/dossier/GUIDE_TESTS_INTERACTIONS_JS.md"
echo ""

echo -e "${BLUE}📊 Couverture tests :${NC}"
echo "   • Types testés : 5/7 (71%)"
echo "   • Natures testées : 21/62 (34%)"
echo "   • Cas limites : Incomplet, Avis défavorable, Plat'AU"
echo ""

echo -e "${BLUE}🧪 Tests prioritaires (Blocs PLAN_MIGRATION_JS_DOSSIER.md) :${NC}"
echo "   1. ${YELLOW}Bloc 1${NC} : Visibilité conditionnelle (Type/Nature)"
echo "   2. ${YELLOW}Bloc 3${NC} : Calendrier commissions & visites"
echo "   3. ${YELLOW}Bloc 6${NC} : Avis & Dérogations"
echo "   4. ${YELLOW}Bloc 8${NC} : Intégrations Plat'AU"
echo ""

echo -e "${BLUE}🔗 Accès rapides :${NC}"
echo "   • Application : http://localhost:7080"
echo "   • Logs app : docker logs -f prevarisc-infra-app-1"
echo "   • Console MySQL : docker exec -it prevarisc-infra-db-1 mysql -u prevarisc -pplanmusique PRV_prevarisc_v2"
echo ""

echo -e "${YELLOW}💡 Astuce :${NC}"
echo "   Ouvrir la console développeur (F12) pour vérifier erreurs JS"
echo "   Documenter résultats dans : docs/tech/dossier/TEST_RESULTS.md"
echo ""

# Optionnel : Ouvrir automatiquement le navigateur (Linux uniquement)
if command -v xdg-open &> /dev/null; then
    read -p "Ouvrir l'application dans le navigateur ? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        xdg-open http://localhost:7080 &
        echo -e "${GREEN}✅ Navigateur ouvert${NC}"
    fi
fi

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Script terminé avec succès 🎉${NC}"
echo -e "${GREEN}=========================================${NC}"

exit 0
