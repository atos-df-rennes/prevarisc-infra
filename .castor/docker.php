<?php

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;
use function Castor\context;
use function Castor\exit_code;
use function Castor\io;
use function Castor\run;

#[AsTask(namespace: 'docker', description: 'Start Prevarisc')]
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

#[AsTask(namespace: 'docker', description: 'Stop Prevarisc')]
function stop(): void
{
    io()->title('Stoping Prevarisc.');

    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'down', '--remove-orphans']);
}

#[AsTask(namespace: 'docker', description: 'Restart Prevarisc')]
function restart(): void
{
    io()->title('Restarting Prevarisc.');

    stop();
    start(false);
}

#[AsTask(namespace: 'docker', description: 'Rebuild and restart Prevarisc containers')]
function rebuild(): void
{
    io()->title('Rebuilding Prevarisc containers.');

    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'build']);
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'up', '-d']);

    io()->success('Containers rebuilt and restarted.');
}

#[AsTask(namespace: 'docker', description: 'Follow Docker logs (all containers or a specific one)')]
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

#[AsTask(namespace: 'docker', description: 'Open an interactive shell inside the app container')]
function shell(): void
{
    io()->title('Opening shell in app container.');

    run(
        ['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'app', 'bash'],
        context: context()->withTty(true)
    );
}