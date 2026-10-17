<?php

use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Infrastructure\Doctrine\Repository\DoctrineAccountRepository;
use App\Access\Infrastructure\Repository\InMemoryAccountRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(AccountRepositoryInterface::class, DoctrineAccountRepository::class)
        ->args([
            '$registry' => '@doctrine',
        ])

        ->set(InMemoryAccountRepository::class)
        ->tag('app.in_memory_repository')
    ;
};
