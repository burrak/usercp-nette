<?php

require __DIR__ . '/../vendor/autoload.php';

$loader = new \Nette\Loaders\RobotLoader();
$loader->addDirectory(__DIR__ . '/../app');
$loader->setTempDirectory(__DIR__ . '/../temp');
$loader->register();

\Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT, __DIR__ . '/../log');

\Tester\Environment::setup();

\Tester\Dumper::$dumpDir = __DIR__ . '/../temp/tester';
