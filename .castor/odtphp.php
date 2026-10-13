<?php

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

use function Castor\exit_code;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'dependencies', namespace: 'odtphp', description: 'Check outdated direct dependencies of OdtPHP')]
function odtphpDependencies(
    #[AsOption(name: 'limit', mode: InputOption::VALUE_REQUIRED, description: 'Fail when the outdated package count reaches this limit')]
    int $limit = 5
): void
{
    if ($limit < 1) {
        throw new InvalidArgumentException('The dependency limit must be greater than zero.');
    }

    io()->title('Checking OdtPHP project dependencies');

    $code = exit_code([
        'docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-T', '-w', '/var/www/html/prevarisc-odtphp',
        'app', 'vendor/bin/swiss-knife', 'breakpoint', '--limit', (string) $limit,
    ]);
    if (0 !== $code) {
        io()->error(sprintf('Dependency check failed (exit code %d).', $code));
        exit(1);
    }
}

#[AsTask(name: 'test', namespace: 'odtphp', description: 'Test OdtPHP')]
function odtphpTest(): void
{
    io()->title('Testing OdtPHP');

    run([
        'docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-T', '-w', '/var/www/html/prevarisc-odtphp',
        'app', 'composer', 'test',
    ]);
}
