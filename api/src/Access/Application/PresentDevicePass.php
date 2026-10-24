<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Shared\Application\Command\CommandInterface;

/**
 * A device presents its pass on a request (F10-T04): the handler records
 * when the device was last seen and returns the inscriptions the pass covers.
 */
final readonly class PresentDevicePass implements CommandInterface
{
    public function __construct(
        #[\SensitiveParameter]
        public string $token,
    ) {
    }
}
