<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class AssertValueObject extends Composite
{
    public string $message = '{{ error }}';

    /**
     * @var Constraint[]
     */
    public array $constraints = [];

    /**
     * @param class-string $class
     * @param ?string[]    $groups
     */
    public function __construct(
        public string $class,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;

        $minLength = null;
        $maxLength = null;

        // 1. Priorité à l'interface StringConstraintsInterface
        $interface = \App\Shared\Domain\ValueObject\StringConstraintsInterface::class;
        if (\is_a($this->class, $interface, true)) {
            $minLength = $this->class::getMinLength();
            $maxLength = $this->class::getMaxLength();
        }
        // 2. Fallback : lire les constantes par réflexion
        else {
            $reflection = new \ReflectionClass($this->class);
            $minLength = $reflection->hasConstant('MIN_LENGTH') ? $reflection->getConstant('MIN_LENGTH') : null;
            $maxLength = $reflection->hasConstant('MAX_LENGTH') ? $reflection->getConstant('MAX_LENGTH') : null;
        }

        // Ajouter la contrainte Length si des bornes sont définies
        if (null !== $minLength || null !== $maxLength) {
            $this->constraints[] = new Length(
                min: $minLength,
                max: $maxLength,
            );
        }

        // Ajouter la contrainte Email si c'est le VO Email
        if ($this->class === \App\Shared\Domain\ValueObject\Email::class) {
            $this->constraints[] = new EmailConstraint();
        }
    }

    #[\Override]
    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
