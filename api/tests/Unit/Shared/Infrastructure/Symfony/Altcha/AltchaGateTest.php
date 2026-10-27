<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Altcha;

use AltchaOrg\Altcha\Algorithm\Sha;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use App\Shared\Infrastructure\Symfony\Altcha\AltchaGate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * F15-T05 (US-15.3): the self-hosted ALTCHA challenge, off by default. Once
 * on, a form passes only with the solution of a challenge this server
 * signed, before it expires.
 */
final class AltchaGateTest extends TestCase
{
    private const string SECRET = 'test-secret';

    public function testOffByDefaultEveryRequestPasses(): void
    {
        $gate = new AltchaGate(false, self::SECRET, new MockClock());

        self::assertFalse($gate->isEnabled());
        self::assertTrue($gate->allows(null));
    }

    public function testOnARequestWithoutSolutionIsRefused(): void
    {
        $gate = new AltchaGate(true, self::SECRET, new MockClock());

        self::assertFalse($gate->allows(null));
        self::assertFalse($gate->allows(''));
        self::assertFalse($gate->allows('not base64 json'));
    }

    public function testOnTheSolutionOfItsOwnChallengePasses(): void
    {
        $gate = new AltchaGate(true, self::SECRET, new MockClock());

        self::assertTrue($gate->allows(self::solve($gate->challenge())));
    }

    public function testOnTheSolutionOfAChallengeSignedElsewhereIsRefused(): void
    {
        $other = new AltchaGate(true, 'another-secret', new MockClock());
        $gate = new AltchaGate(true, self::SECRET, new MockClock());

        self::assertFalse($gate->allows(self::solve($other->challenge())));
    }

    public function testOnAnExpiredChallengeIsRefused(): void
    {
        // Issued an hour ago: its ten minutes are over
        $gate = new AltchaGate(true, self::SECRET, new MockClock('-1 hour'));

        self::assertFalse($gate->allows(self::solve($gate->challenge())));
    }

    public function testTheChallengeStaysLightForAnOldPhone(): void
    {
        $challenge = new AltchaGate(true, self::SECRET, new MockClock())->challenge();

        // One SHA-256 per attempt, at most 20,000 attempts
        self::assertSame('SHA-256', $challenge['parameters']['algorithm']);
        self::assertSame(1, $challenge['parameters']['cost']);
    }

    /**
     * Solves a challenge as the widget does in the browser.
     *
     * @param array<string, mixed> $challenge
     */
    private static function solve(array $challenge): string
    {
        $parsed = Payload::fromArray(['challenge' => $challenge, 'solution' => ['counter' => 0, 'derivedKey' => '']])->challenge;
        \assert($parsed instanceof Challenge);
        $solution = new Altcha()->solveChallenge(new SolveChallengeOptions(algorithm: new Sha(), challenge: $parsed));
        self::assertNotNull($solution);

        return new Payload($parsed, $solution)->toBase64();
    }
}
