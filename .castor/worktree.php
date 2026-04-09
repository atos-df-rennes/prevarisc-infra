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

// ─────────────────────────────────────────────────────────────────────────────
// Tâches publiques
// ─────────────────────────────────────────────────────────────────────────────

#[AsTask(name: 'create', namespace: 'worktree', description: 'Crée un nouveau worktree pour travailler en parallèle')]
function worktreeCreate(): void
{
    io()->title('Création d\'un nouveau worktree');

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

    $repos = io()->choice(
        'Dépôts concernés',
        ['Les deux (prevarisc + prevarisc-migration)', 'prevarisc uniquement', 'prevarisc-migration uniquement'],
        'Les deux (prevarisc + prevarisc-migration)'
    );

    $createPrevarisc = 'prevarisc-migration uniquement' !== $repos;
    $createMigration = 'prevarisc uniquement' !== $repos;

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

    if (io()->confirm('Configurer et basculer vers ce worktree maintenant ?', true)) {
        doWorktreeSetup($name);
    }
}

#[AsTask(name: 'setup', namespace: 'worktree', description: 'Configure un worktree et bascule le conteneur vers celui-ci')]
function worktreeSetup(): void
{
    io()->title('Configuration d\'un worktree');

    $sessions = getWorktreeSessions();

    if ([] === $sessions) {
        io()->warning('Aucun worktree disponible. Utilisez "castor worktree:create" pour en créer un.');

        return;
    }

    $session = selectWorktreeSession($sessions);
    doWorktreeSetup($session['name']);
}

#[AsTask(name: 'switch', namespace: 'worktree', description: 'Bascule le conteneur entre le dépôt principal et un worktree existant')]
function worktreeSwitch(): void
{
    io()->title('Basculer vers un worktree ou le dépôt principal');

    $sessions = getWorktreeSessions();
    $mainLabel = 'Dépôt principal (prevarisc + prevarisc-migration)';
    $choices = [$mainLabel];

    foreach ($sessions as $s) {
        $repos = getSessionRepoLabels($s);
        $choices[] = sprintf('Worktree : %s (%s)', $s['name'], implode(' + ', $repos));
    }

    $choice = io()->choice('Vers quoi souhaitez-vous basculer ?', $choices, 0);

    if ($choice === $mainLabel) {
        switchContainer(null, null);
        io()->success('Basculé vers le dépôt principal.');

        return;
    }

    foreach ($sessions as $s) {
        if (str_contains($choice, $s['name'])) {
            doWorktreeSetup($s['name']);

            return;
        }
    }
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
            run(['git', '-C', 'prevarisc', 'branch', '-d', $session['branch']]);
        }
    }

    if ($session['migration']) {
        io()->section('Suppression du worktree prevarisc-migration.');
        run(['git', '-C', 'prevarisc-migration', 'worktree', 'remove', ...$forceFlag, '../prevarisc-migration-worktree-'.$name]);
        if (null !== $session['branch']) {
            run(['git', '-C', 'prevarisc-migration', 'branch', '-d', $session['branch']]);
        }
    }

    io()->success(sprintf('Worktree "%s" supprimé.', $name));

    askAndSwitch($name);
}

// ─────────────────────────────────────────────────────────────────────────────
// Fonctions internes
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Configure un worktree (copie des fichiers, installation des dépendances)
 * et bascule le conteneur applicatif vers celui-ci.
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

    switchContainer($prevarisc, $migration);

    io()->section('Installation des dépendances Composer.');

    $tasks = [];

    if ($session['prevarisc']) {
        $tasks[] = static function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'app', 'composer', 'install']);
        };
    }

    if ($session['migration']) {
        $tasks[] = static function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'composer', 'install']);
        };
    }

    if (2 === count($tasks)) {
        parallel(...$tasks);
    } elseif (1 === count($tasks)) {
        ($tasks[0])();
    }

    io()->success(sprintf('Worktree "%s" configuré et actif.', $name));
}

/**
 * Redémarre le conteneur applicatif en le pointant vers le(s) répertoire(s) voulu(s).
 * Passer null pour les deux paramètres revient au dépôt principal.
 */
function switchContainer(?string $prevarisc, ?string $migration): void
{
    io()->section('Redémarrage du conteneur applicatif.');

    $env = [];
    $files = ['--file', 'compose.dev.yaml'];

    if (null !== $prevarisc || null !== $migration) {
        array_push($files, '--file', 'compose.worktree.yaml');
        if (null !== $prevarisc) {
            $env['PREVARISC_DIR'] = $prevarisc;
        }
        if (null !== $migration) {
            $env['PREVARISC_MIGRATION_DIR'] = $migration;
        }
    }

    $ctx = [] !== $env ? context()->withEnvironment($env) : null;
    exit_code(['docker', 'compose', ...$files, 'up', '-d', '--no-deps', 'app'], $ctx);

    wait_for_docker_container(
        containerName: 'prevarisc-infra-app-1',
        message: 'Attente du démarrage du conteneur applicatif.'
    );
}

/**
 * Propose à l'utilisateur de basculer vers le dépôt principal ou un autre worktree disponible.
 * Appelé en fin de tâche pour améliorer la DX.
 */
function askAndSwitch(string $excludeName): void
{
    $sessions = getWorktreeSessions();
    $remaining = array_values(array_filter($sessions, static fn (array $s) => $s['name'] !== $excludeName));

    $mainLabel = 'Dépôt principal (prevarisc + prevarisc-migration)';
    $choices = [$mainLabel];

    foreach ($remaining as $s) {
        $repos = getSessionRepoLabels($s);
        $choices[] = sprintf('Worktree : %s (%s)', $s['name'], implode(' + ', $repos));
    }

    if (1 === count($choices)) {
        io()->text('Retour automatique au dépôt principal.');
        switchContainer(null, null);
        io()->success('Basculé vers le dépôt principal.');

        return;
    }

    $choice = io()->choice('Où souhaitez-vous basculer ?', $choices, 0);

    if ($choice === $mainLabel) {
        switchContainer(null, null);
        io()->success('Basculé vers le dépôt principal.');

        return;
    }

    foreach ($remaining as $s) {
        if (str_contains($choice, $s['name'])) {
            switchContainer(
                $s['prevarisc'] ? 'prevarisc-worktree-'.$s['name'] : null,
                $s['migration'] ? 'prevarisc-migration-worktree-'.$s['name'] : null
            );
            io()->success(sprintf('Basculé vers le worktree "%s".', $s['name']));

            return;
        }
    }
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
