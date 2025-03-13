# Infrastructure Prevarisc

Ceci est le dépôt de l'infrastructure Docker pour Prevarisc. Vous trouverez un fichier compose pour une installation de développement (compose.dev.yaml) et un autre pour une installation de production (compose.yaml).

## Installation de développement

Clonez ce dépôt :
```shell
git clone https://github.com/atos-df-rennes/prevarisc-infra.git
```

Puis dans ce répertoire, clonez le dépôt Prevarisc :
```shell
cd prevarisc-infra
git clone https://github.com/atos-df-rennes/prevarisc.git
```

> [!NOTE]
> Pendant la phase de migration, clonez également le dépôt de migration et la passerelle Plat'AU

```shell
git clone https://github.com/atos-df-rennes/prevarisc-migration.git
```
```shell
git clone https://github.com/atos-df-rennes/prevarisc-passerelle-platau.git
```

Copiez les fichiers de configuration d'exmemple et adaptez selon vos besoins :
```shell
cp apache/httpd-prevarisc-config.conf.example apache/httpd-prevarisc-config.conf
cp apache/httpd-prevarisc-version.conf.example apache/httpd-prevarisc-version.conf
```

Buildez les conteneurs et démarrez la stack :
```shell
docker compose --file compose.dev.yaml build
docker compose --file compose.dev.yaml up --detach
```

Enfin, se connectez aux conteneurs PHP de Prevarisc de la passerelle Plat'AU et installez les dépendances :
```shell
docker compose --file compose.dev.yaml exec -ti app bash
cd prevarisc
git checkout migration-symfony
composer install
```
```shell
docker compose --file compose.dev.yaml exec -ti app bash
cd prevarisc-migration
cp .env.example .env
composer install
php bin/console doctrine:migrations:migrate --no-interaction
```
```shell
docker compose --file compose.dev.yaml exec -ti platau bash
cd prevarisc-passerelle-platau
composer install
```

### Outils de développement

Pour installer les outils de développements, connectez-vous au conteneur PHP de la passerelle Plat'AU et installez les dépendances :
```shell
docker compose --file compose.dev.yaml exec -ti platau bash
cd prevarisc/tools
composer install
```
Pour lancer les outils, connectez-vous au conteneur PHP de la passerelle Plat'AU et lancez les commandes de manière standard :
- Prevarisc
```shell
docker compose --file compose.dev.yaml exec -ti platau bash
cd prevarisc

PHPStan : tools/vendor/bin/phpstan --memory-limit=-1
Rector : tools/vendor/bin/rector  [--dry-run]
PHP-CS-FIXER : tools/vendor/bin/php-cs-fixer fix [--dry-run]
Générer le changelog : tools/vendor/bin/conventional-changelog
```
Pour lancer les tests unitaires, connectez-vous au conteneur PHP de Prevarisc et lancez les tests :
```shell
docker compose --file compose.dev.yaml exec -ti app bash
cd prevarisc
vendor/bin/phpunit --bootstrap tests/bootstrap/indexTest.php --testdox tests/
```
- Migration Symfony
```shell
docker compose --file compose.dev.yaml exec -ti platau bash
cd prevarisc-migration

PHPStan : tools/vendor/bin/phpstan --memory-limit=-1
Rector : tools/vendor/bin/rector  [--dry-run]
PHP-CS-FIXER : tools/vendor/bin/php-cs-fixer fix [--dry-run]
Générer le changelog : tools/vendor/bin/conventional-changelog
```

## Installation de production

A venir.