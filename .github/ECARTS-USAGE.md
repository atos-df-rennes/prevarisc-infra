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

## Suivi et historique

### `.github/ecarts-history.yml`

Chaque écart traité (fix ou check) génère une entrée dans ce fichier :

```yaml
MOD-10:
  - number: 5
    title: "Description courte"
    action: fix
    status: closed
    date_processed: "2026-07-02"
    validated_by: system
    notes: "Détails de ce qui a été fait"
    files_modified:
      - "prevarisc-migration/src/..."
```

**Cet historique :**
- Trace chaque correction/vérification effectuée
- Facilite l'audit et la maintenabilité
- Est mis à jour automatiquement par l'agent `ecarts`

## Options futures

Possibilités d'extension future (non implémentées) :
- `ecarts fix MOD-10 1-5` : traiter une plage d'écarts
- `ecarts check MOD-10 --force` : forcer la vérification sans interaction
