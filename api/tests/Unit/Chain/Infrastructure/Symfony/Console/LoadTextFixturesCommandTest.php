<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Symfony\Console;

use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextHandler;
use App\Chain\Domain\Entity\Text;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Chain\Infrastructure\Symfony\Console\LoadTextFixturesCommand;
use App\Chain\Infrastructure\Symfony\Console\TextFixtures;
use App\Chain\Infrastructure\Symfony\HtmlSanitizer\SymfonyHtmlSanitizer;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class LoadTextFixturesCommandTest extends TestCase
{
    private InMemoryTextRepository $repository;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->repository = new InMemoryTextRepository();
        $handler = new CreateTextHandler($this->repository, new SymfonyHtmlSanitizer());

        // The commands go through the real handler: the fixtures obey the
        // same rules as a text typed by an organizer
        $commandBus = new class($handler) implements CommandBusInterface {
            public function __construct(private readonly CreateTextHandler $handler)
            {
            }

            #[\Override]
            public function dispatch(CommandInterface $command): mixed
            {
                \assert($command instanceof CreateText);

                return ($this->handler)($command);
            }
        };

        $this->tester = new CommandTester(new LoadTextFixturesCommand($commandBus, $this->repository));
    }

    public function testItFillsAnEmptyLibraryWithFortyEightTexts(): void
    {
        self::assertSame(Command::SUCCESS, $this->tester->execute([]));

        $texts = $this->repository->findAll();
        self::assertCount(48, $texts);

        $titles = array_map(static fn (Text $text): string => $text->getTitle()->value, $texts);
        self::assertCount(48, array_unique($titles), 'Each fixture text has its own title.');

        $sources = implode("\n", array_map(static fn (Text $text): string => $text->getSource()->value, $texts));
        self::assertStringContainsString('Thérèse d’Avila', $sources);
        self::assertStringContainsString('Jean de la Croix', $sources);
        self::assertStringContainsString('Thérèse de Lisieux', $sources);
        self::assertStringContainsString('Psaume', $sources);
    }

    public function testTheBodiesStayWithinWhatTheEditorProduces(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        foreach (TextFixtures::all() as $text) {
            // Nothing is stripped or encoded: the fixtures only use p, br,
            // strong, em and blockquote, and typographic apostrophes
            self::assertSame($text['body'], $sanitizer->sanitize($text['body']), $text['title']);
        }
    }

    public function testItLeavesANonEmptyLibraryUntouched(): void
    {
        $this->tester->execute([]);

        self::assertSame(Command::SUCCESS, $this->tester->execute([]));
        self::assertCount(48, $this->repository->findAll());
        self::assertStringContainsString('La bibliothèque contient déjà des textes', $this->tester->getDisplay());
    }
}
