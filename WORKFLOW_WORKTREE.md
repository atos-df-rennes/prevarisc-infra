# Workflow Git Worktree — Modifications légères en parallèle

## Contexte

Lors de la migration Zend → Symfony, le travail principal s'effectue dans `prevarisc-migration/`. Il arrive régulièrement de devoir effectuer des tâches légères en parallèle sur le code legacy : corrections de bugs, petits ajouts, refactors. Ces tâches ne doivent pas interférer avec le travail en cours.

**Solution : les Git worktrees.**

Un worktree crée un répertoire de travail supplémentaire lié au même dépôt git, sur une branche isolée, sans toucher à la branche courante.

---

## Dépôts concernés

| Dépôt | Rôle | Répertoire |
|-------|------|------------|
| `prevarisc/` | Code legacy Zend | `prevarisc-infra/prevarisc/` |
| `prevarisc-migration/` | Code migré Symfony | `prevarisc-infra/prevarisc-migration/` |

Les modifications légères sur le legacy touchent **`prevarisc/`** (logique métier, services, vues) et, quand une migration de base de données est nécessaire, **`prevarisc-migration/`** (migration Doctrine).

---

## Workflow complet

### 1. Créer les worktrees

Depuis `prevarisc-infra/` :

```bash
# Worktree pour le code legacy
git -C prevarisc worktree add \
  ../prevarisc-worktree-<nom-feature> \
  -b feat/<nom-feature> \
  release/2.8

# Worktree pour la migration Doctrine (si nécessaire)
git -C prevarisc-migration worktree add \
  ../prevarisc-migration-worktree-<nom-feature> \
  -b feat/<nom-feature> \
  release/2.8
```

> **Important :** toujours créer une branche feature (`-b feat/...`) plutôt que de travailler directement sur `release/2.8`.

### 2. Implémenter

Travailler dans le(s) répertoire(s) worktree créés. Le dépôt principal et son worktree actif ne sont pas affectés.

```bash
cd prevarisc-worktree-<nom-feature>
# ... modifications ...
git add .
git commit -m "feat(<scope>): description"
```

#### Règle SQL

Les modifications de base de données ne passent **jamais** par les fichiers SQL de `prevarisc/` (`sql/migrations/`, `sql/init/`). Elles passent exclusivement par une **migration Doctrine** dans `prevarisc-migration/` :

```bash
cd prevarisc-migration-worktree-<nom-feature>/

# Créer manuellement un fichier de migration (generate, pas diff)
# Nommer le fichier : migrations/Version<YYYYMMDDHHmmSS>.php
```

```php
// Exemple : migrations/Version20260401090000.php
final class Version20260401090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Description de la migration.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), '...');
        $this->addSql("INSERT INTO privileges (name, text, id_resource) VALUES ('mon_privilege', 'Mon libellé', 100)");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), '...');
        $this->addSql("DELETE FROM privileges WHERE name = 'mon_privilege'");
    }
}
```

### 3. Pousser et ouvrir les PRs

```bash
# Repo prevarisc
git -C prevarisc-worktree-<nom-feature> push origin feat/<nom-feature>
# → Ouvrir une PR : feat/<nom-feature> → release/2.8

# Repo prevarisc-migration (si concerné)
git -C prevarisc-migration-worktree-<nom-feature> push origin feat/<nom-feature>
# → Ouvrir une PR : feat/<nom-feature> → release/2.8
```

### 4. Nettoyer après merge

```bash
git -C prevarisc worktree remove prevarisc-worktree-<nom-feature>
git -C prevarisc branch -d feat/<nom-feature>

git -C prevarisc-migration worktree remove prevarisc-migration-worktree-<nom-feature>
git -C prevarisc-migration branch -d feat/<nom-feature>
```

---

## Exemple concret — Bloc "Dossiers incomplets"

```bash
# Création
git -C prevarisc worktree add ../prevarisc-worktree-incomplete-dossiers -b feat/dossiers-incomplets release/2.8
git -C prevarisc-migration worktree add ../prevarisc-migration-worktree-incomplete-dossiers -b feat/dossiers-incomplets release/2.8

# Fichiers modifiés dans prevarisc/
#   application/services/Dashboard.php  → config bloc + méthode getDossiersIncomplets()
#   application/services/Count.php      → méthode getDossiersIncompletsCount()

# Fichier créé dans prevarisc-migration/
#   migrations/Version20260401090000.php → INSERT privilege view_doss_incomplets

# Push
git -C prevarisc-worktree-incomplete-dossiers push origin feat/dossiers-incomplets
git -C prevarisc-migration-worktree-incomplete-dossiers push origin feat/dossiers-incomplets
```

---

## Résumé visuel

```
prevarisc-infra/
├── prevarisc/                                    ← dépôt principal (inchangé)
├── prevarisc-migration/                          ← dépôt principal (inchangé)
│
├── prevarisc-worktree-<feature>/                 ← worktree legacy   (feat/<feature>)
└── prevarisc-migration-worktree-<feature>/       ← worktree Symfony  (feat/<feature>)
```

Les worktrees sont des répertoires **temporaires** : ils disparaissent après le nettoyage post-merge et ne sont pas versionnés dans `prevarisc-infra/`.

---

### Validation fonctionnelle en parallèle

La stack worktree est **entièrement isolée** de la stack principale : conteneurs, réseau et base de données séparés. Les deux environnements tournent simultanément.

| Stack | URL | Port DB | Mailpit |
|-------|-----|---------|---------|
| Principale (`compose.dev.yaml`) | http://localhost:7080 | 33061 | http://localhost:8025 |
| Worktree (`compose.worktree-standalone.yaml`) | http://localhost:7081 | 33062 | http://localhost:8026 |

### Démarrer la stack worktree

```bash
castor worktree:start
```

Le wizard guide les étapes :
1. Sélection du worktree existant
2. Copie des fichiers de configuration
3. Démarrage des conteneurs isolés (`worktree-app-1`, `worktree-platau-1`, `worktree-db-1`, `worktree-mailpit-1`)
4. Seeding de la DB depuis un dump (optionnel)
5. Installation des dépendances Composer

### Lint et validation du code worktree

La stack worktree dispose de commandes Castor dédiées, équivalentes aux `castor symfony:*` :

```bash
castor worktree:validate   # ⭐ Validation complète avant commit
castor worktree:analyse    # PHPStan uniquement
castor worktree:cs         # Correction du style (Rector + PHP-CS-Fixer + Twig-CS-Fixer)
castor worktree:cs --dry-run  # Vérification sans modification
castor worktree:test       # Tests PHPUnit
```

> Ne **pas** utiliser `castor symfony:*` pour le code du worktree : ces commandes ciblent la stack principale.

### Seeding de la base de données

Le seeding charge un dump SQL dans la DB du worktree (`worktree-db-1`) pour partir d'un état connu.

```bash
# Créer d'abord un dump frais de la DB principale
castor prevarisc:make-dump

# Ou charger directement un dump existant dans la DB du worktree
castor prevarisc:load-dump --container worktree-db-1
```

### Arrêter la stack worktree

```bash
castor worktree:stop
```

La stack principale n'est pas affectée.

### Exemple concret — Bloc "Dossiers incomplets"

```bash
# 1. Créer le worktree
castor worktree:create
# → type: fix, nom: dossiers-incomplets

# 2. Démarrer la stack isolée (ou répondre "oui" à la question pendant le create)
castor worktree:start

# 3. Tester sur http://localhost:7081 pendant que http://localhost:7080 reste opérationnel

# 4. Arrêter la stack worktree une fois validé
castor worktree:stop
```
