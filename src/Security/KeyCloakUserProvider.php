<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KeyCloakUserProvider implements UserProviderInterface
{
    private array $roleMapping;

    private array $defaultRoles;

    public function __construct(array $roleMapping, array $defaultRoles = ['ROLE_USER', 'ROLE_OAUTH_USER'])
    {
        $this->roleMapping = $roleMapping;
        $this->defaultRoles = $defaultRoles;
    }

    /**
     * @param string $username
     * @param array $keycloakGroups
     * @param array $scopes
     * @param string|null $email
     * @param string|null $fullName
     * @return KeyCloakUser
     */
    public function loadUserByUsername(
        $username,
        array $keycloakGroups = [],
        array $scopes = [],
        ?string $email = null,
        ?string $fullName = null
    ): KeyCloakUser {
        $roles = array_intersect_key($this->roleMapping, array_flip(array_map(static function ($v) {
            return str_replace('-', '_', $v);
        }, $keycloakGroups)));
        $roles = array_merge($roles, $scopes, $this->defaultRoles);

        return new KeyCloakUser($username, array_values($roles), $email, $fullName);
    }

    /**
     * @param UserInterface $user
     * @return KeyCloakUser
     */
    public function refreshUser(UserInterface $user): KeyCloakUser
    {
        if (!$user instanceof KeyCloakUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        return new KeyCloakUser($user->getUsername(), $user->getRoles(), $user->getEmail(), $user->getFullName());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class): bool
    {
        return KeyCloakUser::class === $class;
    }
}
