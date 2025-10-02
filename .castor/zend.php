<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;

use function Castor\io;
use function Castor\run;

#[AsTask(name: 'cs', namespace: 'zend', description: 'Check coding style for Zend application')]
function zendCs(
    #[AsOption(name: 'dry-run', mode: InputOption::VALUE_NONE, description: 'Lance les linters sans modifications')]
    bool $dryRun
): void
{
    $options = [];
    if(true === $dryRun) {
        $options = ['--dry-run'];
    }

    io()->title('Checking coding style for Zend application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'platau', 'tools/vendor/bin/rector', ...$options]);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'platau', 'tools/vendor/bin/php-cs-fixer', 'fix', ...$options]);
}

#[AsTask(name: 'analyse', namespace: 'zend', description: 'Analyse for Zend application')]
function zendAnalyse(): void
{
    io()->title('Analysing Zend application');

    io()->section('Executing PHPStan');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'platau', 'tools/vendor/bin/phpstan', '--memory-limit=-1']);
}

#[AsTask(name: 'test', namespace: 'zend', description: 'Test for Zend application')]
function zendTest(): void
{
    io()->title('Testing Zend application');

    io()->section('Executing PHPUnit');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc', 'app', 'vendor/bin/phpunit', '--bootstrap', 'tests/bootstrap/indexTest.php', '--testdox', 'tests/']);
}
