<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

/**
 * Port for checking database and schema health (F03-T06).
 */
interface DatabaseHealth
{
    public function isUp(): bool;

    public function schemaStatus(): string;
}
