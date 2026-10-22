<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Access\Domain\Event\AccountRegistered;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Shared\Application\Event\AsEventListener;
use App\Shared\Application\Message\LinkMessage;
use App\Shared\Application\Port\PersonalLinkIssuer;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sends the personal login link when an account is registered (F07-T01).
 * Runs after the command transaction commits (transactional outbox), so the
 * account is guaranteed to be persisted when the link is issued.
 */
#[AsEventListener]
final readonly class SendPersonalLinkOnAccountRegistered
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PersonalLinkIssuer $personalLinkIssuer,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(AccountRegistered $event): void
    {
        $account = $this->accountRepository->findByEmail($event->email);

        if (null === $account) {
            return;
        }

        $link = $this->personalLinkIssuer->issue($account->getId()->value->toRfc4122());

        $this->messageBus->dispatch(new LinkMessage(
            $account->getEmail(),
            $link->url,
            'Votre lien personnel',
        ));
    }
}
