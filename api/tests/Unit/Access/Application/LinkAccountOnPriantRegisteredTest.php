<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Application;

use App\Access\Application\LinkAccountOnPriantRegistered;
use App\Shared\Domain\Event\Integration\PriantRegistered;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\Shared\Application\Port\Fixture\FakePriantDirectory;
use PHPUnit\Framework\TestCase;

final class LinkAccountOnPriantRegisteredTest extends TestCase
{
    public function testItLinksTheAccountToThePriant(): void
    {
        $priantDirectory = new FakePriantDirectory();
        $listener = new LinkAccountOnPriantRegistered($priantDirectory);

        $listener(new PriantRegistered(
            '018f3a2e-7c1d-7a2b-9c4d-5e6f7a8b9c0d',
            new Email('famille.martin@bailletced.org'),
        ));

        self::assertCount(1, $priantDirectory->linked);
        self::assertEquals(new Email('famille.martin@bailletced.org'), $priantDirectory->linked[0]['email']);
        self::assertSame('018f3a2e-7c1d-7a2b-9c4d-5e6f7a8b9c0d', $priantDirectory->linked[0]['priantId']);
    }

    public function testItDoesNothingWhenThePriantHasNoEmail(): void
    {
        $priantDirectory = new FakePriantDirectory();
        $listener = new LinkAccountOnPriantRegistered($priantDirectory);

        $listener(new PriantRegistered('018f3a2e-7c1d-7a2b-9c4d-5e6f7a8b9c0d', null));

        self::assertSame([], $priantDirectory->linked);
    }
}
