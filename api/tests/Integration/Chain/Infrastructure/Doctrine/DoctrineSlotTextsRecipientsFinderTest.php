<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Query\SlotTextsRecipientsFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Infrastructure\Doctrine\DoctrineSlotTextsRecipientsFinder;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineSlotTextsRecipientsFinderTest extends KernelTestCase
{
    private SlotTextsRecipientsFinder $finder;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->finder = new DoctrineSlotTextsRecipientsFinder($this->em->getConnection());

        foreach (['inscription', 'priant', 'slot', 'edition', 'text'] as $table) {
            $this->em->getConnection()->executeStatement(\sprintf('DELETE FROM %s', $table));
        }
    }

    public function testItListsOneDistinctRecipientPerPriantWithAnEmail(): void
    {
        $edition = $this->createEdition();
        $marie = $this->createPriant('marie@bailletced.org', 'Marie Dupont');
        $withoutEmail = new Priant(null, new DisplayName('Famille Sans E-mail'));
        $this->em->persist($withoutEmail);
        foreach ([
            new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $marie->getId()),
            new Inscription($edition->getId(), $edition->getSlots()[1]->getId(), $marie->getId()),
            new Inscription($edition->getId(), $edition->getSlots()[2]->getId(), $withoutEmail->getId()),
        ] as $inscription) {
            $this->em->persist($inscription);
        }
        $this->em->flush();

        $recipients = $this->finder->findByEdition($edition->getId());

        self::assertCount(1, $recipients);
        self::assertEquals($marie->getId(), $recipients[0]->priantId);
        self::assertSame('Marie Dupont', $recipients[0]->displayName);
        self::assertSame('marie@bailletced.org', $recipients[0]->email->value);
    }

    public function testItSkipsUnsubscribedAndErasedPriants(): void
    {
        $edition = $this->createEdition();
        $marie = $this->createPriant('marie@bailletced.org', 'Marie Dupont');
        $jean = $this->createPriant('jean@bailletced.org', 'Jean Martin');
        $pierre = $this->createPriant('pierre@bailletced.org', 'Pierre Durand');
        foreach ([$marie, $jean, $pierre] as $priant) {
            $this->em->persist(new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $priant->getId()));
        }
        $this->em->flush();
        $connection = $this->em->getConnection();
        $connection->executeStatement('UPDATE priant SET unsubscribed_at = NOW() WHERE id = :id', ['id' => $jean->getId()->value->toRfc4122()]);
        $connection->executeStatement('UPDATE priant SET erased_at = NOW() WHERE id = :id', ['id' => $pierre->getId()->value->toRfc4122()]);

        $recipients = $this->finder->findByEdition($edition->getId());

        self::assertCount(1, $recipients);
        self::assertSame('marie@bailletced.org', $recipients[0]->email->value);
    }

    public function testAnEditionWithoutWritablePriantHasNoRecipient(): void
    {
        $edition = $this->createEdition();

        self::assertSame([], $this->finder->findByEdition($edition->getId()));
    }

    public function testTheRecipientsAreReadInASingleQuery(): void
    {
        $edition = $this->createEdition();
        $marie = $this->createPriant('marie@bailletced.org', 'Marie Dupont');
        $this->em->persist(new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $marie->getId()));
        $this->em->flush();
        $debugData = self::getContainer()->get('doctrine.debug_data_holder');
        \assert($debugData instanceof DebugDataHolder);
        $debugData->reset();

        $this->finder->findByEdition($edition->getId());

        self::assertCount(1, $debugData->getData()['default'] ?? []);
    }

    private function createEdition(): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->em->persist($edition);
        $this->em->flush();

        return $edition;
    }

    private function createPriant(string $email, string $displayName): Priant
    {
        $priant = new Priant(new Email($email), new DisplayName($displayName));
        $this->em->persist($priant);
        $this->em->flush();

        return $priant;
    }
}
