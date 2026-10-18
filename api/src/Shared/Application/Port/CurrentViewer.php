<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

use App\Shared\Domain\Security\Viewer;

/**
 * Provides the viewer of the current request (F02-T12).
 */
interface CurrentViewer
{
    /**
     * The viewer for the current request; a visitor Viewer when unauthenticated.
     */
    public function current(): Viewer;
}
