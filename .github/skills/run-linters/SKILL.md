---
name: run-linters
description: Guide de référence pour lancer les linters/tests Prevarisc (PHPStan, Rector/PHP-CS-Fixer/Twig-CS-Fixer, PHPUnit) via castor, avec la bonne commande et le bon mode (check vs écriture) selon la situation.
---

# Skill : Commandes de lint et de test — infrastructure Prevarisc

Ce skill référence les tâches Castor disponibles dans le dépôt `prevarisc-infra/`. Exécuter les commandes depuis sa racine. Pour une tâche ciblant uniquement `prevarisc-migration/`, le skill `validate-application` du dépôt applicatif documente la même validation et les commandes sous-jacentes.

## Commandes nominales

| Commande | Outil(s) | Comportement |
|----------|----------|--------------|
| `castor symfony:analyse` | PHPStan (niveau 10) | Analyse statique uniquement, aucune modification de fichier |
| `castor symfony:cs` | PHP-CS-Fixer + Twig-CS-Fixer | **Applique** les corrections de style directement sur les fichiers |
| `castor symfony:refactor` | Rector | Applique les refactorings Rector |
| `castor symfony:test` | PHPUnit | Lance les tests ; exclut le groupe `functional` par défaut |
| `castor symfony:test --all` | PHPUnit | Inclut également le groupe `functional` |
| `castor symfony:validate` | PHPStan, Rector, PHP-CS-Fixer, Twig-CS-Fixer, PHPUnit | Vérifications en lecture seule : Rector/PHP-CS-Fixer en `--dry-run`, Twig-CS-Fixer en `check`, PHPUnit hors groupe `functional` |

## Points importants

- `castor symfony:cs` modifie les fichiers. Utiliser `castor symfony:validate` pour vérifier le style sans écriture.
- `castor symfony:validate` lance PHPStan, Rector, les deux fixers, puis PHPUnit en mode séquentiel et s'arrête au premier échec.
- `castor symfony:test` (sans `--all`) est la commande nominale pour valider les changements applicatifs. Les tests end-to-end navigateur sont dans `prevarisc-migration/cypress/`; `tests/Functional/` contient des tests PHPUnit HTTP de plus bas niveau.

## Cas d'usage typiques

- **Avant un commit applicatif** : `castor symfony:validate` (vérifie tout sans rien modifier).
- **Corriger le style après une modification** : `castor symfony:cs` (applique les correctifs), puis relancer `castor symfony:analyse` et `castor symfony:test` pour confirmer.
- **Corriger des erreurs PHPStan** : voir le skill `phpstan-fix` du dépôt `prevarisc-migration/`.
