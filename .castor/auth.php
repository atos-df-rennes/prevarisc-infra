<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\exit_code;
use function Castor\io;
use function Castor\wait_for_docker_container;

const AUTH_WORKTREE_CONF = 'apache/httpd-auth-worktree.conf';
const AUTH_PREVARISC_CONF = 'apache/httpd-prevarisc-config.conf';
const AUTH_COMPOSE_AUTH_FILE = 'compose.worktree-auth.yaml';

/**
 * Bascule le mécanisme d'authentification et le repo cible pour les tests.
 *
 * Gère automatiquement :
 *   - La configuration Apache (httpd-auth-worktree.conf + PREVARISC_SYMFONY_LOGIN)
 *   - Le redémarrage du container app avec préservation du worktree actif
 *   - Le démarrage/arrêt des services SSO (ldap, phpldapadmin, cas)
 *   - La création de l'utilisateur de test en base si absent
 */
#[AsTask(name: 'authentification', description: 'Bascule le mécanisme d\'authentification pour tests (sélection interactive)')]
function authentification(): void
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
    authWriteConf($mode);
    authUpdateSymfonyLogin($symfonyLogin);
    io()->success('Fichiers Apache mis à jour.');

    // ── 2. Redémarrage du container app ───────────────────────────────────────
    io()->section('2/5 — Redémarrage du container applicatif');
    authRestartApp();
    io()->success('Container applicatif prêt.');

    // ── 3. Services SSO ────────────────────────────────────────────────────────
    io()->section('3/5 — Services SSO');
    authManageSsoServices($mode);

    // ── 4. Utilisateur de test ─────────────────────────────────────────────────
    io()->section('4/5 — Utilisateur de test en base');
    authEnsureTestUser($mode);

    // ── 5. Instructions ────────────────────────────────────────────────────────
    io()->section('5/5 — Prêt à tester');
    authShowInstructions($mode, $repo);
}

// ─────────────────────────────────────────────────────────────────────────────
// Fonctions internes
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Génère le contenu de httpd-auth-worktree.conf selon le mode d'authentification.
 * Ce fichier est monté dans le container et inclus par IncludeOptional httpd-auth-*.conf.
 */
function authWriteConf(string $mode): void
{
    $content = match ($mode) {
        'ldap' => <<<'CONF'
            # Mode LDAP — géré par castor authentification. Ne pas modifier manuellement.
            # Serveur OpenLDAP local simulant un Active Directory (sAMAccountName + objectClass: user).
            SetEnv PREVARISC_LDAP_ENABLED 1
            SetEnv PREVARISC_LDAP_HOST "ldap"
            SetEnv PREVARISC_LDAP_PORT "389"
            SetEnv PREVARISC_LDAP_USERNAME "cn=admin,dc=prevarisc,dc=local"
            SetEnv PREVARISC_LDAP_PASSWORD "planmusique"
            SetEnv PREVARISC_LDAP_BASEDN "ou=users,dc=prevarisc,dc=local"
            SetEnv PREVARISC_LDAP_FILTER "(sAMAccountName=%s)"
            SetEnv PREVARISC_LDAP_ACCOUNT_FORM "1"
            CONF,
        'cas' => <<<'CONF'
            # Mode CAS — géré par castor authentification. Ne pas modifier manuellement.
            # Serveur Apereo CAS en HTTP uniquement (dev). Première pull ~700 Mo, démarrage ~60 s (JVM).
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
        'ntlm' => <<<'CONF'
            # Mode NTLM simulé — géré par castor authentification. Ne pas modifier manuellement.
            # REMOTE_USER positionné directement (simulation dev — pas de Kerberos/Samba).
            SetEnv PREVARISC_NTLM_ENABLED 1
            SetEnv REMOTE_USER "testntlm"
            CONF,
        default => <<<'CONF'
            # Mode form (dbtable uniquement) — géré par castor authentification.
            # Aucune configuration SSO : authentification par formulaire et base de données.
            CONF,
    };

    // Normaliser l'indentation (heredoc avec indentation PHP 7.3+)
    $lines = array_map(fn (string $l) => ltrim($l), explode("\n", $content));
    file_put_contents(AUTH_WORKTREE_CONF, implode("\n", $lines));
}

/**
 * Met à jour PREVARISC_SYMFONY_LOGIN dans httpd-prevarisc-config.conf.
 */
function authUpdateSymfonyLogin(string $value): void
{
    $config = (string) file_get_contents(AUTH_PREVARISC_CONF);
    $updated = (string) preg_replace(
        '/^SetEnv PREVARISC_SYMFONY_LOGIN\s+\S+/m',
        "SetEnv PREVARISC_SYMFONY_LOGIN {$value}",
        $config
    );
    file_put_contents(AUTH_PREVARISC_CONF, $updated);
}

/**
 * Recrée le container app en préservant les volumes du worktree actif.
 * Le --force-recreate est nécessaire pour prendre en compte les changements
 * de httpd-auth-worktree.conf et httpd-prevarisc-config.conf.
 */
function authRestartApp(): void
{
    $prevariscDir = authGetMountSource('/var/www/html/prevarisc') ?? 'prevarisc';
    $migrationDir = authGetMountSource('/var/www/html/prevarisc-migration') ?? 'prevarisc-migration';

    io()->text(sprintf(
        'Worktree actif : <info>%s</info> + <info>%s</info>',
        $prevariscDir,
        $migrationDir
    ));

    $ctx = context()->withEnvironment([
        'PREVARISC_DIR' => $prevariscDir,
        'PREVARISC_MIGRATION_DIR' => $migrationDir,
    ]);

    exit_code([
        'docker', 'compose',
        '--project-name', WORKTREE_PROJECT,
        '--file', WORKTREE_COMPOSE_FILE,
        'up', '-d', '--force-recreate', '--remove-orphans', 'app',
    ], $ctx);

    wait_for_docker_container(
        containerName: WORKTREE_APP_CONTAINER,
        message: 'Attente du démarrage du container applicatif...'
    );
}

/**
 * Démarre les services SSO requis et arrête ceux qui ne le sont pas.
 *
 * @param string $mode form|ldap|cas|ntlm
 */
function authManageSsoServices(string $mode): void
{
    $composeArgs = [
        'docker', 'compose',
        '--project-name', WORKTREE_PROJECT,
        '--file', WORKTREE_COMPOSE_FILE,
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
        if (authIsContainerRunning("worktree-{$service}-1")) {
            io()->text("Arrêt de <comment>{$service}</comment>...");
            exit_code([...$composeArgs, 'stop', $service]);
        }
    }

    if ([] !== $toStart) {
        io()->text('Démarrage de : <info>' . implode(', ', $toStart) . '</info>');
        exit_code([...$composeArgs, 'up', '-d', ...$toStart]);

        if ('ldap' === $mode) {
            authWaitForLdap();
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
 * @param string $mode form|ldap|cas|ntlm
 */
function authEnsureTestUser(string $mode): void
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

    $count = authDbQuery(sprintf("SELECT COUNT(*) FROM UTILISATEUR WHERE LOGIN='%s'", $user['login']));

    if ((int) $count > 0) {
        io()->success(sprintf('Utilisateur "%s" présent en base ✓', $user['login']));

        return;
    }

    io()->text(sprintf('Utilisateur "%s" absent — création...', $user['login']));

    authDbExec(sprintf(
        "INSERT INTO UTILISATEUR (LOGIN, PASSWORD, ACTIF, SSO, PROFIL_ID) VALUES ('%s', '', 1, %d, 1)",
        $user['login'],
        $user['sso']
    ));

    io()->success(sprintf('Utilisateur "%s" créé (PROFIL_ID=1, Administrateur).', $user['login']));
}

/**
 * Affiche un récapitulatif des informations de test.
 *
 * @param string $mode form|ldap|cas|ntlm
 * @param string $repo migration|legacy
 */
function authShowInstructions(string $mode, string $repo): void
{
    $symfonyLoginLabel = 'migration' === $repo
        ? '1 → formulaire Symfony'
        : '0 → formulaire Zend (legacy)';

    /** @var array<array{string, string}> $rows */
    $rows = [
        ['URL', 'http://localhost:7081/session/login'],
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
 * Retourne le nom du répertoire source monté pour une destination donnée dans worktree-app-1.
 * Permet de détecter quel worktree est actif sans casser les volumes lors du redémarrage.
 */
function authGetMountSource(string $destination): ?string
{
    $output = [];
    exec(
        sprintf('docker inspect %s --format "{{json .Mounts}}" 2>/dev/null', WORKTREE_APP_CONTAINER),
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
    exec(sprintf('docker inspect %s --format "{{.State.Running}}" 2>/dev/null', $name), $output);

    return !empty($output) && 'true' === trim($output[0]);
}

/**
 * Attend que le serveur LDAP soit opérationnel (max 20 secondes).
 */
function authWaitForLdap(): void
{
    io()->text('Vérification de la disponibilité du serveur LDAP...');

    for ($i = 0; $i < 10; ++$i) {
        $output = [];
        exec(
            'docker exec worktree-ldap-1 ldapsearch -x -H ldap://localhost:389'
            . ' -D "cn=admin,dc=prevarisc,dc=local" -w planmusique'
            . ' -b "ou=users,dc=prevarisc,dc=local" "(sAMAccountName=testldap)"'
            . ' 2>/dev/null | grep -c numEntries',
            $output
        );

        if (!empty($output) && (int) $output[0] > 0) {
            io()->success('Serveur LDAP opérationnel ✓');

            return;
        }

        sleep(2);
    }

    io()->warning('Le serveur LDAP n\'a pas répondu dans les délais. Vérifiez : docker logs worktree-ldap-1');
}

/**
 * Exécute une requête SELECT dans la DB worktree et retourne la première valeur.
 */
function authDbQuery(string $sql): string
{
    $output = [];
    exec(
        sprintf(
            'docker exec %s mysql -uprevarisc -pplanmusique PRV_prevarisc_v2 -e "%s" --skip-column-names 2>/dev/null',
            WORKTREE_DB_CONTAINER,
            $sql
        ),
        $output
    );

    return trim(implode('', $output));
}

/**
 * Exécute une requête DML (INSERT/UPDATE/DELETE) dans la DB worktree.
 */
function authDbExec(string $sql): void
{
    exec(sprintf(
        'docker exec %s mysql -uprevarisc -pplanmusique PRV_prevarisc_v2 -e "%s" 2>/dev/null',
        WORKTREE_DB_CONTAINER,
        $sql
    ));
}
