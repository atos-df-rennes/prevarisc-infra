# Infrastructure Prevarisc

Dépôt de l'infrastructure Docker pour Prevarisc. Il orchestre trois applications :

| Dépôt | Rôle |
|---|---|
| `prevarisc/` | Code legacy Zend 1.12 (lecture seule en dehors des bug fixes) |
| `prevarisc-migration/` | Code migré Symfony 4.4 (travail principal) |
| `prevarisc-passerelle-platau/` | API de passerelle Plat'AU |

---

## Installation de développement

### Pré-requis

- [Docker](https://docs.docker.com/get-docker/)
- [Castor](https://castor.jolicode.com/installation/#as-a-static-binary) — task runner utilisé pour piloter l'environnement
- (Recommandé) [Activer l'autocomplétion Castor](https://castor.jolicode.com/going-further/interacting-with-castor/autocomplete/#installation)

### Démarrage rapide

```bash
# 1. Cloner ce dépôt
git clone https://github.com/atos-df-rennes/prevarisc-infra.git
cd prevarisc-infra

# 2. Installation complète (clone des repos, Docker, .env, Composer, migrations)
castor prevarisc:setup

# 3. L'application est prête — l'URL est affichée en fin de setup
```

Pour les sessions suivantes :

```bash
castor prevarisc:start   # démarrer
castor prevarisc:stop    # arrêter
```

### Référence des commandes

Toutes les commandes disponibles sont documentées dans **[CASTOR.md](CASTOR.md)**, avec les cas d'usage de chacune.

Pour la liste brute : `castor list`

---

## Installation de production

À venir.

