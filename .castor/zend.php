<?php

use Castor\Attribute\AsTask;
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
