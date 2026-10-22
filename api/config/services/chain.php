<?php

declare(strict_types=1);

use App\Chain\Application\AssignTextHandler;
use App\Chain\Application\CreateEditionHandler;
use App\Chain\Application\CreateTextHandler;
use App\Chain\Application\DeleteTextHandler;
use App\Chain\Application\DistributeTextsHandler;
use App\Chain\Application\Port\HtmlSanitizer;
use App\Chain\Application\Query\AdminEditionViewFinder;
use App\Chain\Application\Query\AdminTextViewFinder;
use App\Chain\Application\Query\PublicEditionViewFinder;
use App\Chain\Application\ScheduleEditionHandler;
use App\Chain\Application\UnscheduleEditionHandler;
use App\Chain\Application\UpdateEditionHandler;
use App\Chain\Application\UpdateTextHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminEditionDistributeTextsProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminEditionProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminEditionScheduleProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminSlotProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminTextProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\AdminEditionProvider;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\AdminTextProvider;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\PublicEditionProvider;
use App\Chain\Infrastructure\Doctrine\DoctrineAdminEditionViewFinder;
use App\Chain\Infrastructure\Doctrine\DoctrineAdminTextViewFinder;
use App\Chain\Infrastructure\Doctrine\DoctrinePublicEditionViewFinder;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineEditionRepository;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineTextRepository;
use App\Chain\Infrastructure\InMemory\Query\InMemoryAdminEditionViewFinder;
use App\Chain\Infrastructure\InMemory\Query\InMemoryAdminTextViewFinder;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Chain\Infrastructure\Symfony\Console\LoadEditionFixturesCommand;
use App\Chain\Infrastructure\Symfony\HtmlSanitizer\SymfonyHtmlSanitizer;
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

    $services->set(DoctrineTextRepository::class)
        ->args([
            new Reference('doctrine'),
            Text::class,
        ])
        ->alias(TextRepositoryInterface::class, DoctrineTextRepository::class);

    $services->set(InMemoryTextRepository::class)
        ->tag('app.in_memory_repository');

    $services->set(SymfonyHtmlSanitizer::class)
        ->autoconfigure()
        ->autowire()
        ->alias(HtmlSanitizer::class, SymfonyHtmlSanitizer::class);

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

    $services->set(CreateTextHandler::class)
        ->autoconfigure()
        ->autowire();

    $services->set(UpdateTextHandler::class)
        ->autoconfigure()
        ->autowire();

    $services->set(DeleteTextHandler::class)
        ->autoconfigure()
        ->autowire();

    $services->set(AssignTextHandler::class)
        ->autoconfigure()
        ->autowire();

    $services->set(DistributeTextsHandler::class)
        ->autoconfigure()
        ->autowire();

    $services->set(DoctrineAdminEditionViewFinder::class)
        ->autoconfigure()
        ->autowire()
        ->alias(AdminEditionViewFinder::class, DoctrineAdminEditionViewFinder::class);

    $services->set(InMemoryAdminEditionViewFinder::class)
        ->autowire();

    $services->set(DoctrineAdminTextViewFinder::class)
        ->autoconfigure()
        ->autowire()
        ->alias(AdminTextViewFinder::class, DoctrineAdminTextViewFinder::class);

    $services->set(InMemoryAdminTextViewFinder::class)
        ->autowire();

    $services->set(DoctrinePublicEditionViewFinder::class)
        ->autoconfigure()
        ->autowire()
        ->public()
        ->alias(PublicEditionViewFinder::class, DoctrinePublicEditionViewFinder::class);

    $services->set(AdminEditionProvider::class)
        ->autoconfigure()
        ->autowire();

    $services->set(AdminTextProvider::class)
        ->autoconfigure()
        ->autowire();

    $services->set(AdminEditionProcessor::class)
        ->autoconfigure()
        ->autowire();

    $services->set(AdminEditionScheduleProcessor::class)
        ->autoconfigure()
        ->autowire();

    $services->set(AdminTextProcessor::class)
        ->autoconfigure()
        ->autowire();

    $services->set(AdminSlotProcessor::class)
        ->autoconfigure()
        ->autowire();

    $services->set(AdminEditionDistributeTextsProcessor::class)
        ->autoconfigure()
        ->autowire();

    $services->set(PublicEditionProvider::class)
        ->autoconfigure()
        ->autowire();

    $services->set(LoadEditionFixturesCommand::class)
        ->autoconfigure()
        ->autowire()
        ->tag('console.command');
};
