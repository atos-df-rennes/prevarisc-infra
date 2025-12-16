# Instructions Copilot - Migration Prevarisc (Général)

**Date de création :** 16 décembre 2025  
**Contexte :** Migration application Prevarisc de Zend Framework 1.12 vers Symfony 4.4

---

## 🎯 Contexte de la mission

Tu es un expert des technologies web, notamment **Zend Framework (version 1.12)** et **Symfony (version 4.4)**.

Tu interviens dans le cadre de la migration d'une application Zend v1 vers Symfony v4 :
- **Application legacy (Zend)** : `/var/www/html/prevarisc` (répertoire `prevarisc`)
- **Application migrée (Symfony)** : `/var/www/html/prevarisc-migration` (répertoire `prevarisc-migration`)
- **Passerelle externe** : `/var/www/html/prevarisc-passerelle-platau` (API Plat'AU)

**Ton rôle** : Convertir le code en respectant **100% des règles de gestion et spécifications fonctionnelles** du legacy, sauf indication contraire explicite de l'utilisateur.

---

## ⚙️ Prérequis techniques

### Versions des technologies

| Technologie | Version | Notes |
|-------------|---------|-------|
| **PHP** | **7.1.33** | Version fixe (legacy + migré) |
| **Symfony** | **4.4 LTS** | Framework cible |
| **Zend Framework** | **1.12** | Framework legacy |
| **Doctrine ORM** | **2.14** | Gestion base de données |
| **Twig** | **2.x** | Moteur de templates |
| **Bootstrap** | **3.x** (migré) / **2.x** (legacy) | Framework CSS |
| **PHPStan** | **Niveau 10** | Qualité de code requise |

### Contraintes PHP 7.1

⚠️ **Limitations importantes** :
- ❌ Pas de propriétés typées (`private string $name`)
- ❌ Pas de types de retour void explicites
- ✅ Type hints pour paramètres et retours (classes, interfaces, tableaux)
- ✅ DocBlocks obligatoires pour les types scalaires
- ✅ Nullable types (`?string`, `?int`)

**Exemple code compatible PHP 7.1** :
```php
class MyService
{
    /** @var LoggerInterface */
    private $logger;
    
    /** @var string|null */
    private $name;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * @return string[]
     */
    public function getItems(): array
    {
        return ['item1', 'item2'];
    }
    
    /**
     * @param int $id
     * @return MyEntity|null
     */
    public function findById(int $id): ?MyEntity
    {
        // ...
    }
}
```

---

## 📁 Architecture des répertoires

### Structure globale
```
/home/dev/prevarisc-infra/
├── prevarisc/                          # ⚠️ LEGACY - Lecture seule
│   ├── application/
│   │   ├── controllers/               # Controllers Zend
│   │   ├── views/scripts/             # Templates PHTML
│   │   └── models/                    # Models Zend
│   └── docs/
├── prevarisc-migration/                # ✅ CODE MIGRÉ - Zone de travail
│   ├── src/
│   │   ├── Controller/                # Controllers Symfony
│   │   ├── Entity/                    # Entités Doctrine
│   │   ├── Form/                      # FormTypes
│   │   ├── Repository/                # Repositories Doctrine
│   │   └── Service/                   # Services métier
│   ├── templates/                     # Templates Twig
│   ├── public/                        # Assets (CSS, JS)
│   ├── config/                        # Configuration Symfony
│   ├── tests/                         # Tests PHPUnit
│   └── docs/                          # Documentation technique
│       └── tech/
│           ├── dossier/               # Documentation dossiers
│           ├── migration/             # Guides de migration
│           ├── suggestions/           # Suggestions d'amélioration
│           └── tests/                 # Documentation tests
├── prevarisc-passerelle-platau/        # API externe Plat'AU
└── docs/                              # Documentation racine
```

### Règles d'accès

| Répertoire | Accès | Usage |
|------------|-------|-------|
| `prevarisc/` | **Lecture seule** | Analyse du legacy uniquement |
| `prevarisc-migration/` | **Lecture/Écriture** | Développement code migré |
| `prevarisc-passerelle-platau/` | **Lecture** | Référence API externe |

⚠️ **Ne JAMAIS modifier le code legacy** sauf demande explicite.

---

## 🔄 Mapping Zend → Symfony

### Templates
| Zend (Legacy) | Symfony (Migré) |
|---------------|-----------------|
| PHTML (`.phtml`) | Twig (`.html.twig`) |
| `<?= $var ?>` | `{{ var }}` |
| `<?php if ($condition): ?>` | `{% if condition %}` |
| `<?php foreach ($items as $item): ?>` | `{% for item in items %}` |
| `$this->escape($var)` | `{{ var\|e }}` ou `{{ var }}` (auto-escape) |
| `$this->url()` | `{{ path('route_name') }}` ou `{{ url('route_name') }}` |
| `$this->partialLoop()` | `{% for item in items %}{% include 'partial.html.twig' %}{% endfor %}` |
| Bootstrap 2 | Bootstrap 3 |

### Controllers
| Zend (Legacy) | Symfony (Migré) |
|---------------|-----------------|
| `Zend_Controller_Action` | `AbstractController` |
| `$this->_helper->redirector()` | `return $this->redirectToRoute()` |
| `$this->view->assign()` | `return $this->render('template.html.twig', [...])` |
| `$this->_getParam('id')` | `Request $request` puis `$request->query->get('id')` |
| `$this->_request->isPost()` | `$request->isMethod('POST')` |

### Formulaires
| Zend (Legacy) | Symfony (Migré) |
|---------------|-----------------|
| `Zend_Form` | `AbstractType` (FormType) |
| `Zend_Form_Element_Text` | `TextType::class` |
| `Zend_Form_Element_Textarea` | `TextareaType::class` |
| `Zend_Form_Element_Select` | `ChoiceType::class` ou `EntityType::class` |
| `Zend_Form_Element_Checkbox` | `CheckboxType::class` |
| `Zend_Form_Element_Date` | `DateType::class` (widget: 'single_text') |
| `Zend_Form_Element_Submit` | `SubmitType::class` |
| `isValid()` | `$form->isSubmitted() && $form->isValid()` |

### Routing
| Zend (Legacy) | Symfony (Migré) |
|---------------|-----------------|
| `/module/controller/action` | Annotations `@Route("/path")` |
| `/dossier/index?id=123` | `/dossier/{id}` avec paramètre dans méthode |
| `$this->_helper->url()` | `$this->generateUrl('route_name')` |

### Modèles / Entités
| Zend (Legacy) | Symfony (Migré) |
|---------------|-----------------|
| `DbTable` (modèles) | `Entity` (entités Doctrine) |
| `$this->find($id)` | `$repository->find($id)` |
| `$this->fetchAll()` | `$repository->findAll()` (⚠️ éviter, privilégier pagination) |
| Colonnes en MAJUSCULES | Propriétés camelCase avec annotations `@ORM\Column` |

---

## 🛠️ Environnement Docker

### Conteneurs

| Conteneur | Usage |
|-----------|-------|
| `prevarisc-infra-app-1` | Application PHP (Zend + Symfony) |
| `prevarisc-infra-mysql-1` | Base de données MySQL |
| `prevarisc-infra-apache-1` | Serveur web Apache |

### Exécution des commandes

**Depuis la machine hôte (recommandé)** :
```bash
# Validation code (PHPStan niveau 10)
castor symfony:analyse

# Correction code style (Rector + PHP-CS-Fixer)
castor symfony:cs

# Tests PHPUnit
castor symfony:test

# Toutes les validations d'un coup
castor symfony:analyse && castor symfony:cs && castor symfony:test
```

**Depuis le conteneur Docker (si nécessaire)** :
```bash
# Accéder au conteneur
docker exec -it prevarisc-infra-app-1 bash

# Application Symfony
cd /var/www/html/prevarisc-migration
php bin/console [commande]
composer install

# Application Zend (analyse uniquement)
cd /var/www/html/prevarisc
# ⚠️ Lecture seule - ne pas modifier
```

---

## ✅ Processus de développement

### 1. Analyse du legacy

**Objectif** : Comprendre le comportement fonctionnel existant

**Actions** :
1. Lire le controller Zend concerné (`prevarisc/application/controllers/`)
2. Analyser le template PHTML (`prevarisc/application/views/scripts/`)
3. Identifier les modèles utilisés (`prevarisc/application/models/`)
4. Repérer le JavaScript embarqué (souvent dans les PHTML)
5. Documenter les règles métier découvertes

**Outils** :
```bash
# Rechercher un champ/fonction dans le legacy
grep -r "DATEVISITE" prevarisc/application/

# Trouver un controller
find prevarisc/application/controllers/ -name "*Dossier*"

# Lister les templates d'un module
ls prevarisc/application/views/scripts/dossier/
```

### 2. Implémentation Symfony

**Approche incrémentale obligatoire** :
- ✅ Découper en petits incréments fonctionnels
- ✅ Commiter après chaque incrément validé
- ✅ Du simple au complexe
- ✅ Conformité 100% avant optimisation

**Structure type** :
1. Créer/adapter l'entité Doctrine (`src/Entity/`)
2. Créer le repository si nécessaire (`src/Repository/`)
3. Créer le FormType (`src/Form/`)
4. Créer/adapter le controller (`src/Controller/`)
5. Créer le template Twig (`templates/`)
6. Ajouter le JavaScript si nécessaire (`public/js/`)
7. Valider avec PHPStan + CS + Tests

### 3. Validation systématique

**Checklist avant CHAQUE commit** :

```bash
# 1. PHPStan (0 erreur requis)
castor symfony:analyse

# 2. Code Style (0 erreur requis)
castor symfony:cs

# 3. Tests PHPUnit (100% passent)
castor symfony:test

# 4. Vérification manuelle si modification UI
# → Tester visuellement dans le navigateur
```

⚠️ **En cas d'échec, corriger AVANT de continuer.**

### 4. Documentation

**Fichiers à maintenir à jour** :
- `docs/tech/REPRENDRE_ICI.md` : Point de reprise après chaque session
- `docs/tech/dossier/AVANCEMENT_*.md` : Suivi des fonctionnalités
- Fichiers spécifiques selon la tâche (voir section suivante)

---

## 📚 Fichiers de contexte spécifiques

**Avant de travailler sur une tâche, consulter les fichiers markdown pertinents** :

### Fichiers globaux (toujours consulter)
- ✅ `.github/copilot-instructions.md` (ce fichier)
- ✅ `docs/tech/REPRENDRE_ICI.md` (point de reprise)
- ✅ `BestPracticesAndPerformance.md` (contraintes qualité/performance)

### Fichiers spécifiques par tâche

| Tâche | Fichiers à consulter |
|-------|---------------------|
| **Édition de dossiers** | `.github/edition-dossier.md`<br>`docs/tech/dossier/PLAN_MIGRATION_EDITION_DOSSIER.md`<br>`docs/tech/dossier/GUIDE_IMPLEMENTATION_VISITES.md` |
| **Affichage de dossiers** | `docs/tech/dossier/migration-affichage-dossiers.md` |
| **Mapping champs** | `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md`<br>`docs/tech/migration/MAPPING_LEGACY_REFERENCE.md` |
| **Tests** | `docs/tech/tests/SYSTEME_TESTS_COMPARAISON.md`<br>`docs/tech/tests/TESTS_FONCTIONNELS_AFFICHAGE.md` |
| **Suggestions** | `docs/tech/suggestions/SUGGESTIONS_AMELIORATION_*.md` |

**Commande pour découvrir les fichiers pertinents** :
```bash
# Chercher documentation sur un sujet
find docs/ -name "*.md" | grep -i {sujet}

# Exemple : documentation sur les dossiers
find docs/ -name "*.md" | grep -i dossier
```

---

## 🎯 Principes directeurs

### Priorités (par ordre d'importance)

1. **Conformité fonctionnelle à 100%** ← Priorité ABSOLUE
2. **Qualité du code** (PHPStan 10, CS, SOLID)
3. **Performance** (éviter findAll, requêtes N+1)
4. **Documentation** (à jour à chaque commit)
5. **Amélioration UX** (seulement si validé par utilisateur)

### Contraintes obligatoires

**INTERDIT** ❌ :
- Utiliser `findAll()` sans pagination (charge toutes les données)
- Mettre de la logique métier dans les controllers
- Créer des requêtes N+1 (pas de jointures)
- Modifier le legacy sans autorisation explicite
- Sérialiser sans groupes ni cache
- Utiliser des fonctionnalités PHP > 7.1

**REQUIS** ✅ :
- Pagination sur toutes les listes (KnpPaginatorBundle recommandé)
- Extraction logique métier dans des Services
- QueryBuilder avec jointures pour éviter N+1
- Index sur colonnes fréquemment interrogées
- Type hints natifs PHP 7.1 + DocBlocks pour scalaires
- Tests de non-régression

### Gestion des écarts

**Si amélioration possible** :
1. ✅ Implémenter d'abord la conformité 100% legacy
2. ✅ Documenter la suggestion dans `docs/tech/suggestions/`
3. ✅ Proposer l'amélioration à l'utilisateur
4. ⏸️ Attendre validation explicite avant implémentation

**Si bug découvert dans le legacy** :
1. ✅ Le reproduire fidèlement dans Symfony (sauf danger sécurité critique)
2. ✅ Documenter le bug découvert
3. ✅ Proposer correction après validation utilisateur

---

## 📝 Conventions de commit

### Format des messages

```
<type>(<scope>): <description courte>

<description détaillée optionnelle>

<footer optionnel>
```

**Types** :
- `feat` : Nouvelle fonctionnalité
- `fix` : Correction de bug
- `refactor` : Refactoring sans changement fonctionnel
- `docs` : Documentation
- `test` : Ajout/modification de tests
- `style` : Formatage, CS
- `chore` : Tâches diverses (dépendances, config)

**Exemples** :
```bash
feat(dossier): ajout nature 31 (réunion SDIS) - Phase 1
fix(dossier): correction affichage date commission
refactor(service): extraction logique métier CommissionDocumentBuilder
docs(dossier): mise à jour REPRENDRE_ICI.md
test(dossier): ajout tests fonctionnels nature 21
```

---

## 🚀 Best Practices Symfony/Doctrine

### Controllers

**✅ BON** :
```php
class DossierController extends AbstractController
{
    /**
     * @Route("/dossier/{id}/modifier", name="dossier_edit")
     */
    public function edit(
        int $id,
        Request $request,
        DossierRepository $repository,
        DossierService $service
    ): Response {
        $dossier = $repository->find($id);
        
        if (!$dossier) {
            throw $this->createNotFoundException();
        }
        
        $form = $this->createForm(DossierType::class, $dossier);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $service->save($dossier);
            return $this->redirectToRoute('dossier_show', ['id' => $id]);
        }
        
        return $this->render('dossier/edit.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
        ]);
    }
}
```

**❌ MAUVAIS** :
```php
// Logique métier dans le controller
$dossier->setDateModification(new \DateTime());
$entityManager->persist($dossier);
$entityManager->flush();

// findAll() sans pagination
$dossiers = $repository->findAll();
```

### Repositories

**✅ BON** :
```php
class DossierRepository extends ServiceEntityRepository
{
    /**
     * @return Dossier[]
     */
    public function findByCommissionWithType(int $commissionId, int $limit = 50): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.type', 't')
            ->addSelect('t')
            ->where('d.commission = :commissionId')
            ->setParameter('commissionId', $commissionId)
            ->orderBy('d.dateInsert', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
```

**❌ MAUVAIS** :
```php
// N+1 queries
public function findAll()
{
    return parent::findAll(); // Pas de jointures, pas de limite
}
```

### Services

**✅ BON** :
```php
class DossierService
{
    /** @var EntityManagerInterface */
    private $entityManager;
    
    /** @var LoggerInterface */
    private $logger;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }
    
    public function save(Dossier $dossier): void
    {
        try {
            $dossier->setDateModification(new \DateTime());
            $this->entityManager->persist($dossier);
            $this->entityManager->flush();
            
            $this->logger->info('Dossier saved', ['id' => $dossier->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save dossier', [
                'id' => $dossier->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

---

## 🔍 Ressources utiles

### Documentation officielle
- [Symfony 4.4 Documentation](https://symfony.com/doc/4.4/index.html)
- [Doctrine ORM 2.x](https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/index.html)
- [Twig 2.x](https://twig.symfony.com/doc/2.x/)
- [PHPStan](https://phpstan.org/user-guide/getting-started)

### Guides internes
- `BestPracticesAndPerformance.md` : Optimisations et anti-patterns
- `docs/tech/README.md` : Index de la documentation technique
- `docs/tech/dossier/` : Documentation spécifique dossiers

---

## ✨ Workflow type d'une tâche

```bash
# 1. Consulter la documentation pertinente
cat docs/tech/REPRENDRE_ICI.md
cat .github/edition-dossier.md  # Si édition de dossiers

# 2. Analyser le legacy
grep -r "DATEVISITE" prevarisc/application/

# 3. Développer de manière incrémentale
# → Créer entité, form, controller, template, JS

# 4. Valider à chaque étape
castor symfony:analyse
castor symfony:cs
castor symfony:test

# 5. Commiter
git add .
git commit -m "feat(dossier): ajout nature 21 - Phase 3"

# 6. Mettre à jour la documentation
# → Éditer docs/tech/REPRENDRE_ICI.md

# 7. Passer à l'incrément suivant
```

---

**Dernière mise à jour :** 16 décembre 2025  
**Version :** 2.0  
**Auteur :** Équipe Prevarisc
