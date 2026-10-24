<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\RenamePriant;
use App\Chain\Application\RenamePriantHandler;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Event\PriantRenamed;
use App\Chain\Domain\Exception\PriantNotFoundException;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryPriantRepository;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\Shared\Application\Event\Fixture\EventBusSpy;
use PHPUnit\Framework\TestCase;

/**
 * RenamePriant (F14-T03, US-14.2): the name is carried by the priant, so
 * the new one shows everywhere — grid, relay, intentions, history (RG-IN-2).
 */
final class RenamePriantHandlerTest extends TestCase
{
    private InMemoryPriantRepository $priantRepository;

    private EventBusSpy $eventBus;

    private RenamePriantHandler $handler;

    private Priant $thomas;

    protected function setUp(): void
    {
        $this->priantRepository = new InMemoryPriantRepository();
        $this->eventBus = new EventBusSpy();
        $this->handler = new RenamePriantHandler($this->priantRepository, $this->eventBus);
        $this->thomas = new Priant(new Email('thomas@bailletced.org'), new DisplayName('Famille Martin'));
        $this->priantRepository->add($this->thomas);
    }

    public function testThePriantIsRenamedAndTheChangeIsAnnounced(): void
    {
        ($this->handler)(new RenamePriant($this->thomas->getId(), new DisplayName('Famille Martin-Dubois')));

        self::assertSame('Famille Martin-Dubois', $this->priantRepository->findById($this->thomas->getId())?->getDisplayName()->value);
        self::assertEquals([new PriantRenamed($this->thomas->getId(), new DisplayName('Famille Martin-Dubois'))], $this->eventBus->dispatched);
    }

    public function testTheSameNameChangesNothing(): void
    {
        ($this->handler)(new RenamePriant($this->thomas->getId(), new DisplayName(' Famille Martin ')));

        self::assertSame([], $this->eventBus->dispatched);
    }

    public function testAnEmptyOrTooLongNameIsRefused(): void
    {
        foreach (['   ', str_repeat('a', DisplayName::MAX_LENGTH + 1)] as $invalid) {
            try {
                new RenamePriant($this->thomas->getId(), new DisplayName($invalid));
                self::fail(\sprintf('« %s » should be refused.', $invalid));
            } catch (\InvalidArgumentException $e) {
                self::assertSame('Le nom affiché doit comporter entre 1 et 80 caractères.', $e->getMessage());
            }
        }
    }

    public function testAnUnknownPriantCannotBeRenamed(): void
    {
        $this->expectException(PriantNotFoundException::class);

        ($this->handler)(new RenamePriant(new PriantId(), new DisplayName('Famille Martin-Dubois')));
    }
}
