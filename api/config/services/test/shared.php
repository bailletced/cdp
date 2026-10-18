<?php

declare(strict_types=1);

use App\Tests\Functional\Shared\Fixture\InMemoryMailTransportFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('App\\Tests\\Functional\\Shared\\Fixture\\', dirname(__DIR__, 3).'/tests/Functional/Shared/Fixture')
        ->exclude([dirname(__DIR__, 3).'/tests/Functional/Shared/Fixture/{*Command,*Event}.php'])
        ->public();

    // The mailer TransportFactoryInterface is not autoconfigured: tag the fake factory explicitly.
    $services->set(InMemoryMailTransportFactory::class)
        ->tag('mailer.transport_factory');
};
