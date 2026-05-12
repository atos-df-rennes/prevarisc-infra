---
name: rapport-ecarts
description: Génère le rapport d'écarts obligatoire lors d'une migration Zend vers Symfony dans le projet Prevarisc. À utiliser après avoir porté du code legacy pour documenter les différences entre le code migré et le legacy.
---

# Skill : Rapport d'écarts — Migration Prevarisc

Ce skill guide la génération du **rapport d'écarts obligatoire** à produire après chaque migration de code Zend → Symfony.

## Quand utiliser ce skill

Utiliser ce skill systématiquement après avoir porté :
- Un controller Zend → Controller Symfony
- Une vue `.phtml` → template Twig
- Un modèle Zend → Entité Doctrine
- Un formulaire Zend → FormType Symfony
- Une action complète (controller + vue + form)

## Processus de génération du rapport

### Étape 1 : Analyser les fichiers legacy concernés

Pour chaque fichier migré, lire le fichier legacy correspondant et identifier :

1. **La logique de flux** : redirections, conditions d'accès
2. **Les appels de données** : requêtes SQL, modèles utilisés
3. **Le rendu** : variables passées à la vue, templates inclus
4. **Les effets de bord** : sessions, cookies, flash messages

### Étape 2 : Classer chaque différence

| Niveau | Critère | Exemple |
|--------|---------|---------|
| ✅ **Port direct** | Syntaxe changée, logique identique | `$this->view->x` → `render(..., ['x'])` |
| ⚠️ **Adaptation** | Glue Symfony nécessaire, comportement équivalent | Gestion CSRF ajoutée |
| 🔴 **Logique modifiée** | Réorganisation inévitable ou comportement incertain | Ordre d'exécution changé |

### Étape 3 : Produire le rapport

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RAPPORT D'ÉCARTS PAR RAPPORT AU LEGACY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Fichiers legacy analysés :
  - prevarisc/application/controllers/[NomController].php
  - prevarisc/application/views/scripts/[module]/[action].phtml
  - (autres si applicable)

Fichiers migrés :
  - prevarisc-migration/src/Controller/[NomController].php
  - prevarisc-migration/templates/[module]/[action].html.twig
  - (autres si applicable)

Niveau de confiance global : ✅ / ⚠️ / 🔴

✅ PORT DIRECT — aucune vérification nécessaire sur ces points :
- Routing : `/module/controller/action` → `@Route` Symfony (mécanique)
- Injection dépendances : `new Model()` → injection constructeur
- Variables de vue : `$this->view->x` → `render(..., ['x' => ...])`
- Boucles et conditions : portées sans modification logique
- [autres points mécaniques]

⚠️ ADAPTATIONS — vérifier ces points précis :
- [NomController.php:45] Gestion du paramètre `id` : `$this->_getParam('id')` → `$request->get('id')` (même comportement, type non typé) → Tester : ouvrir la page avec id valide, id invalide, sans id
- [NomController.php:78] Redirection : `$this->_redirect('/path')` → `redirectToRoute('route_name')` → Tester : vérifier que la redirection aboutit au même endroit
- [action.html.twig:23] Format de date : `date('d/m/Y', strtotime($var))` → `{{ date|date('d/m/Y') }}` → Tester : vérifier l'affichage avec une date valide et une date null

🔴 LOGIQUE COMPLEXE — vérification approfondie recommandée :
- (vide si aucun cas)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

## Règles pour remplir le rapport

### Ce qui mérite ✅ Port direct (par défaut pour les fonctions pure‑PHP)

**Run context :** `main` (par défaut).
- Tout ce qui est purement syntaxique (Zend API → Symfony API)
- Rendu de variables simples (string, int, array)
- Boucles `foreach` et conditions `if` portées tel quel
- Inclusion de partials portés mécaniquement

### Ce qui mérite ⚠️ Adaptation
- Gestion des paramètres de requête (`_getParam` → `$request->get`)
- Gestion des redirections
- Flash messages (`$this->_helper->flashMessenger` → `addFlash`)
- Gestion des sessions
- Formulaires (Zend_Form → AbstractType)
- Formatage de dates, nombres si comportement edge-case possible
- **Conversion Bootstrap 2 → 3** : toujours lister les classes converties dans le template (grid `span[N]`, icônes `icon-*`, boutons `btn-small/large`, alertes `alert-error`, etc.)
- **Structure des modales** si présentes (`modal hide fade` → structure BS3 avec `modal-dialog`/`modal-content`)
- **Formulaires BS2** si présents (`control-group`/`controls` → `form-group`)

### Ce qui mérite 🔴 Logique complexe
- Réorganisation de blocs conditionnels imbriqués
- Changement d'ordre d'exécution
- Logique métier extraite dans un service
- Requêtes SQL remaniées (même si résultat équivalent)
- Comportements avec état (transactions, locks)
- Toute transformation non mécanique du code

## Scénarios de test à lister pour ⚠️ et 🔴

Pour chaque point ⚠️ ou 🔴, toujours préciser l'action utilisateur exacte :
- "Ouvrir la page X avec les paramètres Y"
- "Soumettre le formulaire avec les valeurs Z"
- "Cliquer sur le bouton B depuis l'état A"
- "Vérifier l'état de la BDD avant/après action"
