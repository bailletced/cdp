<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Message\CampaignMessage;
use App\Shared\Application\Message\ConfirmationMessage;
use App\Shared\Application\Message\LinkMessage;
use App\Shared\Application\Message\ReminderMessage;
use App\Shared\Domain\ValueObject\Email;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

/**
 * Test pour F00-T18 : Transports Messenger Doctrine.
 * Vérifie que les messages sont routés vers les bons transports — et
 * uniquement ceux-là : un message routé ne doit pas être dupliqué vers le
 * transport "failed" (qui est réservé aux messages réellement en échec).
 */
final class MessageTransportTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->messageBus = self::getContainer()->get(MessageBusInterface::class);
    }

    public function testConfirmationMessageIsRoutedToAsyncTransactional(): void
    {
        $message = new ConfirmationMessage(
            new Email('test@bailletced.org'),
            'token-123',
            new DateTimeImmutable()
        );

        $this->messageBus->dispatch($message);

        $this->transport('async_transactional')->queue()->assertContains(ConfirmationMessage::class);
        $this->transport('failed')->queue()->assertEmpty();
    }

    public function testLinkMessageIsRoutedToAsyncTransactional(): void
    {
        $message = new LinkMessage(
            new Email('test@bailletced.org'),
            'http://example.com/link',
            'Test Subject',
            new DateTimeImmutable()
        );

        $this->messageBus->dispatch($message);

        $this->transport('async_transactional')->queue()->assertContains(LinkMessage::class);
        $this->transport('failed')->queue()->assertEmpty();
    }

    public function testReminderMessageIsRoutedToAsyncTransactional(): void
    {
        $message = new ReminderMessage(
            new Email('test@bailletced.org'),
            'Reminder content',
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable()
        );

        $this->messageBus->dispatch($message);

        $this->transport('async_transactional')->queue()->assertContains(ReminderMessage::class);
        $this->transport('failed')->queue()->assertEmpty();
    }

    public function testCampaignMessageIsRoutedToAsyncBulk(): void
    {
        $message = new CampaignMessage(
            'campaign-123',
            ['recipient1@bailletced.org', 'recipient2@bailletced.org'],
            'Campaign Subject',
            'email_template',
            new DateTimeImmutable('+1 hour'),
            new DateTimeImmutable()
        );

        $this->messageBus->dispatch($message);

        $this->transport('async_bulk')->queue()->assertContains(CampaignMessage::class);
        $this->transport('failed')->queue()->assertEmpty();
    }
}
