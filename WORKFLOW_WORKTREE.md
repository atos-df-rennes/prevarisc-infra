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
