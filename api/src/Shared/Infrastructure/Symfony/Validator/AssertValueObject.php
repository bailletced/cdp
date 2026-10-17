<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Validator;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\StringConstraintsInterface;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\Length;

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

        // Le VO DOIT implémenter StringConstraintsInterface
        if (!\is_a($this->class, StringConstraintsInterface::class, true)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Le Value Object "%s" doit implémenter "%s" pour utiliser AssertValueObject.',
                    $this->class,
                    StringConstraintsInterface::class
                )
            );
        }

        $minLength = $this->class::getMinLength();
        $maxLength = $this->class::getMaxLength();

        // Ajouter la contrainte Length si des bornes sont définies
        if (null !== $minLength || null !== $maxLength) {
            $this->constraints[] = new Length(
                min: $minLength,
                max: $maxLength,
            );
        }

        // Ajouter la contrainte Email si c'est le VO Email
        if ($this->class === Email::class) {
            $this->constraints[] = new EmailConstraint();
        }
    }

    #[\Override]
    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
