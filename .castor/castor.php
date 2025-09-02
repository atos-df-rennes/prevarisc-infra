<?php

use Castor\Attribute\AsTask;

use function Castor\import;
use function Castor\io;
use function Castor\parallel;

import(__DIR__);

#[AsTask(description: 'Check coding style for all applications')]
function csCheck(): void
{
    io()->title('Checking coding style for all applications');

    parallel(
        function () {
            zendCsCheck();
        },
        function () {
            symfonyCsCheck();
        }
    );
}

#[AsTask(description: 'Fix coding style for all applications')]
function csFix(): void
{
    io()->title('Fixing coding style for all applications');

    parallel(
        function () {
            zendCsFix();
        },
        function () {
            symfonyCsFix();
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
