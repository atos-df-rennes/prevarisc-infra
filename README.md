# Infrastructure Prevarisc

Dépôt de l'infrastructure Docker pour Prevarisc. Il orchestre les dépôts applicatifs et leurs dépendances :

| Dépôt | Rôle |
|---|---|
| `prevarisc/` | Référence historique Zend 1.12 (lecture seule, non livrée aux clients) |
| `prevarisc-migration/` | Application Symfony 7.4 en production ; les corrections et évolutions se font dans ce dépôt |
| `prevarisc-passerelle-platau/` | Package applicatif de communication avec l'API Plat'AU, destiné à être intégré à terme dans l'application |
| [`odtphp`](https://github.com/atos-df-rennes/odtphp) | Fork maintenu pour compatibilité avec la stack PHP actuelle, utilisé comme dépendance Composer de l'application |

---

## Installation de développement

### Pré-requis

- [WSL en version 2](https://learn.microsoft.com/en-us/windows/wsl/install) | Cela nécessite d'activer les options de virtualisation dans le BIOS
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
castor docker:start   # démarrer
castor docker:stop    # arrêter
```

### Référence des commandes

Toutes les commandes disponibles sont documentées dans **[CASTOR.md](CASTOR.md)**, avec les cas d'usage de chacune.

Pour la liste brute : `castor list`

---

## Installation de production

À venir.
