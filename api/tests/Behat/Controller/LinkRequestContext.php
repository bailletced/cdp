<?php

declare(strict_types=1);

namespace App\Tests\Behat\Controller;

use App\Shared\Application\Message\LinkMessage;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Behat\BehatKernelTrait;
use App\Tests\Factory\AccountFactory;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Messenger\Test\Transport\TestTransport;

/**
 * Behat context for the POST /auth/link-request endpoint (F02-T15).
 * The endpoint must always answer 202 Accepted with the same body, whether
 * the account exists, the email is invalid, or the honeypot field is filled,
 * so it cannot be used to enumerate accounts.
 */
final class LinkRequestContext implements Context
{
    use BehatKernelTrait;

    private const EXPECTED_MESSAGE = 'Si un compte correspond à cette adresse, un lien de connexion vient de vous être envoyé.';

    #[Given('an account exists with email :email')]
    public function accountExistsWithEmail(string $email): void
    {
        $this->state()->currentAccount = AccountFactory::createOne(['email' => new Email($email)]);
    }

    #[When('I POST JSON to :path with body:')]
    public function postJsonToWithBody(string $path, PyStringNode $body): void
    {
        $request = Request::create($path, 'POST', content: (string) $body);
        $request->headers->set('Content-Type', 'application/json');

        $this->handleRequest($request);
    }

    #[When('I POST JSON-LD to :path with body:')]
    public function postJsonLdToWithBody(string $path, PyStringNode $body): void
    {
        $request = Request::create($path, 'POST', content: (string) $body);
        $request->headers->set('Content-Type', 'application/ld+json');

        $this->handleRequest($request);
    }

    #[Then('the response should be the generic accepted response')]
    public function responseShouldBeTheGenericAcceptedResponse(): void
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

    #[Then('a link message is queued for :email')]
    public function linkMessageIsQueuedFor(string $email): void
    {
        $messages = $this->queuedLinkMessages();

        if (1 !== \count($messages)) {
            throw new \RuntimeException(\sprintf('Expected exactly 1 queued link message, got %d', \count($messages)));
        }

        $message = $messages[0] ?? null;
        if (!$message instanceof LinkMessage || $message->email->value !== $email) {
            $actual = $message instanceof LinkMessage ? $message->email->value : '(none)';
            throw new \RuntimeException(\sprintf('Expected queued link message for "%s", got "%s"', $email, $actual));
        }
    }

    #[Then('no link message is queued')]
    public function noLinkMessageIsQueued(): void
    {
        $messages = $this->queuedLinkMessages();

        if ([] !== $messages) {
            throw new \RuntimeException(\sprintf('Expected no queued link message, got %d', \count($messages)));
        }
    }

    /**
     * @return list<LinkMessage>
     */
    private function queuedLinkMessages(): array
    {
        $container = $this->state()->kernel?->getContainer();
        if (null === $container) {
            throw new \RuntimeException('Kernel is not booted.');
        }

        $transport = $container->get('test.service_container')->get('messenger.transport.async_transactional');
        if (!$transport instanceof TestTransport) {
            throw new \RuntimeException('The async_transactional transport is not a test transport.');
        }

        return $transport->queue()->messages(LinkMessage::class);
    }
}
