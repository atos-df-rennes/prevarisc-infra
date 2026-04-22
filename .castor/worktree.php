<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;
use function Castor\context;
use function Castor\exit_code;
use function Castor\fs;
use function Castor\io;
use function Castor\parallel;
use function Castor\run;
use function Castor\wait_for_docker_container;

const WORKTREE_PROJECT = 'worktree';
const WORKTREE_COMPOSE_FILE = 'compose.worktree-standalone.yaml';
const WORKTREE_APP_CONTAINER = 'worktree-app-1';
const WORKTREE_DB_CONTAINER = 'worktree-db-1';
const WORKTREE_PLATAU_CONTAINER = 'worktree-platau-1';

// ─────────────────────────────────────────────────────────────────────────────
// Tâches publiques
// ─────────────────────────────────────────────────────────────────────────────

#[AsTask(name: 'list', namespace: 'worktree', description: 'Liste les worktrees actifs')]
function worktreeList(): void
{
    io()->title('Worktrees actifs');

    $sessions = getWorktreeSessions();

    if ([] === $sessions) {
        io()->warning('Aucun worktree actif. Utilisez "castor worktree:create" pour en créer un.');

        return;
    }

    $rows = [];
    foreach ($sessions as $s) {
        $repos = getSessionRepoLabels($s);
        $rows[] = [$s['name'], implode(' + ', $repos), $s['branch'] ?? '—'];
    }

    io()->table(['Nom', 'Dépôts', 'Branche'], $rows);
}

#[AsTask(name: 'create', namespace: 'worktree', description: 'Crée un nouveau worktree pour travailler en parallèle')]
function worktreeCreate(): void
{
    io()->title('Création d\'un nouveau worktree');

    $mode = io()->choice(
        'Objectif',
        ['Nouvelle branche (développement)', 'Branche existante (relecture)'],
        'Nouvelle branche (développement)'
    );

    $repos = io()->choice(
        'Dépôts concernés',
        ['Les deux (prevarisc + prevarisc-migration)', 'prevarisc uniquement', 'prevarisc-migration uniquement'],
        'Les deux (prevarisc + prevarisc-migration)'
    );

    $createPrevarisc = 'prevarisc-migration uniquement' !== $repos;
    $createMigration = 'prevarisc uniquement' !== $repos;

    if ('Branche existante (relecture)' === $mode) {
        [$branch, $createPrevarisc, $createMigration] = selectReviewBranch($createPrevarisc, $createMigration);

        // Dérive un nom de répertoire valide depuis le nom de la branche (ex: feat/mon-fix → feat-mon-fix).
        $name = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($branch)), '-');

        if ($createPrevarisc) {
            io()->section('Création du worktree prevarisc.');
            run(['git', '-C', 'prevarisc', 'worktree', 'add', '../prevarisc-worktree-'.$name, 'origin/'.$branch]);
        }

        if ($createMigration) {
            io()->section('Création du worktree prevarisc-migration.');
            run(['git', '-C', 'prevarisc-migration', 'worktree', 'add', '../prevarisc-migration-worktree-'.$name, 'origin/'.$branch]);
        }

        io()->success(sprintf('Worktree de relecture "%s" créé sur la branche "%s".', $name, $branch));

        if (io()->confirm('Démarrer la stack Docker du worktree maintenant ?', true)) {
            doWorktreeSetup($name);
        }

        return;
    }

    $type = io()->choice(
        'Type de modification',
        ['feat', 'fix', 'refactor', 'docs', 'chore', 'test'],
        'feat'
    );

    $name = io()->ask(
        'Nom de la feature (kebab-case, ex : dossiers-incomplets)',
        null,
        function (?string $value): string {
            if (null === $value || '' === $value) {
                throw new \RuntimeException('Le nom ne peut pas être vide.');
            }
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
                throw new \RuntimeException('Le nom doit être en kebab-case (lettres minuscules et chiffres séparés par des tirets).');
            }

            return $value;
        }
    );

    [$baseBranch, $createPrevarisc, $createMigration] = selectBaseBranch($createPrevarisc, $createMigration);

    $branch = $type.'/'.$name;

    if ($createPrevarisc) {
        io()->section('Création du worktree prevarisc.');
        run(['git', '-C', 'prevarisc', 'worktree', 'add', '../prevarisc-worktree-'.$name, '-b', $branch, $baseBranch]);
    }

    if ($createMigration) {
        io()->section('Création du worktree prevarisc-migration.');
        run(['git', '-C', 'prevarisc-migration', 'worktree', 'add', '../prevarisc-migration-worktree-'.$name, '-b', $branch, $baseBranch]);
    }

    io()->success(sprintf('Worktree(s) créé(s) sur la branche "%s".', $branch));

    if (io()->confirm('Démarrer la stack Docker du worktree maintenant ?', true)) {
        doWorktreeSetup($name);
    }
}

#[AsTask(name: 'start', namespace: 'worktree', description: 'Démarre la stack Docker isolée pour un worktree existant')]
function worktreeStart(): void
{
    io()->title('Démarrage de la stack worktree.');

    $sessions = getWorktreeSessions();

    if ([] === $sessions) {
        io()->warning('Aucun worktree actif. Utilisez "castor worktree:create" pour en créer un.');

        return;
    }

    $session = selectWorktreeSession($sessions);
    doWorktreeSetup($session['name']);
}

#[AsTask(name: 'stop', namespace: 'worktree', description: 'Arrête la stack Docker isolée du worktree')]
function worktreeStop(): void
{
    io()->title('Arrêt de la stack worktree.');

    exit_code(['docker', 'compose', '--project-name', WORKTREE_PROJECT, '--file', WORKTREE_COMPOSE_FILE, 'down']);

    io()->success('Stack worktree arrêtée.');
}

#[AsTask(name: 'remove', namespace: 'worktree', description: 'Nettoie un worktree et supprime la branche associée')]
function worktreeRemove(
    #[ASOption(name: 'force', mode: InputOption::VALUE_NONE, description: 'Force la suppression même en cas de modifications non commitées')]
    bool $force
): void {
    io()->title('Suppression d\'un worktree');

    $sessions = getWorktreeSessions();

    if ([] === $sessions) {
        io()->warning('Aucun worktree disponible.');

        return;
    }

    $session = selectWorktreeSession($sessions);
    $name = $session['name'];
    $forceFlag = $force ? ['--force'] : [];

    if ($session['prevarisc']) {
        io()->section('Suppression du worktree prevarisc.');
        run(['git', '-C', 'prevarisc', 'worktree', 'remove', ...$forceFlag, '../prevarisc-worktree-'.$name]);
        if (null !== $session['branch']) {
            run(['git', '-C', 'prevarisc', 'branch', '-D', $session['branch']]);
        }
    }

    if ($session['migration']) {
        io()->section('Suppression du worktree prevarisc-migration.');
        run(['git', '-C', 'prevarisc-migration', 'worktree', 'remove', ...$forceFlag, '../prevarisc-migration-worktree-'.$name]);
        if (null !== $session['branch']) {
            run(['git', '-C', 'prevarisc-migration', 'branch', '-D', $session['branch']]);
        }
    }

    io()->success(sprintf('Worktree "%s" supprimé.', $name));

    if (io()->confirm('Arrêter la stack worktree si elle est en cours d\'exécution ?', true)) {
        exit_code(['docker', 'compose', '--project-name', WORKTREE_PROJECT, '--file', WORKTREE_COMPOSE_FILE, 'down']);
    }
}

#[AsTask(name: 'analyse', namespace: 'worktree', description: 'Lance PHPStan sur le worktree actif')]
function worktreeAnalyse(): void
{
    io()->title('Analysing worktree application');

    $composeArgs = ['docker', 'compose', '--project-name', WORKTREE_PROJECT, '--file', WORKTREE_COMPOSE_FILE];

    io()->section('Executing PHPStan');
    run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/phpstan', '--memory-limit=-1']);
}

#[AsTask(name: 'cs', namespace: 'worktree', description: 'Corrige le style de code du worktree actif (Rector + PHP-CS-Fixer + Twig-CS-Fixer)')]
function worktreeCs(
    #[ASOption(name: 'dry-run', mode: InputOption::VALUE_NONE, description: 'Lance les linters sans modifications')]
    bool $dryRun
): void {
    io()->title('Checking coding style for worktree application');

    $composeArgs = ['docker', 'compose', '--project-name', WORKTREE_PROJECT, '--file', WORKTREE_COMPOSE_FILE];
    $options = [];
    $rectorTitle = 'Executing Rector';
    $phpCsFixerTitle = 'Executing PHP-CS-FIXER';
    $twigCsFixerTitle = 'Executing TWIG-CS-FIXER';
    $optionsTwigCsFixer = ['fix'];

    if (true === $dryRun) {
        $options = ['--dry-run'];
        $rectorTitle .= ' with '.implode(', ', $options);
        $phpCsFixerTitle .= ' with '.implode(', ', $options);
        $twigCsFixerTitle .= ' with '.implode(', ', $options);
        $optionsTwigCsFixer = ['check'];
    }

    io()->section($rectorTitle);
    exit_code([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/rector', ...$options]);

    io()->section($phpCsFixerTitle);
    exit_code([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix', ...$options]);

    io()->section($twigCsFixerTitle);
    exit_code([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/twig-cs-fixer', ...$optionsTwigCsFixer]);
}

#[AsTask(name: 'test', namespace: 'worktree', description: 'Exécute les tests PHPUnit sur le worktree actif')]
function worktreeTest(
    #[ASOption(name: 'all', mode: InputOption::VALUE_NONE, description: 'Lance la suite complète de tests incluant les tests fonctionnels')]
    bool $all
): void {
    io()->title('Testing worktree application');

    $composeArgs = ['docker', 'compose', '--project-name', WORKTREE_PROJECT, '--file', WORKTREE_COMPOSE_FILE];

    io()->section('Executing PHPUnit');

    $options = [];
    if (false === $all) {
        $options = ['--exclude-group', 'functional'];
    }

    run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'php', 'bin/phpunit', '--testdox', ...$options]);
}

#[AsTask(name: 'validate', namespace: 'worktree', description: "Validation complète du worktree (PHPStan + CS dry-run + tests) — s'arrête au premier échec")]
function worktreeValidate(): void
{
    io()->title('Validating worktree application');

    $composeArgs = ['docker', 'compose', '--project-name', WORKTREE_PROJECT, '--file', WORKTREE_COMPOSE_FILE];

    io()->section('Step 1/3 — PHPStan');
    run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/phpstan', '--memory-limit=-1']);

    io()->section('Step 2/3 — Rector (dry-run)');
    run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/rector', '--dry-run']);

    io()->section('Step 2/3 — PHP-CS-Fixer (dry-run)');
    run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix', '--dry-run']);

    io()->section('Step 2/3 — Twig-CS-Fixer (check)');
    run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/twig-cs-fixer', 'check']);

    io()->section('Step 3/3 — PHPUnit');
    run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'php', 'bin/phpunit', '--testdox', '--exclude-group', 'functional']);

    io()->success('All checks passed!');
}

// ─────────────────────────────────────────────────────────────────────────────
// Fonctions internes
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Configure un worktree (copie des fichiers, installation des dépendances)
 * et démarre la stack Docker isolée pour ce worktree.
 */
function doWorktreeSetup(string $name): void
{
    $sessions = getWorktreeSessions();
    $session = null;

    foreach ($sessions as $s) {
        if ($s['name'] === $name) {
            $session = $s;
            break;
        }
    }

    if (null === $session) {
        io()->error(sprintf('Le worktree "%s" est introuvable.', $name));

        return;
    }

    io()->section('Copie des fichiers de configuration.');

    if ($session['prevarisc']) {
        $dest = 'prevarisc-worktree-'.$name.'/application/plugins/HashedFileDataStore.php';
        if (!fs()->exists($dest)) {
            io()->text('Copie de HashedFileDataStore.');
            fs()->copy('app/HashedFileDataStore.php', $dest);
        } else {
            io()->text('HashedFileDataStore déjà présent.');
        }
    }

    if ($session['migration']) {
        $dest = 'prevarisc-migration-worktree-'.$name.'/.env';
        if (!fs()->exists($dest)) {
            io()->text('Copie du fichier .env.');
            fs()->copy('prevarisc-migration/.env', $dest);
        } else {
            io()->text('Fichier .env déjà présent.');
        }
    }

    $prevarisc = $session['prevarisc'] ? 'prevarisc-worktree-'.$name : null;
    $migration = $session['migration'] ? 'prevarisc-migration-worktree-'.$name : null;

    startWorktreeStack($prevarisc, $migration);

    if (io()->confirm('Seeder la base de données du worktree depuis un dump ?', true)) {
        seedWorktreeDb();
    }

    io()->section('Installation des dépendances Composer.');

    $composeArgs = ['docker', 'compose', '--project-name', WORKTREE_PROJECT, '--file', WORKTREE_COMPOSE_FILE];
    $tasks = [];

    if ($session['prevarisc']) {
        $tasks[] = static function () use ($composeArgs): void {
            run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc', 'app', 'composer', 'install']);
        };
    }

    if ($session['migration']) {
        $tasks[] = static function () use ($composeArgs): void {
            run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'composer', 'install']);
        };

        $tasks[] = static function () use ($composeArgs): void {
            run([...$composeArgs, 'exec', '-w', '/var/www/html/prevarisc-migration/tools', 'platau', 'composer', 'install']);
        };
    }

    if (count($tasks) > 1) {
        parallel(...$tasks);
    } elseif (1 === count($tasks)) {
        ($tasks[0])();
    }

    io()->success(sprintf('Worktree "%s" démarré → http://localhost:7081', $name));
}

/**
 * Démarre la stack Docker isolée du worktree.
 */
function startWorktreeStack(?string $prevarisc, ?string $migration): void
{
    io()->section('Démarrage de la stack worktree isolée.');

    $env = [];

    if (null !== $prevarisc) {
        $env['PREVARISC_DIR'] = $prevarisc;
    }

    if (null !== $migration) {
        $env['PREVARISC_MIGRATION_DIR'] = $migration;
    }

    $ctx = [] !== $env ? context()->withEnvironment($env) : null;
    exit_code(['docker', 'compose', '--project-name', WORKTREE_PROJECT, '--file', WORKTREE_COMPOSE_FILE, 'up', '-d'], $ctx);

    wait_for_docker_container(
        containerName: WORKTREE_APP_CONTAINER,
        message: 'Attente du démarrage du conteneur applicatif du worktree.'
    );
}

/**
 * Propose de créer un dump frais puis le charge dans la DB du worktree.
 */
function seedWorktreeDb(): void
{
    io()->section('Seeding de la base de données du worktree.');

    if (io()->confirm('Créer un nouveau dump depuis la DB principale d\'abord ?', false)) {
        makeDump();
    }

    loadDump(WORKTREE_DB_CONTAINER);
}

// ─────────────────────────────────────────────────────────────────────────────
// Helpers de détection des worktrees
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Retourne la liste des sessions de worktree disponibles.
 * Une session regroupe un worktree prevarisc et/ou prevarisc-migration par nom de feature.
 *
 * @return array<int, array{name: string, prevarisc: bool, migration: bool, branch: string|null}>
 */
function getWorktreeSessions(): array
{
    $sessions = [];

    foreach (parseGitWorktrees('prevarisc') as $worktree) {
        $basename = basename($worktree['path']);
        if (!str_starts_with($basename, 'prevarisc-worktree-')) {
            continue;
        }
        $name = substr($basename, \strlen('prevarisc-worktree-'));
        if (!isset($sessions[$name])) {
            $sessions[$name] = ['name' => $name, 'prevarisc' => false, 'migration' => false, 'branch' => null];
        }
        $sessions[$name]['prevarisc'] = true;
        $sessions[$name]['branch'] = $sessions[$name]['branch'] ?? $worktree['branch'];
    }

    foreach (parseGitWorktrees('prevarisc-migration') as $worktree) {
        $basename = basename($worktree['path']);
        if (!str_starts_with($basename, 'prevarisc-migration-worktree-')) {
            continue;
        }
        $name = substr($basename, \strlen('prevarisc-migration-worktree-'));
        if (!isset($sessions[$name])) {
            $sessions[$name] = ['name' => $name, 'prevarisc' => false, 'migration' => false, 'branch' => null];
        }
        $sessions[$name]['migration'] = true;
        $sessions[$name]['branch'] = $sessions[$name]['branch'] ?? $worktree['branch'];
    }

    return array_values($sessions);
}

/**
 * Parse la sortie de `git worktree list --porcelain` pour un dépôt donné.
 * Exclut le worktree principal (premier de la liste).
 *
 * @return array<int, array{path: string, branch: string|null}>
 */
function parseGitWorktrees(string $repoDir): array
{
    $output = [];
    exec('git -C '.escapeshellarg($repoDir).' worktree list --porcelain 2>/dev/null', $output);

    $worktrees = [];
    $current = [];

    foreach ($output as $line) {
        if ('' === $line) {
            if ([] !== $current) {
                $worktrees[] = $current;
            }
            $current = [];
            continue;
        }

        if (str_starts_with($line, 'worktree ')) {
            $current['path'] = substr($line, \strlen('worktree '));
            $current['branch'] = null;
        } elseif (str_starts_with($line, 'branch refs/heads/')) {
            $current['branch'] = substr($line, \strlen('branch refs/heads/'));
        }
    }

    if ([] !== $current) {
        $worktrees[] = $current;
    }

    // Le premier worktree est toujours le dépôt principal.
    array_shift($worktrees);

    return $worktrees;
}

/**
 * Affiche une liste de choix et retourne la session sélectionnée.
 * Si une seule session est disponible, elle est sélectionnée automatiquement.
 *
 * @param array<int, array{name: string, prevarisc: bool, migration: bool, branch: string|null}> $sessions
 *
 * @return array{name: string, prevarisc: bool, migration: bool, branch: string|null}
 */
function selectWorktreeSession(array $sessions): array
{
    if (1 === count($sessions)) {
        $session = $sessions[0];
        $repos = getSessionRepoLabels($session);
        io()->text(sprintf('Worktree sélectionné : <info>%s</info> (%s).', $session['name'], implode(' + ', $repos)));

        return $session;
    }

    $choices = [];
    foreach ($sessions as $session) {
        $repos = getSessionRepoLabels($session);
        $choices[] = sprintf('%s (%s)', $session['name'], implode(' + ', $repos));
    }

    $choice = io()->choice('Sélectionnez le worktree', $choices);
    $index = array_search($choice, $choices, true);

    return $sessions[$index];
}

/**
 * Retourne les libellés des dépôts d'une session.
 *
 * @param array{prevarisc: bool, migration: bool} $session
 *
 * @return string[]
 */
function getSessionRepoLabels(array $session): array
{
    $repos = [];
    if ($session['prevarisc']) {
        $repos[] = 'prevarisc';
    }
    if ($session['migration']) {
        $repos[] = 'migration';
    }

    return $repos;
}

// ─────────────────────────────────────────────────────────────────────────────
// Helpers de sélection de la branche de base
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Interroge les dépôts concernés, présente la liste des branches disponibles
 * et retourne la branche sélectionnée ainsi que les flags de création (ajustés
 * si la branche choisie n'existe pas dans l'un des dépôts).
 *
 * @return array{0: string, 1: bool, 2: bool}  [branche, createPrevarisc, createMigration]
 */
function selectBaseBranch(bool $createPrevarisc, bool $createMigration): array
{
    // Récupère les branches filtrées pour chaque dépôt concerné.
    $prevarisc = $createPrevarisc ? getFilteredRemoteBranches('prevarisc') : [];
    $migration = $createMigration ? getFilteredRemoteBranches('prevarisc-migration') : [];

    // Construit le catalogue : branche → présence dans chaque dépôt.
    /** @var array<string, array{prevarisc: bool, migration: bool}> $catalog */
    $catalog = [];
    foreach ($prevarisc as $branch) {
        $catalog[$branch] = ['prevarisc' => true, 'migration' => false];
    }
    foreach ($migration as $branch) {
        if (!isset($catalog[$branch])) {
            $catalog[$branch] = ['prevarisc' => false, 'migration' => false];
        }
        $catalog[$branch]['migration'] = true;
    }

    if ([] === $catalog) {
        io()->warning('Impossible de détecter les branches distantes. La branche release/2.8 sera utilisée par défaut.');

        return ['release/2.8', $createPrevarisc, $createMigration];
    }

    // Trie : release/* décroissant, puis develop, puis le reste.
    uksort($catalog, static function (string $a, string $b): int {
        $isRelease = static fn (string $s) => str_starts_with($s, 'release/');

        if ($isRelease($a) && $isRelease($b)) {
            return version_compare(
                substr($b, \strlen('release/')),
                substr($a, \strlen('release/'))
            );
        }
        if ($isRelease($a)) {
            return -1;
        }
        if ($isRelease($b)) {
            return 1;
        }
        if ('develop' === $a) {
            return -1;
        }
        if ('develop' === $b) {
            return 1;
        }

        return strcmp($a, $b);
    });

    $both = $createPrevarisc && $createMigration;

    // Construit les libellés affichés.
    $labels = [];
    foreach ($catalog as $branch => $info) {
        if ($both && $info['prevarisc'] !== $info['migration']) {
            $only = $info['prevarisc'] ? 'prevarisc uniquement' : 'migration uniquement';
            $labels[] = sprintf('%s  (%s)', $branch, $only);
        } else {
            $labels[] = $branch;
        }
    }

    $branchKeys = array_keys($catalog);
    $choice = io()->choice('Branche de base', $labels, 0);
    $index = array_search($choice, $labels, true);
    $selectedBranch = $branchKeys[$index];

    // Si la branche n'existe pas dans l'un des dépôts, désactive la création pour ce dépôt.
    $availability = $catalog[$selectedBranch];
    if ($createPrevarisc && !$availability['prevarisc']) {
        io()->warning(sprintf('La branche "%s" est absente de prevarisc : le worktree prevarisc ne sera pas créé.', $selectedBranch));
        $createPrevarisc = false;
    }
    if ($createMigration && !$availability['migration']) {
        io()->warning(sprintf('La branche "%s" est absente de prevarisc-migration : le worktree migration ne sera pas créé.', $selectedBranch));
        $createMigration = false;
    }

    return [$selectedBranch, $createPrevarisc, $createMigration];
}

/**
 * Retourne les branches distantes pertinentes d'un dépôt :
 * branches release/*, develop, main, 2.5.
 *
 * @return string[]
 */
function getFilteredRemoteBranches(string $repoDir): array
{
    $output = [];
    exec('git -C '.escapeshellarg($repoDir).' branch -r 2>/dev/null', $output);

    $branches = [];
    foreach ($output as $line) {
        $branch = trim(preg_replace('/^origin\//', '', trim($line)) ?? '');
        if (str_starts_with($branch, 'HEAD')) {
            continue;
        }
        if (
            \in_array($branch, ['develop', 'main', '2.5'], true)
            || str_starts_with($branch, 'release/')
        ) {
            $branches[] = $branch;
        }
    }

    return $branches;
}

/**
 * Retourne toutes les branches distantes d'un dépôt après un fetch.
 *
 * @return string[]
 */
function getAllRemoteBranches(string $repoDir): array
{
    exec('git -C '.escapeshellarg($repoDir).' fetch --quiet origin 2>/dev/null');

    $output = [];
    exec('git -C '.escapeshellarg($repoDir).' branch -r 2>/dev/null', $output);

    $branches = [];
    foreach ($output as $line) {
        $branch = trim(preg_replace('/^origin\//', '', trim($line)) ?? '');
        if (str_starts_with($branch, 'HEAD')) {
            continue;
        }
        $branches[] = $branch;
    }

    sort($branches);

    return $branches;
}

/**
 * Récupère toutes les branches distantes des dépôts concernés, propose une sélection
 * et retourne la branche choisie ainsi que les flags de création ajustés.
 *
 * @return array{0: string, 1: bool, 2: bool}  [branche, createPrevarisc, createMigration]
 */
function selectReviewBranch(bool $createPrevarisc, bool $createMigration): array
{
    io()->text('Récupération des branches distantes (fetch en cours)...');

    $prevarisc = $createPrevarisc ? getAllRemoteBranches('prevarisc') : [];
    $migration = $createMigration ? getAllRemoteBranches('prevarisc-migration') : [];

    /** @var array<string, array{prevarisc: bool, migration: bool}> $catalog */
    $catalog = [];
    foreach ($prevarisc as $branch) {
        $catalog[$branch] = ['prevarisc' => true, 'migration' => false];
    }
    foreach ($migration as $branch) {
        if (!isset($catalog[$branch])) {
            $catalog[$branch] = ['prevarisc' => false, 'migration' => false];
        }
        $catalog[$branch]['migration'] = true;
    }

    if ([] === $catalog) {
        throw new \RuntimeException('Aucune branche distante trouvée.');
    }

    $both = $createPrevarisc && $createMigration;

    $labels = [];
    foreach ($catalog as $branch => $info) {
        if ($both && $info['prevarisc'] !== $info['migration']) {
            $only = $info['prevarisc'] ? 'prevarisc uniquement' : 'migration uniquement';
            $labels[] = sprintf('%s  (%s)', $branch, $only);
        } else {
            $labels[] = $branch;
        }
    }

    $branchKeys = array_keys($catalog);
    $choice = io()->choice('Branche à relire', $labels);
    $index = array_search($choice, $labels, true);
    $selectedBranch = $branchKeys[$index];

    $availability = $catalog[$selectedBranch];
    if ($createPrevarisc && !$availability['prevarisc']) {
        io()->warning(sprintf('La branche "%s" est absente de prevarisc : le worktree prevarisc ne sera pas créé.', $selectedBranch));
        $createPrevarisc = false;
    }
    if ($createMigration && !$availability['migration']) {
        io()->warning(sprintf('La branche "%s" est absente de prevarisc-migration : le worktree migration ne sera pas créé.', $selectedBranch));
        $createMigration = false;
    }

    return [$selectedBranch, $createPrevarisc, $createMigration];
}
