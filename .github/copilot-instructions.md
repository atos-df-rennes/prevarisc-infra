# Instructions Copilot — Migration Prevarisc

**Contexte :** Migration Zend 1.12 → Symfony 4.4 | PHP 7.1.33

## Répertoires

| Rôle | Répertoire |
|------|-----------|
| Legacy (lecture seule) | `prevarisc/` |
| Code migré | `prevarisc-migration/` |
| API Plat'AU | `prevarisc-passerelle-platau/` |

## Stack technique

| PHP | Symfony | Doctrine | Twig | PHPStan |
|-----|---------|----------|------|---------|
| 7.1.33 | 4.4 LTS | 2.14 | 2.x | Niveau 10 |

## Commandes Castor (depuis l'hôte)

```bash
castor symfony:analyse    # PHPStan niveau 10
castor symfony:cs         # Code Style
castor symfony:test       # Tests PHPUnit
castor symfony:validate   # Validation Doctrine
castor migration:progress # Avancement migration
```

## PHP 7.1 — Règles strictes

- ❌ Propriétés typées (`private string $name`) → PHP 7.4+
- ❌ Attributs PHP 8 (`#[Route]`) → utiliser `@Route`
- ❌ Union types, named arguments → PHP 8+
- ✅ DocBlocks obligatoires (`@var`, `@param`, `@return`)

## Règles absolues

**INTERDIT ❌**
- `findAll()` sans pagination sur les listes
- Modifier `prevarisc/` (lecture seule)
- Fonctionnalités PHP > 7.1
- Extraire de la logique inline du legacy sans validation explicite
- Écrire "iso-fonctionnalité 100%" sans rapport d'écarts

**REQUIS ✅**
- Port direct comme point de départ pour les fonctions pure‑PHP ; adaptation ciblée pour les blocs dépendant de Zend (refactor léger autorisé si comportement inchangé)
- Skills deterministics & run_context : les préférences par skill sont définies dans `.github/skills-config.yml` (default run_context = main, worktree opt‑in).
- PHPStan niveau 10 + CS sans erreur avant commit
- Rapport d'écarts à chaque livraison
- Mettre à jour `docs/tech/migration/MANIFEST.yaml` dans chaque commit

**Optionnel (seulement si demandé explicitement) :**
- Extraction en service, QueryBuilder custom, tests unitaires

## Format des commits

```
feat(scope): description
fix(scope): description
docs(scope): description

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>
```

## Outils spécialisés

| Besoin | Outil |
|--------|-------|
| Pipeline complet de migration | Skill `migrate-feature` |
| Explorer le legacy | Skill `analyse-legacy` |
| Migrer du code | Agent `symfony-migration` |
| État de la migration | Skill `migration-status` |
| Corriger PHPStan | Skill `phpstan-fix` |
| Rapport d'écarts | Skill `rapport-ecarts` |
| Tâche dans le worktree | Skill `worktree-task` |
| Revue de code (Opus) | Agent `code-review` |
| Audit sécurité | Agent `security-review` |
| Audit performance | Agent `performance-review` |
| Audit accessibilité | Agent `rgaa-review` |

## Flux de revue de code agrégée (PR)

Quand une PR existe, toujours suivre ce flux **avant** d'invoquer l'agent `code-review` :

### Étape 1 — Identifier le bon repo et le bon numéro de PR

Les PRs de migration sont dans **`atos-df-rennes/prevarisc-migration`**.

Si le numéro de PR fourni ne correspond pas, **rechercher par nom de branche** :
```
github-mcp-server-list_pull_requests → owner: atos-df-rennes, repo: prevarisc-migration, state: open
```
→ Filtrer sur `head.ref` correspondant au nom de branche.

### Étape 2 — Fetch des reviews GitHub (en parallèle)

```
github-mcp-server-pull_request_read → get_reviews          (reviews formelles)
github-mcp-server-pull_request_read → get_review_comments  (commentaires inline)
github-mcp-server-pull_request_read → get_comments         (commentaires généraux)
```

Si les trois retournent vide, le noter dans le rapport (pas de reviews externes).

### Étape 3 — Invoquer l'agent `code-review`

Passer dans le prompt :
- Le diff complet (`git diff origin/develop...origin/BRANCHE` dans `prevarisc-migration/`)
- Les reviews GitHub formatées (ou "aucune review externe" si vide)
- Le contexte métier de la fonctionnalité

### Étape 4 — Rapport agrégé

L'agent produit un `REVIEW_REPORT.md` dans `prevarisc-migration/`, avec les marqueurs :
- 🐙 Copilot (reviews GitHub Copilot bot)
- 👤 Utilisateur (reviews humaines GitHub)
- 🤖 CLI Agent (analyse de cet agent)

**Version :** 5.3 | **Dernière màj :** 23 avril 2026
