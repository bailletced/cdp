<?php

declare(strict_types=1);

use App\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->alias(CommandBusInterface::class, 'App\Shared\Infrastructure\Symfony\Messenger\MessengerCommandBus')->public();

    $services->load('App\\Tests\\Functional\\Shared\\Fixture\\', \dirname(__DIR__, 3).'/tests/Functional/Shared/Fixture')
        ->exclude([\dirname(__DIR__, 3).'/tests/Functional/Shared/Fixture/*Command.php']);
};
