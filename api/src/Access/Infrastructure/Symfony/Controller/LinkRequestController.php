<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Symfony\Controller;

use App\Access\Application\RequestPersonalLink;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Symfony\RateLimiter\PublicFormLimiter;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /auth/link-request (F02-T15).
 *
 * Always answers 202 Accepted with the same body, whether the account exists,
 * the email is invalid, or the honeypot field "website" is filled, so the
 * endpoint cannot be used to enumerate accounts. The command is only
 * dispatched for a plausible non-bot request; the handler stays silent for
 * unknown accounts. Hourly limits (F15-T03): beyond 10 requests from an IP,
 * a 429; beyond 3 for an address, the same 202 but no e-mail leaves.
 */
final class LinkRequestController
{
    private const ACCEPTED_MESSAGE = 'Si un compte correspond à cette adresse, un lien de connexion vient de vous être envoyé.';

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly PublicFormLimiter $publicFormLimiter,
    ) {
    }

    public function requestLink(Request $request): JsonResponse
    {
        // Hourly limit per IP (F15-T03): 429 + Retry-After
        $this->publicFormLimiter->countLinkRequest();

        $payload = json_decode($request->getContent(), true);

        if (\is_array($payload) && !$this->isHoneypotFilled($payload)) {
            $this->dispatchIfValidEmail($payload);
        }

        return new JsonResponse(['message' => self::ACCEPTED_MESSAGE], Response::HTTP_ACCEPTED);
    }

    /**
     * @param array<mixed, mixed> $payload
     */
    private function isHoneypotFilled(array $payload): bool
    {
        $honeypot = $payload['website'] ?? null;

        return \is_string($honeypot) && '' !== trim($honeypot);
    }

    /**
     * @param array<mixed, mixed> $payload
     */
    private function dispatchIfValidEmail(array $payload): void
    {
        $email = $payload['email'] ?? null;

        if (!\is_string($email)) {
            return;
        }

        try {
            $emailVo = new Email($email);
        } catch (InvalidArgumentException) {
            return;
        }

        // Hourly limit per e-mail (F15-T03): beyond, the same 202, no e-mail
        if (!$this->publicFormLimiter->allowsLinkTo($emailVo)) {
            return;
        }

        $this->commandBus->dispatch(new RequestPersonalLink($emailVo));
    }
}
