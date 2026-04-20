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
- Port direct comme point de départ systématique
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
| Revue de code (Opus) | Agent `code-review` |
| Audit sécurité | Agent `security-review` |
| Audit performance | Agent `performance-review` |
| Audit accessibilité | Agent `rgaa-review` |

**Version :** 5.0 optimisé | **Dernière màj :** 20 avril 2026
