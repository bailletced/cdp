<?php

declare(strict_types=1);

namespace App\Shared\Application\Message\Handler;

use App\Shared\Application\Message\LinkMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

/**
 * Handler for LinkMessage: sends the personal link email, from the Communion
 * identity (F02-T14).
 */
#[AsMessageHandler]
final class LinkMessageHandler
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire('%communion_email%')]
        private string $senderEmail,
        #[Autowire('%communion_name%')]
        private string $senderName,
    ) {
    }

    public function __invoke(LinkMessage $message): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($message->email->value)
            ->subject($message->subject)
            ->htmlTemplate('emails/personal_link.html.twig')
            ->textTemplate('emails/personal_link.txt.twig')
            ->context(['link' => $message->link])
        ;

        $this->mailer->send($email);
    }
}
