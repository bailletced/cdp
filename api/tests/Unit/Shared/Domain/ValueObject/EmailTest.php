<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testItRejectsAnAddressWithoutValidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'adresse e-mail n'est pas valide.");

        new Email('pas-un-email');
    }

    public function testItRejectsAnAddressLongerThanTheMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'adresse e-mail doit comporter entre 3 et 254 caractères.");

        new Email(str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('b', 63).'.'.str_repeat('b', 62));
    }

    public function testABlankAddressIsRejectedAsTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'adresse e-mail doit comporter entre 3 et 254 caractères.");

        new Email('   ');
    }

    public function testItTrimsAndLowercasesTheAddress(): void
    {
        self::assertSame('marie@example.com', new Email('  Marie@Example.COM ')->value);
    }
}
