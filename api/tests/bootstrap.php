<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Symfony\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Dotenv\Dotenv;

require \dirname(__DIR__).'/vendor/autoload.php';

if (\method_exists(Dotenv::class, 'bootEnv')) {
    new Dotenv()->bootEnv(\dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    \umask(0o000);
}

// La base de test (suffixe _test) est créée à la volée, pour que `make test-back` marche sur un poste neuf.
$kernel = new Kernel('test', (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$application = new Application($kernel);
$application->setAutoExit(false);
$application->find('doctrine:database:create')->run(new ArrayInput(['--if-not-exists' => true]), new NullOutput());

$kernel->shutdown();
