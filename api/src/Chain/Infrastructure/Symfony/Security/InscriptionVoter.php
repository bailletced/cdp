<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Security;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Policy\VisibilityPolicy;
use App\Shared\Application\Port\CurrentViewer;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * INSCRIPTION_ACT (F10-T07): may the viewer act on this inscription on behalf
 * of its priant? Delegates to the VisibilityPolicy; used by the operations as
 * `security: "is_granted('INSCRIPTION_ACT', object)"`.
 *
 * @extends Voter<string, Inscription>
 */
final class InscriptionVoter extends Voter
{
    public const string ACT = 'INSCRIPTION_ACT';

    public function __construct(
        private readonly CurrentViewer $currentViewer,
        private readonly VisibilityPolicy $visibilityPolicy,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACT === $attribute && $subject instanceof Inscription;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $this->visibilityPolicy->canActOn($this->currentViewer->current(), $subject);
    }
}
