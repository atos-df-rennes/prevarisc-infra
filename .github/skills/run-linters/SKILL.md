---
name: run-linters
description: Guide de référence pour lancer les linters/tests Prevarisc (PHPStan, Rector/PHP-CS-Fixer/Twig-CS-Fixer, PHPUnit) via castor, avec la bonne commande et le bon mode (check vs écriture) selon la situation.
---

# Skill : Commandes de lint et de test — Prevarisc

Ce skill liste les commandes `castor` à utiliser pour analyser, corriger le style et tester `prevarisc-migration/`, ainsi que leurs nuances de comportement (check-only vs écriture).

## Commandes nominales

| Commande | Outil(s) | Comportement |
|----------|----------|--------------|
| `castor symfony:analyse` | PHPStan (niveau 10) | Analyse statique uniquement, aucune modification de fichier |
| `castor symfony:cs` | Rector + PHP-CS-Fixer + Twig-CS-Fixer | **Applique** les corrections de style/refactoring directement sur les fichiers |
| `castor symfony:test` | PHPUnit (sans `--all`) | Lance les tests unitaires/non-fonctionnels uniquement |
| `castor symfony:validate` | Les trois outils ci-dessus | Lance PHPStan + Rector/PHP-CS-Fixer/Twig-CS-Fixer + PHPUnit, tous en **mode `--dry-run`** (aucune écriture) |

## Points importants

- Les commandes individuelles (`symfony:analyse`, `symfony:cs`, `symfony:test`) n'ont **pas** d'option `--dry-run` : `symfony:cs` modifie les fichiers directement.
- Seule `castor symfony:validate` exécute tout en mode `--dry-run` (vérification sans écriture) — utile avant de committer pour vérifier sans risque de modification.
- `castor symfony:test` (sans `--all`) est la commande nominale à utiliser pour valider des changements de code. L'option `--all` inclut les tests fonctionnels, qui ont des échecs préexistants connus sans rapport avec les changements en cours (fixture d'auth/session cassée) — ne pas l'utiliser pour valider un changement.

## Cas d'usage typiques

- **Avant de committer** : `castor symfony:validate` (vérifie tout sans rien modifier).
- **Corriger le style/code après une modification** : `castor symfony:cs` (applique les correctifs), puis relancer `castor symfony:analyse` et `castor symfony:test` pour confirmer.
- **Corriger des erreurs PHPStan** : voir le skill `phpstan-fix` pour les patterns de correction courants.
