<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\RateLimiter;

use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Applies the hourly limits of the public forms (F15-T03, §6.7, US-15.2):
 * the inscription per IP and per e-mail, the link request per IP — refused
 * with a 429 and its Retry-After — and the link request per e-mail, which
 * the caller enforces silently, so that the answer never tells whether an
 * address is known.
 *
 * The IP is the real client one behind Traefik (F15-T02). An address is
 * counted hashed: the limiter state, kept in PostgreSQL for an hour, never
 * holds it in clear.
 */
final readonly class PublicFormLimiter
{
    private const string MESSAGE = 'Trop de tentatives, réessayez dans quelques minutes.';

    public function __construct(
        private RequestStack $requestStack,
        private RateLimiterFactoryInterface $inscriptionByIpLimiter,
        private RateLimiterFactoryInterface $inscriptionByEmailLimiter,
        private RateLimiterFactoryInterface $linkRequestByIpLimiter,
        private RateLimiterFactoryInterface $linkRequestByEmailLimiter,
    ) {
    }

    /**
     * Counts an inscription attempt, valid or not; the e-mail counts too
     * when it is a well-formed address.
     *
     * @throws TooManyRequestsHttpException
     */
    public function countInscription(?string $email): void
    {
        self::consumeOrRefuse($this->inscriptionByIpLimiter->create($this->clientIp()));

        $address = self::address($email);
        if (null !== $address) {
            self::consumeOrRefuse($this->inscriptionByEmailLimiter->create(self::key($address)));
        }
    }

    /**
     * @throws TooManyRequestsHttpException
     */
    public function countLinkRequest(): void
    {
        self::consumeOrRefuse($this->linkRequestByIpLimiter->create($this->clientIp()));
    }

    /**
     * Whether a link may still be sent to this address this hour: counts the
     * request, never refuses it.
     */
    public function allowsLinkTo(Email $email): bool
    {
        return $this->linkRequestByEmailLimiter->create(self::key($email))->consume()->isAccepted();
    }

    private function clientIp(): string
    {
        return $this->requestStack->getMainRequest()?->getClientIp() ?? 'unknown';
    }

    private static function consumeOrRefuse(LimiterInterface $limiter): void
    {
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException(max(1, $limit->getRetryAfter()->getTimestamp() - time()), self::MESSAGE);
        }
    }

    private static function address(?string $email): ?Email
    {
        if (null === $email) {
            return null;
        }

        try {
            return new Email($email);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private static function key(Email $email): string
    {
        return hash('sha256', $email->value);
    }
}
