<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;
use Castor\Attribute\AsOption;
use Symfony\Component\Console\Input\InputOption;
use function Castor\io;
use function Castor\yaml_parse;

/**
 * Lit le manifest YAML et scanne les deux repos pour produire un rapport
 * de progression de la migration Zend → Symfony.
 *
 * Usage :
 *   castor migration:progress              # rapport complet
 *   castor migration:progress --todo       # uniquement les tâches restantes
 *   castor migration:progress --parallel   # suggestion de tâches parallélisables
 */
#[AsTask(name: 'progress', namespace: 'migration', description: 'Rapport de progression de la migration Zend → Symfony')]
function migrationProgress(
    #[AsOption(name: 'todo', mode: InputOption::VALUE_NONE, description: 'Affiche uniquement les tâches non terminées')]
    bool $todo,
    #[AsOption(name: 'parallel', mode: InputOption::VALUE_NONE, description: 'Suggère des paires de tâches parallélisables via worktree')]
    bool $parallel,
): void {
    io()->title('Migration Progress — Zend → Symfony');

    $manifestPath = __DIR__ . '/../docs/tech/migration/MANIFEST.yaml';
    if (!file_exists($manifestPath)) {
        io()->error('Manifest introuvable : ' . $manifestPath);
        return;
    }

    /** @var array{version: int, tasks: array<array<string, mixed>>} $manifest */
    $manifest = yaml_parse(file_get_contents($manifestPath));
    $tasks = $manifest['tasks'];

    // ── Compteurs globaux ──────────────────────────────────────────────────────
    $counts = ['done' => 0, 'partial' => 0, 'todo' => 0, 'skip' => 0];
    foreach ($tasks as $task) {
        $counts[$task['status']] = ($counts[$task['status']] ?? 0) + 1;
    }
    $total = array_sum($counts);
    $done = $counts['done'] + $counts['skip'];

    io()->section('📊 Progression globale');
    $progressBar = (int) round(($done / $total) * 20);
    $bar = str_repeat('█', $progressBar) . str_repeat('░', 20 - $progressBar);
    $percent = round(($done / $total) * 100);
    io()->writeln(sprintf('  [%s] %d%% (%d/%d tâches terminées)', $bar, $percent, $done, $total));
    io()->writeln('');
    io()->writeln(sprintf('  ✅ done    : %d', $counts['done']));
    io()->writeln(sprintf('  ⚠️  partial : %d', $counts['partial']));
    io()->writeln(sprintf('  ❌ todo    : %d', $counts['todo']));
    io()->writeln(sprintf('  ⏭️  skip    : %d', $counts['skip']));

    // ── Scan des dépôts pour validation ───────────────────────────────────────
    $migratedRoutes = _scanMigratedRoutes();
    $inconsistencies = _checkInconsistencies($tasks, $migratedRoutes);

    if (!empty($inconsistencies)) {
        io()->section('⚠️  Incohérences manifest ↔ code');
        io()->writeln('  Ces routes sont déclarées "done" dans le manifest mais introuvables dans le code :');
        foreach ($inconsistencies as $item) {
            io()->writeln(sprintf('  • [%s] route manquante : %s', $item['task'], $item['route']));
        }
    }

    // ── Tâches par priorité ───────────────────────────────────────────────────
    if ($parallel) {
        _displayParallelSuggestions($tasks);
        return;
    }

    $statusFilter = $todo ? ['partial', 'todo'] : ['done', 'partial', 'todo', 'skip'];
    $grouped = _groupByPriority($tasks, $statusFilter);

    foreach ($grouped as $priority => $priorityTasks) {
        io()->section(sprintf('🎯 Priorité %d', $priority));

        $rows = [];
        foreach ($priorityTasks as $task) {
            $statusIcon = match ($task['status']) {
                'done'    => '✅',
                'partial' => '⚠️ ',
                'todo'    => '❌',
                'skip'    => '⏭️ ',
                default   => '?',
            };
            $complexity = match ($task['complexity'] ?? 'medium') {
                'low'    => '🟢 low',
                'medium' => '🟡 med',
                'high'   => '🔴 high',
                default  => '?',
            };
            $entities = implode(', ', $task['entities'] ?? []);

            $rows[] = [
                $statusIcon . ' ' . ($task['id'] ?? ''),
                $task['description'] ?? '',
                $complexity,
                $entities,
                $task['notes'] ?? '',
            ];
        }

        io()->table(
            ['ID', 'Description', 'Complexité', 'Entités', 'Notes'],
            $rows
        );
    }

    // ── Résumé des prochaines tâches ─────────────────────────────────────────
    $nextTasks = array_filter($tasks, fn ($t) => in_array($t['status'], ['partial', 'todo'], true));
    usort($nextTasks, fn ($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));
    $nextTasks = array_slice(array_values($nextTasks), 0, 3);

    if (!empty($nextTasks)) {
        io()->section('🚀 Prochaines tâches recommandées');
        foreach ($nextTasks as $i => $task) {
            $status = $task['status'] === 'partial' ? '(en cours)' : '(non démarré)';
            io()->writeln(sprintf('  %d. [%s] %s %s', $i + 1, $task['id'], $task['description'], $status));
            if (!empty($task['notes'])) {
                io()->writeln(sprintf('     → %s', mb_substr($task['notes'], 0, 120)));
            }
        }
    }
}

/**
 * @param array<array<string, mixed>> $tasks
 */
function _displayParallelSuggestions(array $tasks): void
{
    io()->section('🔀 Tâches parallélisables (worktree)');

    $todoTasks = array_filter($tasks, fn ($t) => in_array($t['status'], ['partial', 'todo'], true));
    $todoTasks = array_values($todoTasks);

    $pairs = [];
    for ($i = 0; $i < count($todoTasks); $i++) {
        for ($j = $i + 1; $j < count($todoTasks); $j++) {
            $a = $todoTasks[$i];
            $b = $todoTasks[$j];
            $sharedEntities = array_intersect($a['entities'] ?? [], $b['entities'] ?? []);
            if (empty($sharedEntities)) {
                $pairs[] = [$a, $b];
            }
        }
    }

    if (empty($pairs)) {
        io()->writeln('  Aucune paire de tâches parallélisables identifiée (toutes partagent des entités).');
        return;
    }

    // Limiter aux meilleures paires (priorité la plus haute)
    usort($pairs, function (array $pairA, array $pairB): int {
        $scoreA = ($pairA[0]['priority'] ?? 99) + ($pairA[1]['priority'] ?? 99);
        $scoreB = ($pairB[0]['priority'] ?? 99) + ($pairB[1]['priority'] ?? 99);
        return $scoreA <=> $scoreB;
    });

    $displayed = 0;
    foreach ($pairs as $pair) {
        if ($displayed >= 5) {
            break;
        }
        [$a, $b] = $pair;
        io()->writeln(sprintf(
            '  • [%s] + [%s]',
            $a['id'],
            $b['id']
        ));
        io()->writeln(sprintf('      "%s"', $a['description']));
        io()->writeln(sprintf('      "%s"', $b['description']));
        io()->writeln(sprintf(
            '      Entités A: [%s] | Entités B: [%s] → aucun conflit',
            implode(', ', $a['entities'] ?? []),
            implode(', ', $b['entities'] ?? [])
        ));
        io()->writeln('');
        $displayed++;
    }

    io()->writeln(sprintf('  Total paires parallélisables : %d', count($pairs)));
    io()->writeln('');
    io()->writeln('  Pour travailler en parallèle :');
    io()->writeln('    git -C prevarisc-migration worktree add ../prevarisc-migration-worktree-<feature> -b feat/<feature> release/2.8');
    io()->writeln('  → Voir WORKFLOW_WORKTREE.md pour le guide complet.');
}

/**
 * @return string[]
 */
function _scanMigratedRoutes(): array
{
    $controllerDir = __DIR__ . '/../prevarisc-migration/src/Controller';
    if (!is_dir($controllerDir)) {
        return [];
    }

    $routes = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllerDir));
    foreach ($iterator as $file) {
        if (!($file instanceof SplFileInfo) || $file->getExtension() !== 'php') {
            continue;
        }
        $content = file_get_contents($file->getPathname());
        if (preg_match_all('/name="([^"]+)"/', $content, $matches)) {
            foreach ($matches[1] as $route) {
                $routes[] = $route;
            }
        }
    }

    return array_unique($routes);
}

/**
 * @param array<array<string, mixed>> $tasks
 * @param string[] $migratedRoutes
 * @return array<array{task: string, route: string}>
 */
function _checkInconsistencies(array $tasks, array $migratedRoutes): array
{
    $issues = [];
    foreach ($tasks as $task) {
        if ($task['status'] !== 'done') {
            continue;
        }
        foreach ($task['routes'] ?? [] as $route) {
            if (!in_array($route, $migratedRoutes, true)) {
                $issues[] = ['task' => $task['id'], 'route' => $route];
            }
        }
    }
    return $issues;
}

/**
 * @param array<array<string, mixed>> $tasks
 * @param string[] $statusFilter
 * @return array<int, array<array<string, mixed>>>
 */
function _groupByPriority(array $tasks, array $statusFilter): array
{
    $grouped = [];
    foreach ($tasks as $task) {
        if (!in_array($task['status'], $statusFilter, true)) {
            continue;
        }
        $priority = (int) ($task['priority'] ?? 99);
        $grouped[$priority][] = $task;
    }
    ksort($grouped);
    return $grouped;
}
