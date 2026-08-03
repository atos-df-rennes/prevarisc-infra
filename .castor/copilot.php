<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;
use function Castor\io;

/**
 * Cleanup Copilot session folders older than X days.
 * 
 * Similar to cleanup_copilot_sessions.sh, this task lists and optionally deletes
 * session folders in ~/.copilot/session-state based on age threshold.
 */
#[AsTask(
    name: 'clean-sessions',
    namespace: 'copilot',
    description: 'List and optionally delete Copilot session folders older than N days'
)]
function copilotCleanSessions(
    #[ASOption(
        name: 'dry-run',
        mode: InputOption::VALUE_NONE,
        description: 'List matching sessions but do not delete'
    )]
    bool $dryRun,
    #[ASOption(
        name: 'age',
        shortcut: 'a',
        mode: InputOption::VALUE_REQUIRED,
        description: 'Age threshold in days (numeric) or "all" to match all sessions. Default: 90'
    )]
    string $age = '90'
): void {
    io()->title('Cleaning Copilot session folders');

    $sessionDir = getenv('HOME') . '/.copilot/session-state';

    if (!is_dir($sessionDir)) {
        io()->warning("Session directory not found: {$sessionDir}");
        return;
    }

    // Validate age parameter
    if ($age !== 'all' && !preg_match('/^\d+$/', $age)) {
        io()->error("Age must be a number of days or 'all'. Got: {$age}");
        exit(2);
    }

    // Find matching sessions
    $matches = findSessions($sessionDir, $age);
    $count = count($matches);

    io()->section('Search Results');
    if ($age === 'all') {
        io()->text("Listing <info>all</info> session folders in: {$sessionDir}");
    } else {
        io()->text("Listing session folders in: {$sessionDir} older than <info>{$age} days</info>");
    }
    io()->text("Found: <info>{$count}</info>");

    if ($count === 0) {
        io()->info('Nothing to do.');
        return;
    }

    // Display sessions with timestamps
    io()->newLine();
    io()->section('Matching Sessions');
    foreach ($matches as $path => $time) {
        io()->text(sprintf('%s    %s', $time, $path));
    }

    // Handle dry-run mode
    io()->newLine();
    if ($dryRun) {
        io()->info('Dry-run enabled; no deletions will be performed.');
        return;
    }

    // Ask for confirmation
    $confirm = io()->ask(
        "Delete these {$count} folders?",
        'no',
        static fn (string $answer) => strtolower($answer)
    );

    if ($confirm !== 'yes') {
        io()->info('Aborted by user; no changes made.');
        return;
    }

    // Delete folders
    io()->newLine();
    io()->section('Deletion Progress');
    $deleted = 0;
    $failed = 0;

    foreach ($matches as $path => $_) {
        if (deletePath($path)) {
            io()->writeln("Deleted: <info>{$path}</info>");
            $deleted++;
        } else {
            io()->writeln("Failed to delete: <error>{$path}</error>");
            $failed++;
        }
    }

    // Summary
    io()->newLine();
    io()->section('Summary');
    io()->text("Deleted: <info>{$deleted}</info>");
    if ($failed > 0) {
        io()->text("Failed: <error>{$failed}</error>");
    }
}

/**
 * Find session directories matching the age criteria.
 *
 * @param string $sessionDir Path to session directory
 * @param string $age        Age in days or "all"
 * 
 * @return array<string, string> Associative array of [path => formatted_timestamp]
 */
function findSessions(string $sessionDir, string $age): array
{
    $matches = [];

    if (!is_readable($sessionDir)) {
        return $matches;
    }

    $entries = @scandir($sessionDir, SCANDIR_SORT_ASCENDING);
    if ($entries === false) {
        return $matches;
    }

    // Remove . and ..
    $entries = array_filter($entries, static fn (string $name): bool => !in_array($name, ['.', '..'], true));

    // Filter by age if not "all"
    if ($age === 'all') {
        $pathsToCheck = $entries;
    } else {
        $ageDays = (int) $age;
        $cutoffTime = time() - ($ageDays * 86400); // 86400 seconds per day
        
        $pathsToCheck = array_filter(
            $entries,
            static function (string $name) use ($sessionDir, $cutoffTime): bool {
                $fullPath = $sessionDir . '/' . $name;
                if (!is_dir($fullPath)) {
                    return false;
                }
                $mtime = @filemtime($fullPath);
                return $mtime !== false && $mtime < $cutoffTime;
            }
        );
    }

    // Build result with formatted timestamps
    foreach ($pathsToCheck as $name) {
        $fullPath = $sessionDir . '/' . $name;
        if (!is_dir($fullPath)) {
            continue;
        }

        $mtime = @filemtime($fullPath);
        if ($mtime !== false) {
            $timeStr = date('Y-m-d H:i:s', $mtime);
        } else {
            $timeStr = 'UNKNOWN_TIME';
        }

        $matches[$fullPath] = $timeStr;
    }

    return $matches;
}

/**
 * Recursively delete a directory.
 *
 * @param string $path Path to delete
 * 
 * @return bool True on success, false on failure
 */
function deletePath(string $path): bool
{
    if (!is_dir($path)) {
        return false;
    }

    $entries = @scandir($path, SCANDIR_SORT_ASCENDING);
    if ($entries === false) {
        return false;
    }

    foreach ($entries as $entry) {
        if (in_array($entry, ['.', '..'], true)) {
            continue;
        }

        $fullPath = $path . '/' . $entry;
        if (is_dir($fullPath)) {
            if (!deletePath($fullPath)) {
                return false;
            }
        } elseif (!@unlink($fullPath)) {
            return false;
        }
    }

    return @rmdir($path);
}
