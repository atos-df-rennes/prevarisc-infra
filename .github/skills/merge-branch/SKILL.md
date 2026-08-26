---
name: merge-branch
description: Guide pour merger une branche release/x.y (PHP 7.1/Symfony 4.4) dans une branche cible sur une version PHP/Symfony différente (ex. release/3.0, PHP 8.5/Symfony 7.4), en résolvant les conflits d'annotations→attributs et de promotion de propriétés, puis en vérifiant que les tests passent. À utiliser après `castor git:merge-upwards` (ou un `git merge` manuel) quand un conflit doit être résolu par une IA.
---

# Skill : Merge cross-version (release/x.y → branche cible sur version PHP/Symfony supérieure)

Ce skill documente la résolution de conflits lors du merge d'une branche corrective
(ex. `release/2.10`, PHP 7.1 / Symfony 4.4) dans une branche cible plus récente
(ex. `release/3.0`, PHP 8.5 / Symfony 7.4). Les commits apportent des correctifs
fonctionnels ; la branche cible a déjà migré la syntaxe (attributs PHP 8,
promotion de propriétés, PHPUnit 11). Le but : appliquer les correctifs sans
régresser la modernisation déjà faite sur la cible.

## Point d'entrée : ne pas réinventer la mécanique Git

Le dépôt fournit déjà l'orchestration de la cascade de merge :

```bash
castor git:merge-upwards            # démarre/poursuit la cascade release/x.y → ... → develop
castor git:merge-upwards --continue # reprend après résolution manuelle d'un conflit
```

Cette commande s'arrête sur conflit et génère un fichier `.merge-upwards-copilot-context.txt`
avec le contenu brut des fichiers en conflit. **Ce skill sert à traiter ce point d'arrêt** :
résoudre les conflits listés, puis relancer la commande. Si aucune cascade n'est en cours
(merge ponctuel demandé explicitement), utiliser directement :

```bash
cd prevarisc-migration   # dépôt indépendant, toujours se placer dedans avant de committer
git switch <branche-cible>
git merge --no-ff --no-commit <branche-source>
```

## Étape 1 — Identifier le contexte avant de résoudre

```bash
git merge-base <branche-source> <branche-cible>
git log --oneline <branche-source> ^$(git merge-base <branche-source> <branche-cible>)
```

Vérifier que la base de fusion correspond bien à la dernière version déjà intégrée
(ex. tag `vX.Y.Z-1`) : si ce n'est pas le cas, une cascade intermédiaire a peut-être été
sautée — le signaler avant de continuer.

## Étape 2 — Résoudre chaque fichier en conflit

Traiter chaque hunk conflictuel en appliquant le correctif fonctionnel de la branche
source **avec la syntaxe déjà en vigueur sur la branche cible**. Ne jamais reculer la
cible vers l'ancienne syntaxe.

### Patron 1 — Annotations Doctrine/Route/PHPUnit → Attributs

```php
// 🚫 Coté branche source (legacy), à ne PAS réintroduire
/**
 * @Route("/api/x", name="api_x", methods={"GET"}, options={"expose"=true})
 */
#[Route(path: '/api/x', name: 'api_x', methods: ['GET'])]

// ✅ Garder uniquement l'attribut déjà présent côté cible ; supprimer le bloc
// annotation devenu redondant (souvent un simple doublon dans le commentaire).
```

Même logique pour `@ORM\Column(...)` (legacy) vs `#[ORM\Column(...)]` (cible) et
`@dataProvider methodName` (legacy, méthode d'instance) vs `#[DataProvider('methodName')]`
(PHPUnit ≥ 10, **la méthode doit devenir `static`**).

### Patron 2 — Constructeur : propriétés promues vs propriétés classiques

Quand la branche source ajoute une nouvelle dépendance au constructeur (nouveau fix),
et que la cible utilise déjà la promotion de propriétés PHP 8 :

```php
// 🚫 Côté source (ajout d'une dépendance, style legacy)
private $nouvelleDependance;

public function __construct(A $a, B $b, NouvelleDependance $nouvelleDependance)
{
    $this->a = $a;
    $this->b = $b;
    $this->nouvelleDependance = $nouvelleDependance;
}

// ✅ Fusionné : garder la promotion de la cible, ajouter le nouveau paramètre promu
public function __construct(
    private readonly A $a,
    private readonly B $b,
    private readonly NouvelleDependance $nouvelleDependance,
) {
}
```

**Toujours vérifier l'usage réel** (`grep -n nouvelleDependance` dans le fichier) avant
de l'ajouter : confirmer qu'elle est bien utilisée plus loin (sinon c'est un reliquat
de refactor à ignorer).

### Patron 3 — Nullable / contraintes assouplies (fix fonctionnel pur)

```php
// Un fix du type "le champ ne devrait pas être obligatoire" :
// 🚫 Avant (cible, avant fix)      nullable: false
// ✅ Après (source, fix appliqué)  nullable: true
// → reporter nullable: true en gardant la syntaxe attribut de la cible.
```

### Patron 4 — Fichiers de configuration versionnés (composer.json, package.json, framework.php)

- Le champ `version` (composer.json/package.json) : garder celui de la **branche cible**
  (ex. `3.0.0-rc.1`), jamais celui de la source.
- Les nouvelles clés de config ajoutées par un fix (ex. `assets.version` pour
  l'invalidation de cache) : les conserver, en adaptant leur valeur au numéro de version
  de la cible plutôt que de copier tel quel celui de la source.

## Étape 3 — Vérifier qu'aucun marqueur ne subsiste

```bash
grep -rl "^<<<<<<<\|^=======\|^>>>>>>>" --include="*.php" --include="*.twig" --include="*.json" . | grep -v vendor
git diff --cached --name-only --diff-filter=U   # doit être vide
```

## Étape 4 — Committer le merge

```bash
git add -A
git commit --no-edit     # ou -m "Merge branch '<source>' into <cible>"
```

⚠️ Ne jamais faire `git stash` / créer un worktree parallèle **après** avoir résolu les
conflits sans re-stager immédiatement (`git add -A`) juste avant de committer : un
`stash pop` ou un changement de HEAD entre-temps peut désynchroniser l'index du
répertoire de travail et produire un commit de merge vide. Toujours vérifier après coup :

```bash
git diff HEAD --stat   # doit être vide juste après le commit
```

## Étape 5 — Valider (tests, analyse statique, style)

```bash
castor symfony:test
castor symfony:analyse
castor symfony:cs
```

Si l'environnement Docker (`compose.dev.yaml`) tourne sur une version PHP différente de
celle déclarée par la branche cible (ex. conteneur en PHP 7.1 alors que la cible requiert
PHP 8.5), les tests via castor échoueront pour une raison d'infrastructure et non de
logique. Solution de repli pour valider quand même :

```bash
composer install --ignore-platform-req=php --ignore-platform-req=ext-ldap
php bin/phpunit --exclude-group functional
```

Pour les tests nécessitant la base de données, pointer temporairement vers le port exposé
du conteneur MySQL (ne jamais committer ce fichier) :

```bash
cat > .env.test.local <<'EOF'
DATABASE_URL="mysql://<user>:<password>@127.0.0.1:<port_exposé>/<db>?serverVersion=5.6&charset=utf8mb4"
EOF
php bin/phpunit --exclude-group functional
rm -f .env.test.local .php-cs-fixer.cache   # nettoyage avant de committer
```

## Étape 6 — Distinguer échecs liés au merge des échecs préexistants

Avant de corriger un test en échec, vérifier qu'il n'échoue pas déjà sur la branche
cible **avant** le merge (bug préexistant, hors périmètre) :

```bash
git worktree add /tmp/baseline-check <sha-cible-avant-merge>
cd /tmp/baseline-check && cp -r <repo>/vendor . && cp <repo>/.env .
php bin/phpunit --filter <NomDuTestEnEchec>
cd - && git worktree remove /tmp/baseline-check --force
```

- Si l'échec est déjà présent sur la baseline : le documenter dans le récapitulatif,
  ne pas le corriger (hors périmètre du merge, sauf si trivialement couplé à un fichier
  touché par la fusion).
- Si l'échec apparaît uniquement après le merge : c'est un vrai conflit de compatibilité
  à corriger (le plus souvent : test apporté par la source utilisant une syntaxe legacy
  PHPUnit incompatible avec la version installée côté cible — voir Patron 1).

## Checklist finale avant de livrer

- [ ] Aucun marqueur de conflit résiduel
- [ ] `composer.json`/`package.json` : version = celle de la branche cible
- [ ] Toutes les nouvelles dépendances de constructeur ajoutées par la source sont
      promues en lecture seule (`private readonly`), pas réintroduites en propriétés
      classiques
- [ ] Toutes les annotations legacy dupliquées par erreur (Route, ORM, dataProvider)
      sont supprimées au profit des attributs déjà en place côté cible
- [ ] `git diff HEAD --stat` vide juste après le commit (pas de commit de merge vide)
- [ ] Tests, PHPStan et CS-Fixer relancés ; tout échec résiduel est identifié comme
      préexistant (avec preuve) ou corrigé
- [ ] Fichiers temporaires de validation (`.env.test.local`, `.php-cs-fixer.cache`)
      supprimés avant de committer
