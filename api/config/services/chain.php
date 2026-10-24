<?php

declare(strict_types=1);

use App\Chain\Application\AssignTextHandler;
use App\Chain\Application\CreateEditionHandler;
use App\Chain\Application\CreateTextHandler;
use App\Chain\Application\DeleteTextHandler;
use App\Chain\Application\DistributeTextsHandler;
use App\Chain\Application\Port\CalendarFile;
use App\Chain\Application\Port\HtmlSanitizer;
use App\Chain\Application\Port\Notifier;
use App\Chain\Application\Port\SlotPicker;
use App\Chain\Application\Query\AdminEditionViewFinder;
use App\Chain\Application\Query\AdminTextViewFinder;
use App\Chain\Application\Query\EmptySlotsFinder;
use App\Chain\Application\Query\InscriptionDigestFinder;
use App\Chain\Application\Query\MySpaceViewFinder;
use App\Chain\Application\Query\PlanningViewFinder;
use App\Chain\Application\Query\PublicEditionViewFinder;
use App\Chain\Application\Query\SlotTextViewFinder;
use App\Chain\Application\RegisterInscriptionsHandler;
use App\Chain\Application\ScheduleEditionHandler;
use App\Chain\Application\SendConfirmationOnInscriptionsRegistered;
use App\Chain\Application\SuggestEmptySlot;
use App\Chain\Application\UnscheduleEditionHandler;
use App\Chain\Application\UpdateEditionHandler;
use App\Chain\Application\UpdateTextHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Policy\VisibilityPolicy;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminEditionDistributeTextsProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminEditionProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminEditionScheduleProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminSlotProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminTextProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\RegisterInscriptionsProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\AdminEditionProvider;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\AdminTextProvider;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\MyInscriptionsProvider;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\PublicEditionProvider;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\PublicEditionSlotsProvider;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\SlotSuggestionProvider;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\SlotTextProvider;
use App\Chain\Infrastructure\Calendar\EluceoCalendarFile;
use App\Chain\Infrastructure\Doctrine\DoctrineAdminEditionViewFinder;
use App\Chain\Infrastructure\Doctrine\DoctrineAdminTextViewFinder;
use App\Chain\Infrastructure\Doctrine\DoctrineEmptySlotsFinder;
use App\Chain\Infrastructure\Doctrine\DoctrineInscriptionDigestFinder;
use App\Chain\Infrastructure\Doctrine\DoctrineInscriptionDirectory;
use App\Chain\Infrastructure\Doctrine\DoctrineMySpaceViewFinder;
use App\Chain\Infrastructure\Doctrine\DoctrinePlanningViewFinder;
use App\Chain\Infrastructure\Doctrine\DoctrinePriantProfileReader;
use App\Chain\Infrastructure\Doctrine\DoctrinePublicEditionViewFinder;
use App\Chain\Infrastructure\Doctrine\DoctrineSlotTextViewFinder;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineEditionRepository;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineInscriptionRepository;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrinePriantRepository;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineTextRepository;
use App\Chain\Infrastructure\InMemory\Query\InMemoryAdminEditionViewFinder;
use App\Chain\Infrastructure\InMemory\Query\InMemoryAdminTextViewFinder;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryInscriptionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryPriantRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Chain\Infrastructure\Mailer\MailerNotifier;
use App\Chain\Infrastructure\Random\RandomizerSlotPicker;
use App\Chain\Infrastructure\Symfony\Console\LoadEditionFixturesCommand;
use App\Chain\Infrastructure\Symfony\Console\LoadTextFixturesCommand;
use App\Chain\Infrastructure\Symfony\Controller\MyCalendarController;
use App\Chain\Infrastructure\Symfony\HtmlSanitizer\SymfonyHtmlSanitizer;
use App\Chain\Infrastructure\Symfony\Security\InscriptionVoter;
use App\Chain\Infrastructure\Twig\RichTextExtension;
use App\Shared\Application\Port\InscriptionDirectory;
use App\Shared\Application\Port\PriantProfileReader;
use Random\Randomizer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;

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

    $services->set(DoctrinePriantRepository::class)
        ->args([
            new Reference('doctrine'),
            Priant::class,
        ])
        ->alias(PriantRepositoryInterface::class, DoctrinePriantRepository::class);

    $services->set(InMemoryPriantRepository::class)
        ->tag('app.in_memory_repository');

    $services->set(DoctrineInscriptionRepository::class)
        ->args([
            new Reference('doctrine'),
            Inscription::class,
        ])
        ->alias(InscriptionRepositoryInterface::class, DoctrineInscriptionRepository::class);

    $services->set(InMemoryInscriptionRepository::class)
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

    $services->set(RegisterInscriptionsHandler::class)
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

    $services->set(DoctrinePlanningViewFinder::class)
        ->autoconfigure()
        ->autowire()
        ->public()
        ->alias(PlanningViewFinder::class, DoctrinePlanningViewFinder::class);

    $services->set(DoctrineInscriptionDigestFinder::class)
        ->autowire()
        ->alias(InscriptionDigestFinder::class, DoctrineInscriptionDigestFinder::class);

    $services->set(DoctrineEmptySlotsFinder::class)
        ->autowire()
        ->alias(EmptySlotsFinder::class, DoctrineEmptySlotsFinder::class);

    $services->set(DoctrineMySpaceViewFinder::class)
        ->autowire()
        ->alias(MySpaceViewFinder::class, DoctrineMySpaceViewFinder::class);

    $services->set(DoctrineSlotTextViewFinder::class)
        ->autowire()
        ->alias(SlotTextViewFinder::class, DoctrineSlotTextViewFinder::class);

    $services->set(DoctrinePriantProfileReader::class)
        ->autoconfigure()
        ->autowire()
        ->alias(PriantProfileReader::class, DoctrinePriantProfileReader::class);

    $services->set(EluceoCalendarFile::class)
        ->autowire()
        ->alias(CalendarFile::class, EluceoCalendarFile::class);

    $services->set(MyCalendarController::class)
        ->autoconfigure()
        ->autowire()
        ->tag('controller.service_arguments');

    $services->set(RichTextExtension::class)
        ->autoconfigure();

    $services->set(SendConfirmationOnInscriptionsRegistered::class)
        ->autoconfigure()
        ->autowire();

    $services->set(MailerNotifier::class)
        ->autowire()
        ->alias(Notifier::class, MailerNotifier::class);

    // The default engine of a Randomizer is the secure one (CSPRNG)
    $services->set(RandomizerSlotPicker::class)
        ->args([inline_service(Randomizer::class)])
        ->alias(SlotPicker::class, RandomizerSlotPicker::class);

    $services->set(VisibilityPolicy::class)
        ->autowire();

    $services->set(InscriptionVoter::class)
        ->autoconfigure()
        ->autowire();

    $services->set(DoctrineInscriptionDirectory::class)
        ->autowire()
        ->alias(InscriptionDirectory::class, DoctrineInscriptionDirectory::class);

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

    $services->set(RegisterInscriptionsProcessor::class)
        ->autoconfigure()
        ->autowire();

    $services->set(PublicEditionProvider::class)
        ->autoconfigure()
        ->autowire();

    $services->set(PublicEditionSlotsProvider::class)
        ->autoconfigure()
        ->autowire();

    $services->set(MyInscriptionsProvider::class)
        ->autoconfigure()
        ->autowire();

    $services->set(SuggestEmptySlot::class)
        ->autowire();

    $services->set(SlotSuggestionProvider::class)
        ->autoconfigure()
        ->autowire();

    $services->set(SlotTextProvider::class)
        ->autoconfigure()
        ->autowire();

    $services->set(LoadEditionFixturesCommand::class)
        ->autoconfigure()
        ->autowire()
        ->tag('console.command');

    $services->set(LoadTextFixturesCommand::class)
        ->autoconfigure()
        ->autowire()
        ->tag('console.command');
};
