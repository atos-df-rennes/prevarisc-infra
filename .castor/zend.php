<?php

use Castor\Attribute\AsTask;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'cs-check', namespace: 'zend', description: 'Check coding style for Zend application')]
function zendCsCheck(): void
{
    io()->title('Checking coding style for Zend application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'platau', 'tools/vendor/bin/rector', '--dry-run']);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix', '--dry-run']);
}

#[AsTask(name: 'cs-fix', namespace: 'zend', description: 'Fix coding style for Zend application')]
function zendCsFix(): void
{
    io()->title('Fixing coding style for Zend application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'platau', 'tools/vendor/bin/rector']);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix']);
}

#[AsTask(name: 'analyse', namespace: 'zend', description: 'Analyse for Zend application')]
function zendAnalyse(): void
{
    io()->title('Analysing Zend application');

    io()->section('Executing PHPStan');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'platau', 'tools/vendor/bin/phpstan', '--memory-limit=-1']);
}
