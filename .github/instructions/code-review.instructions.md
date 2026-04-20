---
applyTo: "prevarisc-migration/src/**,prevarisc-migration/templates/**"
---

# Revue de code - Migration Prevarisc

> Pour une revue approfondie (Opus), utiliser l'agent `code-review`.

Lors d'une revue de code, vérifie les points suivants et génère un rapport `REVIEW_REPORT.md` à la racine du projet.

---

## Checklist code

- ✅ Respect des standards PSR-12
- ✅ PHP 7.1 et Symfony 4.4 uniquement pour le code migré
- ✅ Typage strict (propriétés, paramètres, retours) via DocBlocks
- ✅ Pas de code deprecated
- ✅ PHPDoc uniquement si nécessaire (types complexes)

## Checklist tests

- ✅ **Pertinence** : Chaque test apporte une valeur ajoutée
- ✅ **Pas de tests inutiles** : Pas de tests sur getters/setters simples, code framework
- ✅ **Nommage explicite** : Le nom du test décrit clairement ce qui est testé
- ✅ **Isolation** : Le test peut s'exécuter seul
- ✅ **Assertions précises** : `assertSame()` pour types stricts, messages d'erreur clairs
- ✅ **Cas limites testés** : Valeurs nulles, vides, négatives selon le contexte
- ✅ **Pas de logique complexe** : Pas de if/for dans les tests
- ✅ **DataProvider** : Utilisé quand plusieurs cas similaires
- ⚠️ **Éviter surcharge** : Pas de tests "au cas où" sans valeur métier

---

## Format du rapport REVIEW_REPORT.md

```markdown
# Code Review Report

## Fichiers analysés
- liste des fichiers

## Points positifs
- ...

## Problèmes détectés
| Gravité | Fichier | Description |
|---------|---------|-------------|
| 🔴 Critique | ... | ... |
| 🟡 Mineur   | ... | ... |

## Suggestions d'amélioration
- ...

## Métriques
- Nombre de fichiers : X
- Problèmes critiques : X
- Suggestions : X
```
