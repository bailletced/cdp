<?php

declare(strict_types=1);

namespace App\Tests\Functional\Access\Application;

use App\Access\Application\RequestPersonalLink;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Message\LinkMessage;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Factory\AccountFactory;
use App\Tests\Functional\Shared\Fixture\InMemoryMailTransport;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MimeEmail;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

/**
 * F02-T14: RequestPersonalLink command.
 * A known account receives a personal link + a "Votre lien personnel" email
 * through the async_transactional transport; an unknown account gets nothing,
 * and neither the email address nor the personal link leaks into the logs.
 */
final class RequestPersonalLinkTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private CommandBusInterface $commandBus;

    private EntityManagerInterface $entityManager;

    private InMemoryMailTransport $mailTransport;

    private TestHandler $logCapture;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->commandBus = $container->get(CommandBusInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->mailTransport = $container->get(InMemoryMailTransport::class);
        $this->mailTransport->reset();

        $logCapture = $container->get('monolog.handler.test_capture');
        self::assertInstanceOf(TestHandler::class, $logCapture);
        $this->logCapture = $logCapture;
        $this->logCapture->clear();

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE account CASCADE');
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE account CASCADE');
        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testKnownAccountReceivesPersonalLinkEmail(): void
    {
        AccountFactory::createOne(['email' => new Email('thomas@bailletced.org')]);

        $this->commandBus->dispatch(new RequestPersonalLink(new Email('thomas@bailletced.org')));

        // The link message is routed to the async_transactional transport
        $this->transport('async_transactional')->queue()->assertCount(1);
        $this->transport('async_transactional')->queue()->assertContains(LinkMessage::class);

        $message = $this->transport('async_transactional')->queue()->first(LinkMessage::class)->getMessage();
        self::assertInstanceOf(LinkMessage::class, $message);
        self::assertSame('thomas@bailletced.org', $message->email->value);
        self::assertSame('Votre lien personnel', $message->subject);
        self::assertStringContainsString('/auth/login', $message->link);

        // Consume the message like a worker would: the email is sent
        $this->transport('async_transactional')->processOrFail();
        $this->transport('async_transactional')->queue()->assertEmpty();
        $this->transport('async_transactional')->acknowledged()->assertCount(1);

        self::assertCount(1, $this->mailTransport->sent);
        $email = $this->mailTransport->sent[0] ?? self::fail('No email was sent.');
        self::assertInstanceOf(MimeEmail::class, $email);

        $to = array_map(static fn (Address $address): string => $address->getAddress(), $email->getTo());
        self::assertSame(['thomas@bailletced.org'], $to);
        self::assertSame('Votre lien personnel', $email->getSubject());

        $fromAddresses = array_map(static fn (Address $address): string => $address->getAddress(), $email->getFrom());
        self::assertSame([self::getContainer()->getParameter('communion_email')], $fromAddresses);
        $fromNames = array_map(static fn (Address $address): string => $address->getName(), $email->getFrom());
        self::assertSame([self::getContainer()->getParameter('communion_name')], $fromNames);

        $html = $email->getHtmlBody();
        self::assertIsString($html);
        // Twig escapes the link in the HTML body ("&" becomes "&amp;")
        self::assertStringContainsString(htmlspecialchars($message->link), $html);
        self::assertStringContainsString('Ce lien est personnel', $html);

        $text = $email->getTextBody();
        self::assertIsString($text);
        self::assertStringContainsString($message->link, $text);
    }

    public function testUnknownAccountGetsNothing(): void
    {
        $this->commandBus->dispatch(new RequestPersonalLink(new Email('inconnu@bailletced.org')));

        $this->transport('async_transactional')->queue()->assertEmpty();
        self::assertCount(0, $this->mailTransport->sent);
    }

    public function testPersonalLinkIsNotLoggedInClear(): void
    {
        AccountFactory::createOne(['email' => new Email('thomas@bailletced.org')]);

        $this->commandBus->dispatch(new RequestPersonalLink(new Email('thomas@bailletced.org')));
        $this->transport('async_transactional')->processOrFail();

        $message = $this->transport('async_transactional')->acknowledged()->first(LinkMessage::class)->getMessage();
        self::assertInstanceOf(LinkMessage::class, $message);

        $rendered = '';
        $renderedWithoutDoctrine = '';
        foreach ($this->logCapture->getRecords() as $record) {
            $line = $record['message']."\n".(string) json_encode($record['context'], \JSON_PARTIAL_OUTPUT_ON_ERROR)."\n";
            $rendered .= $line;

            // Doctrine logs raw SQL with bound parameters at debug level; in
            // production the fingers_crossed handler (action_level: error)
            // discards them, so they are out of scope for this test.
            if ('doctrine' !== $record['channel']) {
                $renderedWithoutDoctrine .= $line;
            }
        }

        self::assertStringNotContainsString('thomas@bailletced.org', $renderedWithoutDoctrine);
        self::assertStringNotContainsString($message->link, $rendered);
    }
}
