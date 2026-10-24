<?php

declare(strict_types=1);

namespace App\Access\Domain\Repository;

use App\Access\Domain\Entity\DevicePass;

interface DevicePassRepositoryInterface
{
    /**
     * Add a pass to the repository. The aggregate is persisted when the
     * surrounding transaction flushes; the repository never flushes itself.
     */
    public function add(DevicePass $pass): void;

    /**
     * The pass whose token hash matches the (clear) cookie token, if any.
     */
    public function findByToken(#[\SensitiveParameter] string $token): ?DevicePass;
}
