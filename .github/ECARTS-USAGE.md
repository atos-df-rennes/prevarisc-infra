# Convention d'utilisation de l'agent `ecarts`

## Invocation

Utilise la slash commande `/agent ecarts` pour invoquer directement l'agent :

```
/agent ecarts
```

Puis saisis ta syntaxe :

```
fix MOD-10 5
```

## Syntaxe abrégée

```
<action> <module> <number>
```

### Paramètres

| Paramètre | Valeurs | Exemple |
|-----------|---------|---------|
| **action** | `fix` \| `check` | `fix` (correction), `check` (vérification) |
| **module** | Code doc (MOD-XX) | `MOD-10`, `MOD-12`, `MOD-15` |
| **number** | Numéro écart | `5`, `6`, `1` |

### Exemples

```
# Corriger l'écart 5 du module MOD-10
ecarts fix MOD-10 5

# Vérifier la validité de l'écart 6 du module MOD-10
ecarts check MOD-10 6

# Corriger l'écart 3 du module MOD-12
ecarts fix MOD-12 3
```

## Chemins de base

L'agent `ecarts` utilise automatiquement le chemin de base :
```
/home/dev/prevarisc-infra/prevarisc-migration/docs/migration/
```

Il construit le chemin du fichier à partir du module fourni :
```
MOD-10  → MOD-10-TEXTES-APPLICABLES-ECARTS.md
MOD-12  → MOD-12-[NOM]-ECARTS.md
```

## Comportements contextuels

### Action `fix`
- Implémente la correction décrite dans l'écart
- Lance les validations (PHPStan, tests, linting)
- Commit les changements avec traçabilité
- Met à jour `.github/ecarts-history.yml`

### Action `check`
- Valide le retour utilisateur fourni dans l'écart
- Vérifie l'absence de régressions vs legacy
- Identifie les améliorations fonctionnelles
- **Si OK** → marque l'écart comme `closed` dans le fichier d'écarts
- **Si problème** → propose un plan avant d'agir
- Met à jour `.github/ecarts-history.yml`

## Fichiers à mettre à jour

Chaque écart corrigé/validé impacte **deux fichiers** :

### 1. **Fichier d'écarts** (`MOD-XX-TEXTES-APPLICABLES-ECARTS.md`)
- Met à jour le statut dans la section écart avec les marqueurs : `✅ RÉSOLU`, `✅ VALIDÉ`, `🟢 FERMÉ`
- Ajoute/met à jour la section **Recommandations** avec un résumé daté

### 2. **Historique de suivi** (`ecarts-history.yml`)
- Ajoute/met à jour l'entrée dans la section module
- Inclut : numéro, titre, action, status, date_processed, validated_by, notes, files_modified
- Met à jour les métadonnées (`last_updated`, `total_ecarts_processed`)

**Pourquoi les deux ?**
- **Markdown** : contexte inline lisible pour les lecteurs de specs
- **YAML** : suivi structuré pour audit, automatisation et historique

## Options futures

Possibilités d'extension future (non implémentées) :
- `ecarts fix MOD-10 1-5` : traiter une plage d'écarts
- `ecarts check MOD-10 --force` : forcer la vérification sans interaction
