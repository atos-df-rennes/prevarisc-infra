---
name: analyse-legacy
description: Guide pour analyser le code Zend legacy du projet Prevarisc (dépôt prevarisc/, référence figée). À utiliser pour comprendre le comportement historique d'un controller, d'une vue ou d'un modèle Zend et déterminer si un bug constaté dans prevarisc-migration/ est une régression de migration ou un défaut déjà présent dans le legacy.
---

# Skill : Analyse du code legacy Zend — Prevarisc

Prevarisc est entièrement migré : `prevarisc-migration/` (Symfony 7.4, PHP 8.5) est l'application utilisée en production. Le dépôt `prevarisc/` (Zend 1.12) n'est conservé que comme référence figée, pour comparer un comportement Symfony actuel au comportement legacy d'origine — typiquement lors du diagnostic d'un bug, afin de distinguer une régression introduite par la migration d'un défaut déjà présent avant elle.

Ce skill guide cette analyse comparative du code Zend dans `prevarisc/`.

## Règle fondamentale

Le répertoire `prevarisc/` est **en lecture seule**. Ne jamais modifier ces fichiers.

**Run context :** `main` (par défaut). Ce skill est d'analyse uniquement et ne modifie pas le dépôt. Pour exécuter des actions dans un worktree, préciser `run_context: worktree` (opt‑in).

## Structure du legacy

```
prevarisc/application/
├── controllers/        # Controllers Zend (NomController.php)
├── models/
│   ├── DbTable/        # Modèles Zend_Db_Table (accès BDD)
│   └── *.php           # Modèles métier
├── views/
│   └── scripts/        # Templates .phtml (module/action.phtml)
├── forms/              # Formulaires Zend_Form
├── modules/            # Modules applicatifs
└── Bootstrap.php       # Bootstrap Zend
```

## Processus d'analyse comparative

### 1. Identifier les fichiers à migrer

```bash
# Trouver un controller
find prevarisc/application/controllers/ -name "*Dossier*"
find prevarisc/application/modules/ -name "*Dossier*"

# Trouver les vues associées
find prevarisc/application/views/scripts/ -type d -name "dossier"

# Trouver les modèles associés
find prevarisc/application/models/ -name "*Dossier*"
find prevarisc/application/models/DbTable/ -name "*Dossier*"

# Trouver les formulaires
find prevarisc/application/forms/ -name "*Dossier*"
```

### 2. Analyser un controller Zend

Pour chaque action du controller, identifier :

| Élément | Code Zend | Équivalent Symfony |
|---------|-----------|-------------------|
| Paramètres | `$this->_getParam('id')` | `$request->get('id')` |
| Accès modèle | `$this->_model` ou `new Application_Model_X()` | Repository injecté |
| Passage à la vue | `$this->view->x = $value` | `return $this->render(..., ['x' => $value])` |
| Redirection | `$this->_redirect('/path')` | `return $this->redirectToRoute('name')` |
| Flash messages | `$this->_helper->flashMessenger->addMessage(...)` | `$this->addFlash('type', 'message')` |
| Auth/ACL | `$this->_helper->acl` | `$this->denyAccessUnlessGranted(...)` |
| Session | `$session = new Zend_Session_Namespace('x')` | `$session->get('x')` via RequestStack |
| JSON response | `echo json_encode(...)` | `return new JsonResponse(...)` |

### 3. Analyser une vue .phtml

Pour chaque template, identifier :

| Élément | Code Zend | Équivalent Twig |
|---------|-----------|----------------|
| Affichage variable | `<?= $this->var ?>` | `{{ var }}` |
| Condition | `<?php if ($cond): ?>` | `{% if cond %}` |
| Boucle | `<?php foreach ($items as $item): ?>` | `{% for item in items %}` |
| Escape | `<?= $this->escape($var) ?>` | `{{ var }}` (auto) |
| URL | `$this->url(...)` | `{{ path('route', params) }}` |
| Partial | `$this->partial('_partial.phtml', ...)` | `{{ include('_partial.html.twig', vars) }}` |
| Layout placeholder | `$this->placeholder('sidebar')` | `{% block sidebar %}` |
| Traduction | `$this->translate('key')` | `{{ 'key'|trans }}` |
| Date format | `date('d/m/Y', strtotime($var))` | `{{ var|date('d/m/Y') }}` |
| Truncate | `mb_substr($str, 0, 100)` | `{{ str|slice(0, 100) }}` |

### 3b. Détecter les patterns Bootstrap 2 dans les templates

**Étape obligatoire** : avant de migrer un template, lancer ce grep pour inventorier tous les patterns BS2 à convertir en BS3 :

```bash
grep -n "span[0-9]\|row-fluid\|icon-[a-z]\|btn-small\|btn-large\|btn-mini\|btn-inverse\|alert-error\|alert-block\|control-group\|controls\b\|input-prepend\|input-append\|modal hide\|tabs-left\|tabs-right\|tabbable\|navbar-inner\|offset[0-9]\|hero-unit" \
  prevarisc/application/views/scripts/[module]/[action].phtml
```

Construire un inventaire des patterns trouvés avant de coder le template Twig. Chaque pattern doit être converti selon le mapping du fichier agent `symfony-migration.agent.md`.

**Points d'attention critiques lors de l'analyse :**

| Pattern BS2 détecté | Impact sur la migration |
|---------------------|------------------------|
| `class="modal hide fade"` | Structure modale complètement différente en BS3 (2 wrappers à ajouter) |
| `tabs-left` / `tabs-right` | Pas d'équivalent BS3 natif → CSS custom nécessaire |
| `input-prepend` / `input-append` | Refactoring complet en `input-group` |
| `control-group` + `controls` | Remplacer par `form-group` + supprimer le div `controls` |

### 4. Analyser un modèle Zend_Db_Table

Pour chaque méthode de modèle, identifier :

```php
// LEGACY — Zend_Db_Table
class Application_Model_DbTable_Dossier extends Zend_Db_Table_Abstract {
    protected $_name = 'dossier';           // → @ORM\Table(name="dossier")
    protected $_primary = 'ID_DOSSIER';     // → @ORM\Id

    public function fetchAll() { ... }      // → DossierRepository::findAll()
    public function find($id) { ... }       // → DossierRepository::find($id)

    // Requête custom
    public function fetchByEtablissement($idEtab) {
        $db = $this->getAdapter();
        $select = $db->select()
            ->from('dossier')
            ->where('ID_ETABLISSEMENT = ?', $idEtab)
            ->order('DATE_DEPOT DESC');
        return $db->fetchAll($select);
    }
}
```

→ Porter comme : `DossierRepository::findByEtablissement(int $idEtab): array`

### 5. Analyser un formulaire Zend_Form

```php
// LEGACY
class Application_Form_Dossier extends Zend_Form {
    public function init() {
        $objet = new Zend_Form_Element_Text('objet');
        $objet->setLabel('Objet')->setRequired(true);
        $this->addElement($objet);
    }
}
```

→ Porter comme : `DossierType extends AbstractType`

### 6. Questions à se poser pour situer le comportement legacy

- [ ] Quelles sont les routes de cette fonctionnalité ?
- [ ] Quels modèles/tables sont utilisés ?
- [ ] Y a-t-il des dépendances sur d'autres controllers/models ?
- [ ] Y a-t-il des ACL/permissions spécifiques ?
- [ ] Y a-t-il des sessions/cookies utilisés ?
- [ ] Y a-t-il des formulaires avec upload de fichiers ?
- [ ] Y a-t-il des appels AJAX/JSON ?
- [ ] Y a-t-il des transactions base de données ?
- [ ] Y a-t-il du code JavaScript associé dans les vues ?

### 7. Cartographier les dépendances

```bash
# Trouver tous les modèles utilisés par un controller
grep -r "Application_Model" prevarisc/application/controllers/NomController.php

# Trouver les vues incluses
grep -r "partial\|render\|renderScript" prevarisc/application/views/scripts/nom/

# Trouver les helpers utilisés
grep -r "_helper->" prevarisc/application/controllers/NomController.php
```

## Checklist avant de conclure l'analyse

1. ✅ Tous les fichiers legacy concernés lus et compris
2. ✅ Dépendances cartographiées (modèles, vues partielles, helpers)
3. ✅ Requêtes SQL identifiées et analysées
4. ✅ Logique métier non triviale identifiée (→ niveau 🔴 dans le rapport d'écarts)
5. ✅ Comportement legacy comparé au comportement Symfony actuel constaté
6. ✅ Conclusion posée : régression de migration vs défaut déjà présent dans le legacy
7. ✅ **Patterns Bootstrap 2 inventoriés** si le sujet concerne un template `.phtml` (grep — cf. étape 3b)
