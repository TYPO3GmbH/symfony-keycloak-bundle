<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Security;

use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
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
     * @return OAuthUser
     */
    public function loadUserByUsername($username, array $keycloakGroups = []): OAuthUser
    {
        $roles = array_intersect_key($this->roleMapping, array_flip($keycloakGroups));
        $roles = array_merge($roles, $this->defaultRoles);

        return new OAuthUser($username, array_values($roles));
    }

    /**
     * @param UserInterface $user
     * @return OAuthUser
     */
    public function refreshUser(UserInterface $user): OAuthUser
    {
        if (!$user instanceof OAuthUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        return new OAuthUser($user->getUsername(), $user->getRoles());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class): bool
    {
        return OAuthUser::class === $class;
    }
}
