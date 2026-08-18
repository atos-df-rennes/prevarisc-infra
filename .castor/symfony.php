<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\AsRawTokens;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;
use function Castor\exit_code;
use function Castor\io;
use function Castor\parallel;
use function Castor\run;

#[AsTask(name: 'cs', namespace: 'symfony', description: 'Fix coding style for Symfony application')]
function symfonyCs(): void
{
    io()->title('Fixing coding style for Symfony application');

    io()->section('Executing PHP-CS-FIXER');
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'tools/vendor/bin/php-cs-fixer', 'fix']);

    io()->section('Executing TWIG-CS-FIXER');
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'tools/vendor/bin/twig-cs-fixer', 'fix']);
}

#[AsTask(name: 'refactor', namespace: 'symfony', description: 'Refactor Symfony application')]
function symfonyRefactor(): void
{
    io()->title('Refactoring Symfony application');

    io()->section('Executing Rector');
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'tools/vendor/bin/rector']);
}

#[AsTask(name: 'analyse', namespace: 'symfony', description: 'Analyse Symfony application')]
function symfonyAnalyse(): void
{
    io()->title('Analysing Symfony application');

    io()->section('Executing PHPStan');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'tools/vendor/bin/phpstan', '--memory-limit=-1']);
}

#[AsTask(name: 'test', namespace: 'symfony', description: 'Test Symfony application')]
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

    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'php', 'vendor/bin/phpunit', '--testdox', ...$options]);
}

#[AsTask(name: 'validate', namespace: 'symfony', description: 'Run PHPStan + CS (dry-run) + tests sequentially — stops on first failure')]
function symfonyValidate(): void
{
    io()->title('Validating Symfony application');

    io()->section('Step 1/3 — PHPStan');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'tools/vendor/bin/phpstan', '--memory-limit=-1']);

    io()->section('Step 2/3 — Rector (dry-run)');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'tools/vendor/bin/rector', '--dry-run']);

    io()->section('Step 2/3 — PHP-CS-Fixer (dry-run)');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'tools/vendor/bin/php-cs-fixer', 'fix', '--dry-run']);

    io()->section('Step 2/3 — Twig-CS-Fixer (check)');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'tools/vendor/bin/twig-cs-fixer', 'check']);

    io()->section('Step 3/3 — PHPUnit');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'app', 'php', 'vendor/bin/phpunit', '--testdox', '--exclude-group', 'functional']);

    io()->success('All checks passed!');
}
