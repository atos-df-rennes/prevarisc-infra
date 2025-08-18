<?php

use Castor\Attribute\AsTask;

use Symfony\Component\Process\ExecutableFinder;
use function Castor\check;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'cs-check', namespace: 'zend', description: 'Check coding style for Zend application')]
function zendCsCheck(): void
{
    io()->title('Checking coding style for Zend application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'platau', 'php', 'prevarisc/tools/vendor/bin/rector', '--config', 'prevarisc/rector.php', '--dry-run']);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'platau', 'php', 'prevarisc/tools/vendor/bin/php-cs-fixer', '--config=prevarisc/.php-cs-fixer.dist.php', 'fix', '--dry-run']);
}

#[AsTask(name: 'cs-fix', namespace: 'zend', description: 'Fix coding style for Zend application')]
function zendCsFix(): void
{
    io()->title('Fixing coding style for Zend application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'platau', 'php', 'prevarisc/tools/vendor/bin/rector', '--config', 'prevarisc/rector.php']);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'platau', 'php', 'prevarisc/tools/vendor/bin/php-cs-fixer', '--config=prevarisc/.php-cs-fixer.dist.php', 'fix']);
}

#[AsTask(name: 'cs-check', namespace: 'symfony', description: 'Check coding style for Symfony application')]
function symfonyCsCheck(): void
{
    io()->title('Checking coding style for Symfony application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'platau', 'bash', '-c', 'cd prevarisc-migration && tools/vendor/bin/rector --dry-run']);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'platau', 'php', 'prevarisc-migration/tools/vendor/bin/php-cs-fixer', '--config=prevarisc-migration/.php-cs-fixer.dist.php', 'fix', '--dry-run']);
}

#[AsTask(name: 'cs-fix', namespace: 'symfony', description: 'Fix coding style for Symfony application')]
function symfonyCsFix(): void
{
    io()->title('Fixing coding style for Symfony application');

    io()->section('Executing Rector');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'platau', 'bash', '-c', 'cd prevarisc-migration && tools/vendor/bin/rector']);

    io()->section('Executing PHP-CS-FIXER');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', 'platau', 'php', 'prevarisc-migration/tools/vendor/bin/php-cs-fixer', '--config=prevarisc-migration/.php-cs-fixer.dist.php', 'fix']);
}

#[AsTask(description: 'Check coding style for all applications')]
function csCheck(): void
{
  io()->title('Checking coding style for all applications');

  zendCsCheck();
  symfonyCsCheck();
}

#[AsTask(description: 'Fix coding style for all applications')]
function csFix(): void
{
    io()->title('Fixing coding style for all applications');

    zendCsFix();
    symfonyCsFix();
}

#[AsTask(namespace: 'prevarisc', description: 'Setup the project')]
function setup(): void
{
    io()->title('Setting up the project');

    check(
      'Checking if Git is installed',
      'Git is not installed. Please install it first.',
      fn () => (new ExecutableFinder())->find('git')
    );

    io()->section('Cloning Prevarisc repository');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc.git']);

    io()->info('You will need a Personal Access Token for the next operation. Please create one if you do not have one already.');
    io()->confirm('Resume script?');
    io()->section('Cloning Prevarisc Migration repository');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc-migration.git']);

    io()->section('Cloning Prevarisc Passerelle Plat\'AU repository');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc-passerelle-platau.git']);
}