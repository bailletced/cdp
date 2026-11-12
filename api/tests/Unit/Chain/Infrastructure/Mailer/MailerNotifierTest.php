<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Mailer;

use App\Chain\Application\Dto\InscriptionConfirmation;
use App\Chain\Application\Dto\InscriptionDigest;
use App\Chain\Application\Dto\InscriptionDigestSlot;
use App\Chain\Application\Dto\SlotTextsEmail;
use App\Chain\Infrastructure\Mailer\MailerNotifier;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

final class MailerNotifierTest extends TestCase
{
    public function testTheConfirmationIsATemplatedEmailWithTheAgendaFileAttached(): void
    {
        $mailer = new class implements MailerInterface {
            /** @var list<RawMessage> */
            public array $sent = [];

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                $this->sent[] = $message;
            }
        };
        $slot = new InscriptionDigestSlot('0193', '0194', 1, new \DateTimeImmutable('2026-11-28 19:00:00 UTC'), new \DateTimeImmutable('2026-11-28 19:30:00 UTC'), '20h00–20h30', 'samedi 28 novembre', null, null, null, null);

        new MailerNotifier($mailer, 'no-reply@communion.test', 'Communion de prière')->confirmInscriptions(new InscriptionConfirmation(
            new Email('marie@bailletced.org'),
            'Marie Dupont',
            '24 heures de prière',
            [$slot],
            'https://communion.test/auth/connexion?hash=abc',
            "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n",
        ));

        self::assertCount(1, $mailer->sent);
        $email = $mailer->sent[0];
        self::assertInstanceOf(TemplatedEmail::class, $email);
        self::assertEquals([new Address('no-reply@communion.test', 'Communion de prière')], $email->getFrom());
        self::assertEquals([new Address('marie@bailletced.org')], $email->getTo());
        self::assertSame('Votre inscription : 24 heures de prière', $email->getSubject());
        self::assertSame('emails/inscription_confirmation.html.twig', $email->getHtmlTemplate());
        self::assertSame('emails/inscription_confirmation.txt.twig', $email->getTextTemplate());
        self::assertSame('Marie Dupont', $email->getContext()['displayName']);
        self::assertSame('https://communion.test/auth/connexion?hash=abc', $email->getContext()['link']);
        self::assertSame([$slot], $email->getContext()['slots']);

        $attachments = $email->getAttachments();
        self::assertCount(1, $attachments);
        self::assertSame('mes-creneaux.ics', $attachments[0]->getFilename());
        self::assertSame('text/calendar', $attachments[0]->getContentType());
        self::assertSame("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n", $attachments[0]->getBody());
    }

    public function testTheSlotTextsAreATemplatedEmailWithEverySlotOfThePriant(): void
    {
        $mailer = new class implements MailerInterface {
            /** @var list<RawMessage> */
            public array $sent = [];

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                $this->sent[] = $message;
            }
        };
        $withText = new InscriptionDigestSlot('0193', '0194', 1, new \DateTimeImmutable('2026-11-28 19:00:00 UTC'), new \DateTimeImmutable('2026-11-28 19:30:00 UTC'), '20h00–20h30', 'samedi 28 novembre', 'Que rien ne te trouble', 'Thérèse d’Avila', '<p>Que rien ne te trouble.</p>');
        $withoutText = new InscriptionDigestSlot('0195', '0196', 1, new \DateTimeImmutable('2026-11-28 20:00:00 UTC'), new \DateTimeImmutable('2026-11-28 20:30:00 UTC'), '21h00–21h30', 'samedi 28 novembre', null, null, null);
        $digest = new InscriptionDigest('24 heures de prière', new \DateTimeImmutable('2026-11-29 19:00:00 UTC'), [$withText, $withoutText]);

        new MailerNotifier($mailer, 'no-reply@communion.test', 'Communion de prière')->sendSlotTexts(new SlotTextsEmail(
            new Email('marie@bailletced.org'),
            'Marie Dupont',
            $digest,
        ));

        self::assertCount(1, $mailer->sent);
        $email = $mailer->sent[0];
        self::assertInstanceOf(TemplatedEmail::class, $email);
        self::assertEquals([new Address('no-reply@communion.test', 'Communion de prière')], $email->getFrom());
        self::assertEquals([new Address('marie@bailletced.org')], $email->getTo());
        self::assertSame('Vos textes : 24 heures de prière', $email->getSubject());
        self::assertSame('emails/slot_texts.html.twig', $email->getHtmlTemplate());
        self::assertSame('emails/slot_texts.txt.twig', $email->getTextTemplate());
        self::assertSame('Marie Dupont', $email->getContext()['displayName']);
        self::assertSame('24 heures de prière', $email->getContext()['editionTitle']);
        self::assertSame([$withText, $withoutText], $email->getContext()['slots']);
        self::assertCount(0, $email->getAttachments());
    }
}
