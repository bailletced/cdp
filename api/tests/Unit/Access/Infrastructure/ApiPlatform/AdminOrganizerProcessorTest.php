<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use App\Access\Application\Query\OrganizerViewFinder;
use App\Access\Domain\Exception\LastOrganizerException;
use App\Access\Infrastructure\ApiPlatform\AdminOrganizer;
use App\Access\Infrastructure\ApiPlatform\AdminOrganizerProcessor;
use App\Shared\Application\Command\CommandBusInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * F04-T02 : le processor mappe LastOrganizerException → 422 Unprocessable Entity
 * (refus de supprimer le dernier organisateur), et non 409.
 */
final class AdminOrganizerProcessorTest extends TestCase
{
    public function testRemovingLastOrganizerThrows422(): void
    {
        $commandBus = $this->createStub(CommandBusInterface::class);
        $commandBus->method('dispatch')->willThrowException(new LastOrganizerException());

        $finder = $this->createStub(OrganizerViewFinder::class);

        $processor = new AdminOrganizerProcessor($commandBus, $finder);

        $data = new AdminOrganizer(id: 'id-1', email: 'anne@bailletced.org');
        $operation = $this->createStub(Operation::class);

        $this->expectException(UnprocessableEntityHttpException::class);

        $processor->process($data, $operation);
    }
}
