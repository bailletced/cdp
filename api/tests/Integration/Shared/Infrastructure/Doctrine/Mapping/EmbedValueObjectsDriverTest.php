<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Doctrine\Mapping;

use App\Shared\Domain\ValueObject\Email;
use App\Tests\Integration\Fixture\ProbeAggregate;
use App\Tests\Integration\Fixture\ProbeAggregateId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EmbedValueObjectsDriverTest extends KernelTestCase
{
    public function testAnAggregateWithValueObjectsRoundTripsThroughPostgreSql(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = [$entityManager->getClassMetadata(ProbeAggregate::class)];
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $id = new ProbeAggregateId();
        $entityManager->persist(new ProbeAggregate($id, new Email('Marie@bailletced.org')));
        $entityManager->flush();
        $entityManager->clear();

        $found = $entityManager->find(ProbeAggregate::class, $id->value);

        self::assertSame('marie@bailletced.org', $found?->email->value);
        self::assertTrue($found->id->equals($id));
    }

    public function testValueObjectPropertiesAreMappedAsEmbeddablesWithoutPrefix(): void
    {
        $driver = self::getContainer()->get(EntityManagerInterface::class)->getConfiguration()->getMetadataDriverImpl();
        $metadata = new ClassMetadata(ProbeAggregate::class);
        $metadata->initializeReflection(new RuntimeReflectionService());

        $driver->loadMetadataForClass(ProbeAggregate::class, $metadata);

        foreach (['id' => ProbeAggregateId::class, 'email' => Email::class] as $field => $class) {
            self::assertArrayHasKey($field, $metadata->embeddedClasses);
            self::assertSame($class, $metadata->embeddedClasses[$field]->class);
            self::assertFalse($metadata->embeddedClasses[$field]->columnPrefix);
        }
    }
}
