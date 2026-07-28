<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\AsArgument;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\ExecutableFinder;
use function Castor\context;
use function Castor\exit_code;
use function Castor\finder;
use function Castor\fs;
use function Castor\io;
use function Castor\parallel;
use function Castor\run;
use function Castor\wait_for_docker_container;
use function Castor\watch;

#[AsTask(namespace: 'prevarisc', description: 'Setup Prevarisc')]
function setup(): void
{
    io()->title('Setting up Prevarisc.');

    io()->text('Checking if Git is installed.');
    if (!(new ExecutableFinder())->find('git')) {
        io()->warning('Git n\'est pas installé. Installation automatique en cours...');
        installGit();
    } else {
        io()->text('Git est installé.');
    }

    io()->text('Checking if Docker is installed.');
    if (!(new ExecutableFinder())->find('docker')) {
        io()->warning('Docker n\'est pas installé. Installation automatique en cours...');
        installDocker();
        postInstallDocker();
    } else {
        io()->text('Docker est installé.');

        // Vérifie que le plugin docker compose est bien disponible
        if (exit_code(['docker', 'compose', 'version'], quiet: true) !== 0) {
            io()->warning('Le plugin Docker Compose n\'est pas disponible. Installation en cours...');
            run(['sudo', 'apt-get', 'install', '-y', 'docker-compose-plugin']);
        } else {
            io()->text('Docker Compose est installé.');
        }
    }

    io()->section('Cloning repositories.');

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

    // @todo: Cloner OdtPHP

    io()->section('Copying web server files.');

    if (!fs()->exists('apache/httpd-prevarisc-config.conf')) {
        io()->text('Copying configuration file.');
        fs()->copy('apache/httpd-prevarisc-config.conf.example', 'apache/httpd-prevarisc-config.conf');

        io()->info('You may want to update the configuration file with your values.');
        $continueInstallation = io()->confirm('Resume script?');

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

    watchForApacheConfigurationChanges();
}

#[AsTask(namespace: 'prevarisc', description: 'Start Prevarisc')]
function start(
    #[ASOption(name: 'watch', mode: InputOption::VALUE_NONE, description: 'Surveille les fichiers Apache/PHP et recharge automatiquement')]
    bool $watch
): void
{
    io()->title('Starting Prevarisc.');

    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'up', '-d']);

    if ($watch) {
        watchForApacheConfigurationChanges();
    }
}

#[AsTask(namespace: 'prevarisc', description: 'Stop Prevarisc')]
function stop(): void
{
    io()->title('Stoping Prevarisc.');

    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'down', '--remove-orphans']);
}

#[AsTask(namespace: 'prevarisc', description: 'Rebuild and restart Prevarisc containers')]
function rebuild(): void
{
    io()->title('Rebuilding Prevarisc containers.');

    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'build']);
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'up', '-d']);

    io()->success('Containers rebuilt and restarted.');
}

#[AsTask(namespace: 'prevarisc', description: 'Follow Docker logs (all containers or a specific one)')]
function logs(
    #[AsArgument(name: 'service', description: 'Nom du service Docker à surveiller (ex: app, db, platau). Tous si omis.')]
    string $service = ''
): void
{
    io()->title('Prevarisc logs' . ('' !== $service ? " — {$service}" : ''));

    $cmd = ['docker', 'compose', '--file', 'compose.dev.yaml', 'logs', '--follow', '--tail=100'];
    if ('' !== $service) {
        $cmd[] = $service;
    }

    run($cmd);
}

#[AsTask(namespace: 'prevarisc', description: 'Open an interactive shell inside the app container')]
function shell(): void
{
    io()->title('Opening shell in app container.');

    run(
        ['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'app', 'bash'],
        context: context()->withTty(true)
    );
}

#[AsTask(namespace: 'prevarisc', description: 'Installs all composer dependencies and execute Doctrine migrations')]
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
    );

    symfonyMigrate();
}

#[AsTask(namespace: 'prevarisc', description: 'Make Prevarisc dump from db container to local host (WSL)')]
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

    $dumpName = 'dump_sql/prevarisc_'.date('YmdHis').'.sql';
    run('docker exec -w / -i '.$container.' mysqldump -u root -p"planmusique" PRV_prevarisc_v2 > '.$dumpName);

    io()->success('Dump made successfully.');
}

#[AsTask(namespace: 'prevarisc', description: 'Load a selected Prevarisc dump from local host (WSL) to db container')]
function loadDump(
    #[ASOption(name: 'container', description: 'Nom du conteneur MySQL cible')]
    string $container = 'prevarisc-infra-db-1'
): void {
    io()->title('Loading Prevarisc dump.');

    io()->text('Searching dump files (ordered by modification time, newest first).');
    $sqlDumps = finder()->files()->in(dirname(__DIR__).'/dump_sql')->depth('== 0')->name('*.sql')->sortByModifiedTime()->reverseSorting();

    if (!$sqlDumps->hasResults()) {
        io()->error('No dump file found.');

        return;
    }

    if (iterator_count($sqlDumps) === 1) {
        $dumpPath = $sqlDumps->getIterator()->current();
    } else {
        $dumpPaths = array_values(iterator_to_array($sqlDumps));
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

function watchForApacheConfigurationChanges(): void
{
    io()->info('Watching for changes in Apache and PHP configuration files.');
    io()->comment('Press Ctrl+C to stop watching.');

    $handler = function (string $file, string $action): void {
        if (str_ends_with($file, '~')) {
            return;
        }

        io()->writeln("[{$action}] {$file}. Reloading Apache configuration.");
        exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'restart', 'app']);
    };

    parallel(
        fn () => watch('apache/', $handler),
        fn () => watch('php/prevarisc/', $handler),
    );
}

function installGit(): void
{
    run(['sudo', 'apt-get', 'update', '-qq']);
    run(['sudo', 'apt-get', 'install', '-y', 'git']);
    io()->success('Git installé avec succès.');
}

function installDocker(): void
{
    io()->text('Installation des prérequis...');
    run(['sudo', 'apt-get', 'update', '-qq']);
    run(['sudo', 'apt-get', 'install', '-y', 'ca-certificates', 'curl']);

    io()->text('Ajout de la clé GPG officielle Docker...');
    run(['sudo', 'install', '-m', '0755', '-d', '/etc/apt/keyrings']);
    run(['sudo', 'curl', '-fsSL', 'https://download.docker.com/linux/ubuntu/gpg', '-o', '/etc/apt/keyrings/docker.asc']);
    run(['sudo', 'chmod', 'a+r', '/etc/apt/keyrings/docker.asc']);

    io()->text('Ajout du dépôt Docker...');
    $arch = trim((string) shell_exec('dpkg --print-architecture'));
    $codename = trim((string) shell_exec('. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}"'));

    $sourcesContent = implode("\n", [
        'Types: deb',
        'URIs: https://download.docker.com/linux/ubuntu',
        "Suites: {$codename}",
        'Components: stable',
        "Architectures: {$arch}",
        'Signed-By: /etc/apt/keyrings/docker.asc',
        '',
    ]);

    $tempFile = tempnam(sys_get_temp_dir(), 'docker_sources_');
    file_put_contents($tempFile, $sourcesContent);
    run(['sudo', 'cp', $tempFile, '/etc/apt/sources.list.d/docker.sources']);
    run(['sudo', 'chmod', '644', '/etc/apt/sources.list.d/docker.sources']);
    unlink($tempFile);

    io()->text('Installation de Docker Engine et Docker Compose...');
    run(['sudo', 'apt-get', 'update', '-qq']);
    run(['sudo', 'apt-get', 'install', '-y', 'docker-ce', 'docker-ce-cli', 'containerd.io', 'docker-buildx-plugin', 'docker-compose-plugin']);

    io()->success('Docker installé avec succès.');
}

function postInstallDocker(): void
{
    io()->text('Configuration post-installation Docker...');

    // Création du groupe docker (idempotent)
    exit_code('sudo groupadd docker 2>/dev/null || true');

    // Ajout de l'utilisateur courant au groupe docker
    $currentUser = trim((string) shell_exec('whoami'));
    if ('' !== $currentUser) {
        run(['sudo', 'usermod', '-aG', 'docker', $currentUser]);
        io()->text("Utilisateur '{$currentUser}' ajouté au groupe docker.");
    }

    // Activation du démarrage automatique via systemd
    // Note : sous WSL2, systemd doit être activé dans /etc/wsl.conf ([boot] systemd=true)
    io()->text('Activation du démarrage automatique de Docker (systemd)...');
    exit_code('sudo systemctl enable docker.service 2>/dev/null || true');
    exit_code('sudo systemctl enable containerd.service 2>/dev/null || true');
    exit_code('sudo systemctl start docker.service 2>/dev/null || sudo service docker start 2>/dev/null || true');

    io()->success('Configuration post-installation Docker terminée.');
    io()->note(
        'Pour que les droits du groupe docker prennent effet sans sudo, '
        . 'déconnectez-vous et reconnectez-vous, ou exécutez : newgrp docker'
    );
    io()->note(
        'Sous WSL2 : assurez-vous que systemd est activé dans /etc/wsl.conf '
        . '([boot] systemd=true) pour que Docker démarre automatiquement au boot.'
    );
}

function composerInstall(): void
{
    io()->title('Installing composer dependencies.');

    parallel(
        function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'composer', 'install']);
        },
        function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-passerelle-platau', 'app', 'composer', 'install']);
        },
    );
}

function composerInstallDev(): void
{
    io()->title('Installing composer dev (tools) dependencies.');

    parallel(
        function () {
            run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration/tools', 'app', 'composer', 'install']);
        },
    );
}
