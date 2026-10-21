<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\AdminEditionView;
use App\Chain\Domain\ValueObject\EditionId;

interface AdminEditionViewFinder
{
    /**
     * @return AdminEditionView[]
     */
    public function findAll(): array;

    public function findOne(EditionId $id): ?AdminEditionView;
}
