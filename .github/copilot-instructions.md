# Instructions Copilot - Migration Prevarisc

**Contexte :** Migration Zend 1.12 → Symfony 4.4 | **PHP 7.1.33**

---

## 🎯 Mission

Migrer l'application Prevarisc de Zend vers Symfony en respectant **100% l'iso-fonctionnalité** du legacy.

**Répertoires :**
- Legacy (lecture seule) : `prevarisc/`
- Code migré : `prevarisc-migration/`
- API Plat'AU : `prevarisc-passerelle-platau/`

---

## ⚙️ Stack technique

| Techno | Version | Contrainte |
|--------|---------|------------|
| PHP | **7.1.33** | Pas de propriétés typées, pas de void, DocBlocks obligatoires |
| Symfony | 4.4 LTS | Framework cible |
| Zend | 1.12 | Framework legacy |
| Doctrine | 2.14 | ORM |
| Twig | 2.x | Templates (Bootstrap 3) |
| PHPStan | Niveau 10 | Qualité code |

**PHP 7.1 - Exemple valide :**
```php
class MyService {
    /** @var LoggerInterface */
    private $logger;
    
    /** @return string[] */
    public function getItems(): array { return []; }
}
```

---

## 🔄 Mapping Zend → Symfony (synthèse)

**Templates :** `.phtml` → `.html.twig` | Bootstrap 2 → Bootstrap 3  
**Controllers :** `Zend_Controller_Action` → `AbstractController`  
**Forms :** `Zend_Form` → `AbstractType` (FormType)  
**Models :** `DbTable` → `Entity` (Doctrine)  
**Routing :** `/module/controller/action` → Annotations `@Route`

**Détails complets :** Voir `docs/tech/migration/MAPPING_LEGACY_REFERENCE.md`

---

## 🛠️ Environnement

**Docker :** `prevarisc-infra-app-1` (PHP), `prevarisc-infra-mysql-1` (DB)

**Commandes (depuis l'hôte) :**
```bash
castor symfony:analyse  # PHPStan niveau 10
castor symfony:cs       # Code Style
castor symfony:test     # Tests PHPUnit
```

---

## ✅ Workflow développement

### 1. Analyser le legacy
```bash
grep -r "DATEVISITE" prevarisc/application/
find prevarisc/application/controllers/ -name "*Dossier*"
```

### 2. Implémenter (incrémental)
1. Entité → Repository → FormType → Controller → Template → JS
2. Valider : `castor symfony:analyse && castor symfony:cs && castor symfony:test`
3. Commiter : `feat(scope): description`
4. Mettre à jour `docs/tech/REPRENDRE_ICI.md`

### 3. Validation avant commit (obligatoire)
- ✅ PHPStan 0 erreur
- ✅ CS 0 erreur
- ✅ Tests 100% passent
- ✅ Vérification manuelle UI

---

## 📚 Documentation par tâche

| Tâche | Fichiers à consulter |
|-------|---------------------|
| **Édition dossiers** | `.github/edition-dossier.md`<br>`docs/tech/dossier/PLAN_MIGRATION_EDITION_DOSSIER.md` |
| **Affichage dossiers** | `docs/tech/dossier/migration-affichage-dossiers.md` |
| **Mapping champs** | `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md` |
| **État avancement** | `docs/tech/REPRENDRE_ICI.md` |

---

## 🎯 Règles absolues

**INTERDIT ❌**
- `findAll()` sans pagination
- Logique métier dans controllers
- Requêtes N+1 (manque jointures)
- Modifier legacy sans autorisation
- Fonctionnalités PHP > 7.1

**REQUIS ✅**
- Pagination (KnpPaginatorBundle)
- Services pour logique métier
- QueryBuilder avec jointures
- Type hints + DocBlocks
- Conformité 100% legacy avant optimisation

**Gestion écarts :** Implémenter conformité → Documenter suggestion → Attendre validation

---

## 🚀 Best Practices (résumé)

**Controller :**
```php
public function edit(int $id, Request $request, DossierService $service): Response {
    $form = $this->createForm(DossierType::class, $dossier);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $service->save($dossier); // Pas dans le controller
        return $this->redirectToRoute('dossier_show', ['id' => $id]);
    }
    return $this->render('dossier/edit.html.twig', ['form' => $form->createView()]);
}
```

**Repository :**
```php
public function findWithRelations(int $limit = 50): array {
    return $this->createQueryBuilder('d')
        ->leftJoin('d.type', 't')->addSelect('t')
        ->setMaxResults($limit)
        ->getQuery()->getResult();
}
```

---

## Stratégie de tests

**Philosophie** : Privilégie la **qualité** sur la quantité. Chaque test doit avoir une **valeur ajoutée** claire pour réduire la dette technique.

### Configuration
- Répertoire : `prevarisc-migration/tests/`
- Namespace : `App\Tests\`
- Framework : PHPUnit 7.5

### Pyramide de tests à respecter

1. **Tests unitaires (70%)** - Rapides, isolés, sans dépendances
    - Services métier avec logique complexe
    - Validations, calculs, transformations de données
    - Enums, Value Objects, DTOs
    - **Quand tester** : Dès qu'une méthode contient de la logique métier
    - **Ne pas tester** : Getters/setters simples, constructeurs triviaux

2. **Tests d'intégration (20%)** - Services avec dépendances réelles
    - Repositories Doctrine avec requêtes DQL/QB complexes
    - Services orchestrant plusieurs dépendances
    - EventListeners/EventSubscribers
    - **Quand tester** : Interactions entre composants, requêtes SQL complexes
    - **Ne pas tester** : Méthodes générées par Doctrine (`findBy`, `findOneBy`)

3. **Tests fonctionnels (10%)** - Parcours utilisateur critiques
    - Formulaires Symfony avec validations complexes
    - Controllers critiques (authentification, établissement, dossier, commission)
    - API endpoints
    - **Quand tester** : Parcours métier critiques, formulaires avec logique de transformation
    - **Ne pas tester** : CRUD simples sans logique métier

### Ce qu'il NE FAUT PAS tester (éviter les tests inutiles)

❌ **Getters/Setters simples** sans logique métier\
❌ **Constructeurs** sans validation ou transformation\
❌ **Méthodes Doctrine générées** (`find()`, `findAll()`, `findBy()`)\
❌ **Configuration Symfony** (routes, services, DI) - déjà testée par le framework\
❌ **Code tiers** (bundles, bibliothèques) - déjà testés par leurs auteurs\
❌ **Propriétés d'entités** sans logique de validation\
❌ **Tests redondants** testant la même chose différemment

### Ce qu'il FAUT tester (valeur ajoutée)

✅ **Logique métier complexe** (calculs, algorithmes, règles métier)\
✅ **Validations personnalisées** (contraintes Symfony, validators)\
✅ **Requêtes SQL complexes** (DQL, QueryBuilder, jointures multiples)\
✅ **Transformations de données** (serializers, data transformers)\
✅ **Comportements conditionnels** (if/switch avec plusieurs branches)\
✅ **Intégrité des données** (contraintes base de données, cascades)\
✅ **Cas limites et erreurs** (valeurs nulles, chaînes vides, dépassements)\
✅ **Parcours métier critiques** (authentification, sauvegarde d'entité complexe, mise à jour conditionnelles)

### Structure des tests

```php
namespace App\Tests\Services;

use App\Service\CalculService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CalculServiceTest extends TestCase
{
    private CalculService $service;

    protected function setUp(): void
    {
        $this->service = new CalculService();
    }

    /**
     * Test avec un cas nominal clair
     */
    public function testCalculeMontantAvecTauxStandard(): void
    {
        $result = $this->service->calcule(100, 0.2);
        
        self::assertSame(120.0, $result);
    }

    /**
     * Test des cas limites
     */
    #[DataProvider('casLimitesProvider')]
    public function testGereCasLimites(float $montant, float $taux, float $expected): void
    {
        $result = $this->service->calcule($montant, $taux);
        
        self::assertSame($expected, $result);
    }

    public static function casLimitesProvider(): array
    {
        return [
            'montant zéro' => [0, 0.2, 0.0],
            'taux zéro' => [100, 0, 100.0],
            'valeurs négatives' => [-100, 0.2, -120.0],
        ];
    }
}
```

### Tests fonctionnels avec KernelTestCase

Pour tester des services avec dépendances (Doctrine, etc.) :

```php
namespace App\Tests\Repository;

use App\Tests\Factory\MarcheFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MarcheRepositoryTest extends KernelTestCase
{
    public function testTrouverMarchesActifsParAnnee(): void
    {
        // Arrange
        MarcheFactory::createOne(['actif' => true, 'annee' => 2025]);
        MarcheFactory::createOne(['actif' => false, 'annee' => 2025]);

        $repository = static::getContainer()->get(MarcheRepository::class);

        // Act
        $result = $repository->findMarchesActifsParAnnee(2025);

        // Assert
        self::assertCount(1, $result);
        self::assertTrue($result[0]->isActif());
    }
}
```

### Groupe de tests "functional"

Pour tests nécessitant ressources externes ou configuration spécifique :

```php
use PHPUnit\Framework\Attributes\Group;

#[Group('functional')]
class ExportPdfTest extends KernelTestCase
{
    // Tests nécessitant des librairies système (wkhtmltopdf, etc.)
}
```

Exécution :
```bash
castor symfony:test                         # Tous les tests
castor symfony:test --group functional           # Uniquement les tests locaux
castor symfony:test --exclude-group functional   # Exclure les tests locaux
castor symfony:test --filter NomDuTest      # Test spécifique
castor symfony:test --testdox                # Format lisible
```

### Bonnes pratiques

1. **Nommage explicite** : `testCalculeMontantAvecTauxNegatif()` plutôt que `testCalcul()`
3. **Un concept par test** : Ne teste qu'une seule chose à la fois
4. **DataProvider** : Mutualise les tests avec différentes données
6. **Isolation** : Chaque test doit pouvoir s'exécuter seul
7. **Assertions précises** : `assertSame()` plutôt que `assertEquals()` pour les types stricts
8. **Messages d'erreur** : Ajoute un message clair sur les assertions critiques

### Couverture de code

- **Objectif** : 70-80% pour le code métier critique
- **Ne pas viser 100%** : Perte de temps sur code trivial
- **Focus** : Services métier, repositories custom, validations

### Quand écrire les tests ?

1. **Nouveau code** : Écris les tests en même temps que le code (TDD si possible)
2. **Bug fix** : Écris un test qui reproduit le bug avant de corriger
3. **Refactoring** : Assure-toi d'avoir des tests avant de refactoriser
4. **Legacy** : Ajoute des tests progressivement sur le code modifié (pas de test sur code non touché)

### Détection de tests inutiles à supprimer

Supprime les tests qui :
- Ne testent que l'assignation de valeurs (`$obj->setX(5); assertSame(5, $obj->getX())`)
- Testent du code du framework Symfony/Doctrine
- Ont 100% de mock sans valeur métier
- Ne casseraient jamais en cas de régression
- Sont en double (testent exactement la même chose)

---

## Revue de code

Lors d'une revue de code, vérifie :

### Code
- ✅ Respect des standards PSR-12
- ✅ Utilisation de PHP 7.1 et Symfony 4.4 pour le code migré
- ✅ Typage strict (propriétés, paramètres, retours)
- ✅ Pas de code deprecated
- ✅ Documentation PHPDoc uniquement si nécessaire (types complexes)

### Tests (checklist qualité)
- ✅ **Pertinence** : Chaque test apporte une valeur ajoutée
- ✅ **Pas de tests inutiles** : Pas de tests sur getters/setters simples, code framework
- ✅ **Nommage explicite** : Le nom du test décrit clairement ce qui est testé
- ✅ **Isolation** : Test peut s'exécuter seul sans dépendre d'autres tests
- ✅ **Assertions précises** : `assertSame()` pour types stricts, messages d'erreur clairs
- ✅ **Cas limites testés** : Valeurs nulles, vides, négatives selon le contexte
- ✅ **Pas de logique complexe** : Tests simples et lisibles (pas de if/for dans tests)
- ✅ **DataProvider** : Utilisation quand plusieurs cas similaires
- ⚠️ **Éviter surcharge** : Pas de tests "au cas où" sans valeur métier

**Génère un rapport** dans `REVIEW_REPORT.md` à la racine du projet avec :
- Liste des fichiers analysés
- Points positifs
- Problèmes détectés et leur gravité
- Suggestions d'amélioration
- Métriques (si pertinent)

---

## 📝 Commits

**Format :** `<type>(<scope>): <description>`

**Exemples :**
```
feat(dossier): ajout nature 21 - Phase 3
fix(dossier): correction affichage date commission
docs(dossier): mise à jour REPRENDRE_ICI.md
```

---

**Version :** 3.0 optimisée | **Dernière màj :** 16 décembre 2025
