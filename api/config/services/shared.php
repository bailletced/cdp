<?php

declare(strict_types=1);

use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Infrastructure\Symfony\Messenger\MessengerCommandBus;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

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
};
