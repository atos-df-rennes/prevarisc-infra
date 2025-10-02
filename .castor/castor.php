<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;

use function Castor\import;
use function Castor\io;
use function Castor\parallel;

import(__DIR__);

#[AsTask(description: 'Check coding style for all applications')]
function cs(
    #[AsOption(name: 'dry-run', mode: InputOption::VALUE_NONE, description: 'Lance les linters sans modifications')]
    bool $dryRun
): void
{
    io()->title('Checking coding style for all applications');

    parallel(
        function () use ($dryRun): void {
            zendCs($dryRun);
        },
        function () use ($dryRun): void {
            symfonyCs($dryRun);
        }
    );
}

#[AsTask(description: 'Analyse all applications')]
function analyse(): void
{
    io()->title('Analysing all applications');

    parallel(
        function () {
            zendAnalyse();
        },
        function () {
            symfonyAnalyse();
        }
    );
}

#[AsTask(description: 'Test all applications')]
function test(): void
{
    io()->title('Testing all applications');

    parallel(
        function () {
            zendTest();
        },
        function () {
            symfonyTest();
        }
    );
}
