<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Query\InscriptionDigestFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\Doctrine\DoctrineInscriptionDigestFinder;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineInscriptionDigestFinderTest extends KernelTestCase
{
    private InscriptionDigestFinder $finder;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->finder = new DoctrineInscriptionDigestFinder($this->em->getConnection());

        foreach (['inscription', 'priant', 'slot', 'edition', 'text'] as $table) {
            $this->em->getConnection()->executeStatement(\sprintf('DELETE FROM %s', $table));
        }
    }

    public function testThePriantsSlotsComeWithTheirFullTextsInChronologicalOrder(): void
    {
        $edition = $this->createEdition('2026-11-28 19:00:00');
        $text = Text::create(new TextTitle('Que rien ne te trouble'), new TextSource('Thérèse d’Avila'), new RichText('<p>Que rien ne te trouble.</p>'), 'Relire lentement.');
        $this->em->persist($text);
        $edition->assignText($edition->getSlots()[5]->getId(), $text->getId());
        $marie = $this->createPriant('marie@bailletced.org');
        $jean = $this->createPriant('jean@bailletced.org');
        $later = new Inscription($edition->getId(), $edition->getSlots()[5]->getId(), $marie->getId());
        $earlier = new Inscription($edition->getId(), $edition->getSlots()[2]->getId(), $marie->getId());
        foreach ([$later, $earlier, new Inscription($edition->getId(), $edition->getSlots()[3]->getId(), $jean->getId())] as $inscription) {
            $this->em->persist($inscription);
        }
        $this->em->flush();

        $digest = $this->finder->findByPriantAndEdition($marie->getId(), $edition->getId());

        self::assertNotNull($digest);
        self::assertSame('24 heures de prière', $digest->editionTitle);
        self::assertEquals(new \DateTimeImmutable('2026-11-29 19:00:00', new \DateTimeZone('UTC')), $digest->editionEndsAt);
        self::assertCount(2, $digest->slots);
        [$first, $second] = $digest->slots;

        // 19:00 UTC is 20h00 in Paris: slot n° 3 starts at 21h00
        self::assertSame((string) $earlier->getId(), $first->inscriptionId);
        self::assertSame(1, $first->inscriptionVersion);
        self::assertSame('21h00–21h30', $first->label);
        self::assertSame('samedi 28 novembre', $first->dayLabel);
        self::assertEquals(new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('UTC')), $first->startsAt);
        self::assertEquals(new \DateTimeImmutable('2026-11-28 20:30:00', new \DateTimeZone('UTC')), $first->endsAt);
        // A slot without text yet
        self::assertNull($first->textTitle);
        self::assertNull($first->textBody);

        self::assertSame('22h30–23h00', $second->label);
        self::assertSame('Que rien ne te trouble', $second->textTitle);
        self::assertSame('Thérèse d’Avila', $second->textSource);
        self::assertSame('<p>Que rien ne te trouble.</p>', $second->textBody);
        self::assertSame('Relire lentement.', $second->textHints);
    }

    public function testAPriantWithoutInscriptionInTheEditionHasNoDigest(): void
    {
        $edition = $this->createEdition('2026-11-28 19:00:00');

        self::assertNull($this->finder->findByPriantAndEdition($this->createPriant('marie@bailletced.org')->getId(), $edition->getId()));
    }

    public function testTheDigestIsReadInASingleQuery(): void
    {
        $edition = $this->createEdition('2026-11-28 19:00:00');
        $marie = $this->createPriant('marie@bailletced.org');
        $this->em->persist(new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $marie->getId()));
        $this->em->flush();
        $debugData = self::getContainer()->get('doctrine.debug_data_holder');
        \assert($debugData instanceof DebugDataHolder);
        $debugData->reset();

        $this->finder->findByPriantAndEdition($marie->getId(), $edition->getId());

        self::assertCount(1, $debugData->getData()['default'] ?? []);
    }

    private function createEdition(string $startsAtUtc): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable($startsAtUtc, new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->em->persist($edition);
        $this->em->flush();

        return $edition;
    }

    private function createPriant(string $email): Priant
    {
        $priant = new Priant(new Email($email), new DisplayName('Marie Dupont'));
        $this->em->persist($priant);
        $this->em->flush();

        return $priant;
    }
}
