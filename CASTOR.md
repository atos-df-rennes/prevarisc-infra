# Référence des commandes Castor

[Castor](https://castor.jolicode.com/) est le task runner utilisé pour piloter l'ensemble de l'infrastructure et des applications Prevarisc. Il remplace les scripts Bash, les `Makefile` et les longues commandes Docker.

> **Pré-requis :** [Installer Castor](https://castor.jolicode.com/installation/#as-a-static-binary) et [activer l'autocomplétion](https://castor.jolicode.com/going-further/interacting-with-castor/autocomplete/#installation).  
> Toutes les commandes s'exécutent depuis la racine du dépôt `prevarisc-infra/`.

---

## Parcours d'onboarding

Pour un développeur qui rejoint le projet, les étapes sont les suivantes :

```bash
# 1. Installation complète (clone des repos, Docker, .env, Composer, migrations)
castor prevarisc:setup

# 2. Démarrer l'application
castor prevarisc:start

# 3. Vérifier que tout fonctionne
castor symfony:validate

# 4. Développer — puis avant chaque commit :
castor symfony:validate

# 5. Fin de journée
castor prevarisc:stop
```

---

## Commandes racine (toutes applications)

Ces commandes lancent les vérifications en **parallèle** sur les trois applications (Zend legacy, Symfony migration, Plat'AU).

| Commande | Description |
|---|---|
| `castor cs [--dry-run]` | Corrige le style de code sur toutes les applications. Avec `--dry-run` : vérifie sans modifier. |
| `castor analyse` | Lance PHPStan/Psalm sur toutes les applications. |
| `castor test` | Exécute les suites de tests de toutes les applications. |

**Quand les utiliser :** CI, ou revue globale avant merge. Pour le développement quotidien, préférer les commandes ciblées (`symfony:validate`, `symfony:analyse`).

---

## `prevarisc:*` — Infrastructure Docker

Commandes de gestion du cycle de vie de l'environnement.

### `castor prevarisc:setup`
**Installation complète du projet depuis zéro.**

Clone les trois dépôts (`prevarisc`, `prevarisc-migration`, `prevarisc-passerelle-platau`), copie les fichiers de configuration, build les images Docker, crée le fichier `.env` Symfony avec des valeurs de développement, installe les dépendances Composer et exécute les migrations Doctrine.

```bash
castor prevarisc:setup
```

> À n'utiliser qu'une fois, à l'installation initiale. Si les dépôts existent déjà, les étapes concernées sont ignorées.

---

### `castor prevarisc:start`
**Démarre les containers Docker.**

```bash
castor prevarisc:start           # démarrage simple
castor prevarisc:start --watch   # démarrage + surveillance Apache/PHP (rechargement automatique)
```

Sans `--watch`, le terminal reste disponible. Avec `--watch`, le processus reste actif et recharge le container `app` à chaque modification dans `apache/` ou `php/prevarisc/`.

---

### `castor prevarisc:stop`
**Arrête et supprime les containers.**

```bash
castor prevarisc:stop
```

---

### `castor prevarisc:rebuild`
**Reconstruit les images Docker puis redémarre les containers.**

```bash
castor prevarisc:rebuild
```

**Quand l'utiliser :** après une modification de configuration Docker (`php/prevarisc/php.ini`, `Dockerfile`, extensions PHP…). Un simple `start` ne suffit pas dans ce cas.

---

### `castor prevarisc:update`
**Met à jour les dépendances et applique les migrations.**

```bash
castor prevarisc:update
```

Lance en parallèle : `composer install` sur les trois applications + `doctrine:migrations:migrate`. À utiliser après un `git pull` sur l'un des dépôts.

---

### `castor prevarisc:logs [service]`
**Suit les logs Docker en temps réel.**

```bash
castor prevarisc:logs           # tous les services
castor prevarisc:logs app       # uniquement le container PHP/Apache
castor prevarisc:logs db        # uniquement MySQL
castor prevarisc:logs platau    # uniquement le container Plat'AU
```

**Quand l'utiliser :** débugger une erreur 500, suivre les requêtes SQL, inspecter les erreurs Apache.

---

### `castor prevarisc:shell`
**Ouvre un shell Bash interactif dans le container `app`.**

```bash
castor prevarisc:shell
```

**Quand l'utiliser :** débugger un problème à l'intérieur du container, inspecter des fichiers, exécuter des commandes one-shot qui n'ont pas de raccourci Castor.

---

### `castor prevarisc:make-dump`
**Crée un dump SQL de la base de données.**

```bash
castor prevarisc:make-dump
```

Génère un fichier `prevarisc_YYYYMMDD.sql` à la racine de `prevarisc-infra/`.

---

### `castor prevarisc:load-dump`
**Charge un dump SQL dans la base de données.**

```bash
castor prevarisc:load-dump
```

Détecte automatiquement les fichiers `.sql` à la racine. Si plusieurs fichiers existent, propose un choix interactif (le plus récent en premier).

---

## `symfony:*` — Application Symfony (migration)

Commandes de développement pour `prevarisc-migration/`.

### `castor symfony:validate` ⭐
**Validation complète avant un commit — s'arrête au premier échec.**

```bash
castor symfony:validate
```

Exécute dans l'ordre :
1. **PHPStan** (niveau 10) — analyse statique
2. **Rector** (dry-run) — détecte les transformations de code non appliquées
3. **PHP-CS-Fixer** (dry-run) — vérifie le style sans modifier
4. **Twig-CS-Fixer** (check) — vérifie les templates
5. **PHPUnit** — tests (hors groupe `functional`)

> C'est la commande à lancer systématiquement avant tout commit sur `prevarisc-migration`.

---

### `castor symfony:analyse`
**Lance uniquement PHPStan.**

```bash
castor symfony:analyse
```

Plus rapide que `validate` pour une itération rapide sur des erreurs de typage.

---

### `castor symfony:cs [--dry-run]`
**Corrige le style de code (Rector + PHP-CS-Fixer + Twig-CS-Fixer).**

```bash
castor symfony:cs            # corrige les fichiers
castor symfony:cs --dry-run  # vérifie sans modifier (mode CI)
```

---

### `castor symfony:test [--all]`
**Exécute les tests PHPUnit.**

```bash
castor symfony:test          # tests unitaires et d'intégration (exclut @group functional)
castor symfony:test --all    # inclut les tests fonctionnels
```

---

### `castor symfony:cache`
**Vide le cache Symfony.**

```bash
castor symfony:cache
```

**Quand l'utiliser :** après modification d'un service, d'une annotation de route, d'un fichier de configuration YAML, ou en cas de comportement inattendu.

---

### `castor symfony:migrate`
**Applique les migrations Doctrine en attente.**

```bash
castor symfony:migrate
```

---

### `castor symfony:migration-status`
**Affiche l'état des migrations Doctrine (appliquées / en attente).**

```bash
castor symfony:migration-status
```

**Quand l'utiliser :** avant de créer une nouvelle migration, ou pour vérifier l'état après un `git pull`.

---

### `castor symfony:make <type> [options]`
**Génère du code avec les makers Symfony.**

```bash
castor symfony:make entity NomEntite
castor symfony:make migration
castor symfony:make form NomFormType
castor symfony:make controller NomController
```

Raccourci pour `castor symfony:console make:<type>`.

---

### `castor symfony:console <commande>`
**Exécute n'importe quelle commande `bin/console` Symfony.**

```bash
castor symfony:console debug:router
castor symfony:console doctrine:schema:validate
castor symfony:console debug:container NomService
```

---

### `castor symfony:status`
**Affiche le `git status` en parallèle sur `prevarisc/` et `prevarisc-migration/`.**

```bash
castor symfony:status
```

**Quand l'utiliser :** orientation rapide en début de session — voir sur quelles branches on est et quels fichiers sont modifiés.

---

## `worktree:*` — Travail en parallèle sur branches isolées

Les worktrees permettent de travailler sur une feature de migration Symfony **pendant** qu'un bug fix sur le legacy est en cours, sans interférence entre les branches.

> Voir `WORKFLOW_WORKTREE.md` pour le détail du workflow complet.

### Workflow type

```bash
# 1. Créer un worktree pour une nouvelle feature
castor worktree:create
# → Choisir : type (feat/fix/…), nom, dépôts concernés, branche de base

# 2. Voir les worktrees actifs
castor worktree:list

# 3. Basculer entre worktrees (ou revenir au dépôt principal)
castor worktree:switch

# 4. Après merge de la PR, nettoyer
castor worktree:remove
```

---

### `castor worktree:create`
**Crée un nouveau worktree sur une branche isolée.**

Guide interactif : type de modification, nom de la feature (kebab-case), dépôts concernés (`prevarisc`, `prevarisc-migration`, ou les deux), branche de base. Propose de basculer immédiatement vers le worktree créé.

---

### `castor worktree:list`
**Liste les worktrees actifs avec leur branche.**

```bash
castor worktree:list
```

```
 Nom                  Dépôts                Branche
 dossiers-incomplets  prevarisc + migration  feat/dossiers-incomplets
 fix-date-visite      prevarisc             fix/date-visite
```

---

### `castor worktree:switch`
**Bascule le container `app` vers un worktree ou vers le dépôt principal.**

```bash
castor worktree:switch
```

Guide interactif : liste les worktrees disponibles + option « Dépôt principal ». Copie les fichiers de configuration nécessaires (`.env`, `HashedFileDataStore`), installe les dépendances Composer si besoin, et redémarre le container.

---

### `castor worktree:remove [--force]`
**Supprime un worktree et sa branche locale.**

```bash
castor worktree:remove           # échoue si des modifications ne sont pas commitées
castor worktree:remove --force   # force la suppression
```

Propose de basculer vers un autre worktree ou le dépôt principal après suppression.

---

## `zend:*` — Application Zend (legacy)

Commandes de vérification du code legacy `prevarisc/`.

> Le legacy est en **lecture seule** dans le workflow de migration : ces commandes servent principalement au CI et à la vérification ponctuelle lors d'un bug fix sur le legacy.

| Commande | Description |
|---|---|
| `castor zend:cs [--dry-run]` | Rector + PHP-CS-Fixer sur le legacy |
| `castor zend:analyse` | PHPStan sur le legacy |
| `castor zend:test` | PHPUnit sur le legacy |

---

## `platau:*` — Passerelle Plat'AU

Commandes de vérification de l'application `prevarisc-passerelle-platau/`.

| Commande | Description |
|---|---|
| `castor platau:cs [--dry-run]` | PHP-CS-Fixer sur la passerelle |
| `castor platau:analyse` | Psalm sur la passerelle |
| `castor platau:test` | PHPUnit sur la passerelle |

---

## Aide-mémoire rapide

```bash
# Démarrage
castor prevarisc:start

# Développement quotidien (migration Symfony)
castor symfony:status          # où en suis-je ?
castor symfony:cache           # comportement inattendu ?
castor symfony:analyse         # itération rapide PHPStan
castor symfony:validate        # avant chaque commit ✅

# Gestion de la base de données
castor symfony:migration-status
castor symfony:migrate
castor prevarisc:make-dump
castor prevarisc:load-dump

# Debugging
castor prevarisc:logs app
castor prevarisc:shell

# Worktrees (travail en parallèle)
castor worktree:create
castor worktree:list
castor worktree:switch
castor worktree:remove

# Fin de journée
castor prevarisc:stop
```
