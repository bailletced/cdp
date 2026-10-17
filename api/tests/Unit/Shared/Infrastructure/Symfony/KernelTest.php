<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony;

use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Application\Event\AsEventListener;
use App\Shared\Infrastructure\Symfony\Kernel;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class KernelTest extends TestCase
{
    public function testItAutoconfiguresEventListeners(): void
    {
        $container = new ContainerBuilder();

        (new ReflectionMethod(Kernel::class, 'build'))->invoke(new Kernel('test', true), $container);

        self::assertArrayHasKey(AsEventListener::class, $container->getAttributeAutoconfigurators());
    }

    public function testItAutoconfiguresCommandHandlers(): void
    {
        $container = new ContainerBuilder();

        (new ReflectionMethod(Kernel::class, 'build'))->invoke(new Kernel('test', true), $container);

        self::assertArrayHasKey(AsCommandHandler::class, $container->getAttributeAutoconfigurators());
    }
}
