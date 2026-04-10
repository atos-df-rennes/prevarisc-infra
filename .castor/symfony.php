<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\AsRawTokens;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;
use function Castor\exit_code;
use function Castor\io;
use function Castor\parallel;
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

#[AsTask(name: 'migration-status', namespace: 'symfony', description: 'Show Doctrine migration status')]
function symfonyMigrationStatus(): void
{
    io()->title('Doctrine migration status');

    symfonyConsole(['d:m:status']);
}

#[AsTask(name: 'cache', namespace: 'symfony', description: 'Clear Symfony cache')]
function symfonyCache(): void
{
    io()->title('Clearing Symfony cache');

    symfonyConsole(['cache:clear']);
}

#[AsTask(name: 'make', namespace: 'symfony', description: 'Run a Symfony maker command (make:entity, make:migration…)')]
function symfonyMake(#[AsRawTokens] array $args): void
{
    io()->title('Running Symfony maker');

    symfonyConsole(['make:' . array_shift($args), ...$args]);
}

#[AsTask(name: 'status', namespace: 'symfony', description: 'Show git status for prevarisc and prevarisc-migration repos')]
function symfonyStatus(): void
{
    io()->title('Git status — prevarisc + prevarisc-migration');

    parallel(
        function () {
            io()->section('prevarisc');
            run(['git', '-C', 'prevarisc', 'status', '--short', '--branch']);
        },
        function () {
            io()->section('prevarisc-migration');
            run(['git', '-C', 'prevarisc-migration', 'status', '--short', '--branch']);
        }
    );
}

#[AsTask(name: 'validate', namespace: 'symfony', description: 'Run PHPStan + CS (dry-run) + tests sequentially — stops on first failure')]
function symfonyValidate(): void
{
    io()->title('Validating Symfony application');

    io()->section('Step 1/3 — PHPStan');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/phpstan', '--memory-limit=-1']);

    io()->section('Step 2/3 — Rector (dry-run)');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/rector', '--dry-run']);

    io()->section('Step 2/3 — PHP-CS-Fixer (dry-run)');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix', '--dry-run']);

    io()->section('Step 2/3 — Twig-CS-Fixer (check)');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/twig-cs-fixer', 'check']);

    io()->section('Step 3/3 — PHPUnit');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'php', 'bin/phpunit', '--testdox', '--exclude-group', 'functional']);

    io()->success('All checks passed!');
}
