<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Validator;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Symfony\Validator\AssertValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Test pour F00-T14 : Validateur AssertValueObject publiant MIN_LENGTH/MAX_LENGTH dans le JSON Schema.
 */
final class AssertValueObjectTest extends TestCase
{
    public function testItIsACompositeConstraint(): void
    {
        $constraint = new AssertValueObject(Email::class);

        $this->assertInstanceOf(
            \Symfony\Component\Validator\Constraints\Composite::class,
            $constraint,
            'AssertValueObject doit étendre Composite pour inclure des contraintes imbriquées'
        );
    }

    public function testCompositeConstraintsIncludeLengthForEmail(): void
    {
        $constraint = new AssertValueObject(Email::class);

        // Vérifier que les contraintes composées incluent Length avec les bonnes bornes
        $this->assertCount(2, $constraint->constraints, 'Doit avoir 2 contraintes (Length et Email)');

        // Trouver la contrainte Length
        $lengthConstraint = null;
        $emailConstraint = null;
        
        foreach ($constraint->constraints as $c) {
            if ($c instanceof Length) {
                $lengthConstraint = $c;
            }
            if ($c instanceof \Symfony\Component\Validator\Constraints\Email) {
                $emailConstraint = $c;
            }
        }

        $this->assertInstanceOf(Length::class, $lengthConstraint, 'Doit inclure une contrainte Length');
        $this->assertSame(Email::MIN_LENGTH, $lengthConstraint->min);
        $this->assertSame(Email::MAX_LENGTH, $lengthConstraint->max);
        
        $this->assertInstanceOf(\Symfony\Component\Validator\Constraints\Email::class, $emailConstraint);
    }
}
