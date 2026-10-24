<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Symfony\Security;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Symfony\Security\SecurityUser;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * F10-T07: the voter is wired into the security layer, so the operations can
 * be protected with `security: "is_granted('INSCRIPTION_ACT', object)"`.
 */
final class InscriptionVoterIntegrationTest extends KernelTestCase
{
    public function testIsGrantedAnswersForTheSignedInPriant(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();
        \assert($em instanceof EntityManagerInterface);
        $em->getConnection()->executeStatement('TRUNCATE organizer, account CASCADE');

        $priantId = new PriantId();
        $account = new Account(new AccountId(), new Email('marie@bailletced.org'), 1, $priantId->value->toRfc4122());
        $em->persist($account);
        $em->flush();

        $container->get('security.token_storage')->setToken(new UsernamePasswordToken(
            new SecurityUser($account->getId()->value, 'marie@bailletced.org', ''),
            'main',
            ['ROLE_PRIANT'],
        ));
        $checker = $container->get('security.authorization_checker');

        self::assertTrue($checker->isGranted('INSCRIPTION_ACT', new Inscription(new EditionId(), new SlotId(), $priantId)));
        self::assertFalse($checker->isGranted('INSCRIPTION_ACT', new Inscription(new EditionId(), new SlotId(), new PriantId())));
    }
}
