<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Validator;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Symfony\Validator\AssertValueObject;
use App\Shared\Infrastructure\Symfony\Validator\AssertValueObjectValidator;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<AssertValueObjectValidator>
 */
final class AssertValueObjectValidatorTest extends ConstraintValidatorTestCase
{
    public function testAValueTheValueObjectRefusesReportsItsMessage(): void
    {
        $this->validator->validate('pas-un-email', new AssertValueObject(Email::class));

        $this->buildViolation('{{ error }}')
            ->setParameter('{{ error }}', "L'adresse e-mail n'est pas valide.")
            ->setInvalidValue('pas-un-email')
            ->assertRaised();
    }

    public function testNullIsLeftToTheTypeWhenThePropertyIsNullable(): void
    {
        $payload = new class {
            public ?string $email = null;
        };
        $this->setObject($payload);
        $this->setProperty($payload, 'email');

        $this->validator->validate(null, new AssertValueObject(Email::class));

        $this->assertNoViolation();
    }

    public function testNullIsReportedWhenThePropertyIsRequired(): void
    {
        $payload = new class {
            public string $email = '';
        };
        $this->setObject($payload);
        $this->setProperty($payload, 'email');

        $this->validator->validate(null, new AssertValueObject(Email::class));

        $this->buildViolation('Cette valeur ne doit pas être nulle.')->setCode(NotNull::IS_NULL_ERROR)->assertRaised();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new AssertValueObjectValidator();
    }
}
