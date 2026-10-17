<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Domain;

use App\Access\Domain\Organizer;
use App\Access\Domain\ValueObject\OrganizerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrganizerTest extends TestCase
{
    public function testCreateOrganizer(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();
        $addedAt = new \DateTimeImmutable('2026-10-17 10:00:00');

        $organizer = new Organizer($accountId, $addedBy, $addedAt);

        $this->assertSame($accountId, $organizer->getAccountId());
        $this->assertSame($addedBy, $organizer->getAddedBy());
        $this->assertEquals($addedAt, $organizer->getAddedAt());
    }

    public function testCreateOrganizerWithDefaultAddedAt(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();

        $before = new \DateTimeImmutable();
        $organizer = new Organizer($accountId, $addedBy);
        $after = new \DateTimeImmutable();

        $addedAt = $organizer->getAddedAt();
        
        $this->assertSame($accountId, $organizer->getAccountId());
        $this->assertSame($addedBy, $organizer->getAddedBy());
        $this->assertGreaterThanOrEqual($before, $addedAt);
        $this->assertLessThanOrEqual($after, $addedAt);
    }

    public function testGetId(): void
    {
        $accountId = Uuid::v7();
        $addedBy = Uuid::v7();

        $organizer = new Organizer($accountId, $addedBy);

        $this->assertInstanceOf(OrganizerId::class, $organizer->getId());
    }
}
