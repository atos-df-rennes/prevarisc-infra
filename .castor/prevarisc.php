<?php

use Castor\Attribute\AsTask;
use Symfony\Component\Process\ExecutableFinder;
use function Castor\check;
use function Castor\exit_code;
use function Castor\finder;
use function Castor\fs;
use function Castor\io;
use function Castor\parallel;
use function Castor\run;
use function Castor\wait_for_docker_container;

#[AsTask(namespace: 'prevarisc', description: 'Setup Prevarisc')]
function setup(): void
{
    io()->title('Setting up Prevarisc.');

    check(
        'Checking if Git is installed.',
        'Git is not installed. Please install it first.',
        fn () => (new ExecutableFinder())->find('git')
    );

    io()->section('Cloning repositories.');

    if (!fs()->exists('prevarisc')) {
        io()->text('Cloning Prevarisc repository.');
        run([
            'git',
            'clone',
            'https://github.com/atos-df-rennes/prevarisc.git'
        ]);
    } else {
        io()->text('Prevarisc repository already exists.');
    }

    if (!fs()->exists('prevarisc-migration')) {
        io()->info('You will need a Personal Access Token for the next operation. Please create one if you do not have one already.');

        $resumeScript = io()->confirm('Resume script?');
        if (!$resumeScript) {
            return;
        }

        io()->text('Cloning Prevarisc Migration repository.');
        run([
            'git',
            'clone',
            'https://github.com/atos-df-rennes/prevarisc-migration.git'
        ]);
    } else {
        io()->text('Prevarisc Migration repository already exists.');
    }

    if (!fs()->exists('prevarisc-passerelle-platau')) {
        io()->text('Cloning Prevarisc Passerelle Plat\'AU repository.');
        run([
            'git',
            'clone',
            'https://github.com/atos-df-rennes/prevarisc-passerelle-platau.git'
        ]);
    } else {
        io()->text('Prevarisc Passerelle Plat\'AU repository already exists.');
    }

    io()->section('Copying web server files.');

    if (!fs()->exists('apache/httpd-prevarisc-config.conf')) {
        io()->text('Copying configuration file.');
        fs()->copy('apache/httpd-prevarisc-config.conf.example', 'apache/httpd-prevarisc-config.conf');

        io()->info('You may want to update the configuration file with your values.');
        $continueInstallation = io()->confirm('Resume sript?');

        if (!$continueInstallation) {
            return;
        }
    } else {
        io()->text('Configuration file already exists.');
    }

    if (!fs()->exists('apache/httpd-prevarisc-version.conf')) {
        io()->text('Copying version file.');
        fs()->copy('apache/httpd-prevarisc-version.conf.example', 'apache/httpd-prevarisc-version.conf');
    } else {
        io()->text('Version file already exists.');
    }

    if (!fs()->exists('prevarisc/application/plugins/HashedFileDataStore.php')) {
        io()->text('Copying HashedFileDataStore.');
        fs()->copy('app/HashedFileDataStore.php', 'prevarisc/application/plugins/HashedFileDataStore.php');
    } else {
        io()->text('HashedFileDataStore already exists.');
    }

    io()->section('Starting Prevarisc.');

    io()->text('Starting containers.');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'build']);
    /*
     * Utilisation de la fonction exit_code() plutôt que run() pour permettre au process de fail.
     * Aucun impact sur le fonctionnement mais le post_start du fichier compose semble causer cette erreur.
     */
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'up', '--detach']);

    wait_for_docker_container(
        containerName: 'prevarisc-infra-app-1',
        message: 'Waiting for prevarisc application container to be ready.'
    );
    wait_for_docker_container(
        containerName: 'prevarisc-infra-platau-1',
        message: 'Waiting for prevarisc platau container to be ready.'
    );

    if (!fs()->exists('prevarisc-migration/.env')) {
        io()->text('Creating Symfony env file and filling it with default dev values.');
        fs()->copy('prevarisc-migration/.env.example', 'prevarisc-migration/.env');

        $envFileContent = fs()->readFile('prevarisc-migration/.env');
        $envFileContentArray = explode(PHP_EOL, $envFileContent);

        foreach ($envFileContentArray as $key => $envFileContentLine) {
            if (str_starts_with($envFileContentLine, 'APP_ENV')) {
                $envFileContentArray[$key] = 'APP_ENV=dev';
            }
            if (str_starts_with($envFileContentLine, 'APP_DEBUG')) {
                $envFileContentArray[$key] = 'APP_DEBUG=true';
            }
            if (str_starts_with($envFileContentLine, 'APP_SECRET')) {
                $envFileContentArray[$key] = 'APP_SECRET=' . bin2hex(random_bytes(16));
            }
            if (str_starts_with($envFileContentLine, 'DATABASE_URL="mysql')) {
                $envFileContentArray[$key] = 'DATABASE_URL="mysql://root:planmusique@db:3306/PRV_prevarisc_v2?serverVersion=5.6&charset=utf8mb4"';
            }
            if (str_starts_with($envFileContentLine, 'PREVARISC_PATH')) {
                $envFileContentArray[$key] = 'PREVARISC_PATH=/var/www/html/prevarisc';
            }
        }

        $envFileContent = implode(PHP_EOL, $envFileContentArray);
        file_put_contents('prevarisc-migration/.env', $envFileContent);
    } else {
        io()->text('Symfony env file already exists.');
    }

    composerInstall();
    composerInstallDev();
    symfonyMigrate();

    io()->success('Prevarisc is now ready!');
}

#[AsTask(namespace: 'prevarisc', description: 'Start Prevarisc')]
function start(): void
{
    io()->title('Starting Prevarisc.');

    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'up', '-d']);
}

#[AsTask(namespace: 'prevarisc', description: 'Stop Prevarisc')]
function stop(): void
{
    io()->title('Stoping Prevarisc.');

    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'down', '--remove-orphans']);
}

#[AsTask(namespace: 'prevarisc', description: 'Update Prevarisc')]
function update(): void
{
    io()->title('Updating Prevarisc.');

    parallel(
        function () {
            composerInstall();
        },
        function () {
            composerInstallDev();
        },
        function () {
            symfonyMigrate();
        },
    );
}

#[AsTask(namespace: 'prevarisc', description: 'Load Prevarisc dump')]
function loadDump(): void
{
    io()->title('Loading Prevarisc dump.');

    io()->text('Searching dump files (ordered by modification time, newest first).');
    $sqlDumps = finder()->files()->in(dirname(__DIR__))->depth('== 0')->name('*.sql')->sortByModifiedTime()->reverseSorting();

    if (!$sqlDumps->hasResults()) {
        io()->error('No dump file found.');

        return;
    }

    if (iterator_count($sqlDumps) === 1) {
        $dumpName = $sqlDumps->getIterator()->current()->getFilename();
    } else {
        $dumpNames = array_values(array_map(fn ($sqlDump) => $sqlDump->getFilename(), iterator_to_array($sqlDumps)));

        $dumpName = io()->choice('Please choose which dump file you want to load', $dumpNames, 0);
    }

    io()->info('Loading dump file: '.$dumpName);

    run(['docker', 'cp', $dumpName, 'prevarisc-infra-db-1:/'.$dumpName]);
    run('docker exec -w / -i prevarisc-infra-db-1 mysql -u root -p"planmusique" PRV_prevarisc_v2 < '.$dumpName);

    io()->success('Dump loaded successfully.');
}

function composerInstall(): void
{
    io()->title('Installing composer dependencies.');

    parallel(
        function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'app', 'composer', 'install']);
        },
        function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'composer', 'install']);
        },
        function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-passerelle-platau', 'platau', 'composer', 'install']);
        },
    );
}

function composerInstallDev(): void
{
    io()->title('Installing composer dev (tools) dependencies.');

    parallel(
        function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc/tools', 'platau', 'composer', 'install']);
        },
        function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration/tools', 'platau', 'composer', 'install']);
        },
    );
}
