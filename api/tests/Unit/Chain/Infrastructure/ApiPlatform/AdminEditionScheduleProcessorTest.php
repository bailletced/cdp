<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Post;
use App\Chain\Application\ScheduleEdition;
use App\Chain\Application\UnscheduleEdition;
use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Infrastructure\ApiPlatform\AdminEdition;
use App\Chain\Infrastructure\ApiPlatform\AdminEditionScheduleProcessor;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use PHPUnit\Framework\TestCase;

final class AdminEditionScheduleProcessorTest extends TestCase
{
    public function testScheduleDispatchesScheduleEdition(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public ?object $lastCommand = null;

            public function dispatch(CommandInterface $command): mixed
            {
                $this->lastCommand = $command;

                return null;
            }
        };

        $processor = new AdminEditionScheduleProcessor($commandBus);
        $editionId = new EditionId();

        $result = $processor->process(
            new AdminEdition(),
            new Post(uriTemplate: '/admin/editions/{id}/schedule'),
            ['id' => (string) $editionId],
            [],
        );

        self::assertInstanceOf(ScheduleEdition::class, $commandBus->lastCommand);
        self::assertTrue($commandBus->lastCommand->editionId->equals($editionId));
        self::assertSame((string) $editionId, $result->id);
    }

    public function testUnscheduleDispatchesUnscheduleEdition(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public ?object $lastCommand = null;

            public function dispatch(CommandInterface $command): mixed
            {
                $this->lastCommand = $command;

                return null;
            }
        };

        $processor = new AdminEditionScheduleProcessor($commandBus);
        $editionId = new EditionId();

        $processor->process(
            new AdminEdition(),
            new Post(uriTemplate: '/admin/editions/{id}/unschedule'),
            ['id' => (string) $editionId],
            [],
        );

        self::assertInstanceOf(UnscheduleEdition::class, $commandBus->lastCommand);
        self::assertTrue($commandBus->lastCommand->editionId->equals($editionId));
    }

    public function testScheduleOnUnknownEditionThrowsNotFound(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public function dispatch(CommandInterface $command): mixed
            {
                throw new EditionNotFoundException();
            }
        };

        $processor = new AdminEditionScheduleProcessor($commandBus);

        $this->expectException(EditionNotFoundException::class);

        $processor->process(
            new AdminEdition(),
            new Post(uriTemplate: '/admin/editions/{id}/schedule'),
            ['id' => (string) new EditionId()],
            [],
        );
    }
}
