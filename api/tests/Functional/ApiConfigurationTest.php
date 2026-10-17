<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ApiConfigurationTest extends KernelTestCase
{
    public function testResourcesAreNotStatelessBecauseAuthenticationUsesTheSession(): void
    {
        self::bootKernel();

        /** @var array{stateless: bool} $defaults */
        $defaults = self::getContainer()->getParameter('api_platform.defaults');

        self::assertFalse($defaults['stateless']);
    }
}
