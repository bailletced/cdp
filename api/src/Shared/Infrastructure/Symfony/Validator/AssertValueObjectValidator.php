<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidator;

final class AssertValueObjectValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        \assert($constraint instanceof AssertValueObject);

        if (null === $value) {
            if (!$this->propertyAllowsNull()) {
                $this->context->buildViolation('Cette valeur ne doit pas être nulle.')
                    ->setCode(NotNull::IS_NULL_ERROR)
                    ->addViolation();
            }

            return;
        }

        try {
            new ($constraint->class)($value);
        } catch (\InvalidArgumentException $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ error }}', $e->getMessage())
                ->setInvalidValue($value)
                ->addViolation();
        }
    }

    private function propertyAllowsNull(): bool
    {
        $object = $this->context->getObject();
        $property = $this->context->getPropertyName();

        if (!\is_object($object) || null === $property || !\property_exists($object, $property)) {
            return true;
        }

        $type = new \ReflectionProperty($object, $property)->getType();

        return !$type instanceof \ReflectionType || $type->allowsNull();
    }
}
