<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

/**
 * Mappe en embeddable, sans préfixe de colonne, toute propriété d'une entité typée par un objet-valeur.
 */
#[AsDecorator('doctrine.orm.default_metadata_driver')]
final readonly class EmbedValueObjectsDriver implements MappingDriver
{
    public function __construct(
        #[AutowireDecorated]
        private MappingDriver $decorated,
    ) {
    }

    #[\Override]
    public function loadMetadataForClass(string $className, ClassMetadata $metadata): void
    {
        $this->decorated->loadMetadataForClass($className, $metadata);

        if (!$metadata instanceof OrmClassMetadata || $metadata->isMappedSuperclass) {
            return;
        }

        foreach ($metadata->getReflectionClass()->getProperties() as $property) {
            $name = $property->getName();

            if ($property->isStatic() || $property->isVirtual()) {
                continue;
            }

            if (isset($metadata->fieldMappings[$name])
                || isset($metadata->associationMappings[$name])
                || isset($metadata->embeddedClasses[$name])
            ) {
                continue;
            }

            $type = $property->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            /** @var class-string $class */
            $class = $type->getName();
            if (!$this->isEmbeddable($class)) {
                continue;
            }

            $metadata->mapEmbedded([
                'fieldName' => $name,
                'class' => $class,
                'columnPrefix' => false,
            ]);
        }
    }

    /**
     * @return list<class-string>
     */
    #[\Override]
    public function getAllClassNames(): array
    {
        return $this->decorated->getAllClassNames();
    }

    #[\Override]
    public function isTransient(string $className): bool
    {
        return $this->decorated->isTransient($className);
    }

    /**
     * @param class-string $class
     */
    private function isEmbeddable(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        return [] !== new ReflectionClass($class)->getAttributes(Embeddable::class);
    }
}
