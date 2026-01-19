<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\AsRawTokens;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;
use function Castor\exit_code;
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
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/rector', ...$options]);

    io()->section($phpCsFixerTitle);
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix', ...$options]);

    io()->section($twigCsFixerTitle);
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/twig-cs-fixer', ...$optionsTwigCsFixer]);
}

#[AsTask(name: 'analyse', namespace: 'symfony', description: 'Analyse for Symfony application')]
function symfonyAnalyse(): void
{
    io()->title('Analysing Symfony application');

    io()->section('Executing PHPStan');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/phpstan', '--memory-limit=-1']);
}

#[AsTask(name: 'test', namespace: 'symfony', description: 'Test for Symfony application')]
function symfonyTest(
    #[AsOption(name: 'all', mode: InputOption::VALUE_NONE, description: 'Lance la suite complète de tests incluant les tests fonctionnels')]
    bool $all
): void
{
    io()->title('Testing Symfony application');

    io()->section('Executing PHPUnit');

    $options = [];
    if (false === $all) {
        $options = ['--exclude-group', 'functional'];
    }

    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'php', 'bin/phpunit', '--testdox', ...$options]);
}

#[AsTask(name: 'migrate', namespace: 'symfony', description: 'Migrate database schema')]
function symfonyMigrate(): void
{
    io()->title('Migrating database schema');

    io()->section('Executing Doctrine migrations');
    symfonyConsole(['d:m:m', '--no-interaction']);
}
