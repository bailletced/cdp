<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
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

    public function testCreateWithTooShortTitleThrowsValidationExceptionOnTitle(): void
    {
        $processor = self::processorReturningCreateResult();

        try {
            $processor->process(new AdminEdition(
                title: 'ab',
                startsAt: '2026-11-28T20:00:00+01:00',
                opensAt: '2026-11-13T20:00:00+01:00',
            ), new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame('title', $violations[0]->getPropertyPath());
            self::assertSame('Le titre doit comporter entre 3 et 120 caractères.', $violations[0]->getMessage());
        }
    }

    public function testCreateWithMissingTitleThrowsValidationExceptionOnTitle(): void
    {
        $processor = self::processorReturningCreateResult();

        try {
            $processor->process(new AdminEdition(
                startsAt: '2026-11-28T20:00:00+01:00',
                opensAt: '2026-11-13T20:00:00+01:00',
            ), new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            self::assertSame('title', $e->getConstraintViolationList()[0]->getPropertyPath());
        }
    }

    public function testCreateWithStartOffTheFullOrHalfHourThrowsValidationExceptionOnStartsAt(): void
    {
        $processor = self::processorReturningCreateResult();

        try {
            $processor->process(new AdminEdition(
                title: '24 heures de prière',
                startsAt: '2026-11-28T20:15:00+01:00',
                opensAt: '2026-11-13T20:00:00+01:00',
            ), new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame('startsAt', $violations[0]->getPropertyPath());
            self::assertSame("L'heure du début de l'édition doit tomber sur l'heure pleine ou la demi-heure (heure de Paris).", $violations[0]->getMessage());
        }
    }

    public function testCreateWithUnparseableStartThrowsValidationExceptionOnStartsAt(): void
    {
        $processor = self::processorReturningCreateResult();

        try {
            $processor->process(new AdminEdition(
                title: '24 heures de prière',
                startsAt: 'pas-une-date',
                opensAt: '2026-11-13T20:00:00+01:00',
            ), new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame('startsAt', $violations[0]->getPropertyPath());
            self::assertSame("La date et l'heure du début de l'édition sont invalides.", $violations[0]->getMessage());
        }
    }

    public function testCreateWithOpensAtAfterStartThrowsValidationExceptionOnOpensAt(): void
    {
        $processor = self::processorReturningCreateResult();

        try {
            $processor->process(new AdminEdition(
                title: '24 heures de prière',
                startsAt: '2026-11-28T20:00:00+01:00',
                opensAt: '2026-11-28T21:00:00+01:00',
            ), new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame('opensAt', $violations[0]->getPropertyPath());
            self::assertSame("L'ouverture des inscriptions doit être antérieure au début de l'édition.", $violations[0]->getMessage());
        }
    }

    public function testPatchWithOpensAtAfterStartThrowsValidationExceptionOnOpensAt(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public function dispatch(CommandInterface $command): mixed
            {
                return new \App\Chain\Application\UpdateEditionResult(
                    new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
                    '20h00–20h30',
                );
            }
        };
        $processor = new AdminEditionProcessor($commandBus);

        try {
            $processor->process(new AdminEdition(
                title: 'Titre modifié',
                startsAt: '2026-11-28T20:00:00+01:00',
                opensAt: '2026-11-28T21:00:00+01:00',
                version: 1,
            ), new Patch(), ['id' => (string) new EditionId()], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            self::assertSame('opensAt', $e->getConstraintViolationList()[0]->getPropertyPath());
        }
    }

    private static function processorReturningCreateResult(): AdminEditionProcessor
    {
        return new AdminEditionProcessor(new class implements CommandBusInterface {
            public function dispatch(CommandInterface $command): mixed
            {
                return new CreateEditionResult(
                    new EditionId(),
                    new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
                    '20h00–20h30',
                );
            }
        });
    }
}
