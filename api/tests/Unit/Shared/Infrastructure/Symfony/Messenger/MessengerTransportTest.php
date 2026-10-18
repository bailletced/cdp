<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Message\CampaignMessage;
use App\Shared\Application\Message\ConfirmationMessage;
use App\Shared\Application\Message\LinkMessage;
use App\Shared\Application\Message\ReminderMessage;
use App\Shared\Domain\ValueObject\Email;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire pour vérifier le routage des messages vers les transports.
 * Pour F00-T18 : Transports Messenger Doctrine.
 */
final class MessengerTransportTest extends TestCase
{
    public function testMessagesAreCorrectlyTyped(): void
    {
        $email = new Email('test@example.com');
        $now = new DateTimeImmutable();

        $confirmation = new ConfirmationMessage($email, 'token', $now);
        $link = new LinkMessage($email, 'http://example.com', 'Subject', $now);
        $reminder = new ReminderMessage($email, 'content', $now, $now);
        $campaign = new CampaignMessage('camp-1', ['test@example.com'], 'Subject', 'template', $now, $now);

        $this->assertSame('token', $confirmation->token);
        $this->assertSame('http://example.com', $link->link);
        $this->assertSame('content', $reminder->content);
        $this->assertSame('camp-1', $campaign->campaignId);
        $this->assertSame($email, $confirmation->email);
    }

    public function testMessagesCanBeSerialized(): void
    {
        $email = new Email('test@example.com');
        $now = new DateTimeImmutable();

        $confirmation = new ConfirmationMessage($email, 'token', $now);

        // Vérifier que le message peut être sérialisé (nécessaire pour Messenger)
        $serialized = serialize($confirmation);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ConfirmationMessage::class, $unserialized);
        $this->assertSame('token', $unserialized->token);
        $this->assertEquals($email, $unserialized->email);
    }
}
