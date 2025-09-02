<?php

use Castor\Attribute\AsTask;
use Symfony\Component\Process\ExecutableFinder;
use function Castor\check;
use function Castor\exit_code;
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

    io()->text('Cloning Prevarisc repository.');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc.git']);

    io()->info('You will need a Personal Access Token for the next operation. Please create one if you do not have one already.');

    $resumeScript = io()->confirm('Resume script?');
    if (!$resumeScript) {
        return;
    }

    io()->text('Cloning Prevarisc Migration repository.');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc-migration.git']);

    io()->text('Cloning Prevarisc Passerelle Plat\'AU repository.');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc-passerelle-platau.git']);

    io()->section('Copying web server files.');

    io()->text('Copying configuration file.');
    fs()->copy('apache/httpd-prevarisc-config.conf.example', 'apache/httpd-prevarisc-config.conf');

    io()->text('Copying version file.');
    fs()->copy('apache/httpd-prevarisc-version.conf.example', 'apache/httpd-prevarisc-version.conf');

    io()->info('The configuration file is missing some information.');
    $continueInstallation = io()->confirm('Do you want to fill it out now? (installation can continue)');
    if (!$continueInstallation) {
        return;
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
            $envFileContentArray[$key] = 'APP_SECRET='.bin2hex(random_bytes(16));
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

    composerInstall();
    composerInstallDev();

    io()->section('Creating database and updating schema.');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'php', 'bin/console', 'd:d:c']);
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'php', 'bin/console', 'd:m:m', '--no-interaction']);

    io()->success('Prevarisc is now ready!');
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
