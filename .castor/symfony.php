<?php

use Castor\Attribute\AsTask;
use function Castor\io;
use function Castor\run;

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
