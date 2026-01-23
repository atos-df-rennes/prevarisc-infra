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
- **Base de données** : Pas de base de données de test dédiée ni de fixtures (Foundry) pour l'instant
  - Tests unitaires : Sans base de données (mocks/stubs si nécessaire)
  - Tests d'intégration : Utilisation de la base de développement (lecture seule recommandée)
  - Tests fonctionnels : Marqués `@group functional` et exécutés en environnement contrôlé

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

**Exemple 1 : Test unitaire d'un service métier**

```php
namespace App\Tests\Service;

use App\Service\Prescriptions;
use PHPUnit\Framework\TestCase;

class PrescriptionsTest extends TestCase
{
    /** @var Prescriptions */
    private $service;

    protected function setUp(): void
    {
        $this->service = new Prescriptions();
    }

    /**
     * Test avec un cas nominal clair
     */
    public function testValiderPrescriptionAvecTexteApplicable(): void
    {
        $result = $this->service->valider('Article R123-45', 'Type A');
        
        self::assertTrue($result);
    }

    /**
     * Test des cas limites avec DataProvider
     * @dataProvider casLimitesProvider
     */
    public function testGereCasLimites(?string $texte, ?string $type, bool $expected): void
    {
        $result = $this->service->valider($texte, $type);
        
        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function casLimitesProvider(): array
    {
        return [
            'texte vide' => ['', 'Type A', false],
            'type null' => ['Article R123-45', null, false],
            'tous vides' => ['', '', false],
        ];
    }
}
```

**Exemple 2 : Test unitaire avec logique conditionnelle**

```php
namespace App\Tests\Service;

use App\Service\Changement;
use PHPUnit\Framework\TestCase;

class ChangementTest extends TestCase
{
    /** @var Changement */
    private $service;

    protected function setUp(): void
    {
        $this->service = new Changement();
    }

    public function testDetecterChangementCategorie(): void
    {
        $ancienne = 'ERP de 1ère catégorie';
        $nouvelle = 'ERP de 2ème catégorie';
        
        $resultat = $this->service->detecterChangement($ancienne, $nouvelle);
        
        self::assertTrue($resultat['aChangement']);
        self::assertSame('Changement de catégorie', $resultat['type']);
    }
}
```

### Tests d'intégration avec KernelTestCase

Pour tester des services ou repositories avec dépendances (Doctrine, services injectés) :

```php
namespace App\Tests\Repository;

use App\Entity\Dossier;
use App\Entity\DossierType;
use App\Repository\DossierRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DossierRepositoryTest extends KernelTestCase
{
    /** @var DossierRepository */
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(DossierRepository::class);
    }

    /**
     * Teste une requête complexe avec jointures
     */
    public function testFindDossiersByTypeAvecJointures(): void
    {
        // Arrange
        $typeId = 1; // Type ERP existant dans la base de données de test
        
        // Act
        $result = $this->repository->findByTypeWithRelations($typeId);
        
        // Assert
        self::assertIsArray($result);
        foreach ($result as $dossier) {
            self::assertInstanceOf(Dossier::class, $dossier);
            self::assertNotNull($dossier->getType());
        }
    }

    /**
     * Teste le QueryBuilder custom avec critères multiples
     */
    public function testFindDossiersAvecCriteresMultiples(): void
    {
        $qb = $this->repository->createQueryBuilder('d')
            ->leftJoin('d.type', 't')
            ->where('t.id = :typeId')
            ->andWhere('d.dateDepot >= :dateDebut')
            ->setParameter('typeId', 1)
            ->setParameter('dateDebut', new \DateTime('2025-01-01'))
            ->setMaxResults(10);
        
        $result = $qb->getQuery()->getResult();
        
        self::assertLessThanOrEqual(10, count($result));
    }
}
```

**Note importante** : Ce projet n'utilise pas de fixtures ni de base de données de test dédiée pour l'instant. Les tests d'intégration doivent donc :
- Soit utiliser des données existantes dans la base de développement (avec précaution)
- Soit être marqués comme `@group functional` pour être exécutés uniquement en environnement contrôlé
- Être conçus pour ne pas modifier les données existantes (requêtes en lecture seule)

**Exemple : Test de formulaire Symfony**

```php
namespace App\Tests\Form\Type;

use App\Entity\Dossier;
use App\Form\Type\DossierType;
use Symfony\Component\Form\Test\TypeTestCase;

class DossierTypeTest extends TypeTestCase
{
    /**
     * Teste la soumission d'un formulaire avec données valides
     */
    public function testSoumissionFormulaireValide(): void
    {
        $formData = [
            'objet' => 'Aménagement local',
            'dateDepot' => '2025-01-15',
            'type' => 1,
        ];

        $model = new Dossier();
        $form = $this->factory->create(DossierType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertSame('Aménagement local', $model->getObjet());
    }

    /**
     * Teste la validation avec données invalides
     */
    public function testValidationDonneesInvalides(): void
    {
        $formData = [
            'objet' => '', // Champ obligatoire vide
            'dateDepot' => 'date-invalide',
        ];

        $form = $this->factory->create(DossierType::class);
        $form->submit($formData);

        self::assertFalse($form->isValid());
        self::assertCount(2, $form->getErrors(true));
    }
}
```

### Groupe de tests "functional"

Pour tests nécessitant ressources externes, base de données ou configuration spécifique :

```php
namespace App\Tests\Service;

use App\Service\Informations;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests nécessitant la base de données et les dépendances réelles
 * @group functional
 */
class InformationsServiceTest extends KernelTestCase
{
    /** @var Informations */
    private $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(Informations::class);
    }

    public function testRecupererInformationsEtablissement(): void
    {
        $etablissementId = 1; // Établissement existant en base
        
        $result = $this->service->getInformations($etablissementId);
        
        self::assertIsArray($result);
        self::assertArrayHasKey('libelle', $result);
    }
}
```

**Tests de génération de documents (PDF, exports)**

```php
namespace App\Tests\Export;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group functional
 */
class ExportPdfDossierTest extends KernelTestCase
{
    /**
     * Teste la génération d'un PDF de dossier
     * Nécessite wkhtmltopdf installé sur le système
     */
    public function testGenererPdfDossier(): void
    {
        // Test de génération PDF...
        self::markTestIncomplete('Nécessite wkhtmltopdf et configuration spécifique');
    }
}
```

Exécution :
```bash
castor symfony:test                              # Tous les tests
castor symfony:test --group functional           # Uniquement les tests fonctionnels
castor symfony:test --exclude-group functional   # Exclure les tests fonctionnels
castor symfony:test --filter NomDuTest           # Test spécifique
castor symfony:test --testdox                    # Format lisible
```

### Bonnes pratiques

1. **Nommage explicite** : `testValiderDossierAvecTypeErp()` plutôt que `testValider()`
2. **Un concept par test** : Ne teste qu'une seule chose à la fois
3. **DataProvider** : Mutualise les tests avec différentes données (annotation `@dataProvider`)
4. **Isolation** : Chaque test doit pouvoir s'exécuter seul, sans dépendre d'autres tests
5. **Assertions précises** : `assertSame()` plutôt que `assertEquals()` pour les types stricts
6. **Messages d'erreur** : Ajoute un message clair sur les assertions critiques
7. **Type hints PHP 7.1** : Utilise les DocBlocks pour les types (pas d'attributs PHP 8)
8. **Arrange-Act-Assert** : Structure tes tests clairement en 3 phases distinctes

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
