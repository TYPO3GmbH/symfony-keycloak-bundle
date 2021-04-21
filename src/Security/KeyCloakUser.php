<?php

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class KeyCloakUser implements UserInterface
{
    private string $username;
    private array $roles;
    private ?string $fullName = null;
    private ?string $email = null;
    private bool $fresh = false;

    public function __construct(string $username, array $roles, ?string $email = null, ?string $fullName = null, bool $fresh = false)
    {
        $this->username = $username;
        $this->roles = $roles;
        $this->email = $email;
        $this->fullName = $fullName;
        $this->fresh = $fresh;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return '';
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function eraseCredentials(): void
    {
        // Do nothing.
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string The full name of the user. When not present, the username
     */
    public function getDisplayName(): string
    {
        return $this->fullName ?? $this->username;
    }

    public function isFresh(): bool
    {
        return $this->fresh;
    }

    public function setFresh(bool $fresh): self
    {
        $this->fresh = $fresh;

        return $this;
    }
}
