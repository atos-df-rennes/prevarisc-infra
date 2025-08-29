<?php

use Castor\Attribute\AsTask;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'cs-check', namespace: 'symfony', description: 'Check coding style for Symfony application')]
function symfonyCsCheck(): void
{
    io()->title('Checking coding style for Symfony application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/rector', '--dry-run']);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix', '--dry-run']);
}

#[AsTask(name: 'cs-fix', namespace: 'symfony', description: 'Fix coding style for Symfony application')]
function symfonyCsFix(): void
{
    io()->title('Fixing coding style for Symfony application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/rector']);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix']);
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
