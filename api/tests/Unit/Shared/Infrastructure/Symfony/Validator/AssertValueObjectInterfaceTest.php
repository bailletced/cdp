<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Validator;

use App\Shared\Infrastructure\Symfony\Validator\AssertValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Test que AssertValueObject utilise l'interface StringConstraintsInterface
 * lorsque disponible, avec fallback sur la réflexion.
 */
final class AssertValueObjectInterfaceTest extends TestCase
{
    public function testItUsesInterfaceWhenAvailable(): void
    {
        // Créer un VO factice qui implémente StringConstraintsInterface
        $constraint = new AssertValueObject(FakeStringValueObject::class);

        $lengthConstraint = null;
        foreach ($constraint->constraints as $c) {
            if ($c instanceof Length) {
                $lengthConstraint = $c;
                break;
            }
        }

        $this->assertInstanceOf(Length::class, $lengthConstraint);
        $this->assertSame(5, $lengthConstraint->min);
        $this->assertSame(100, $lengthConstraint->max);
    }

    public function testItFallsBackToReflectionWhenInterfaceNotAvailable(): void
    {
        // Utiliser Email qui n'implémente pas encore StringConstraintsInterface
        // mais a les constantes MIN_LENGTH/MAX_LENGTH
        $constraint = new AssertValueObject(\App\Shared\Domain\ValueObject\Email::class);

        $lengthConstraint = null;
        foreach ($constraint->constraints as $c) {
            if ($c instanceof Length) {
                $lengthConstraint = $c;
                break;
            }
        }

        $this->assertInstanceOf(Length::class, $lengthConstraint);
        $this->assertSame(3, $lengthConstraint->min);
        $this->assertSame(254, $lengthConstraint->max);
    }
}

// VO factice pour le test
final readonly class FakeStringValueObject implements \App\Shared\Domain\ValueObject\StringConstraintsInterface
{
    public function __construct(public string $value) {}

    public static function getMinLength(): ?int
    {
        return 5;
    }

    public static function getMaxLength(): ?int
    {
        return 100;
    }
}
