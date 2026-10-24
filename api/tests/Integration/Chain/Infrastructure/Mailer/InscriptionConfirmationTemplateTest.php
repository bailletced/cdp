<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Mailer;

use App\Chain\Application\Dto\InscriptionConfirmation;
use App\Chain\Application\Dto\InscriptionDigestSlot;
use App\Chain\Infrastructure\Mailer\MailerNotifier;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * The confirmation email rendered for real (F11-T04, US-11.1), frozen in
 * snapshots: delete a snapshot to regenerate it, then review the diff.
 */
final class InscriptionConfirmationTemplateTest extends KernelTestCase
{
    private const string SNAPSHOTS = __DIR__.'/__snapshots__/';

    public function testTheHtmlVersion(): void
    {
        $email = $this->render();

        self::assertMatchesSnapshot('inscription_confirmation.html', (string) $email->getHtmlBody());
        self::assertStringContainsString('(heure de Paris)', (string) $email->getHtmlBody());
    }

    public function testTheTextVersion(): void
    {
        $email = $this->render();

        self::assertMatchesSnapshot('inscription_confirmation.txt', (string) $email->getTextBody());
    }

    private function render(): TemplatedEmail
    {
        self::bootKernel();
        $mailer = new class implements MailerInterface {
            public ?RawMessage $sent = null;

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                $this->sent = $message;
            }
        };
        $utc = new \DateTimeZone('UTC');

        new MailerNotifier($mailer, 'no-reply@communion.test', 'Communion de prière')->confirmInscriptions(new InscriptionConfirmation(
            new Email('marie@bailletced.org'),
            'Marie Dupont',
            '24 heures de prière',
            [
                new InscriptionDigestSlot('0193a1b2-0000-7000-8000-000000000001', '0193a1b2-0000-7000-8000-0000000000a1', 1, new \DateTimeImmutable('2026-11-28 22:30', $utc), new \DateTimeImmutable('2026-11-28 23:00', $utc), '23h30–0h00', 'samedi 28 novembre', 'Que rien ne te trouble', 'Thérèse d’Avila', '<p>Que rien ne te trouble,<br>que rien ne t’effraie.</p><blockquote><p>Dieu seul suffit.</p></blockquote>', "Relire lentement.\nS’arrêter sur un mot."),
                new InscriptionDigestSlot('0193a1b2-0000-7000-8000-000000000002', '0193a1b2-0000-7000-8000-0000000000a2', 1, new \DateTimeImmutable('2026-11-28 23:00', $utc), new \DateTimeImmutable('2026-11-28 23:30', $utc), '0h00–0h30', 'dimanche 29 novembre', null, null, null, null),
            ],
            'https://communion.test/auth/connexion?user=0193&expires=1&hash=abc',
            "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n",
        ));

        $email = $mailer->sent;
        \assert($email instanceof TemplatedEmail);
        $renderer = self::getContainer()->get('twig.mime_body_renderer');
        \assert($renderer instanceof BodyRendererInterface);
        $renderer->render($email);

        return $email;
    }

    private static function assertMatchesSnapshot(string $name, string $actual): void
    {
        $file = self::SNAPSHOTS.$name;
        if (!is_file($file)) {
            file_put_contents($file, $actual);
            self::markTestIncomplete(\sprintf('Snapshot %s written: review it and run the test again.', $name));
        }

        self::assertSame(file_get_contents($file), $actual, \sprintf('The rendering differs from the snapshot %s.', $name));
    }
}
