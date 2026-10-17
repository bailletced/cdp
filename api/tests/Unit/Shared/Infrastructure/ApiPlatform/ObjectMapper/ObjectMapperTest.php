<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ApiPlatform\ObjectMapper;

use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;
use App\Shared\Infrastructure\ApiPlatform\ObjectMapper\ObjectMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Test pour F00-T15 : ObjectMapper des ressources DTO + MissingModelException.
 */
final class ObjectMapperTest extends TestCase
{
    private ObjectMapper $mapper;

    protected function setUp(): void
    {
        $propertyAccessor = new PropertyAccessor();
        $this->mapper = new ObjectMapper($propertyAccessor);
    }

    public function testItThrowsMissingModelExceptionWhenClassDoesNotExist(): void
    {
        $this->expectException(MissingModelException::class);
        $this->expectExceptionMessage('Le modèle "NonExistentModel" est introuvable.');

        $this->mapper->mapToObject([], 'NonExistentModel');
    }

    public function testItMapsToExistingModel(): void
    {
        $data = ['name' => 'test'];
        $result = $this->mapper->mapToObject($data, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testItMapsFromSource(): void
    {
        $source = new class { public string $name = 'test'; };
        $result = $this->mapper->mapFromSource($source, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testItThrowsMissingModelExceptionFromSourceWhenClassDoesNotExist(): void
    {
        $this->expectException(MissingModelException::class);
        $this->expectExceptionMessage('Le modèle "NonExistentModel" est introuvable.');

        $source = new \stdClass();
        $this->mapper->mapFromSource($source, 'NonExistentModel');
    }
}
