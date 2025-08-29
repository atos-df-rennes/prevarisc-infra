<?php

use Castor\Attribute\AsTask;

use Symfony\Component\Process\ExecutableFinder;
use function Castor\check;
use function Castor\fs;
use function Castor\import;
use function Castor\io;
use function Castor\notify;
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

#[AsTask(namespace: 'prevarisc', description: 'Setup Prevarisc')]
function setup(): void
{
    io()->title('Setting up Prevarisc.');

    check(
      'Checking if Git is installed.',
      'Git is not installed. Please install it first.',
      fn () => (new ExecutableFinder())->find('git')
    );

    io()->section('Cloning repositories.');

    io()->text('Cloning Prevarisc repository.');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc.git']);

    io()->info('You will need a Personal Access Token for the next operation. Please create one if you do not have one already.');

    $resumeScript = io()->confirm('Resume script?');
    if (!$resumeScript) {
        return;
    }

    io()->text('Cloning Prevarisc Migration repository.');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc-migration.git']);

    io()->text('Cloning Prevarisc Passerelle Plat\'AU repository.');
    run(['git', 'clone', 'https://github.com/atos-df-rennes/prevarisc-passerelle-platau.git']);

    io()->section('Copying web server files.');

    io()->text('Copying configuration file.');
    fs()->copy('apache/httpd-prevarisc-config.conf.example', 'apache/httpd-prevarisc-config.conf');

    io()->text('Copying version file.');
    fs()->copy('apache/httpd-prevarisc-version.conf.example', 'apache/httpd-prevarisc-version.conf');

    // @todo: Voir pour faire fonctionner la notification
    // notify('Prevarisc is ready!');
}