<?php

declare(strict_types=1);

use App\Chain\Application\CreateEditionHandler;
use App\Chain\Application\Query\AdminEditionViewFinder;
use App\Chain\Application\ScheduleEditionHandler;
use App\Chain\Application\UnscheduleEditionHandler;
use App\Chain\Application\UpdateEditionHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Infrastructure\ApiPlatform\AdminEditionProcessor;
use App\Chain\Infrastructure\ApiPlatform\AdminEditionProvider;
use App\Chain\Infrastructure\ApiPlatform\AdminEditionScheduleProcessor;
use App\Chain\Infrastructure\Console\LoadEditionFixturesCommand;
use App\Chain\Infrastructure\Doctrine\DoctrineAdminEditionViewFinder;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineEditionRepository;
use App\Chain\Infrastructure\Query\InMemoryAdminEditionViewFinder;
use App\Chain\Infrastructure\Repository\InMemoryEditionRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(DoctrineEditionRepository::class)
        ->args([
            new Reference('doctrine'),
            Edition::class,
        ])
        ->alias(EditionRepositoryInterface::class, DoctrineEditionRepository::class);

    $services->set(InMemoryEditionRepository::class)
        ->tag('app.in_memory_repository');

    $services->set(CreateEditionHandler::class)
        ->autoconfigure()
        ->autowire();

    $services->set(UpdateEditionHandler::class)
        ->autoconfigure()
        ->autowire();

    $services->set(ScheduleEditionHandler::class)
        ->autoconfigure()
        ->autowire();

    $services->set(UnscheduleEditionHandler::class)
        ->autoconfigure()
        ->autowire();

    $services->set(DoctrineAdminEditionViewFinder::class)
        ->autoconfigure()
        ->autowire()
        ->alias(AdminEditionViewFinder::class, DoctrineAdminEditionViewFinder::class);

    $services->set(InMemoryAdminEditionViewFinder::class)
        ->autowire();

    $services->set(AdminEditionProvider::class)
        ->autoconfigure()
        ->autowire();

    $services->set(AdminEditionProcessor::class)
        ->autoconfigure()
        ->autowire();

    $services->set(AdminEditionScheduleProcessor::class)
        ->autoconfigure()
        ->autowire();

    $services->set(LoadEditionFixturesCommand::class)
        ->autoconfigure()
        ->autowire()
        ->tag('console.command');
};
