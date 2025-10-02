<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\AsRawTokens;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'console', namespace: 'symfony', description: 'Execute Symfony console command')]
function symfonyConsole(#[AsRawTokens] array $args): void
{
    io()->title('Executing Symfony console command');

    run(
        [
            'docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration',
            'app', 'php', 'bin/console', ...$args
        ]
    );
}

#[AsTask(name: 'cs', namespace: 'symfony', description: 'Check coding style for Symfony application')]
function symfonyCs(
    #[AsOption(name: 'dry-run', mode: InputOption::VALUE_NONE, description: 'Lance les linters sans modifications')]
    bool $dryRun
): void
{
    io()->title('Checking coding style for Symfony application');

    $options = [];
    if(true === $dryRun) {
        $options = ['--dry-run'];
    }

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/rector', ...$options]);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix', ...$options]);
}

#[AsTask(name: 'analyse', namespace: 'symfony', description: 'Analyse for Symfony application')]
function symfonyAnalyse(): void
{
    io()->title('Analysing Symfony application');

    io()->section('Executing PHPStan');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/phpstan', '--memory-limit=-1']);
}

#[AsTask(name: 'test', namespace: 'symfony', description: 'Test for Symfony application')]
function symfonyTest(): void
{
    io()->title('Testing Symfony application');

    io()->section('Executing PHPUnit');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'php', 'bin/phpunit', '--testdox']);
}

#[AsTask(name: 'migrate', namespace: 'symfony', description: 'Migrate database schema')]
function symfonyMigrate(): void
{
    io()->title('Migrating database schema');

    io()->section('Executing Doctrine migrations');
    symfonyConsole(['d:m:m', '--no-interaction']);
}
