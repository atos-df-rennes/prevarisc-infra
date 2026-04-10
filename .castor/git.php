<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;
use function Castor\exit_code;
use function Castor\io;
use function Castor\run;

// ─────────────────────────────────────────────────────────────────────────────
// Constantes
// ─────────────────────────────────────────────────────────────────────────────

/** Fichier de persistance de l'état d'une cascade en cours. */
const MERGE_UPWARDS_STATE_FILE    = '.merge-upwards-state.json';

/** Fichier de contexte généré pour Copilot lors d'un conflit. */
const MERGE_UPWARDS_COPILOT_FILE  = '.copilot-conflict-context.txt';

/** @var array<string, string> Clé de dépôt → répertoire. */
const MERGE_UPWARDS_REPOS = [
    'prevarisc' => 'prevarisc',
    'migration' => 'prevarisc-migration',
];

// ─────────────────────────────────────────────────────────────────────────────
// Tâche publique
// ─────────────────────────────────────────────────────────────────────────────

#[AsTask(
    name: 'merge-upwards',
    namespace: 'git',
    description: 'Propage un fix en cascade vers les branches supérieures (merge upwards)'
)]
function gitMergeUpwards(
    #[ASOption(
        name: 'from',
        mode: InputOption::VALUE_OPTIONAL,
        description: 'Branche source du fix (ex: release/2.8). Détectée automatiquement si omise.'
    )]
    ?string $from,
    #[ASOption(
        name: 'continue',
        mode: InputOption::VALUE_NONE,
        description: 'Reprend la cascade après résolution manuelle ou Copilot d\'un conflit.'
    )]
    bool $continueFlag,
    #[ASOption(
        name: 'repos',
        mode: InputOption::VALUE_OPTIONAL,
        description: 'Dépôts concernés : all, prevarisc, migration (défaut: auto-détection des deux).'
    )]
    string $repos = 'auto'
): void {
    io()->title('Merge upwards — propagation en cascade');

    if ($continueFlag) {
        resumeUpwardsCascade();
        return;
    }

    // Cascade déjà en cours ?
    if (file_exists(MERGE_UPWARDS_STATE_FILE)) {
        $resume = io()->confirm('⚠️  Une cascade est déjà en cours. Reprendre où elle s\'est arrêtée ?', true);
        if ($resume) {
            resumeUpwardsCascade();
            return;
        }
        clearUpwardsState();
    }

    // 1. Dépôts
    $repoMap = buildRepoMap($repos);
    if ([] === $repoMap) {
        io()->error('Aucun dépôt trouvé. Vérifiez que prevarisc/ et/ou prevarisc-migration/ existent.');
        return;
    }

    // 2. Branche source
    $sourceBranch = $from ?? pickSourceBranch(array_values($repoMap));
    if (null === $sourceBranch) {
        return;
    }

    io()->text("Branche source : <info>{$sourceBranch}</info>");

    // 3. Construction des étapes de cascade
    /** @var array<int, array{repo: string, dir: string, from: string, to: string}> $steps */
    $steps = [];
    foreach ($repoMap as $repoKey => $repoDir) {
        $chain = buildCascadeChain($repoDir, $sourceBranch);

        if (count($chain) < 2) {
            io()->comment("  {$repoKey} ({$repoDir}) : <info>{$sourceBranch}</info> introuvable ou déjà au sommet — ignoré.");
            continue;
        }

        for ($i = 0; $i < count($chain) - 1; ++$i) {
            $steps[] = [
                'repo' => $repoKey,
                'dir'  => $repoDir,
                'from' => $chain[$i],
                'to'   => $chain[$i + 1],
            ];
        }
    }

    if ([] === $steps) {
        io()->warning('Rien à cascader : branche source absente ou déjà au sommet de la hiérarchie.');
        return;
    }

    // 4. Confirmation
    io()->section('Cascade planifiée');
    io()->table(
        ['Dépôt', 'De', '', 'Vers'],
        array_map(static fn (array $s): array => [$s['repo'], $s['from'], '→', $s['to']], $steps)
    );

    if (!io()->confirm('Lancer la cascade ?', true)) {
        return;
    }

    // 5. Persistance + exécution
    saveUpwardsState(['steps' => $steps, 'index' => 0]);
    runUpwardsCascade();
}

// ─────────────────────────────────────────────────────────────────────────────
// Moteur de cascade
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Exécute la cascade depuis l'index sauvegardé.
 */
function runUpwardsCascade(): void
{
    $state = loadUpwardsState();
    if (null === $state) {
        io()->error('État introuvable. Lancez la commande sans --continue pour une nouvelle cascade.');
        return;
    }

    $steps = $state['steps'];
    $index = $state['index'];

    while ($index < count($steps)) {
        $step = $steps[$index];
        saveUpwardsState(['steps' => $steps, 'index' => $index]);

        $ok = executeMergeStep($step);

        if (!$ok) {
            // Conflit : pause
            saveUpwardsState(['steps' => $steps, 'index' => $index]);
            handleMergeConflict($step);
            return;
        }

        ++$index;
    }

    clearUpwardsState();
    io()->success('✓ Cascade terminée ! Tous les merges ont été poussés vers origin.');
}

/**
 * Reprend la cascade après résolution d'un conflit.
 */
function resumeUpwardsCascade(): void
{
    $state = loadUpwardsState();
    if (null === $state) {
        io()->error('Aucune cascade en cours. Relancez sans --continue.');
        return;
    }

    $step = $state['steps'][$state['index']];
    $dir  = $step['dir'];

    // Vérifier que les conflits sont bien résolus
    $unmerged = [];
    exec('git -C ' . escapeshellarg($dir) . ' ls-files --unmerged 2>/dev/null', $unmerged);

    if ([] !== $unmerged) {
        io()->error("Conflits non résolus dans <info>{$dir}</info>. Réglez-les avant de relancer --continue.");
        io()->listing([
            'git -C ' . $dir . ' status',
            'git -C ' . $dir . ' add .',
            'git -C ' . $dir . ' merge --continue',
            'castor git:merge-upwards --continue',
        ]);
        return;
    }

    if (file_exists($dir . '/.git/MERGE_HEAD')) {
        io()->error("Le merge n'est pas encore commité dans <info>{$dir}</info>.");
        io()->text('Commitez, puis relancez : <info>castor git:merge-upwards --continue</info>');
        return;
    }

    // Merge commité — push
    io()->section("Push {$step['to']} ({$step['repo']})");
    $pushCode = exit_code(['git', '-C', $dir, 'push', 'origin', $step['to']]);

    if (0 !== $pushCode) {
        io()->error("Le push de <info>{$step['to']}</info> a échoué. Résolvez le problème et relancez --continue.");
        return;
    }

    $state['index']++;
    saveUpwardsState($state);
    runUpwardsCascade();
}

// ─────────────────────────────────────────────────────────────────────────────
// Exécution d'une étape
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Exécute une étape de merge (fetch + checkout + pull + merge + push).
 * Retourne true si succès, false si conflit ou erreur.
 *
 * @param array{repo: string, dir: string, from: string, to: string} $step
 */
function executeMergeStep(array $step): bool
{
    $dir  = $step['dir'];
    $from = $step['from'];
    $to   = $step['to'];

    io()->section("  {$step['repo']} : {$from} → {$to}");

    // Mise à jour de la cible depuis origin
    exit_code(['git', '-C', $dir, 'fetch', 'origin', $to]);

    if (0 !== exit_code(['git', '-C', $dir, 'checkout', $to])) {
        io()->error("  Impossible de checkout <info>{$to}</info> dans {$dir}.");
        return false;
    }

    // Fast-forward si possible, sinon merge pull
    $pullCode = exit_code(['git', '-C', $dir, 'pull', '--ff-only', 'origin', $to]);
    if (0 !== $pullCode) {
        io()->comment("  Pull fast-forward impossible sur {$to} — merge pull.");
        exit_code(['git', '-C', $dir, 'pull', 'origin', $to]);
    }

    // Merge de la branche source dans la cible
    $mergeCode = exit_code([
        'git', '-C', $dir,
        'merge', '--no-ff', $from,
        '-m', "chore: merge {$from} into {$to}

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>",
    ]);

    if (0 !== $mergeCode) {
        return false; // conflit détecté
    }

    // Push
    $pushCode = exit_code(['git', '-C', $dir, 'push', 'origin', $to]);
    if (0 !== $pushCode) {
        io()->error("  Push de <info>{$to}</info> échoué dans {$dir}.");
        return false;
    }

    io()->text("  ✓ {$from} → {$to}");
    return true;
}

// ─────────────────────────────────────────────────────────────────────────────
// Gestion des conflits
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Affiche le menu de résolution de conflit (manuelle ou Copilot).
 *
 * @param array{repo: string, dir: string, from: string, to: string} $step
 */
function handleMergeConflict(array $step): void
{
    $dir  = $step['dir'];
    $from = $step['from'];
    $to   = $step['to'];

    $conflictedFiles = [];
    exec('git -C ' . escapeshellarg($dir) . ' diff --name-only --diff-filter=U 2>/dev/null', $conflictedFiles);

    io()->newLine();
    io()->writeln('<fg=yellow;options=bold>⚠  Conflit de merge</>');
    io()->writeln("   Dépôt  : <info>{$step['repo']}</info>  ({$dir})");
    io()->writeln("   Merge  : <info>{$from}</info> → <info>{$to}</info>");
    io()->writeln("   Fichiers en conflit :");
    foreach ($conflictedFiles as $file) {
        io()->writeln("     · {$file}");
    }
    io()->newLine();

    $choice = io()->choice(
        'Comment souhaitez-vous résoudre ce conflit ?',
        [
            'manual'  => 'Résoudre manuellement dans l\'éditeur, puis --continue',
            'copilot' => 'Générer le contexte pour Copilot (1 requête), puis --continue',
        ],
        'manual'
    );

    if ('manual' === $choice) {
        displayManualInstructions($step, $conflictedFiles);
        return;
    }

    generateCopilotContext($step, $conflictedFiles);
}

/**
 * Affiche les instructions de résolution manuelle.
 *
 * @param array{repo: string, dir: string, from: string, to: string} $step
 * @param string[]                                                    $conflictedFiles
 */
function displayManualInstructions(array $step, array $conflictedFiles): void
{
    $dir = $step['dir'];

    io()->section('Résolution manuelle');
    io()->text('1. Ouvrez les fichiers en conflit et supprimez les marqueurs <<<<<<, =======, >>>>>>>');
    io()->newLine();
    foreach ($conflictedFiles as $file) {
        io()->writeln("   <comment>{$dir}/{$file}</comment>");
    }
    io()->newLine();
    io()->text('2. Une fois résolu, exécutez :');
    io()->listing([
        "git -C {$dir} add .",
        "git -C {$dir} merge --continue",
        'castor git:merge-upwards --continue',
    ]);
}

/**
 * Génère le contexte de conflit structuré pour Copilot et l'affiche dans le terminal.
 *
 * @param array{repo: string, dir: string, from: string, to: string} $step
 * @param string[]                                                    $conflictedFiles
 */
function generateCopilotContext(array $step, array $conflictedFiles): void
{
    $dir  = $step['dir'];
    $from = $step['from'];
    $to   = $step['to'];
    $bar  = str_repeat('─', 72);

    $output  = "Voici des conflits de merge Git à résoudre.\n";
    $output .= "Dépôt : {$step['repo']} | Opération : git merge --no-ff {$from} (dans {$to})\n\n";
    $output .= "Pour chaque fichier ci-dessous, fournis le contenu complet corrigé\n";
    $output .= "(sans aucun marqueur <<<<<<, =======, >>>>>>>).\n\n";

    foreach ($conflictedFiles as $file) {
        $filePath = $dir . '/' . $file;
        $content  = file_exists($filePath)
            ? file_get_contents($filePath)
            : "(fichier introuvable : {$filePath})";

        $output .= "{$bar}\n";
        $output .= "FICHIER : {$file}\n";
        $output .= "{$bar}\n";
        $output .= $content . "\n\n";
    }

    $output .= "{$bar}\n";
    $output .= "Après avoir reçu les fichiers corrigés, j'appliquerai les modifications puis :\n";
    $output .= "  git -C {$dir} add .\n";
    $output .= "  git -C {$dir} merge --continue\n";
    $output .= "  castor git:merge-upwards --continue\n";

    file_put_contents(MERGE_UPWARDS_COPILOT_FILE, $output);

    io()->section('Contexte Copilot généré');
    io()->writeln("Fichier sauvegardé : <info>" . MERGE_UPWARDS_COPILOT_FILE . "</info>");
    io()->newLine();
    io()->writeln('<options=bold>─── Copiez le contenu ci-dessous dans le chat Copilot ───</>');
    io()->newLine();
    io()->writeln($output);
    io()->writeln('<options=bold>─────────────────────────────────────────────────────────</>');
    io()->newLine();
    io()->text('Après avoir appliqué la résolution de Copilot, exécutez :');
    io()->listing([
        "git -C {$dir} add .",
        "git -C {$dir} merge --continue",
        'castor git:merge-upwards --continue',
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Construction de la hiérarchie de branches
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Retourne la chaîne de branches à partir de $fromBranch (incluse) vers le sommet.
 * Exemple pour from=release/2.8 : ['release/2.8', 'release/2.9', 'develop']
 *
 * @return string[]
 */
function buildCascadeChain(string $repoDir, string $fromBranch): array
{
    $hierarchy = buildBranchHierarchy($repoDir);
    $index     = array_search($fromBranch, $hierarchy, true);

    if (false === $index) {
        return [];
    }

    return array_values(array_slice($hierarchy, (int) $index));
}

/**
 * Retourne la hiérarchie complète de branches en ordre ascendant :
 * [main|master] → [release/x.y …] → [develop]
 *
 * @return string[]
 */
function buildBranchHierarchy(string $repoDir): array
{
    $raw = [];
    exec(
        'git -C ' . escapeshellarg($repoDir) . ' branch -r --format="%(refname:short)" 2>/dev/null',
        $raw
    );

    $bottom   = [];
    $releases = [];
    $hasDev   = false;

    foreach ($raw as $line) {
        $branch = trim((string) preg_replace('/^origin\//', '', trim($line)));

        if ('' === $branch || str_starts_with($branch, 'HEAD')) {
            continue;
        }

        if (\in_array($branch, ['main', 'master'], true)) {
            $bottom[] = $branch;
            continue;
        }

        if (str_starts_with($branch, 'release/')) {
            $releases[] = $branch;
            continue;
        }

        if ('develop' === $branch) {
            $hasDev = true;
        }
    }

    usort($releases, static function (string $a, string $b): int {
        return version_compare(
            substr($a, \strlen('release/')),
            substr($b, \strlen('release/'))
        );
    });

    $hierarchy = array_merge($bottom, $releases);

    if ($hasDev) {
        $hierarchy[] = 'develop';
    }

    return $hierarchy;
}

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Détecte la branche courante d'un dépôt Git.
 */
function detectCurrentBranch(string $repoDir): ?string
{
    $out = [];
    exec('git -C ' . escapeshellarg($repoDir) . ' branch --show-current 2>/dev/null', $out);

    $branch = trim($out[0] ?? '');
    return '' !== $branch ? $branch : null;
}

/**
 * Détecte ou demande interactivement la branche source.
 *
 * @param string[] $repoDirs
 */
function pickSourceBranch(array $repoDirs): ?string
{
    $detected = null;
    foreach ($repoDirs as $dir) {
        $b = detectCurrentBranch($dir);
        if (null !== $b && null === $detected) {
            $detected = $b;
        }
    }

    if (null !== $detected) {
        $ok = io()->confirm("Branche source détectée : <info>{$detected}</info>. Confirmer ?", true);
        if ($ok) {
            return $detected;
        }
    }

    $entered = io()->ask('Entrez la branche source (ex: release/2.8)');

    return ('' !== (string) $entered) ? $entered : null;
}

/**
 * Résout la map de dépôts selon l'option --repos.
 *
 * @return array<string, string>
 */
function buildRepoMap(string $option): array
{
    /** @var array<string, string> $available */
    $available = array_filter(MERGE_UPWARDS_REPOS, static fn (string $dir): bool => is_dir($dir));

    if (\in_array($option, ['auto', 'all'], true)) {
        return $available;
    }

    if ('prevarisc' === $option && isset($available['prevarisc'])) {
        return ['prevarisc' => $available['prevarisc']];
    }

    if ('migration' === $option && isset($available['migration'])) {
        return ['migration' => $available['migration']];
    }

    return $available;
}

// ─────────────────────────────────────────────────────────────────────────────
// Persistance de l'état
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @param array{steps: array<int, array{repo: string, dir: string, from: string, to: string}>, index: int} $state
 */
function saveUpwardsState(array $state): void
{
    file_put_contents(MERGE_UPWARDS_STATE_FILE, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * @return array{steps: array<int, array{repo: string, dir: string, from: string, to: string}>, index: int}|null
 */
function loadUpwardsState(): ?array
{
    if (!file_exists(MERGE_UPWARDS_STATE_FILE)) {
        return null;
    }

    $content = file_get_contents(MERGE_UPWARDS_STATE_FILE);
    if (false === $content) {
        return null;
    }

    /** @var array{steps: array<int, array{repo: string, dir: string, from: string, to: string}>, index: int}|null $decoded */
    $decoded = json_decode($content, true);

    return $decoded;
}

function clearUpwardsState(): void
{
    foreach ([MERGE_UPWARDS_STATE_FILE, MERGE_UPWARDS_COPILOT_FILE] as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
