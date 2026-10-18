<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Application\Message\LinkMessage;
use App\Shared\Application\Port\PersonalLinkIssuer;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handles RequestPersonalLink: issues a personal link for a known account and
 * dispatches it as a LinkMessage on the async_transactional transport, inside
 * the command transaction (transactional outbox). Unknown accounts are
 * ignored on purpose: the caller must not learn whether an account exists.
 */
#[AsCommandHandler]
final readonly class RequestPersonalLinkHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PersonalLinkIssuer $personalLinkIssuer,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(RequestPersonalLink $command): void
    {
        $account = $this->accountRepository->findByEmail($command->email);

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
