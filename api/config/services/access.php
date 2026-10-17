<?php

use App\Access\Domain\Account;
use App\Access\Domain\Organizer;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use App\Access\Infrastructure\Console\CreateOrganizerCommand;
use App\Access\Infrastructure\Doctrine\Repository\DoctrineAccountRepository;
use App\Access\Infrastructure\Doctrine\Repository\DoctrineOrganizerRepository;
use App\Access\Infrastructure\Repository\InMemoryAccountRepository;
use App\Access\Infrastructure\Repository\InMemoryOrganizerRepository;
use App\Access\Infrastructure\Security\SecurityUser;
use App\Access\Infrastructure\Security\UserProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(DoctrineAccountRepository::class)
        ->args([
            new \Symfony\Component\DependencyInjection\Reference('doctrine'),
            Account::class,
        ])
        ->alias(AccountRepositoryInterface::class, DoctrineAccountRepository::class)

        ->set(DoctrineOrganizerRepository::class)
        ->args([
            new \Symfony\Component\DependencyInjection\Reference('doctrine'),
            Organizer::class,
        ])
        ->alias(OrganizerRepositoryInterface::class, DoctrineOrganizerRepository::class)

        ->set(InMemoryAccountRepository::class)
        ->tag('app.in_memory_repository')

        ->set(InMemoryOrganizerRepository::class)
        ->tag('app.in_memory_repository')

        ->set(CreateOrganizerCommand::class)
        ->autoconfigure()
        ->autowire()
        ->tag('console.command')

        ->set(UserProvider::class)
        ->autoconfigure()
        ->autowire()

        ->set(SecurityUser::class)
        ->autoconfigure()
    ;
};
