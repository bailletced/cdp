<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use App\Access\Application\Query\OrganizerViewFinder;
use App\Access\Domain\Exception\LastOrganizerException;
use App\Access\Infrastructure\ApiPlatform\Resource\AdminOrganizerResource;
use App\Access\Infrastructure\ApiPlatform\State\Processor\AdminOrganizerProcessor;
use App\Shared\Application\Command\CommandBusInterface;
use PHPUnit\Framework\TestCase;

/**
 * F04-T02 : le processor laisse remonter LastOrganizerException (refus de
 * supprimer le dernier organisateur) — le mécanisme DomainException la
 * traduit en 400, sans conversion dans la couche Infrastructure.
 */
final class AdminOrganizerProcessorTest extends TestCase
{
    public function testRemovingLastOrganizerThrowsLastOrganizerException(): void
    {
        $commandBus = $this->createStub(CommandBusInterface::class);
        $commandBus->method('dispatch')->willThrowException(new LastOrganizerException());

        $finder = $this->createStub(OrganizerViewFinder::class);

        $processor = new AdminOrganizerProcessor($commandBus, $finder);

        $data = new AdminOrganizerResource(id: 'id-1', email: 'anne@bailletced.org');
        $operation = $this->createStub(Operation::class);

        $this->expectException(LastOrganizerException::class);

        $processor->process($data, $operation);
    }
}
