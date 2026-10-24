<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Shared\Application\Command\CommandInterface;

/**
 * Grants the device pass for inscriptions made from a device (F10-T02).
 *
 * The presented token is the one of the cdp_pass cookie, if any; the fresh
 * token is used when a new pass has to be created. The handler returns the
 * token the device keeps.
 */
final readonly class GrantDevicePass implements CommandInterface
{
    /**
     * @param list<string> $inscriptionIds
     */
    public function __construct(
        #[\SensitiveParameter]
        public ?string $presentedToken,
        #[\SensitiveParameter]
        public string $freshToken,
        public array $inscriptionIds,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
