<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Altcha;

use AltchaOrg\Altcha\Algorithm\Sha;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\VerifySolutionOptions;
use Psr\Clock\ClockInterface;

/**
 * Self-hosted ALTCHA proof of work for the public forms (F15-T05, US-15.3,
 * §6.7): off by default, switched on by ALTCHA_ENABLED during an attack,
 * without a code deployment. No third party: the challenge is signed with
 * the application secret, and the visitor's browser solves it on its own.
 *
 * Kept light for an old phone: one SHA-256 per attempt, 10,000 to 50,000
 * attempts — under a second — and valid for ten minutes.
 */
final readonly class AltchaGate
{
    private const int COST = 1;

    private const int MIN_ATTEMPTS = 10_000;

    private const int MAX_ATTEMPTS = 50_000;

    private const int VALIDITY_SECONDS = 600;

    private Altcha $altcha;

    public function __construct(
        private bool $enabled,
        string $secret,
        private ClockInterface $clock,
    ) {
        $this->altcha = new Altcha($secret);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * A new challenge, as the widget expects it.
     *
     * @return array<string, mixed>
     */
    public function challenge(): array
    {
        return $this->altcha->createChallenge(new CreateChallengeOptions(
            algorithm: new Sha(),
            cost: self::COST,
            counter: random_int(self::MIN_ATTEMPTS, self::MAX_ATTEMPTS),
            expiresAt: $this->clock->now()->getTimestamp() + self::VALIDITY_SECONDS,
        ))->toArray();
    }

    /**
     * Whether a form may go through: always while off; once on, only with
     * the solution (the widget's base64 payload) of a challenge signed here
     * and not expired.
     */
    public function allows(?string $payload): bool
    {
        if (!$this->enabled) {
            return true;
        }

        if (null === $payload || '' === $payload) {
            return false;
        }

        try {
            return $this->altcha->verifySolution(new VerifySolutionOptions(
                payload: $payload,
                algorithm: new Sha(),
            ))->verified;
        } catch (\Throwable) {
            // A malformed payload is no solution
            return false;
        }
    }
}
