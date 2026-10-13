<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;

use function Castor\exit_code;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'cs', namespace: 'platau', description: 'Fix Plat\'AU application coding style')]
function platauCs(): void
{
    io()->title('Fixing coding style for Plat\'AU application');

    io()->section('Executing PHP-CS-FIXER');
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-passerelle-platau', 'app', 'vendor/bin/php-cs-fixer', '--config=vendor/kdubuc/php-cs-fixer-rules/php-cs-fixer.php', 'fix']);
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

#[AsTask(name: 'dependencies', namespace: 'platau', description: 'Check outdated direct dependencies of the Plat\'AU application')]
function platauDependencies(
    #[ASOption(name: 'limit', mode: InputOption::VALUE_REQUIRED, description: 'Fail when the outdated package count reaches this limit')]
    int $limit = 5
): void
{
    if ($limit < 1) {
        throw new InvalidArgumentException('The dependency limit must be greater than zero.');
    }

    io()->title('Checking Plat\'AU project dependencies');

    $code = exit_code([
        'docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-T', '-w', '/var/www/html/prevarisc-passerelle-platau',
        'app', 'vendor/bin/swiss-knife', 'breakpoint', '--limit', (string) $limit,
    ]);
    if (0 !== $code) {
        io()->error(sprintf('Dependency check failed (exit code %d).', $code));
        exit(1);
    }
}

#[AsTask(name: 'test', namespace: 'platau', description: 'Test Plat\'AU application')]
function platauTest(): void
{
    io()->title('Testing Plat\'AU application');

    io()->section('Executing PHPUnit');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-passerelle-platau', 'app', 'vendor/bin/phpunit', '--testdox', 'tests/']);
}
