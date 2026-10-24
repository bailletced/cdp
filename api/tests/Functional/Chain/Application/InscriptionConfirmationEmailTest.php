<?php

declare(strict_types=1);

namespace App\Tests\Functional\Chain\Application;

use App\Chain\Application\RegisterInscriptions;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Functional\Shared\Fixture\InMemoryMailTransport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MimeEmail;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

/**
 * F11-T05, US-11.1: one inscription batch → one confirmation email, sent by
 * the worker, with the agenda file attached and the personal link; nothing
 * for a priant without email (RG-NO-3).
 */
final class InscriptionConfirmationEmailTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private CommandBusInterface $commandBus;

    private EntityManagerInterface $em;

    private InMemoryMailTransport $mailTransport;

    private Edition $edition;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->commandBus = $container->get(CommandBusInterface::class);
        $this->em = $container->get(EntityManagerInterface::class);
        $this->mailTransport = $container->get(InMemoryMailTransport::class);
        $this->mailTransport->reset();

        $connection = $this->em->getConnection();
        foreach (['inscription', 'priant', 'slot', 'edition'] as $table) {
            $connection->executeStatement(\sprintf('DELETE FROM %s', $table));
        }
        $connection->executeStatement('TRUNCATE organizer, account CASCADE');

        $this->edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2099-01-01 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->em->persist($this->edition);
        $this->em->flush();
    }

    public function testThreeSlotsAtOnceMakeOneEmailWithTheAgendaFileAndThePersonalLink(): void
    {
        $this->register(new Email('marie@bailletced.org'), 0, 1, 2);

        // Queued during the request, sent by the worker
        self::assertCount(0, $this->mailTransport->sent);
        $this->transport('async_transactional')->process();

        self::assertCount(1, $this->mailTransport->sent);
        $email = $this->mailTransport->sent[0];
        self::assertInstanceOf(MimeEmail::class, $email);
        self::assertSame(['marie@bailletced.org'], array_map(static fn (Address $address): string => $address->getAddress(), $email->getTo()));
        self::assertSame('Votre inscription : 24 heures de prière', $email->getSubject());

        $text = (string) $email->getTextBody();
        self::assertSame(3, substr_count($text, '(heure de Paris)'));
        self::assertSame(3, substr_count($text, 'Le texte de ce créneau sera bientôt disponible.'));
        self::assertStringContainsString('/auth/login', $text);

        $attachments = $email->getAttachments();
        self::assertCount(1, $attachments);
        self::assertSame('text/calendar', $attachments[0]->getContentType());
        self::assertSame(3, substr_count($attachments[0]->getBody(), 'BEGIN:VEVENT'));
    }

    public function testASecondBatchOnlyRecallsItsOwnSlots(): void
    {
        $this->register(new Email('marie@bailletced.org'), 0);
        $this->transport('async_transactional')->process();
        $this->register(new Email('marie@bailletced.org'), 5);
        $this->transport('async_transactional')->process();

        self::assertCount(2, $this->mailTransport->sent);
        $second = $this->mailTransport->sent[1];
        self::assertInstanceOf(MimeEmail::class, $second);
        self::assertSame(1, substr_count((string) $second->getTextBody(), '(heure de Paris)'));
    }

    public function testAPriantWithoutEmailGetsNoEmail(): void
    {
        $this->register(null, 0);
        $this->transport('async_transactional')->process();

        self::assertCount(0, $this->mailTransport->sent);
    }

    private function register(?Email $email, int ...$slotIndexes): void
    {
        $this->commandBus->dispatch(new RegisterInscriptions(
            array_map(fn (int $index) => $this->edition->getSlots()[$index]->getId(), $slotIndexes),
            new DisplayName('Marie Dupont'),
            $email,
            consent: true,
        ));
    }
}
