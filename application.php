#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use App\Command\CheckSummonersInGameCommand;
use App\Command\CheckSummonersOutGameCommand;
use App\Command\AddUserCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

$application = new Application();

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . "/.env");

$_ENV["BASEDIR"] = __DIR__;

// Add commands
$application->add(new CheckSummonersInGameCommand());
$application->add(new CheckSummonersOutGameCommand());
$application->add(new AddUserCommand());

$application->run();