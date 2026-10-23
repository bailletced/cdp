<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Port\Fixture;

use App\Shared\Application\Port\PriantDirectory;
use App\Shared\Domain\ValueObject\Email;

/**
 * Fake priant directory: records every link request.
 */
final class FakePriantDirectory implements PriantDirectory
{
    /**
     * @var list<array{email: Email, priantId: string}>
     */
    public array $linked = [];

    #[\Override]
    public function linkAccountToPriant(Email $email, string $priantId): void
    {
        $this->linked[] = ['email' => $email, 'priantId' => $priantId];
    }
}
