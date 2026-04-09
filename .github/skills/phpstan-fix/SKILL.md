---
name: phpstan-fix
description: Guide pour corriger les erreurs PHPStan niveau 10 dans le projet Prevarisc (PHP 7.1, Symfony 4.4, Doctrine 2.14). À utiliser pour comprendre et corriger les erreurs PHPStan sans changer la logique métier.
---

# Skill : Correction PHPStan niveau 10 — Prevarisc

Ce skill guide la correction des erreurs PHPStan niveau 10 pour PHP 7.1 dans le projet Prevarisc.

## Lancer l'analyse

```bash
castor symfony:analyse
```

## Erreurs courantes et corrections

### 1. Propriété sans type déclaré

```
# Erreur : Property X::$y has no type hint specified.

// 🚫 Avant
class DossierController extends AbstractController {
    private $dossierRepository;
}

// ✅ Après — DocBlock obligatoire (pas de propriété typée en PHP 7.1)
class DossierController extends AbstractController {
    /** @var DossierRepository */
    private $dossierRepository;
}
```

### 2. Méthode sans type de retour

```
# Erreur : Method X::y() has no return type specified.

// 🚫 Avant
public function getItems() {
    return $this->items;
}

// ✅ Après
/** @return Item[] */
public function getItems(): array {
    return $this->items;
}

// ✅ Pour un type mixte ou complexe
/** @return array<string, mixed> */
public function getData(): array { ... }

// ✅ Pour une valeur nullable
/** @return Dossier|null */
public function findDossier(int $id): ?Dossier { ... }
```

### 3. Paramètre sans type

```
# Erreur : Parameter $x of method Y::z() has no type hint specified.

// 🚫 Avant
public function process($id, $data) { ... }

// ✅ Après
/** @param array<string, mixed> $data */
public function process(int $id, array $data): void { ... }
```

### 4. Type nullable non géré

```
# Erreur : Cannot call method x() on TYPE|null.

// 🚫 Avant
$dossier = $this->dossierRepository->find($id);
$dossier->getNom(); // Erreur si null

// ✅ Après
$dossier = $this->dossierRepository->find($id);
if (null === $dossier) {
    throw $this->createNotFoundException('Dossier introuvable.');
}
$dossier->getNom();
```

### 5. Accès à propriété/méthode inexistante sur un type union

```
# Erreur : Call to an undefined method object|false::getResult().

// ✅ Assertion de type explicite
/** @var MyType $result */
$result = $queryResult;
$result->getResult();
```

### 6. Array sans type dans DocBlock

```
# Erreur : Parameter $items type has no value type specified in iterable type array.

// 🚫 Avant
/** @param array $items */
public function processItems(array $items): void { ... }

// ✅ Après — préciser le type des éléments
/** @param Dossier[] $items */
public function processItems(array $items): void { ... }

/** @param array<int, string> $items */
public function processTitles(array $items): void { ... }

/** @param array<string, mixed> $data */
public function processData(array $data): void { ... }
```

### 7. Doctrine — Type de retour des repositories

```php
// ✅ Annotations correctes pour les méthodes de repository
class DossierRepository extends ServiceEntityRepository {

    /** @return Dossier[] */
    public function findAll(): array {
        return parent::findAll();
    }

    /** @return Dossier|null */
    public function findOneByReference(string $ref): ?Dossier {
        return $this->findOneBy(['reference' => $ref]);
    }

    /**
     * @return Dossier[]
     */
    public function findByEtablissement(int $id): array {
        return $this->createQueryBuilder('d')
            ->where('d.etablissement = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
}
```

### 8. Entities Doctrine — Champs nullable

```php
// ✅ Champ nullable dans l'entité
/**
 * @ORM\Column(type="string", nullable=true)
 * @var string|null
 */
private $commentaire;

/** @return string|null */
public function getCommentaire(): ?string {
    return $this->commentaire;
}

/** @param string|null $commentaire */
public function setCommentaire(?string $commentaire): void {
    $this->commentaire = $commentaire;
}
```

### 9. Symfony — AbstractController

```php
// ✅ Type de retour sur les actions de controller
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/** @Route("/dossier/{id}", name="dossier_voir") */
public function voir(int $id): Response {
    return $this->render('dossier/voir.html.twig', ['id' => $id]);
}

public function redirect(): RedirectResponse {
    return $this->redirectToRoute('dossier_index');
}

public function api(): JsonResponse {
    return new JsonResponse(['status' => 'ok']);
}
```

### 10. Variables potentiellement undefined

```
# Erreur : Variable $x might not be defined.

// 🚫 Avant
if ($condition) {
    $result = fetch();
}
echo $result; // Undefined si !$condition

// ✅ Après
$result = null;
if ($condition) {
    $result = fetch();
}
echo $result;
```

## Processus de correction

1. Lancer `castor symfony:analyse` et noter toutes les erreurs
2. Regrouper les erreurs par fichier
3. Corriger dans l'ordre : entités → repositories → services → controllers
4. **Ne jamais modifier la logique métier** pour corriger PHPStan
5. Utiliser uniquement des DocBlocks et annotations (pas de refactoring)
6. Relancer `castor symfony:analyse` pour vérifier : 0 erreur attendu

## Annotations PHPDoc utiles pour PHPStan

```php
/** @var int */
/** @var string|null */
/** @var array<string, mixed> */
/** @var Dossier[] */
/** @return void */
/** @param mixed $value */
/** @throws \RuntimeException */
/** @psalm-suppress MissingReturnType */ // En dernier recours
```
