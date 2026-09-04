<?php

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use function Castor\finder;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'dump', namespace: 'database', description: 'Make Prevarisc dump from db container to local host (WSL)')]
function makeDump(
    #[ASOption(name: 'container', description: 'Nom du conteneur MySQL source')]
    string $container = 'prevarisc-infra-db-1'
): void
{
    io()->title('Making Prevarisc dump.');

    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/', $container)) {
        io()->error(sprintf('Nom de conteneur invalide : "%s". Seuls les caractères alphanumériques, tirets, underscores et points sont autorisés.', $container));

        return;
    }

    $dumpName = 'dump_sql/prevarisc_'.MYSQL_IDENTIFIER.'_'.date('YmdHis').'.sql';
    run('docker exec -w / -i '.$container.' mysqldump -u root -p"planmusique" PRV_prevarisc_v2 > '.$dumpName);

    io()->success('Dump made successfully.');
}

#[AsTask(namespace: 'database', description: 'Load a selected Prevarisc dump from local host (WSL) to db container')]
function load(
    #[ASOption(name: 'container', description: 'Nom du conteneur MySQL cible')]
    string $container = 'prevarisc-infra-db-1'
): void {
    io()->title('Loading Prevarisc dump.');

    io()->text('Searching dump files (ordered by modification time, newest first).');
    $allSqlDumps = finder()->files()->in(dirname(__DIR__).'/dump_sql')->depth('== 0')->name('*.sql')->sortByModifiedTime()->reverseSorting();

    // Filter out dumps containing the opposite database identifier
    $sqlDumpPaths = array_filter(
        iterator_to_array($allSqlDumps),
        fn ($file) => !str_contains($file->getFilename(), MARIADB_IDENTIFIER)
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
    io()->info('Loading dump file: '.$dumpName);

    run('docker exec -i '.$container.' mysql -u root -p"planmusique" PRV_prevarisc_v2 < '.$dumpFullPath);

    io()->success('Dump loaded successfully.');
}