<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Symfony\Controller;

use App\Access\Application\Register;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Email;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /auth/register (F07-T01).
 *
 * Always answers 202 Accepted with the same body, whether the account was
 * created, already existed, the email is invalid, or the honeypot field
 * "website" is filled, so the endpoint cannot be used to enumerate accounts.
 * The command is only dispatched for a plausible non-bot request.
 */
final class RegisterController
{
    private const ACCEPTED_MESSAGE = 'Un lien de connexion vient de vous être envoyé à cette adresse.';

    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function register(Request $request): JsonResponse
    {
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

        $this->commandBus->dispatch(new Register($emailVo));
    }
}
