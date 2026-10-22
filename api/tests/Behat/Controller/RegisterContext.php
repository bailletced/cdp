<?php

declare(strict_types=1);

namespace App\Tests\Behat\Controller;

use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Behat\BehatKernelTrait;
use App\Tests\Functional\Shared\Fixture\InMemoryMailTransport;
use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MimeEmail;
use Zenstruck\Messenger\Test\Transport\TestTransport;

/**
 * Behat context for the POST /auth/register endpoint (F07-T01).
 * The endpoint must always answer 202 Accepted with the same body, whether
 * the account was created, already existed, the email is invalid, or the
 * honeypot field is filled, so it cannot be used to enumerate accounts.
 *
 * Domain events are routed to the async_transactional transport (transactional
 * outbox): the scenario must explicitly process the queue to simulate the
 * worker consuming the AccountRegistered event and sending the link email.
 */
final class RegisterContext implements Context
{
    use BehatKernelTrait;

    private const EXPECTED_MESSAGE = 'Un lien de connexion vient de vous être envoyé à cette adresse.';

    #[Then('the response should be the registration accepted response')]
    public function responseShouldBeTheRegistrationAcceptedResponse(): void
    {
        $state = $this->state();

        if (Response::HTTP_ACCEPTED !== $state->lastResponseStatus) {
            throw new \RuntimeException(\sprintf('Expected status 202 (Accepted), got %d', $state->lastResponseStatus ?? 0));
        }

        $body = json_decode($state->lastResponseContent ?? '', true);
        if (!\is_array($body) || ['message' => self::EXPECTED_MESSAGE] !== $body) {
            throw new \RuntimeException(\sprintf('Expected body {"message": "%s"}, got "%s"', self::EXPECTED_MESSAGE, (string) $state->lastResponseContent));
        }
    }

    #[Then('an account should exist with email :email')]
    public function accountShouldExistWithEmail(string $email): void
    {
        $account = $this->accountRepository()->findByEmail(new Email($email));

        if (null === $account) {
            throw new \RuntimeException(\sprintf('Expected an account with email "%s", got none', $email));
        }
    }

    #[Then('no account should exist with email :email')]
    public function noAccountShouldExistWithEmail(string $email): void
    {
        $account = $this->accountRepository()->findByEmail(new Email($email));

        if (null !== $account) {
            throw new \RuntimeException(\sprintf('Expected no account with email "%s", but one exists', $email));
        }
    }

    #[When('I process the async queue')]
    public function processAsyncQueue(): void
    {
        $transport = $this->asyncTransport();
        // Process all queued messages (AccountRegistered event, then the
        // LinkMessage it produces, and so on until the queue is empty).
        while ([] !== $transport->queue()->messages()) {
            $transport->process(1);
        }
    }

    #[Then('a personal link email is sent to :email')]
    public function personalLinkEmailIsSentTo(string $email): void
    {
        $sent = $this->mailTransport()->sent;

        foreach ($sent as $message) {
            if (!$message instanceof MimeEmail) {
                continue;
            }

            $recipients = array_map(
                static fn (Address $address): string => $address->getAddress(),
                $message->getTo(),
            );

            if (\in_array($email, $recipients, true)) {
                return;
            }
        }

        throw new \RuntimeException(\sprintf('Expected a personal link email sent to "%s", got %d email(s)', $email, \count($sent)));
    }

    #[Then('no email is sent')]
    public function noEmailIsSent(): void
    {
        $sent = $this->mailTransport()->sent;

        if ([] !== $sent) {
            throw new \RuntimeException(\sprintf('Expected no email, got %d', \count($sent)));
        }
    }

    private function accountRepository(): AccountRepositoryInterface
    {
        return $this->testContainer()->get(AccountRepositoryInterface::class);
    }

    private function asyncTransport(): TestTransport
    {
        $transport = $this->testContainer()->get('messenger.transport.async_transactional');
        if (!$transport instanceof TestTransport) {
            throw new \RuntimeException('The async_transactional transport is not a test transport.');
        }

        return $transport;
    }

    private function mailTransport(): InMemoryMailTransport
    {
        $transport = $this->testContainer()->get(InMemoryMailTransport::class);
        if (!$transport instanceof InMemoryMailTransport) {
            throw new \RuntimeException('The mail transport is not the in-memory test transport.');
        }

        return $transport;
    }

    private function testContainer(): \Symfony\Component\DependencyInjection\ContainerInterface
    {
        $container = $this->state()->kernel?->getContainer();
        if (null === $container) {
            throw new \RuntimeException('Kernel is not booted.');
        }

        return $container->get('test.service_container');
    }
}
