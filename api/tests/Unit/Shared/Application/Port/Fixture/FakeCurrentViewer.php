<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Port\Fixture;

use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Domain\Security\Viewer;

/**
 * Fake current viewer: returns a canned Viewer.
 */
final class FakeCurrentViewer implements CurrentViewer
{
    public function __construct(
        private readonly Viewer $viewer,
    ) {
    }

    #[\Override]
    public function current(): Viewer
    {
        return $this->viewer;
    }
}
