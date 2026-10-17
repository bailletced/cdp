<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Clock\SystemClock;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    // Configuration de l'horloge PSR-20
    $services->set('clock', SystemClock::class);
    $services->alias(ClockInterface::class, 'clock');
};
