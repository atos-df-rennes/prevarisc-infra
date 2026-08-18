<?php

use Castor\Attribute\AsTask;
use Castor\Attribute\ASOption;
use Symfony\Component\Console\Input\InputOption;

use function Castor\import;
use function Castor\io;
use function Castor\parallel;

import(__DIR__);

#[AsTask(description: 'Fix coding style for all applications')]
function cs(): void
{
    io()->title('Fixing coding style for all applications');

    parallel(
        function () {
            symfonyCs();
        },
        function () {
            platauCs();
        }
    );
}

#[AsTask(description: 'Refactor all applications')]
function refactor(): void
{
    io()->title('Refactoring all applications');

    parallel(
        function () {
            symfonyRefactor();
        },
        function () {
            platauRefactor();
        }
    );
}

#[AsTask(description: 'Analyse all applications')]
function analyse(): void
{
    io()->title('Analysing all applications');

    parallel(
        function () {
            symfonyAnalyse();
        },
        function () {
            platauAnalyse();
        }
    );
}

#[AsTask(description: 'Test all applications')]
function test(): void
{
    io()->title('Testing all applications');

    parallel(
        function () {
            symfonyTest(false);
        },
        function () {
            platauTest();
        }
    );
}
