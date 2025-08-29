<?php

use Castor\Attribute\AsTask;

use Symfony\Component\Process\ExecutableFinder;
use function Castor\check;
use function Castor\import;
use function Castor\io;
use function Castor\parallel;
use function Castor\run;

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

#[AsTask(namespace: 'prevarisc', description: 'Setup the project')]
function setup(): void
{
    io()->title('Setting up the project');

    check(
      'Checking if Git is installed',
      'Git is not installed. Please install it first.',
      fn () => (new ExecutableFinder())->find('git')
    );

    io()->section('Cloning Prevarisc repository');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc.git']);

    io()->info('You will need a Personal Access Token for the next operation. Please create one if you do not have one already.');
    io()->confirm('Resume script?');
    io()->section('Cloning Prevarisc Migration repository');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc-migration.git']);

    io()->section('Cloning Prevarisc Passerelle Plat\'AU repository');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc-passerelle-platau.git']);
}