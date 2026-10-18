<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Application\Message\Handler;

use App\Shared\Application\Message\Handler\LinkMessageHandler;
use App\Shared\Application\Message\LinkMessage;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Functional\Shared\Fixture\InMemoryMailTransport;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MimeEmail;

/**
 * F02-T14: LinkMessageHandler sends the personal link email, from the
 * Communion identity, with the link in the HTML and text bodies.
 */
final class LinkMessageHandlerTest extends KernelTestCase
{
    private InMemoryMailTransport $mailTransport;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->mailTransport = self::getContainer()->get(InMemoryMailTransport::class);
        $this->mailTransport->reset();
    }

    public function testItSendsThePersonalLinkEmail(): void
    {
        $handler = self::getContainer()->get(LinkMessageHandler::class);
        self::assertInstanceOf(LinkMessageHandler::class, $handler);

        $handler(new LinkMessage(
            new Email('thomas@example.com'),
            'http://localhost/auth/login?user=abc&expires=123&hash=def',
            'Votre lien personnel',
        ));

        self::assertCount(1, $this->mailTransport->sent);
        $email = $this->mailTransport->sent[0] ?? self::fail('No email was sent.');
        self::assertInstanceOf(MimeEmail::class, $email);

        $to = array_map(static fn (Address $address): string => $address->getAddress(), $email->getTo());
        self::assertSame(['thomas@example.com'], $to);
        self::assertSame('Votre lien personnel', $email->getSubject());

        $fromAddresses = array_map(static fn (Address $address): string => $address->getAddress(), $email->getFrom());
        self::assertSame([self::getContainer()->getParameter('communion_email')], $fromAddresses);
        $fromNames = array_map(static fn (Address $address): string => $address->getName(), $email->getFrom());
        self::assertSame([self::getContainer()->getParameter('communion_name')], $fromNames);

        $html = $email->getHtmlBody();
        self::assertIsString($html);
        // Twig escapes the link in the HTML body ("&" becomes "&amp;")
        self::assertStringContainsString(htmlspecialchars('http://localhost/auth/login?user=abc&expires=123&hash=def'), $html);
        self::assertStringContainsString('Ce lien est personnel', $html);

        $text = $email->getTextBody();
        self::assertIsString($text);
        self::assertStringContainsString('http://localhost/auth/login?user=abc&expires=123&hash=def', $text);
    }
}
