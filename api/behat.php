<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withSuite(
                (new Suite('default'))
                    ->withContexts(
                        \App\Tests\Behat\SharedContext::class,
                        \App\Tests\Behat\Api\MeContext::class,
                        \App\Tests\Behat\Controller\LinkRequestContext::class,
                        \App\Tests\Behat\Security\LoginLinkContext::class,
                        \App\Tests\Behat\Security\RememberMeContext::class,
                    )
            )
            ->withExtension(
                new Extension(\FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension::class, [
                    'kernel' => [
                        'class' => \App\Shared\Infrastructure\Symfony\Kernel::class,
                        'environment' => 'test',
                        'debug' => false,
                    ],
                    'bootstrap' => 'features/bootstrap/behat_bootstrap.php',
                ])
            )
    );
