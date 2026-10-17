<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ApiPlatform\ObjectMapper;

use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;
use ReflectionClass;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * ObjectMapper pour les ressources API Platform.
 * 
 * Mappe les données (array ou object) vers un objet cible,
 * avec gestion des erreurs MissingModelException.
 */
final class ObjectMapper
{
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor = new PropertyAccess(),
    ) {
    }

    /**
     * Mappe un tableau de données vers un nouvel objet.
     * 
     * @template T of object
     * @param class-string<T> $targetClass
     * @return T
     * @throws MissingModelException
     */
    public function mapToObject(array $data, string $targetClass): object
    {
        if (!class_exists($targetClass)) {
            throw new MissingModelException($targetClass);
        }

        $target = new $targetClass();
        
        foreach ($data as $property => $value) {
            if ($this->propertyAccessor->isWritable($target, $property)) {
                $this->propertyAccessor->setValue($target, $property, $value);
            }
        }

        return $target;
    }

    /**
     * Mappe un objet source vers un nouvel objet cible.
     * 
     * @template T of object
     * @param class-string<T> $targetClass
     * @return T
     * @throws MissingModelException
     */
    public function mapFromSource(object $source, string $targetClass): object
    {
        if (!class_exists($targetClass)) {
            throw new MissingModelException($targetClass);
        }

        $target = new $targetClass();
        
        $sourceReflection = new ReflectionClass($source);
        foreach ($sourceReflection->getProperties() as $sourceProperty) {
            $propertyName = $sourceProperty->getName();
            if ($this->propertyAccessor->isReadable($source, $propertyName) && 
                $this->propertyAccessor->isWritable($target, $propertyName)) {
                $value = $this->propertyAccessor->getValue($source, $propertyName);
                $this->propertyAccessor->setValue($target, $propertyName, $value);
            }
        }

        return $target;
    }
}
