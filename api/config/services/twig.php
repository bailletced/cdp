<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Twig\Extra\CssInliner\CssInlinerExtension;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    // Provides the inline_css filter used by the email layouts (F02-T13)
    $services->set(CssInlinerExtension::class)
        ->autoconfigure();
};
