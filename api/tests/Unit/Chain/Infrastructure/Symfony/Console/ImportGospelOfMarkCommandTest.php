<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Symfony\Console;

use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionHandler;
use App\Chain\Application\CreateEditionResult;
use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextHandler;
use App\Chain\Application\DistributeTexts;
use App\Chain\Application\DistributeTextsHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Slot;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Chain\Infrastructure\Symfony\Console\GospelOfMark;
use App\Chain\Infrastructure\Symfony\Console\ImportGospelOfMarkCommand;
use App\Chain\Infrastructure\Symfony\HtmlSanitizer\SymfonyHtmlSanitizer;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportGospelOfMarkCommandTest extends TestCase
{
    private InMemoryEditionRepository $editions;

    private InMemoryTextRepository $texts;

    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        $this->editions = new InMemoryEditionRepository();
        $this->texts = new InMemoryTextRepository();

        // The commands go through the real handlers: the readings obey the
        // same rules as a text typed by an organizer
        $this->commandBus = new class($this->editions, $this->texts) implements CommandBusInterface {
            public function __construct(
                private readonly InMemoryEditionRepository $editions,
                private readonly InMemoryTextRepository $texts,
            ) {
            }

            #[\Override]
            public function dispatch(CommandInterface $command): mixed
            {
                return match (true) {
                    $command instanceof CreateEdition => new CreateEditionHandler($this->editions, new ParisCalendar())($command),
                    $command instanceof DistributeTexts => new DistributeTextsHandler($this->editions, $this->texts)($command),
                    $command instanceof CreateText => new CreateTextHandler($this->texts, new SymfonyHtmlSanitizer())($command),
                    default => throw new \LogicException($command::class),
                };
            }
        };
    }

    public function testTheGospelIsReadWholeAndInOrderOverFortyEightReadings(): void
    {
        $readings = GospelOfMark::readings();
        self::assertCount(48, $readings);
        self::assertSame('Marc 1, 1-13', $readings[0]['title']);
        self::assertSame('Marc 16, 9-20', $readings[47]['title']);

        // Every verse once, in order: 16 chapters, 678 verses (the long
        // ending 16, 9-20 included)
        $chapters = [];
        $chapter = 0;
        $expected = 1;
        foreach ($readings as $reading) {
            preg_match_all('#<em>(\d+)</em>#', $reading['body'], $matches);
            foreach (array_map(intval(...), $matches[1]) as $verse) {
                if (1 === $verse) {
                    ++$chapter;
                    $expected = 1;
                }
                self::assertSame($expected, $verse, \sprintf('%s: verse %d expected.', $reading['title'], $expected));
                $chapters[$chapter] = $verse;
                ++$expected;
            }
        }
        self::assertCount(16, $chapters);
        self::assertSame(678, array_sum($chapters));
        self::assertSame(47, $chapters[15]);
        self::assertSame(20, $chapters[16]);
    }

    public function testTheBodiesStayWithinWhatTheEditorProduces(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        foreach (GospelOfMark::readings() as $reading) {
            self::assertSame($reading['body'], $sanitizer->sanitize($reading['body']), $reading['title']);
        }
    }

    public function testItAddsTheFortyEightReadingsToTheLibrary(): void
    {
        $tester = $this->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $texts = $this->texts->findAll();
        self::assertCount(48, $texts);
        self::assertSame('Évangile selon saint Marc — traduction Louis Segond (1910)', $texts[0]->getSource()->value);
        self::assertStringContainsString('48 lectures ajoutées à la bibliothèque', $tester->getDisplay());
    }

    public function testRunningItAgainAddsNothing(): void
    {
        $this->execute([]);

        $tester = $this->execute([]);

        self::assertCount(48, $this->texts->findAll());
        self::assertStringContainsString('0 lecture ajoutée à la bibliothèque', $tester->getDisplay());
    }

    public function testItSpreadsTheReadingsOverTheSlotsOfAnEdition(): void
    {
        $editionId = $this->createEdition();

        $tester = $this->execute(['--edition' => (string) $editionId]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $edition = $this->editions->findById($editionId);
        self::assertInstanceOf(Edition::class, $edition);
        $titles = array_map(fn (Slot $slot): ?string => $this->textTitle($slot->getTextId()?->toRfc4122()), $edition->getSlots());
        self::assertSame(array_column(GospelOfMark::readings(), 'title'), $titles);
        self::assertStringContainsString('réparties sur les 48 créneaux', $tester->getDisplay());
    }

    public function testItReusesTheReadingsAlreadyInTheLibraryForAnotherEdition(): void
    {
        $this->execute(['--edition' => (string) $this->createEdition()]);

        $this->execute(['--edition' => (string) $this->createEdition()]);

        self::assertCount(48, $this->texts->findAll());
    }

    public function testItRefusesAnUnknownEdition(): void
    {
        $tester = $this->execute(['--edition' => '01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Édition introuvable', $tester->getDisplay());
        self::assertSame([], $this->texts->findAll(), 'Nothing is imported for an unknown edition.');
    }

    /**
     * @param array<string, string> $input
     */
    private function execute(array $input): CommandTester
    {
        $tester = new CommandTester(new ImportGospelOfMarkCommand($this->commandBus, $this->texts, $this->editions));
        $tester->execute($input);

        return $tester;
    }

    private function createEdition(): EditionId
    {
        $result = $this->commandBus->dispatch(new CreateEdition(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2099-11-28 20:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2099-11-13 20:00', new \DateTimeZone('Europe/Paris')),
        ));
        \assert($result instanceof CreateEditionResult);

        return $result->editionId;
    }

    private function textTitle(?string $textId): ?string
    {
        return null === $textId ? null : $this->texts->findById(new TextId($textId))?->getTitle()->value;
    }
}
