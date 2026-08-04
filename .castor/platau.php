<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;

use function Castor\exit_code;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'cs', namespace: 'platau', description: 'Check Plat\'AU application coding style')]
function platauCs(
    #[AsOption(name: 'dry-run', mode: InputOption::VALUE_NONE, description: 'Lance les linters sans modifications')]
    bool $dryRun
): void
{
    $options = [];
    $phpCsFixerTitle = 'Executing PHP-CS-FIXER';

    if (true === $dryRun) {
        $options = ['--dry-run'];
        $phpCsFixerTitle .= ' with '.implode(', ', $options);
    }

    io()->title('Checking coding style for Plat\'AU application');

    io()->section($phpCsFixerTitle);
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-passerelle-platau', 'app', 'vendor/bin/php-cs-fixer', '--config=vendor/kdubuc/php-cs-fixer-rules/php-cs-fixer.php', 'fix', ...$options]);
}

#[AsTask(name: 'refactor', namespace: 'platau', description: 'Refactor Plat\'AU application')]
function platauRefactor(): void
{
    io()->title('Refactoring Plat\'AU application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-passerelle-platau', 'app', 'vendor/bin/rector']);
}

#[AsTask(name: 'analyse', namespace: 'platau', description: 'Analyse Plat\'AU application')]
function platauAnalyse(): void
{
    io()->title('Analysing Plat\'AU application');

    io()->section('Executing Psalm');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-passerelle-platau', 'app', 'vendor/bin/psalm']);
}

#[AsTask(name: 'test', namespace: 'platau', description: 'Test Plat\'AU application')]
function platauTest(): void
{
    io()->title('Testing Plat\'AU application');

    io()->section('Executing PHPUnit');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-passerelle-platau', 'app', 'vendor/bin/phpunit', '--testdox', 'tests/']);
}
