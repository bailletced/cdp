<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject\Fixture;

use App\Shared\Domain\ValueObject\Identifier;
use App\Shared\Domain\ValueObject\Identity;

final class SampleId implements Identifier
{
    use Identity;
}
