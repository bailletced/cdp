<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Security;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Security\LoginLinkPersonalLinkIssuer;
use App\Access\Infrastructure\Security\UserProvider;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Clock\MockClock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkDetails;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * F02-T11: PersonalLinkIssuer port + login_link adapter.
 * Link lifetime policy (arbitrage Q8):
 * - during an edition (up to its end + 7 days): the link expires at edition end + 7 days
 * - otherwise (between editions): 7 days
 * - organizer accounts: always 7 days.
 */
final class LoginLinkPersonalLinkIssuerTest extends TestCase
{
    private const NOW = 1763000000; // Fixed point in time (UTC)

    private const SEVEN_DAYS_IN_SECONDS = 7 * 24 * 60 * 60;

    private const LINK_URL = 'https://example.com/auth/login?user=abc&expires=123&hash=def';

    private MockClock $clock;

    private AccountRepositoryInterface $accountRepository;

    private OrganizerRepositoryInterface $organizerRepository;

    private LoginLinkPersonalLinkIssuer $issuer;

    private ?int $capturedLifetime = null;

    protected function setUp(): void
    {
        $this->clock = new MockClock(self::NOW);
        $this->accountRepository = $this->createStub(AccountRepositoryInterface::class);
        $this->organizerRepository = $this->createStub(OrganizerRepositoryInterface::class);

        $loginLinkHandler = $this->createStub(LoginLinkHandlerInterface::class);
        $loginLinkHandler
            ->method('createLoginLink')
            ->willReturnCallback(function (UserInterface $user, $request, ?int $lifetime): LoginLinkDetails {
                $this->capturedLifetime = $lifetime;

                return new LoginLinkDetails(
                    self::LINK_URL,
                    $this->clock->now()->modify(\sprintf('+%d seconds', (int) $lifetime))
                );
            });

        $this->issuer = new LoginLinkPersonalLinkIssuer(
            new UserProvider($this->accountRepository, $this->organizerRepository),
            $loginLinkHandler,
            $this->clock,
        );
    }

    public function testLinkExpiresAtEditionEndPlusSevenDaysDuringEdition(): void
    {
        $accountId = $this->givenAccountExists(false);
        $editionEndsAt = $this->clock->now()->modify('+2 days');

        $link = $this->issuer->issue($accountId, $editionEndsAt);

        // The edition ends in 2 days: the link lives until edition end + 7 days (9 days from now)
        $this->assertSame(9 * 24 * 60 * 60, $this->capturedLifetime);
        $this->assertSame(self::LINK_URL, $link->url);
        $this->assertSame(
            $editionEndsAt->modify('+7 days')->getTimestamp(),
            $link->expiresAt->getTimestamp()
        );
    }

    public function testLinkExpiresInSevenDaysOutsideEdition(): void
    {
        $accountId = $this->givenAccountExists(false);

        $link = $this->issuer->issue($accountId);

        $this->assertSame(self::SEVEN_DAYS_IN_SECONDS, $this->capturedLifetime);
        $this->assertSame(
            $this->clock->now()->modify('+7 days')->getTimestamp(),
            $link->expiresAt->getTimestamp()
        );
    }

    public function testOrganizerLinkAlwaysExpiresInSevenDays(): void
    {
        $accountId = $this->givenAccountExists(true);
        $editionEndsAt = $this->clock->now()->modify('+2 days');

        $link = $this->issuer->issue($accountId, $editionEndsAt);

        $this->assertSame(self::SEVEN_DAYS_IN_SECONDS, $this->capturedLifetime);
        $this->assertSame(
            $this->clock->now()->modify('+7 days')->getTimestamp(),
            $link->expiresAt->getTimestamp()
        );
    }

    public function testLinkExpiresInSevenDaysWhenEditionEndedMoreThanSevenDaysAgo(): void
    {
        $accountId = $this->givenAccountExists(false);
        $editionEndsAt = $this->clock->now()->modify('-8 days');

        $this->issuer->issue($accountId, $editionEndsAt);

        $this->assertSame(self::SEVEN_DAYS_IN_SECONDS, $this->capturedLifetime);
    }

    private function givenAccountExists(bool $isOrganizer): string
    {
        $uuid = Uuid::v7();
        $account = new Account(new AccountId($uuid), new Email('priant@bailletced.org'));

        $this->accountRepository
            ->method('findById')
            ->willReturn($account);
        $this->organizerRepository
            ->method('isOrganizer')
            ->willReturn($isOrganizer);

        return $uuid->toRfc4122();
    }
}
