<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextResult;
use App\Chain\Application\DeleteText;
use App\Chain\Application\Port\HtmlSanitizer;
use App\Chain\Application\UpdateText;
use App\Chain\Domain\Exception\TextAttributedToScheduledEditionException;
use App\Chain\Domain\Exception\TextNotFoundException;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminTextResource;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminTextProcessor;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use PHPUnit\Framework\TestCase;

final class AdminTextProcessorTest extends TestCase
{
    public function testPostDispatchesCreateTextCommand(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public ?object $lastCommand = null;

            public function dispatch(CommandInterface $command): mixed
            {
                $this->lastCommand = $command;

                return new CreateTextResult(new TextId());
            }
        };

        $processor = new AdminTextProcessor($commandBus, self::fakeSanitizer());

        $data = new AdminTextResource(
            title: 'Oraison de Thérèse',
            source: 'Thérèse de Lisieux',
            body: '<p>Corps du texte.</p>',
            hints: 'Méditer le matin.',
        );

        $result = $processor->process($data, new Post(), [], []);

        self::assertInstanceOf(AdminTextResource::class, $result);
        self::assertInstanceOf(CreateText::class, $commandBus->lastCommand);
        self::assertSame('Oraison de Thérèse', $commandBus->lastCommand->title->value);
        self::assertSame('Thérèse de Lisieux', $commandBus->lastCommand->source->value);
        self::assertSame('<p>Corps du texte.</p>', $commandBus->lastCommand->body);
        self::assertSame('Méditer le matin.', $commandBus->lastCommand->hints);
        self::assertNotNull($result->id);
    }

    public function testPostWithoutHintsDispatchesCreateTextWithNullHints(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public ?object $lastCommand = null;

            public function dispatch(CommandInterface $command): mixed
            {
                $this->lastCommand = $command;

                return new CreateTextResult(new TextId());
            }
        };

        $processor = new AdminTextProcessor($commandBus, self::fakeSanitizer());

        $processor->process(
            new AdminTextResource(
                title: 'Psaume vingt-trois',
                source: 'La Bible',
                body: '<p>Le Seigneur est mon berger.</p>',
            ),
            new Post(),
            [],
            [],
        );

        self::assertInstanceOf(CreateText::class, $commandBus->lastCommand);
        self::assertNull($commandBus->lastCommand->hints);
    }

    public function testPatchDispatchesUpdateTextCommand(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public ?object $lastCommand = null;

            public function dispatch(CommandInterface $command): mixed
            {
                $this->lastCommand = $command;

                return null;
            }
        };

        $processor = new AdminTextProcessor($commandBus, self::fakeSanitizer());
        $textId = new TextId();

        $data = new AdminTextResource(
            title: 'Titre modifié',
            source: 'Source modifiée',
            body: '<p>Nouveau corps.</p>',
            hints: null,
        );

        $processor->process($data, new Patch(), ['id' => (string) $textId], []);

        self::assertInstanceOf(UpdateText::class, $commandBus->lastCommand);
        self::assertTrue($commandBus->lastCommand->textId->equals($textId));
        self::assertSame('Titre modifié', $commandBus->lastCommand->title->value);
        self::assertSame('Source modifiée', $commandBus->lastCommand->source->value);
        self::assertSame('<p>Nouveau corps.</p>', $commandBus->lastCommand->body);
        self::assertNull($commandBus->lastCommand->hints);
    }

    public function testPatchOnUnknownTextThrowsNotFound(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public function dispatch(CommandInterface $command): mixed
            {
                throw new TextNotFoundException();
            }
        };

        $processor = new AdminTextProcessor($commandBus, self::fakeSanitizer());

        $this->expectException(TextNotFoundException::class);

        $processor->process(
            new AdminTextResource(
                title: 'Titre modifié',
                source: 'Source modifiée',
                body: '<p>Nouveau corps.</p>',
            ),
            new Patch(),
            ['id' => (string) new TextId()],
            [],
        );
    }

    public function testDeleteDispatchesDeleteTextCommand(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public ?object $lastCommand = null;

            public function dispatch(CommandInterface $command): mixed
            {
                $this->lastCommand = $command;

                return null;
            }
        };

        $processor = new AdminTextProcessor($commandBus, self::fakeSanitizer());
        $textId = new TextId();

        $processor->process(
            new AdminTextResource(),
            new Delete(),
            ['id' => (string) $textId],
            [],
        );

        self::assertInstanceOf(DeleteText::class, $commandBus->lastCommand);
        self::assertTrue($commandBus->lastCommand->textId->equals($textId));
    }

    public function testDeleteOnUnknownTextThrowsNotFound(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public function dispatch(CommandInterface $command): mixed
            {
                throw new TextNotFoundException();
            }
        };

        $processor = new AdminTextProcessor($commandBus, self::fakeSanitizer());

        $this->expectException(TextNotFoundException::class);

        $processor->process(
            new AdminTextResource(),
            new Delete(),
            ['id' => (string) new TextId()],
            [],
        );
    }

    public function testDeleteOnTextAttributedToScheduledEditionThrowsConflict(): void
    {
        $commandBus = new class implements CommandBusInterface {
            public function dispatch(CommandInterface $command): mixed
            {
                throw new TextAttributedToScheduledEditionException();
            }
        };

        $processor = new AdminTextProcessor($commandBus, self::fakeSanitizer());

        $this->expectException(TextAttributedToScheduledEditionException::class);

        $processor->process(
            new AdminTextResource(),
            new Delete(),
            ['id' => (string) new TextId()],
            [],
        );
    }

    public function testCreateWithTooShortTitleThrowsValidationExceptionOnTitle(): void
    {
        $processor = self::processorReturningCreateResult();

        try {
            $processor->process(new AdminTextResource(
                title: 'ab',
                source: 'Auteur',
                body: '<p>Corps.</p>',
            ), new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame('title', $violations[0]->getPropertyPath());
            self::assertSame('Le titre doit comporter entre 3 et 120 caractères.', $violations[0]->getMessage());
        }
    }

    public function testCreateWithEmptySourceThrowsValidationExceptionOnSource(): void
    {
        $processor = self::processorReturningCreateResult();

        try {
            $processor->process(new AdminTextResource(
                title: 'Titre valide',
                source: '   ',
                body: '<p>Corps.</p>',
            ), new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame('source', $violations[0]->getPropertyPath());
            self::assertSame('La source est obligatoire.', $violations[0]->getMessage());
        }
    }

    public function testCreateWithEmptyBodyAfterSanitizationThrowsValidationExceptionOnBody(): void
    {
        $processor = self::processorReturningCreateResult();

        try {
            $processor->process(new AdminTextResource(
                title: 'Titre valide',
                source: 'Auteur',
                body: '<p></p>',
            ), new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame('body', $violations[0]->getPropertyPath());
            self::assertSame('Le corps du texte est obligatoire.', $violations[0]->getMessage());
        }
    }

    public function testCreateWithTooLongHintsThrowsValidationExceptionOnHints(): void
    {
        $processor = self::processorReturningCreateResult();

        try {
            $processor->process(new AdminTextResource(
                title: 'Titre valide',
                source: 'Auteur',
                body: '<p>Corps.</p>',
                hints: str_repeat('a', 2001),
            ), new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame('hints', $violations[0]->getPropertyPath());
            self::assertSame('Les pistes de méditation ne doivent pas dépasser 2000 caractères.', $violations[0]->getMessage());
        }
    }

    private static function processorReturningCreateResult(): AdminTextProcessor
    {
        return new AdminTextProcessor(new class implements CommandBusInterface {
            public function dispatch(CommandInterface $command): mixed
            {
                return new CreateTextResult(new TextId());
            }
        }, self::fakeSanitizer());
    }

    private static function fakeSanitizer(): HtmlSanitizer
    {
        return new class implements HtmlSanitizer {
            public function sanitize(string $html): string
            {
                return $html;
            }
        };
    }
}
