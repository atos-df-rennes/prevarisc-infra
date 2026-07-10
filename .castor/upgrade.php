<?php

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;
use function Castor\exit_code;
use function Castor\io;
use function Castor\run;
use function Castor\wait_for_docker_container;

// Ordered list of PHP versions supported by our tooling (Rector withPhpSets/PhpVersion, phpstan.neon.dist).
// Upgrades must move one step at a time along this list (cf. roadmap Phase 3 — PHP incremental).
const UPGRADE_PHP_SUPPORTED_VERSIONS = ['7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5'];

#[AsTask(
    name: 'php',
    namespace: 'upgrade',
    description: 'Upgrade the project PHP version (Dockerfile, composer.json, phpstan.neon.dist, rector.php) and validate'
)]
function upgradePhp(
    #[AsOption(
        name: 'target',
        mode: InputOption::VALUE_REQUIRED,
        description: 'Version PHP cible (ex: 7.3). Note: nommée "target" car "version" est réservé par Castor (--version applicatif).'
    )]
    string $target = ''
): void {
    io()->title('Upgrading PHP version');

    if ('' === $target) {
        io()->error('Missing required option --target=<target_version> (ex: --target=7.3).');
        exit(1);
    }

    if (1 !== preg_match('/^\d+\.\d+$/', $target)) {
        io()->error(sprintf('Invalid version format "%s". Expected format: MAJOR.MINOR (ex: 7.3).', $target));
        exit(1);
    }

    $dockerfilePath = 'php/prevarisc/Dockerfile';
    $composerJsonPath = 'prevarisc-migration/composer.json';
    $phpstanPath = 'prevarisc-migration/phpstan.neon.dist';
    $rectorPath = 'prevarisc-migration/rector.php';

    $dockerfileContent = file_get_contents($dockerfilePath);
    if (1 !== preg_match('/^FROM php:(\d+\.\d+)-apache/m', $dockerfileContent, $matches)) {
        io()->error(sprintf('Unable to determine current PHP version from %s.', $dockerfilePath));
        exit(1);
    }
    $currentVersion = $matches[1];

    if ($currentVersion === $target) {
        io()->warning(sprintf('Project is already on PHP %s. Nothing to do.', $target));
        return;
    }

    $currentIndex = array_search($currentVersion, UPGRADE_PHP_SUPPORTED_VERSIONS, true);
    $targetIndex = array_search($target, UPGRADE_PHP_SUPPORTED_VERSIONS, true);

    if (false === $targetIndex) {
        io()->error(sprintf(
            'Unsupported target PHP version "%s". Supported versions: %s.',
            $target,
            implode(', ', UPGRADE_PHP_SUPPORTED_VERSIONS)
        ));
        exit(1);
    }

    if (false === $currentIndex) {
        io()->error(sprintf('Current PHP version %s is not in the known supported list. Aborting to avoid an unsafe jump.', $currentVersion));
        exit(1);
    }

    if ($targetIndex !== $currentIndex + 1) {
        $nextVersion = UPGRADE_PHP_SUPPORTED_VERSIONS[$currentIndex + 1] ?? null;
        io()->error(sprintf(
            'PHP upgrades must be incremental. Current version is %s, requested %s. Next allowed version is %s.',
            $currentVersion,
            $target,
            $nextVersion ?? 'none (already at the latest supported version)'
        ));
        exit(1);
    }

    io()->section(sprintf('Upgrading PHP %s → %s', $currentVersion, $target));

    $versionCompact = str_replace('.', '', $target); // ex: "7.3" -> "73"
    [$major, $minor] = array_map('intval', explode('.', $target));
    $phpstanCode = $major * 10000 + $minor * 100; // ex: 7.3 -> 70300 (matches Rector\ValueObject\PhpVersion::PHP_73)

    io()->text(sprintf('Updating %s', $dockerfilePath));
    file_put_contents(
        $dockerfilePath,
        preg_replace('/^FROM php:\d+\.\d+-apache/m', "FROM php:{$target}-apache", $dockerfileContent)
    );

    io()->text(sprintf('Updating %s', $composerJsonPath));
    file_put_contents(
        $composerJsonPath,
        preg_replace(
            '/"php": "[^"]*"/',
            "\"php\": \"^{$target}.0\"",
            file_get_contents($composerJsonPath),
            1
        )
    );

    io()->text(sprintf('Updating %s', $phpstanPath));
    file_put_contents(
        $phpstanPath,
        preg_replace(
            '/phpVersion: \d+/',
            "phpVersion: {$phpstanCode}",
            file_get_contents($phpstanPath)
        )
    );

    io()->text(sprintf('Updating %s', $rectorPath));
    $rectorContent = file_get_contents($rectorPath);
    $rectorContent = preg_replace('/PhpVersion::PHP_\d+/', "PhpVersion::PHP_{$versionCompact}", $rectorContent);
    $rectorContent = preg_replace('/withPhpSets\(php\d+: true\)/', "withPhpSets(php{$versionCompact}: true)", $rectorContent);
    file_put_contents($rectorPath, $rectorContent);

    io()->success('Configuration files updated.');

    io()->section('Rebuilding app container');
    run(['docker', 'compose', '--file', 'compose.dev.yaml', 'build', 'app']);
    /*
     * Utilisation de la fonction exit_code() plutôt que run() pour permettre au process de fail.
     * Aucun impact sur le fonctionnement mais le post_start du fichier compose semble causer cette erreur.
     */
    exit_code(['docker', 'compose', '--file', 'compose.dev.yaml', 'up', '-d', 'app']);
    wait_for_docker_container(
        containerName: 'prevarisc-infra-app-1',
        message: 'Waiting for prevarisc application container to be ready.'
    );

    io()->section('Updating composer dependencies');
    run([
        'docker', 'compose', '--file', 'compose.dev.yaml', 'exec', '-w', '/var/www/html/prevarisc-migration',
        'app', 'composer', 'update',
    ]);

    io()->section('Running validation (symfony:analyse, symfony:cs, symfony:test)');
    symfonyAnalyse();
    symfonyCs(false);
    symfonyTest(false);

    io()->success(sprintf('PHP successfully upgraded from %s to %s.', $currentVersion, $target));
}
