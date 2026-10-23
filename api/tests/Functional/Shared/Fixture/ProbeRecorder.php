<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Fixture;

final class ProbeRecorder
{
    /** @var list<bool> */
    public array $transactionWasActive = [];

    /** @var list<bool> */
    public array $insideCommandTransaction = [];
}
