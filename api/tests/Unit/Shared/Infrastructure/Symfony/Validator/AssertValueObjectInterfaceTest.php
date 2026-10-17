<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Validator;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\StringConstraintsInterface;
use App\Shared\Infrastructure\Symfony\Validator\AssertValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Test que AssertValueObject utilise l'interface StringConstraintsInterface.
 * Le fallback par réflexion a été supprimé : le VO DOIT implémenter l'interface.
 */
final class AssertValueObjectInterfaceTest extends TestCase
{
    public function testItRequiresStringConstraintsInterface(): void
    {
        // Un VO qui n'implémente pas StringConstraintsInterface doit lever une exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/doit implémenter.*StringConstraintsInterface/');

        new AssertValueObject(FakeNonConstrainedValueObject::class);
    }

    public function testItUsesInterfaceConstraints(): void
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

    public function testItWorksWithEmailValueObject(): void
    {
        // Email implémente StringConstraintsInterface
        $constraint = new AssertValueObject(Email::class);

        $lengthConstraint = null;
        $emailConstraint = null;

        foreach ($constraint->constraints as $c) {
            if ($c instanceof Length) {
                $lengthConstraint = $c;
            }
            if ($c instanceof EmailConstraint) {
                $emailConstraint = $c;
            }
        }

        $this->assertInstanceOf(Length::class, $lengthConstraint);
        $this->assertSame(3, $lengthConstraint->min);
        $this->assertSame(254, $lengthConstraint->max);
        
        $this->assertInstanceOf(EmailConstraint::class, $emailConstraint);
    }
}

// VO factice qui implémente l'interface
final readonly class FakeStringValueObject implements StringConstraintsInterface
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

// VO factice qui N'IMPLÉMENTE PAS l'interface (pour le test d'échec)
final readonly class FakeNonConstrainedValueObject
{
    public function __construct(public string $value) {}
}
