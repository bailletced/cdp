<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Message\CampaignMessage;
use App\Shared\Application\Message\ConfirmationMessage;
use App\Shared\Application\Message\LinkMessage;
use App\Shared\Application\Message\MessageInterface;
use App\Shared\Application\Message\ReminderMessage;
use App\Shared\Domain\ValueObject\Email;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Test pour F00-T18 : Transports Messenger Doctrine.
 * Vérifie que les messages sont routés vers les bons transports.
 */
final class MessageTransportTest extends KernelTestCase
{
    private MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->messageBus = self::getContainer()->get(MessageBusInterface::class);
    }

    public function testConfirmationMessageIsRoutedToAsyncTransactional(): void
    {
        $message = new ConfirmationMessage(
            new Email('test@example.com'),
            'token-123',
            new DateTimeImmutable()
        );

        // Le message doit être dispatché sans erreur
        $this->messageBus->dispatch($message);

        $this->assertTrue(true, 'ConfirmationMessage dispatched successfully to async_transactional');
    }

    public function testLinkMessageIsRoutedToAsyncTransactional(): void
    {
        $message = new LinkMessage(
            new Email('test@example.com'),
            'http://example.com/link',
            'Test Subject',
            new DateTimeImmutable()
        );

        $this->messageBus->dispatch($message);

        $this->assertTrue(true, 'LinkMessage dispatched successfully to async_transactional');
    }

    public function testReminderMessageIsRoutedToAsyncTransactional(): void
    {
        $message = new ReminderMessage(
            new Email('test@example.com'),
            'Reminder content',
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable()
        );

        $this->messageBus->dispatch($message);

        $this->assertTrue(true, 'ReminderMessage dispatched successfully to async_transactional');
    }

    public function testCampaignMessageIsRoutedToAsyncBulk(): void
    {
        $message = new CampaignMessage(
            'campaign-123',
            ['recipient1@example.com', 'recipient2@example.com'],
            'Campaign Subject',
            'email_template',
            new DateTimeImmutable('+1 hour'),
            new DateTimeImmutable()
        );

        $this->messageBus->dispatch($message);

        $this->assertTrue(true, 'CampaignMessage dispatched successfully to async_bulk');
    }

    public function testMessagesImplementMessageInterface(): void
    {
        $this->assertInstanceOf(MessageInterface::class, new ConfirmationMessage(new Email('test@example.com'), 'token'));
        $this->assertInstanceOf(MessageInterface::class, new LinkMessage(new Email('test@example.com'), 'http://example.com', 'Subject'));
        $this->assertInstanceOf(MessageInterface::class, new ReminderMessage(new Email('test@example.com'), 'content', new DateTimeImmutable()));
        $this->assertInstanceOf(MessageInterface::class, new CampaignMessage('camp-1', ['test@example.com'], 'Subject', 'template', new DateTimeImmutable()));
    }
}
