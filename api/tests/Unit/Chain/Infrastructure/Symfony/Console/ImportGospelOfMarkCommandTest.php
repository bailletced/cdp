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

    /**
     * The cutting follows the reading table of the 2026 printed support:
     * 48 half-hour slots, from Saturday 22:00 (Marc 1, 1-13) to Sunday 21:30
     * (Marc 16, 9-20). The support table carries four typographical slips
     * (Mc 1, 35-46, the skipped 4, 34 and 14, 30-31, and 15, 20 counted
     * twice); the boundaries below keep the gospel whole: 1, 35 ends at
     * verse 45, 4, 34 joins the parables, 14, 30-31 stay with the supper,
     * and 15, 21 takes over after verse 20.
     */
    public function testTheCuttingFollowsTheReadingTableOfThePrintedSupport(): void
    {
        $expected = [
            'Marc 1, 1-13',
            'Marc 1, 14-20',
            'Marc 1, 21-34',
            'Marc 1, 35-45',
            'Marc 2, 1-12',
            'Marc 2, 13-28',
            'Marc 3, 1-12',
            'Marc 3, 13-19',
            'Marc 3, 20-35',
            'Marc 4, 1-34',
            'Marc 4, 35-41',
            'Marc 5, 1-20',
            'Marc 5, 21-43',
            'Marc 6, 1-13',
            'Marc 6, 14-29',
            'Marc 6, 30-52',
            'Marc 6, 53-7, 23',
            'Marc 7, 24-37',
            'Marc 8, 1-10',
            'Marc 8, 11-21',
            'Marc 8, 22-30',
            'Marc 8, 31-9, 1',
            'Marc 9, 2-13',
            'Marc 9, 14-29',
            'Marc 9, 30-37',
            'Marc 9, 38-50',
            'Marc 10, 1-16',
            'Marc 10, 17-31',
            'Marc 10, 32-45',
            'Marc 10, 46-52',
            'Marc 11, 1-11',
            'Marc 11, 12-26',
            'Marc 11, 27-12, 12',
            'Marc 12, 13-34',
            'Marc 12, 35-44',
            'Marc 13, 1-13',
            'Marc 13, 14-37',
            'Marc 14, 1-11',
            'Marc 14, 12-31',
            'Marc 14, 32-42',
            'Marc 14, 43-54',
            'Marc 14, 55-65',
            'Marc 14, 66-72',
            'Marc 15, 1-20',
            'Marc 15, 21-39',
            'Marc 15, 40-47',
            'Marc 16, 1-8',
            'Marc 16, 9-20',
        ];

        self::assertSame($expected, array_column(GospelOfMark::readings(), 'title'));
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
