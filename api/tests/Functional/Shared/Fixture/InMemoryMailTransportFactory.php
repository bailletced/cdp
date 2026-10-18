<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Fixture;

use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Mailer transport factory for the "in-memory://" DSN: always returns the
 * shared InMemoryMailTransport, so the tests and the mailer use the same
 * instance (F02-T14).
 */
final class InMemoryMailTransportFactory implements TransportFactoryInterface
{
    public function __construct(
        private InMemoryMailTransport $transport,
    ) {
    }

    public function create(Dsn $dsn): TransportInterface
    {
        return $this->transport;
    }

    public function supports(Dsn $dsn): bool
    {
        return str_starts_with($dsn->getScheme(), 'in-memory');
    }
}
