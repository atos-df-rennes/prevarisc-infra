<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\AsRawTokens;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;
use function Castor\capture;
use function Castor\context;
use function Castor\exit_code;
use function Castor\fs;
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

#[AsTask(name: 'metrics', namespace: 'symfony', description: 'Generate local PhpMetrics reports for src (without Composer network analysis)')]
function symfonyMetrics(): void
{
    io()->title('Measuring Symfony application complexity');

    $application = dirname(__DIR__) . '/prevarisc-migration';
    $directory = $application . '/var/metrics';
    $docker = ['docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-T', '-w', '/var/www/html/prevarisc-migration', 'app'];
    $options = [
        '--composer=false',
        '--extensions=php',
        '--report-json=var/metrics/phpmetrics.json',
        '--report-html=var/metrics/html',
        '--report-violations=var/metrics/violations.xml',
        'src',
    ];
    $versions = json_decode(capture([...$docker, 'php', '-r', <<<'PHP'
require 'tools/vendor/autoload.php';
echo json_encode([
    'php' => PHP_VERSION,
    'phpmetrics' => Composer\InstalledVersions::getPrettyVersion('phpmetrics/phpmetrics'),
    'phpmetrics_reference' => Composer\InstalledVersions::getReference('phpmetrics/phpmetrics'),
    'php_parser' => Composer\InstalledVersions::getPrettyVersion('nikic/php-parser'),
    'php_parser_reference' => Composer\InstalledVersions::getReference('nikic/php-parser'),
], JSON_THROW_ON_ERROR);
PHP
    ]), true, flags: JSON_THROW_ON_ERROR);
    $manifest = [
        'status' => 'running',
        'started_at' => gmdate('c'),
        'project_sha' => trim(capture(['git', '-C', $application, 'rev-parse', 'HEAD'])),
        'worktree_dirty' => '' !== trim(capture(['git', '-C', $application, 'status', '--porcelain'])),
        'versions' => $versions,
        'scope' => ['src'],
        'options' => $options,
    ];

    fs()->mkdir($directory);
    // Discard previous reports, including HTML history, so a failed run cannot expose an old snapshot.
    fs()->remove([
        $directory . '/phpmetrics.json',
        $directory . '/html',
        $directory . '/violations.xml',
        $directory . '/diagnostics.log',
        $directory . '/manifest.json',
    ]);
    fs()->dumpFile($directory . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
    fs()->dumpFile($directory . '/diagnostics.log', '');

    $process = run(
        [...$docker, 'tools/vendor/bin/phpmetrics', ...$options],
        context: context()->withAllowFailure()->withQuiet()->withTimeout(null),
        callback: static function (string $type, string $output) use ($directory): void {
            fs()->appendToFile($directory . '/diagnostics.log', $output);
        },
    );
    $rawOutput = $process->getOutput() . "\n" . $process->getErrorOutput();
    $output = preg_replace('/\x1b\[[0-9;]*[A-Za-z]/', '', $rawOutput);
    preg_match_all('/Cannot parse ([^\r\n\x1b]+)/', $rawOutput, $matches);
    $rejectedFiles = array_values(array_unique($matches[1]));
    $errors = [];
    if ([] !== $rejectedFiles) {
        $errors[] = 'Files rejected by the parser: ' . implode(', ', $rejectedFiles);
    }
    foreach (['phpmetrics.json', 'html/index.html', 'violations.xml'] as $report) {
        if (!is_file($directory . '/' . $report) || 0 === filesize($directory . '/' . $report)) {
            $errors[] = 'Missing or empty report: ' . $report;
        }
    }
    // PhpMetrics also exits with code 1 for critical metric violations; these are not execution errors.
    $criticalViolations = 1 === preg_match('/\[ERR\] Failed du to \d+ critical violations/', $output);
    if (!$process->isSuccessful() && !(1 === $process->getExitCode() && $criticalViolations)) {
        $errors[] = 'PhpMetrics exited with code ' . $process->getExitCode();
    }
    if (is_file($directory . '/phpmetrics.json')) {
        try {
            json_decode(file_get_contents($directory . '/phpmetrics.json'), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $errors[] = 'Invalid JSON report: ' . $exception->getMessage();
        }
    }
    $manifest['status'] = [] === $errors ? 'complete' : 'failed';
    $manifest['finished_at'] = gmdate('c');
    $manifest['phpmetrics_exit_code'] = $process->getExitCode();
    $manifest['critical_violations'] = $criticalViolations;
    $manifest['runtime_warnings'] = 1 === preg_match('/(?:PHP )?(?:Deprecated|Warning):/', $output);
    $manifest['rejected_files'] = $rejectedFiles;
    $manifest['errors'] = $errors;
    fs()->dumpFile($directory . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");

    if ([] !== $errors) {
        throw new RuntimeException(implode("\n", $errors) . "\nSee " . $directory . '/diagnostics.log');
    }
    if ($criticalViolations) {
        io()->warning('PhpMetrics reports critical metric violations. See violations.xml; measurement remains non-blocking.');
    }
    if ($manifest['runtime_warnings']) {
        io()->warning('PhpMetrics emitted PHP warnings or deprecations. See diagnostics.log.');
    }
    io()->success('Reports generated in prevarisc-migration/var/metrics/ (HTML: html/index.html).');
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
