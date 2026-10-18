<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Security user implementation that loads Account by UUID.
 * Roles are recalculated on each refresh based on the current state of the account.
 */
class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    private Uuid $id;
    private string $email;
    private string $password;
    /**
     * @var string[]
     */
    private array $roles = [];
    private bool $isOrganizer = false;
    private int $credentialsVersion = 1;

    public function __construct(
        Uuid $id,
        string $email,
        string $password,
        bool $isOrganizer = false,
        int $credentialsVersion = 1,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->isOrganizer = $isOrganizer;
        $this->credentialsVersion = $credentialsVersion;
        $this->roles = $this->buildRoles();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->id->toRfc4122();
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        return $this->id->equals($user->id);
    }

    public function isOrganizer(): bool
    {
        return $this->isOrganizer;
    }

    /**
     * Get the user's UUID.
     */
    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * Get credentials version for login_link signature.
     */
    public function getCredentialsVersion(): int
    {
        return $this->credentialsVersion;
    }

    /**
     * Build roles based on organizer status.
     * Always includes ROLE_PRIANT, and adds ROLE_ORGANIZER if applicable.
     *
     * @return string[]
     */
    private function buildRoles(): array
    {
        $roles = ['ROLE_PRIANT'];

        if ($this->isOrganizer) {
            $roles[] = 'ROLE_ORGANIZER';
        }

        return $roles;
    }

    /**
     * Update organizer status and refresh roles.
     */
    public function updateIsOrganizer(bool $isOrganizer): void
    {
        if ($this->isOrganizer === $isOrganizer) {
            return;
        }

        $this->isOrganizer = $isOrganizer;
        $this->roles = $this->buildRoles();
    }

    /**
     * Update credentials version (e.g., after password change).
     */
    public function updateCredentialsVersion(int $version): void
    {
        $this->credentialsVersion = $version;
    }
}
