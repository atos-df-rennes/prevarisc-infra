---
applyTo: "prevarisc-migration/tests/**,**/*Test.php"
---

# Stratégie de tests - Migration Prevarisc

**Philosophie** : Privilégie la **qualité** sur la quantité. Chaque test doit avoir une **valeur ajoutée** claire pour réduire la dette technique.

---

## Configuration

- Répertoire : `prevarisc-migration/tests/`
- Namespace : `App\Tests\`
- Framework : PHPUnit 7.5
- **Base de données** : Pas de base de données de test dédiée ni de fixtures (Foundry) pour l'instant
  - Tests unitaires : Sans base de données (mocks/stubs si nécessaire)
  - Tests d'intégration : Utilisation de la base de développement (lecture seule recommandée)
  - Tests fonctionnels : Marqués `@group functional` et exécutés en environnement contrôlé

---

## Pyramide de tests à respecter

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

---

## Ce qu'il NE FAUT PAS tester

❌ **Getters/Setters simples** sans logique métier\
❌ **Constructeurs** sans validation ou transformation\
❌ **Méthodes Doctrine générées** (`find()`, `findAll()`, `findBy()`)\
❌ **Configuration Symfony** (routes, services, DI) - déjà testée par le framework\
❌ **Code tiers** (bundles, bibliothèques) - déjà testés par leurs auteurs\
❌ **Propriétés d'entités** sans logique de validation\
❌ **Tests redondants** testant la même chose différemment

## Ce qu'il FAUT tester

✅ **Logique métier complexe** (calculs, algorithmes, règles métier)\
✅ **Validations personnalisées** (contraintes Symfony, validators)\
✅ **Requêtes SQL complexes** (DQL, QueryBuilder, jointures multiples)\
✅ **Transformations de données** (serializers, data transformers)\
✅ **Comportements conditionnels** (if/switch avec plusieurs branches)\
✅ **Intégrité des données** (contraintes base de données, cascades)\
✅ **Cas limites et erreurs** (valeurs nulles, chaînes vides, dépassements)\
✅ **Parcours métier critiques** (authentification, sauvegarde d'entité complexe, mise à jour conditionnelles)

---

## Structure des tests

### Test unitaire d'un service métier

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

    public function testValiderPrescriptionAvecTexteApplicable(): void
    {
        $result = $this->service->valider('Article R123-45', 'Type A');
        self::assertTrue($result);
    }

    /**
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
            'texte vide'  => ['', 'Type A', false],
            'type null'   => ['Article R123-45', null, false],
            'tous vides'  => ['', '', false],
        ];
    }
}
```

### Test d'intégration avec KernelTestCase

```php
namespace App\Tests\Repository;

use App\Entity\Dossier;
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

    public function testFindDossiersByTypeAvecJointures(): void
    {
        $result = $this->repository->findByTypeWithRelations(1);
        self::assertIsArray($result);
        foreach ($result as $dossier) {
            self::assertInstanceOf(Dossier::class, $dossier);
            self::assertNotNull($dossier->getType());
        }
    }
}
```

**Note** : Ce projet n'utilise pas de fixtures ni de base de données de test dédiée. Les tests d'intégration doivent :
- Utiliser des données existantes en lecture seule, ou
- Être marqués `@group functional` pour un environnement contrôlé

### Test de formulaire Symfony

```php
namespace App\Tests\Form\Type;

use App\Entity\Dossier;
use App\Form\Type\DossierType;
use Symfony\Component\Form\Test\TypeTestCase;

class DossierTypeTest extends TypeTestCase
{
    public function testSoumissionFormulaireValide(): void
    {
        $formData = ['objet' => 'Aménagement local', 'dateDepot' => '2025-01-15', 'type' => 1];
        $model = new Dossier();
        $form = $this->factory->create(DossierType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertSame('Aménagement local', $model->getObjet());
    }
}
```

### Groupe de tests "functional"

```php
/**
 * @group functional
 */
class InformationsServiceTest extends KernelTestCase
{
    // Tests nécessitant la base de données et les dépendances réelles
}
```

---

## Commandes d'exécution

```bash
castor symfony:test                              # Tous les tests
castor symfony:test --group functional           # Uniquement les tests fonctionnels
castor symfony:test --exclude-group functional   # Exclure les tests fonctionnels
castor symfony:test --filter NomDuTest           # Test spécifique
castor symfony:test --testdox                    # Format lisible
```

---

## Bonnes pratiques

1. **Nommage explicite** : `testValiderDossierAvecTypeErp()` plutôt que `testValider()`
2. **Un concept par test** : Ne teste qu'une seule chose à la fois
3. **DataProvider** : Mutualise les tests avec `@dataProvider` pour plusieurs jeux de données
4. **Isolation** : Chaque test doit pouvoir s'exécuter seul
5. **Assertions précises** : `assertSame()` pour les types stricts
6. **Type hints PHP 7.1** : DocBlocks obligatoires, pas d'attributs PHP 8
7. **Arrange-Act-Assert** : Structurer les tests en 3 phases distinctes

## Couverture de code

- **Objectif** : 70–80% pour le code métier critique
- **Ne pas viser 100%** : Perte de temps sur code trivial
- **Focus** : Services métier, repositories custom, validations

## Quand écrire les tests ?

1. **Nouveau code** : En même temps que le code (TDD si possible)
2. **Bug fix** : Reproduire le bug avec un test avant de corriger
3. **Refactoring** : Avoir des tests avant de refactoriser
4. **Legacy** : Ajouter progressivement sur le code modifié uniquement

## Détection de tests inutiles à supprimer

Supprimer les tests qui :
- Ne testent que l'assignation de valeurs (`setX(5); assertSame(5, getX())`)
- Testent du code du framework Symfony/Doctrine
- Ont 100% de mock sans valeur métier
- Ne casseraient jamais en cas de régression
- Sont en double (testent exactement la même chose)
