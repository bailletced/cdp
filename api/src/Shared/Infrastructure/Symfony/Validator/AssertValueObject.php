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

        // The VO must implement StringConstraintsInterface
        if (!is_a($this->class, StringConstraintsInterface::class, true)) {
            throw new InvalidArgumentException(\sprintf('Le Value Object "%s" doit implémenter "%s" pour utiliser AssertValueObject.', $this->class, StringConstraintsInterface::class));
        }

        // The interface guarantees the bounds, so the Length constraint is always added
        $this->constraints[] = new Length(
            min: $this->class::getMinLength(),
            max: $this->class::getMaxLength(),
        );

        // Add the Email constraint for the Email VO
        if (Email::class === $this->class) {
            $this->constraints[] = new EmailConstraint();
        }
    }

    #[\Override]
    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
