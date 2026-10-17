<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony;

use App\Shared\Infrastructure\Symfony\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class KernelTest extends TestCase
{
    public function testItAutoconfiguresCommandHandlers(): void
    {
        $container = new ContainerBuilder();

        new \ReflectionMethod(Kernel::class, 'build')->invoke(new Kernel('test', true), $container);

        self::assertArrayHasKey('App\Shared\Application\Command\AsCommandHandler', $container->getAttributeAutoconfigurators());
    }
}
