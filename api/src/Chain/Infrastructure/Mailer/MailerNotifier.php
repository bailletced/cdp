<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Mailer;

use App\Chain\Application\Dto\InscriptionConfirmation;
use App\Chain\Application\Port\Notifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Notifier backed by Symfony Mailer (F11-T01, T30): Twig templates in
 * templates/emails, sent from the Communion identity.
 *
 * It is called by the listeners of the domain events, which the worker
 * consumes from the async_transactional transport: the email never leaves
 * during the HTTP request, and a failed send is retried with the event.
 */
final readonly class MailerNotifier implements Notifier
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire('%communion_email%')]
        private string $senderEmail,
        #[Autowire('%communion_name%')]
        private string $senderName,
    ) {
    }

    #[\Override]
    public function confirmInscriptions(InscriptionConfirmation $confirmation): void
    {
        $email = new TemplatedEmail()
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($confirmation->recipient->value)
            ->subject(\sprintf('Votre inscription : %s', $confirmation->editionTitle))
            ->htmlTemplate('emails/inscription_confirmation.html.twig')
            ->textTemplate('emails/inscription_confirmation.txt.twig')
            ->context([
                'displayName' => $confirmation->displayName,
                'editionTitle' => $confirmation->editionTitle,
                'slots' => $confirmation->slots,
                'link' => $confirmation->personalLink,
            ])
            ->attach($confirmation->calendar, 'mes-creneaux.ics', 'text/calendar')
        ;

        $this->mailer->send($email);
    }
}
