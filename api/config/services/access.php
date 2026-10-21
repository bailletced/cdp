<?php

declare(strict_types=1);

use App\Access\Application\AddOrganizerHandler;
use App\Access\Application\Query\OrganizerViewFinder;
use App\Access\Application\RemoveOrganizerHandler;
use App\Access\Application\RequestPersonalLinkHandler;
use App\Access\Domain\Entity\Account;
use App\Access\Domain\Entity\Organizer;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use App\Access\Infrastructure\ApiPlatform\AdminOrganizerProcessor;
use App\Access\Infrastructure\ApiPlatform\AdminOrganizerProvider;
use App\Access\Infrastructure\ApiPlatform\MeProvider;
use App\Access\Infrastructure\Console\CreateOrganizerCliCommand;
use App\Access\Infrastructure\Controller\AuthStatusController;
use App\Access\Infrastructure\Controller\LinkRequestController;
use App\Access\Infrastructure\Controller\LoginLinkController;
use App\Access\Infrastructure\Doctrine\DoctrineOrganizerViewFinder;
use App\Access\Infrastructure\Doctrine\Repository\DoctrineAccountRepository;
use App\Access\Infrastructure\Doctrine\Repository\DoctrineOrganizerRepository;
use App\Access\Infrastructure\Repository\InMemoryAccountRepository;
use App\Access\Infrastructure\Repository\InMemoryOrganizerRepository;
use App\Access\Infrastructure\Security\AdminAccessListener;
use App\Access\Infrastructure\Security\ApiAuthenticationEntryPoint;
use App\Access\Infrastructure\Security\LoginLinkFailureHandler;
use App\Access\Infrastructure\Security\LoginLinkPersonalLinkIssuer;
use App\Access\Infrastructure\Security\LoginLinkSuccessHandler;
use App\Access\Infrastructure\Security\SameOriginListener;
use App\Access\Infrastructure\Security\SecurityUser;
use App\Access\Infrastructure\Security\SymfonyCurrentViewer;
use App\Access\Infrastructure\Security\UserProvider;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Application\Port\PersonalLinkIssuer;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(DoctrineAccountRepository::class)
        ->args([
            new Symfony\Component\DependencyInjection\Reference('doctrine'),
            Account::class,
        ])
        ->alias(AccountRepositoryInterface::class, DoctrineAccountRepository::class)

        ->set(DoctrineOrganizerRepository::class)
        ->args([
            new Symfony\Component\DependencyInjection\Reference('doctrine'),
            Organizer::class,
        ])
        ->alias(OrganizerRepositoryInterface::class, DoctrineOrganizerRepository::class)

        ->set(InMemoryAccountRepository::class)
        ->tag('app.in_memory_repository')

        ->set(InMemoryOrganizerRepository::class)
        ->tag('app.in_memory_repository')

        ->set(AddOrganizerHandler::class)
        ->autoconfigure()
        ->autowire()

        ->set(RemoveOrganizerHandler::class)
        ->autoconfigure()
        ->autowire()

        ->set(RequestPersonalLinkHandler::class)
        ->autoconfigure()
        ->autowire()

        ->set(MeProvider::class)
        ->autoconfigure()
        ->autowire()

        ->set(DoctrineOrganizerViewFinder::class)
        ->autoconfigure()
        ->autowire()
        ->alias(OrganizerViewFinder::class, DoctrineOrganizerViewFinder::class)

        ->set(AdminOrganizerProvider::class)
        ->autoconfigure()
        ->autowire()

        ->set(AdminOrganizerProcessor::class)
        ->autoconfigure()
        ->autowire()

        ->set(CreateOrganizerCliCommand::class)
        ->autoconfigure()
        ->autowire()
        ->tag('console.command')

        ->set(UserProvider::class)
        ->autoconfigure()
        ->autowire()

        ->set(SecurityUser::class)
        ->autoconfigure()

        ->set(LoginLinkController::class)
        ->autoconfigure()
        ->autowire()

        ->set(LinkRequestController::class)
        ->autoconfigure()
        ->autowire()
        ->tag('controller.service_arguments')

        ->set(AuthStatusController::class)
        ->autoconfigure()
        ->autowire()
        ->tag('controller.service_arguments')

        ->set(LoginLinkSuccessHandler::class)
        ->autoconfigure()
        ->autowire()

        ->set(LoginLinkFailureHandler::class)
        ->autoconfigure()
        ->autowire()

        ->set(SameOriginListener::class)
        ->autoconfigure()
        ->autowire()

        ->set(AdminAccessListener::class)
        ->autoconfigure()
        ->autowire()

        ->set(ApiAuthenticationEntryPoint::class)
        ->autoconfigure()
        ->autowire()

        ->set(LoginLinkPersonalLinkIssuer::class)
        ->autoconfigure()
        ->autowire()
        ->args([
            new Symfony\Component\DependencyInjection\Reference(UserProvider::class),
            new Symfony\Component\DependencyInjection\Reference('security.authenticator.login_link_handler.main'),
            new Symfony\Component\DependencyInjection\Reference(ClockInterface::class),
        ])
        ->alias(PersonalLinkIssuer::class, LoginLinkPersonalLinkIssuer::class)

        ->set(SymfonyCurrentViewer::class)
        ->autoconfigure()
        ->autowire()
        ->alias(CurrentViewer::class, SymfonyCurrentViewer::class)
    ;
};
