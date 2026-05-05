<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\exit_code;
use function Castor\io;
use function Castor\wait_for_docker_container;

const AUTH_COMPOSE_AUTH_FILE = 'compose.worktree-auth.yaml';

// ─────────────────────────────────────────────────────────────────────────────
// Configurations des stacks
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Configuration de la stack worktree isolée.
 *
 * @return array{project: string, composeFile: string, appContainer: string, dbContainer: string, authConfFile: string, appPort: string, isWorktree: bool}
 */
function authWorktreeConfig(): array
{
    return [
        'project'      => WORKTREE_PROJECT,
        'composeFile'  => WORKTREE_COMPOSE_FILE,
        'appContainer' => WORKTREE_APP_CONTAINER,
        'dbContainer'  => WORKTREE_DB_CONTAINER,
        'authConfFile' => 'apache/httpd-auth-worktree.conf',
        'appPort'      => '7081',
        'isWorktree'   => true,
    ];
}

/**
 * Configuration de la stack principale (compose.dev.yaml).
 *
 * @return array{project: string, composeFile: string, appContainer: string, dbContainer: string, authConfFile: string, appPort: string, isWorktree: bool}
 */
function authMainConfig(): array
{
    return [
        'project'      => 'prevarisc-infra',
        'composeFile'  => 'compose.dev.yaml',
        'appContainer' => 'prevarisc-infra-app-1',
        'dbContainer'  => 'prevarisc-infra-db-1',
        'authConfFile' => 'apache/httpd-auth-main.conf',
        'appPort'      => '7080',
        'isWorktree'   => false,
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Tâches publiques
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Bascule le mécanisme d'authentification sur le worktree isolé.
 *
 * Gère automatiquement :
 *   - La configuration Apache (httpd-auth-worktree.conf, inclut PREVARISC_SYMFONY_LOGIN)
 *   - Le redémarrage du container app avec préservation du worktree actif
 *   - Le démarrage/arrêt des services SSO (ldap, phpldapadmin, cas)
 *   - La création de l'utilisateur de test en base si absent
 */
#[AsTask(name: 'authentification', namespace: 'worktree', description: 'Bascule le mécanisme d\'authentification du worktree pour tests (sélection interactive)')]
function worktreeAuthentification(): void
{
    $cfg = authWorktreeConfig();

    if (!authIsContainerRunning($cfg['appContainer'])) {
        io()->error(sprintf(
            'Le container "%s" n\'est pas démarré. Lancez "castor worktree:start" avant.',
            $cfg['appContainer']
        ));

        return;
    }

    authRun($cfg);
}

/**
 * Bascule le mécanisme d'authentification sur la stack principale (compose.dev.yaml).
 *
 * Gère automatiquement :
 *   - La configuration Apache (httpd-auth-main.conf, inclut PREVARISC_SYMFONY_LOGIN)
 *   - Le redémarrage du container app
 *   - Le démarrage/arrêt des services SSO (ldap, phpldapadmin, cas)
 *   - La création de l'utilisateur de test en base si absent
 */
#[AsTask(name: 'authentification', namespace: 'symfony', description: 'Bascule le mécanisme d\'authentification de la stack principale pour tests (sélection interactive)')]
function symfonyAuthentification(): void
{
    $cfg = authMainConfig();

    if (!authIsContainerRunning($cfg['appContainer'])) {
        io()->error(sprintf(
            'Le container "%s" n\'est pas démarré. Lancez "docker compose up -d" avant.',
            $cfg['appContainer']
        ));

        return;
    }

    authRun($cfg);
}

// ─────────────────────────────────────────────────────────────────────────────
// Logique commune
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Orchestre le changement de mécanisme d'authentification pour une stack donnée.
 *
 * @param array{project: string, composeFile: string, appContainer: string, dbContainer: string, authConfFile: string, appPort: string, isWorktree: bool} $cfg
 */
function authRun(array $cfg): void
{
    $repoChoice = io()->choice(
        'Dépôt à tester',
        [
            'migration — formulaire Symfony (PREVARISC_SYMFONY_LOGIN=1)',
            'legacy    — formulaire Zend    (PREVARISC_SYMFONY_LOGIN=0)',
        ]
    );
    $repo = str_starts_with($repoChoice, 'migration') ? 'migration' : 'legacy';

    $modeChoice = io()->choice(
        'Mécanisme d\'authentification',
        [
            'form — formulaire + base de données (dbtable)',
            'ldap — annuaire LDAP / Active Directory',
            'cas  — SSO CAS Apereo',
            'ntlm — SSO NTLM simulé (REMOTE_USER)',
        ]
    );
    $mode = explode(' ', trim($modeChoice))[0];

    $symfonyLogin = 'migration' === $repo ? '1' : '0';
    $formLabel = 'migration' === $repo ? 'Symfony' : 'Zend (legacy)';

    io()->title(sprintf(
        'Authentification — mode : %s | repo : %s → formulaire %s',
        strtoupper($mode),
        $repo,
        $formLabel
    ));

    // ── 1. Configuration Apache ────────────────────────────────────────────────
    io()->section('1/5 — Configuration Apache');
    // PREVARISC_SYMFONY_LOGIN est écrit dans le fichier auth conf (pas dans httpd-prevarisc-config.conf)
    // afin d'éviter d'affecter l'autre stack qui partage httpd-prevarisc-config.conf.
    authWriteConf($mode, $symfonyLogin, $cfg['authConfFile']);
    io()->success('Fichier Apache mis à jour.');

    // ── 2. Redémarrage du container app ───────────────────────────────────────
    io()->section('2/5 — Redémarrage du container applicatif');
    authRestartApp($cfg);
    io()->success('Container applicatif prêt.');

    // ── 3. Services SSO ────────────────────────────────────────────────────────
    io()->section('3/5 — Services SSO');
    authManageSsoServices($mode, $cfg);

    // ── 4. Utilisateur de test ─────────────────────────────────────────────────
    io()->section('4/5 — Utilisateur de test en base');
    authEnsureTestUser($mode, $cfg['dbContainer']);

    // ── 5. Instructions ────────────────────────────────────────────────────────
    io()->section('5/5 — Prêt à tester');
    authShowInstructions($mode, $repo, $cfg['appPort']);
}

// ─────────────────────────────────────────────────────────────────────────────
// Fonctions internes
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Génère le contenu du fichier auth Apache selon le mode d'authentification.
 *
 * Inclut PREVARISC_SYMFONY_LOGIN pour éviter de toucher httpd-prevarisc-config.conf,
 * fichier partagé entre la stack principale et le worktree.
 *
 * @param string $mode         form|ldap|cas|ntlm
 * @param string $symfonyLogin 0|1
 * @param string $authConfFile Chemin vers le fichier auth de la stack cible
 */
function authWriteConf(string $mode, string $symfonyLogin, string $authConfFile): void
{
    $symfonyLoginLine = "SetEnv PREVARISC_SYMFONY_LOGIN {$symfonyLogin}";

    $content = match ($mode) {
        'ldap' => <<<CONF
            # Mode LDAP — géré par castor authentification. Ne pas modifier manuellement.
            # Serveur OpenLDAP local simulant un Active Directory (sAMAccountName + objectClass: user).
            {$symfonyLoginLine}
            SetEnv PREVARISC_LDAP_ENABLED 1
            SetEnv PREVARISC_LDAP_HOST "ldap"
            SetEnv PREVARISC_LDAP_PORT "389"
            SetEnv PREVARISC_LDAP_USERNAME "cn=admin,dc=prevarisc,dc=local"
            SetEnv PREVARISC_LDAP_PASSWORD "planmusique"
            SetEnv PREVARISC_LDAP_BASEDN "ou=users,dc=prevarisc,dc=local"
            SetEnv PREVARISC_LDAP_FILTER "(sAMAccountName=%s)"
            SetEnv PREVARISC_LDAP_ACCOUNT_FORM "1"
            CONF,
        'cas' => <<<CONF
            # Mode CAS — géré par castor authentification. Ne pas modifier manuellement.
            # Serveur Apereo CAS en HTTP uniquement (dev). Première pull ~700 Mo, démarrage ~60 s (JVM).
            {$symfonyLoginLine}
            SetEnv PREVARISC_CAS_ENABLED 1
            SetEnv PREVARISC_CAS_HOST "host.docker.internal"
            SetEnv PREVARISC_CAS_PORT "8083"
            SetEnv PREVARISC_CAS_PUBLIC_HOST "localhost"
            SetEnv PREVARISC_CAS_PUBLIC_PORT "8083"
            SetEnv PREVARISC_CAS_CONTEXT "cas"
            SetEnv PREVARISC_CAS_NO_SERVER_VALIDATION "1"
            SetEnv PREVARISC_CAS_NO_SSL "1"
            SetEnv PREVARISC_CAS_VERSION "2.0"
            CONF,
        'ntlm' => <<<CONF
            # Mode NTLM simulé — géré par castor authentification. Ne pas modifier manuellement.
            # REMOTE_USER positionné directement (simulation dev — pas de Kerberos/Samba).
            {$symfonyLoginLine}
            SetEnv PREVARISC_NTLM_ENABLED 1
            SetEnv REMOTE_USER "testntlm"
            CONF,
        default => <<<CONF
            # Mode form (dbtable uniquement) — géré par castor authentification.
            # Aucune configuration SSO : authentification par formulaire et base de données.
            {$symfonyLoginLine}
            CONF,
    };

    // Normaliser l'indentation (heredoc avec indentation PHP 7.3+)
    $lines = array_map(fn (string $l) => ltrim($l), explode("\n", $content));
    file_put_contents($authConfFile, implode("\n", $lines));
}

/**
 * Recrée le container app.
 * Pour le worktree, préserve les répertoires montés du worktree actif.
 * Le --force-recreate recharge les variables Apache du fichier auth conf.
 *
 * @param array{project: string, composeFile: string, appContainer: string, dbContainer: string, authConfFile: string, appPort: string, isWorktree: bool} $cfg
 */
function authRestartApp(array $cfg): void
{
    $composeArgs = [
        'docker', 'compose',
        '--project-name', $cfg['project'],
        '--file', $cfg['composeFile'],
    ];

    if ($cfg['isWorktree']) {
        $prevariscDir = authGetMountSource('/var/www/html/prevarisc', $cfg['appContainer']) ?? 'prevarisc';
        $migrationDir = authGetMountSource('/var/www/html/prevarisc-migration', $cfg['appContainer']) ?? 'prevarisc-migration';

        io()->text(sprintf(
            'Worktree actif : <info>%s</info> + <info>%s</info>',
            $prevariscDir,
            $migrationDir
        ));

        $ctx = context()->withEnvironment([
            'PREVARISC_DIR'           => $prevariscDir,
            'PREVARISC_MIGRATION_DIR' => $migrationDir,
        ]);

        exit_code([...$composeArgs, 'up', '-d', '--force-recreate', '--remove-orphans', 'app'], $ctx);
    } else {
        exit_code([...$composeArgs, 'up', '-d', '--force-recreate', 'app']);
    }

    wait_for_docker_container(
        containerName: $cfg['appContainer'],
        message: 'Attente du démarrage du container applicatif...'
    );
}

/**
 * Démarre les services SSO requis (sous le projet de la stack cible) et arrête ceux qui ne le sont pas.
 *
 * @param string $mode form|ldap|cas|ntlm
 * @param array{project: string, composeFile: string, appContainer: string, dbContainer: string, authConfFile: string, appPort: string, isWorktree: bool} $cfg
 */
function authManageSsoServices(string $mode, array $cfg): void
{
    $composeArgs = [
        'docker', 'compose',
        '--project-name', $cfg['project'],
        '--file', $cfg['composeFile'],
        '--file', AUTH_COMPOSE_AUTH_FILE,
    ];

    /** @var array<string> $toStart */
    $toStart = match ($mode) {
        'ldap'  => ['ldap', 'phpldapadmin'],
        'cas'   => ['cas'],
        default => [],
    };

    /** @var array<string> $allSso */
    $allSso = ['ldap', 'phpldapadmin', 'cas'];
    $toStop = array_values(array_filter($allSso, fn (string $s) => !in_array($s, $toStart, true)));

    foreach ($toStop as $service) {
        $containerName = sprintf('%s-%s-1', $cfg['project'], $service);
        if (authIsContainerRunning($containerName)) {
            io()->text("Arrêt de <comment>{$service}</comment>...");
            exit_code([...$composeArgs, 'stop', $service]);
        }
    }

    if ([] !== $toStart) {
        io()->text('Démarrage de : <info>' . implode(', ', $toStart) . '</info>');
        exit_code([...$composeArgs, 'up', '-d', ...$toStart]);

        if ('ldap' === $mode) {
            authWaitForLdap($cfg['project']);
        }

        if ('cas' === $mode) {
            io()->warning('Le serveur CAS (JVM) peut prendre 30 à 60 secondes. Patientez avant de tester.');
        }
    } else {
        io()->text('Aucun service SSO requis pour ce mode.');
    }
}

/**
 * Vérifie l'existence de l'utilisateur de test en base et le crée si absent.
 *
 * @param string $mode        form|ldap|cas|ntlm
 * @param string $dbContainer Nom du container MySQL cible
 */
function authEnsureTestUser(string $mode, string $dbContainer): void
{
    if ('form' === $mode) {
        io()->text('Mode form : utilisez un compte existant (tout mot de passe MD5 valide).');

        return;
    }

    /**
     * @var array{login: string, sso: int} $user
     */
    $user = match ($mode) {
        'ldap'  => ['login' => 'testldap', 'sso' => 0],
        'cas'   => ['login' => 'testcas',  'sso' => 1],
        'ntlm'  => ['login' => 'testntlm', 'sso' => 1],
    };

    [$count, $queryOk] = authDbQuery($dbContainer, "SELECT COUNT(*) FROM UTILISATEUR WHERE LOGIN='{$user['login']}'");

    if (!$queryOk) {
        io()->warning('Impossible de vérifier l\'utilisateur en base (DB inaccessible ?).');

        return;
    }

    if ((int) $count > 0) {
        io()->success(sprintf('Utilisateur "%s" présent en base ✓', $user['login']));

        return;
    }

    io()->text(sprintf('Utilisateur "%s" absent — création...', $user['login']));

    $execOk = authDbExec(
        $dbContainer,
        "INSERT INTO UTILISATEUR (LOGIN, PASSWORD, ACTIF, SSO, PROFIL_ID) VALUES ('{$user['login']}', '', 1, {$user['sso']}, 1)"
    );

    if ($execOk) {
        io()->success(sprintf('Utilisateur "%s" créé (PROFIL_ID=1, Administrateur).', $user['login']));
    } else {
        io()->error(sprintf('Échec de la création de l\'utilisateur "%s". Vérifiez les logs MySQL.', $user['login']));
    }
}

/**
 * Affiche un récapitulatif des informations de test.
 *
 * @param string $mode    form|ldap|cas|ntlm
 * @param string $repo    migration|legacy
 * @param string $appPort Port HTTP de l'application
 */
function authShowInstructions(string $mode, string $repo, string $appPort): void
{
    $symfonyLoginLabel = 'migration' === $repo
        ? '1 → formulaire Symfony'
        : '0 → formulaire Zend (legacy)';

    /** @var array<array{string, string}> $rows */
    $rows = [
        ['URL', sprintf('http://localhost:%s/session/login', $appPort)],
        ['PREVARISC_SYMFONY_LOGIN', $symfonyLoginLabel],
        ['Mécanisme', strtoupper($mode)],
    ];

    switch ($mode) {
        case 'ldap':
            $rows[] = ['Login', 'testldap'];
            $rows[] = ['Mot de passe', 'Prevarisc2024'];
            $rows[] = ['PhpLDAPadmin', 'http://localhost:8089  (cn=admin,dc=prevarisc,dc=local / planmusique)'];
            break;

        case 'cas':
            $rows[] = ['Login', 'testcas'];
            $rows[] = ['Mot de passe', 'Prevarisc2024  (saisie sur le formulaire CAS → http://localhost:8083/cas)'];
            break;

        case 'ntlm':
            $rows[] = ['Login', 'testntlm'];
            $rows[] = ['Authentification', 'Automatique (REMOTE_USER simulé — aucune saisie)'];
            break;

        case 'form':
            $rows[] = ['Login', 'Tout compte existant en base'];
            $rows[] = ['Mot de passe', 'Mot de passe MD5 (legacy) ou MD5/bcrypt (migration)'];
            break;
    }

    io()->table(['', ''], $rows);
}

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Retourne le nom du répertoire source monté pour une destination donnée dans un container.
 * Utilisé par le worktree pour détecter les repos actifs sans casser les volumes au redémarrage.
 *
 * @param string $destination  Chemin de destination dans le container (ex: /var/www/html/prevarisc)
 * @param string $appContainer Nom du container à inspecter
 */
function authGetMountSource(string $destination, string $appContainer): ?string
{
    $output = [];
    exec(
        sprintf('docker inspect %s --format "{{json .Mounts}}" 2>/dev/null', escapeshellarg($appContainer)),
        $output,
        $code
    );

    if (0 !== $code || [] === $output) {
        return null;
    }

    /** @var array<array{Destination: string, Source: string}>|null $mounts */
    $mounts = json_decode(implode('', $output), true);

    if (!is_array($mounts)) {
        return null;
    }

    foreach ($mounts as $mount) {
        if (($mount['Destination'] ?? '') === $destination) {
            return basename($mount['Source'] ?? '');
        }
    }

    return null;
}

/**
 * Vérifie si un container Docker est en cours d'exécution.
 */
function authIsContainerRunning(string $name): bool
{
    $output = [];
    exec(sprintf('docker inspect %s --format "{{.State.Running}}" 2>/dev/null', escapeshellarg($name)), $output);

    return !empty($output) && 'true' === trim($output[0]);
}

/**
 * Attend que le serveur LDAP soit opérationnel (max 20 secondes).
 *
 * @param string $project Nom du projet Docker (ex: worktree, prevarisc-infra)
 */
function authWaitForLdap(string $project): void
{
    io()->text('Vérification de la disponibilité du serveur LDAP...');

    $ldapContainer = sprintf('%s-ldap-1', $project);

    for ($i = 0; $i < 10; ++$i) {
        $output = [];
        exec(
            sprintf(
                'docker exec %s ldapsearch -x -H ldap://localhost:389'
                . ' -D "cn=admin,dc=prevarisc,dc=local" -w planmusique'
                . ' -b "ou=users,dc=prevarisc,dc=local" "(sAMAccountName=testldap)"'
                . ' 2>/dev/null | grep -c numEntries',
                escapeshellarg($ldapContainer)
            ),
            $output
        );

        if (!empty($output) && (int) $output[0] > 0) {
            io()->success('Serveur LDAP opérationnel ✓');

            return;
        }

        sleep(2);
    }

    io()->warning(sprintf(
        'Le serveur LDAP n\'a pas répondu dans les délais. Vérifiez : docker logs %s',
        $ldapContainer
    ));
}

/**
 * Exécute une requête SELECT dans la DB et retourne [valeur, succès].
 *
 * @param string $dbContainer Nom du container MySQL
 * @param string $sql         Requête SQL SELECT
 *
 * @return array{string, bool}
 */
function authDbQuery(string $dbContainer, string $sql): array
{
    $output = [];
    exec(
        sprintf(
            'docker exec %s mysql -uprevarisc -pplanmusique PRV_prevarisc_v2 -e %s --skip-column-names 2>/dev/null',
            escapeshellarg($dbContainer),
            escapeshellarg($sql)
        ),
        $output,
        $code
    );

    return [trim(implode('', $output)), 0 === $code];
}

/**
 * Exécute une requête DML (INSERT/UPDATE/DELETE) dans la DB. Retourne true en cas de succès.
 *
 * @param string $dbContainer Nom du container MySQL
 * @param string $sql         Requête SQL DML
 */
function authDbExec(string $dbContainer, string $sql): bool
{
    $output = [];
    exec(
        sprintf(
            'docker exec %s mysql -uprevarisc -pplanmusique PRV_prevarisc_v2 -e %s 2>&1',
            escapeshellarg($dbContainer),
            escapeshellarg($sql)
        ),
        $output,
        $code
    );

    return 0 === $code;
}
