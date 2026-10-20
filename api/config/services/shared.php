<?php

declare(strict_types=1);

use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Port\DatabaseHealth;
use App\Shared\Infrastructure\Controller\HealthController;
use App\Shared\Infrastructure\Doctrine\DoctrineDatabaseHealth;
use App\Shared\Infrastructure\Symfony\Messenger\MessengerCommandBus;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $dir = dirname(__DIR__, 2).'/src/Shared';

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->alias(CommandBusInterface::class, MessengerCommandBus::class)->public();

    $services->load('App\\Shared\\', $dir)
        ->exclude([
            $dir.'/Domain',
            $dir.'/Infrastructure/Symfony/Kernel.php',
        ]);

    $services->set(HealthController::class)
        ->autoconfigure()
        ->autowire()
        ->tag('controller.service_arguments')
        ->args([
            new Reference(DatabaseHealth::class),
            '%env(APP_SHA)%',
        ]);

    $services->set(DoctrineDatabaseHealth::class)
        ->autoconfigure()
        ->autowire()
        ->alias(DatabaseHealth::class, DoctrineDatabaseHealth::class);
};
