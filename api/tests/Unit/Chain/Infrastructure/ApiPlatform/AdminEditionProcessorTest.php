<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionResult;
use App\Chain\Application\UpdateEdition;
use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Exception\EditionVersionConflictException;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Infrastructure\ApiPlatform\AdminEdition;
use App\Chain\Infrastructure\ApiPlatform\AdminEditionProcessor;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use App\Shared\Infrastructure\ApiPlatform\Exception\ConflictException;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;
use PHPUnit\Framework\TestCase;

final class AdminEditionProcessorTest extends TestCase
{
    public function testProcessDispatchesCreateEditionCommand(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public ?object $lastCommand = null;

            public function dispatch(CommandInterface $command): mixed
            {
                $this->lastCommand = $command;

                return new CreateEditionResult(
                    new EditionId(),
                    new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
                    '20h00–20h30',
                );
            }
        };

        $processor = new AdminEditionProcessor($commandBus);

        $data = new AdminEdition(
            title: '24 heures de prière',
            presentation: 'Une chaîne.',
            startsAt: '2026-11-28T20:00:00+01:00',
            opensAt: '2026-11-13T20:00:00+01:00',
        );

        $operation = new Post();
        $result = $processor->process($data, $operation, [], []);

        self::assertInstanceOf(AdminEdition::class, $result);
        self::assertInstanceOf(CreateEdition::class, $commandBus->lastCommand);
        self::assertSame('24 heures de prière', $commandBus->lastCommand->title->value);
        self::assertSame('2026-11-28T19:00:00+00:00', $result->resolvedStartsAtUtc);
    }

    public function testPatchDispatchesUpdateEditionCommandWithVersion(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public ?object $lastCommand = null;

            public function dispatch(CommandInterface $command): mixed
            {
                $this->lastCommand = $command;

                return new \App\Chain\Application\UpdateEditionResult(
                    new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
                    '20h00–20h30',
                );
            }
        };

        $processor = new AdminEditionProcessor($commandBus);
        $editionId = new EditionId();

        $data = new AdminEdition(
            title: 'Titre modifié',
            startsAt: '2026-11-28T20:00:00+01:00',
            opensAt: '2026-11-13T20:00:00+01:00',
            version: 3,
        );

        $result = $processor->process($data, new Patch(), ['id' => (string) $editionId], []);

        self::assertInstanceOf(UpdateEdition::class, $commandBus->lastCommand);
        self::assertTrue($commandBus->lastCommand->editionId->equals($editionId));
        self::assertSame(3, $commandBus->lastCommand->expectedVersion);
        self::assertSame(4, $result->version);
    }

    public function testPatchOnUnknownEditionThrowsNotFound(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public function dispatch(CommandInterface $command): mixed
            {
                throw new EditionNotFoundException();
            }
        };

        $processor = new AdminEditionProcessor($commandBus);

        $data = new AdminEdition(
            title: 'Titre modifié',
            startsAt: '2026-11-28T20:00:00+01:00',
            opensAt: '2026-11-13T20:00:00+01:00',
            version: 1,
        );

        $this->expectException(MissingModelException::class);

        $processor->process($data, new Patch(), ['id' => (string) new EditionId()], []);
    }

    public function testPatchOnVersionConflictThrowsConflict(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public function dispatch(CommandInterface $command): mixed
            {
                throw new EditionVersionConflictException();
            }
        };

        $processor = new AdminEditionProcessor($commandBus);

        $data = new AdminEdition(
            title: 'Titre modifié',
            startsAt: '2026-11-28T20:00:00+01:00',
            opensAt: '2026-11-13T20:00:00+01:00',
            version: 1,
        );

        $this->expectException(ConflictException::class);

        $processor->process($data, new Patch(), ['id' => (string) new EditionId()], []);
    }
}
