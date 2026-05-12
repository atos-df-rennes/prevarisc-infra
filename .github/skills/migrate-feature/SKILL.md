---
name: migrate-feature
description: "Orchestre le pipeline complet de migration d'une fonctionnalité Zend vers Symfony dans le projet Prevarisc. À utiliser pour démarrer une migration de zéro : analyse fonctionnelle → exploration legacy → port direct → optimisations légères → lint → revue. Guide étape par étape avec les outils à invoquer à chaque phase."
---

# Skill : Pipeline de migration — Prevarisc

Ce skill orchestre les 6 phases d'une migration de fonctionnalité complète, en indiquant quel outil invoquer à chaque étape.

## Vue d'ensemble du pipeline

```
Phase 0 : ANALYSE FONCTIONNELLE → Documentation specs (docs/specs/MOD-XX.md)
Phase 1 : ANALYSE TECHNIQUE     → Skill analyse-legacy
Phase 2 : PORT DIRECT (pure‑PHP) → Agent symfony-migration
Phase 2b: ADAPTATION (Zend‑dependent) → Agent symfony-migration (refactor léger autorisé si comportement inchangé)
Phase 3 : OPTIMISATIONS         → Inline (suggestions légères)
Phase 4 : LINT                  → castor symfony:analyse + cs + test
Phase 5 : REVUE                 → Agent code-review (Opus)
```

---

## Phase 0 — Analyse fonctionnelle

**Outil :** Lecture du legacy + documentation dans `docs/specs/`

Avant toute analyse technique, documenter **ce que fait la fonctionnalité** du point de vue utilisateur. Cette phase permet de :

- Établir un **contrat fonctionnel** avant d'écrire du code (évite les régressions silencieuses)
- Fournir une **base pour le rapport d'écarts** (on sait exactement quoi comparer)
- Identifier les **cas limites et règles métier** qui deviendront des cas de test
- Conserver une **trace des décisions** lorsque le comportement legacy est discutable

**Vérifier d'abord si la documentation existe déjà :**

```bash
# Trouver le fichier de specs du module concerné
ls prevarisc-migration/docs/specs/
cat prevarisc-migration/docs/specs/MOD-XX-NOM.md
```

**Si la section correspondante existe** (ex : section 4.5 pour l'ordre du jour dans MOD-03) :
→ La lire attentivement, elle constitue le contrat fonctionnel. Passer à la Phase 1.

**Si la documentation est absente ou incomplète** :
→ L'enrichir en analysant le legacy (controller + vues + modèles) et en documentant :

| Élément à documenter | Description |
|----------------------|-------------|
| **Objectif** | Ce que fait la page/fonctionnalité en une phrase |
| **Accès** | Chemin de navigation pour atteindre la fonctionnalité |
| **Données affichées** | Quelles données sont présentées, depuis quelles tables |
| **Actions utilisateur** | Ce que l'utilisateur peut faire (boutons, formulaires, interactions) |
| **Règles métier** | Conditions, filtres, calculs, validations |
| **Droits d'accès** | ACL/privilèges requis |
| **Comportements spéciaux** | AJAX, téléchargements, redirections, messages flash |

**Format de documentation :**

```markdown
### 4.X Nom de la fonctionnalité

**Accès :** Chemin de navigation

Description courte de la fonctionnalité.

**Données affichées :**
- ...

**Actions possibles :**
1. L'utilisateur clique sur X
2. Le système fait Y
3. ...

**Règles métier :**
- RG-MOD-XXX — Description de la règle
```

**Livrable de cette phase :**
- Section dans `docs/specs/MOD-XX.md` couvrant la fonctionnalité à migrer, validée comme contrat fonctionnel

---

## Phase 1 — Analyse du legacy

**Outil :** Skill `analyse-legacy`

Avant d'écrire une seule ligne, explorer et comprendre le code legacy.

```bash
# Identifier les fichiers à migrer
find prevarisc/application/controllers/ -name "*NomController*"
find prevarisc/application/views/scripts/ -type d -name "nom"
find prevarisc/application/models/ -name "*Nom*"
find prevarisc/application/forms/ -name "*Nom*"
```

**Livrables de cette phase :**
- Liste des actions à migrer avec leur logique identifiée
- Inventaire des patterns Bootstrap 2 à convertir (grep obligatoire — cf. skill analyse-legacy §3b)
- Dépendances cartographiées (modèles, vues partielles, helpers)
- Requêtes SQL identifiées
- Points de complexité signalés (logique non triviale, ACL, sessions, AJAX)

---

## Phase 2 — Port direct (pure‑PHP) et Adaptation (Zend‑dependent)

**Outil :** Agent `symfony-migration`

Porter mécaniquement le code legacy vers Symfony pour les fonctions *pure‑PHP* (sans dépendance à Zend). Pour les blocs dépendants d'API Zend (helpers, modèles, view helpers, composants framework), effectuer une adaptation ciblée vers l'équivalent Symfony. Un refactoring léger est autorisé uniquement si le comportement observable reste identique et si le changement est documenté dans le rapport d'écarts.

**Règles pratiques :**
- Port direct : fonctions pure‑PHP, changements purement syntaxiques (Zend API → Symfony API).
- Adaptation : blocs qui appellent des services Zend, utilisent des helpers ou modèlent la donnée via Zend — remplacer par l'équivalent Symfony/Doctrine.
- Modifications autorisées sur fonctions pure‑PHP seulement si les entrées changent du fait de la migration (ex. tableau associatif → collection d'entités Doctrine) ; ces cas doivent être limités, expliqués et testés.
- En cas d'ambiguïté qui affecte le comportement, poser une question interactive pour trancher avant de continuer.

**Traductions mécaniques (sans risque) :**

| Zend | Symfony |
|------|---------|
| `$this->_getParam('x')` | `$request->get('x')` |
| `$this->view->x = $v` | `return $this->render(..., ['x' => $v])` |
| `$this->_redirect('/path')` | `return $this->redirectToRoute('name')` |
| `$this->_helper->flashMessenger` | `$this->addFlash('type', 'msg')` |
| `.phtml` | `.html.twig` |
| Bootstrap 2 classes | Bootstrap 3 classes (cf. mapping agent symfony-migration) |

**Checklist avant de passer à la phase 3 :**
- [ ] Tous les fichiers legacy lus et portés
- [ ] Zéro pattern Bootstrap 2 résiduel dans les templates
- [ ] Manifest MANIFEST.yaml mis à jour (status → done ou partial)

---

## Phase 3 — Optimisations légères

**Outil :** Inline (pas d'agent dédié)

Des améliorations légères sont acceptables si elles ne changent pas le comportement. Elles ne nécessitent pas de validation explicite.

### Corrections autorisées automatiquement

**Sécurité (correction obligatoire si détectée) :**
- Toute concaténation SQL → `setParameter()` Doctrine
- `echo json_encode(...)` dans les actions → `JsonResponse`
- Upload de fichiers sans vérification MIME → ajouter validation

**Syntaxe / patterns Symfony (amélioration légère) :**
- `$this->get('doctrine')` → injection par constructeur
- `$this->getDoctrine()->getManager()` → injection `EntityManagerInterface`
- Variables non initialisées → initialiser à `null` / `[]`
- DocBlocks manquants → ajouter `@var`, `@param`, `@return`

**Twig (amélioration légère) :**
- `{{ var|raw }}` sur données utilisateur → supprimer `|raw` (auto-escape)
- Icônes `icon-*` Bootstrap 2 oubliées → `glyphicon glyphicon-*`

### Ce qui reste INTERDIT même en optimisation légère

- Réorganiser l'ordre des opérations du legacy
- Extraire de la logique inline dans un service
- Ajouter des validations absentes du legacy
- Modifier la gestion des cas null/vide/0
- Changer la structure des requêtes SQL

---

## Phase 4 — Lint et validation

**Outil :** Castor (depuis l'hôte)

```bash
# Lancer dans cet ordre
castor symfony:analyse   # PHPStan niveau 10 — objectif : 0 erreur
castor symfony:cs        # Code Style — objectif : 0 erreur
castor symfony:test      # Tests — objectif : 100% passent
castor symfony:validate  # Validation des mappings Doctrine
castor migration:progress  # Vérifier la cohérence manifest ↔ code
```

**Si des erreurs PHPStan subsistent :** utiliser le skill `phpstan-fix`.

**Critères de passage à la phase 5 :**
- ✅ PHPStan : 0 erreur
- ✅ CS : 0 erreur
- ✅ Tests : 100% passent
- ✅ Manifest cohérent avec le code

---

## Phase 5 — Revue de code

**Outil :** Agent `code-review` (modèle Opus recommandé)

Une fois le lint passé, soumettre le code à la revue de l'agent `code-review` pour une analyse approfondie.

La revue produit un `REVIEW_REPORT.md` couvrant :
- Conformité PHP 7.1 + Symfony 4.4
- Bootstrap 3 (zéro résidu BS2)
- Sécurité (rapide — audit complet via `security-review`)
- Performance (rapide — audit complet via `performance-review`)
- Tests

**Selon le résultat :**
- 🔴 Bloquant → corriger avant commit, relancer lint
- 🟡 Important → corriger avant release
- 🔵 Mineur → traiter selon priorité

---

## Livraison : rapport d'écarts + commit

**Outil :** Skill `rapport-ecarts`

Produire le rapport d'écarts obligatoire, puis commiter :

```bash
git add .
git commit -m "feat(scope): description

- Met à jour MANIFEST.yaml : [task-id] → done

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
```

---

## Checklist finale avant livraison

- [ ] Phase 0 : Analyse fonctionnelle documentée dans `docs/specs/` (contrat fonctionnel établi)
- [ ] Phase 1 : Legacy exploré et compris (skill analyse-legacy)
- [ ] Phase 2 : Code porté (agent symfony-migration), manifest mis à jour
- [ ] Phase 3 : Optimisations légères appliquées
- [ ] Phase 4 : PHPStan ✅ + CS ✅ + Tests ✅ + Validate ✅
- [ ] Phase 5 : Revue Opus effectuée (agent code-review), problèmes bloquants résolus
- [ ] Rapport d'écarts produit (skill rapport-ecarts)
- [ ] Commit effectué avec co-auteur Copilot
