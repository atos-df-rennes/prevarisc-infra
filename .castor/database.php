<?php

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use function Castor\capture;
use function Castor\context;
use function Castor\finder;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'shell', namespace: 'database', description: 'Open an interactive shell inside the db container')]
function dbShell(): void
{
    io()->title('Opening shell in db container.');

    run(
        ['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'db', 'bash'],
        context: context()->withTty()
    );
}

#[AsTask(name: 'dump', namespace: 'database', description: 'Make Prevarisc dump from db container to local host (WSL)')]
function makeDump(
    #[ASOption(name: 'container', description: 'Nom du conteneur MySQL source')]
    string $container = 'prevarisc-infra-db-1',
    #[AsOption(name: 'database', description: 'Base source (sélection interactive si omise)')]
    ?string $database = null
): void
{
    io()->title('Making Prevarisc dump.');

    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/', $container)) {
        io()->error(sprintf('Nom de conteneur invalide : "%s". Seuls les caractères alphanumériques, tirets, underscores et points sont autorisés.', $container));

        return;
    }

    $database = selectDumpDatabase($container, $database);
    io()->info('Dumping database: '.$database);

    $dumpName = 'dump_sql/prevarisc_'.MARIADB_IDENTIFIER.'_'.date('YmdHis').'.sql';
    run('docker exec -w / -i '.escapeshellarg($container).' mariadb-dump -u root -p"planmusique" -- '.escapeshellarg($database).' > '.escapeshellarg($dumpName));

    io()->success('Dump made successfully.');
}

#[AsTask(name: 'load', namespace: 'database', description: 'Load a selected Prevarisc dump from local host (WSL) to db container')]
function loadDump(
    #[ASOption(name: 'container', description: 'Nom du conteneur MySQL cible')]
    string $container = 'prevarisc-infra-db-1',
    #[AsOption(name: 'database', description: 'Base cible (sélection interactive si omise)')]
    ?string $database = null
): void {
    io()->title('Loading Prevarisc dump.');

    io()->text('Searching dump files (ordered by modification time, newest first).');
    $allSqlDumps = finder()->files()->in(dirname(__DIR__).'/dump_sql')->depth('== 0')->name('*.sql')->sortByModifiedTime()->reverseSorting();

    // Filter out dumps containing the opposite database identifier
    $sqlDumpPaths = array_filter(
        iterator_to_array($allSqlDumps),
        fn ($file) => !str_contains($file->getFilename(), MYSQL_IDENTIFIER)
    );

    if (empty($sqlDumpPaths)) {
        io()->error('No compatible dump file found.');

        return;
    }

    if (count($sqlDumpPaths) === 1) {
        $dumpPath = reset($sqlDumpPaths);
    } else {
        $dumpPaths = array_values($sqlDumpPaths);
        $dumpNames = array_map(fn ($sqlDump) => $sqlDump->getFilename(), $dumpPaths);

        $selectedFileName = io()->choice('Please choose which dump file you want to load', $dumpNames, 0);
        $selectedIndex = array_search($selectedFileName, $dumpNames, true);
        $dumpPath = $dumpPaths[$selectedIndex];
    }

    $dumpName = $dumpPath->getFilename();
    $dumpFullPath = (string) $dumpPath;
    $database = selectDumpDatabase($container, $database);
    io()->info('Loading dump file: '.$dumpName.' into database: '.$database);

    run('docker exec -i '.escapeshellarg($container).' mariadb -u root -p"planmusique" -- '.escapeshellarg($database).' < '.escapeshellarg($dumpFullPath));

    io()->success('Dump loaded successfully.');
}

function selectDumpDatabase(string $container, ?string $database): string
{
    if ($database !== null) {
        return $database;
    }

    $output = capture([
        'docker', 'exec', $container, 'mariadb', '-u', 'root', '-pplanmusique',
        '--batch', '--skip-column-names', '--execute', 'SHOW DATABASES',
    ]);
    $databases = array_values(array_filter(
        explode("\n", trim($output)),
        static fn (string $name): bool => $name !== '' && !in_array($name, ['information_schema', 'mysql', 'performance_schema', 'sys'], true)
    ));

    if ($databases === []) {
        throw new RuntimeException('No database available in container: '.$container);
    }

    $default = array_search('PRV_prevarisc_v2', $databases, true);

    return io()->choice('Please choose the database', $databases, $default === false ? 0 : $default);
}