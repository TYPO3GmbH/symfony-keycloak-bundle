<?php declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Service;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use T3G\Bundle\Keycloak\Security\KeyCloakAuthenticator;

class TokenService
{
    private OAuth2ClientInterface $client;
    private SessionInterface $session;

    public function __construct(ClientRegistry $clientRegistry, RequestStack $requestStack)
    {
        $this->client = $clientRegistry->getClient('keycloak');
        $this->session = $requestStack->getSession();
    }

    /**
     * @return array{realm_access: ?array{roles: ?string[]}, name?: ?string, preferred_username: string, email?: ?string}
     */
    public function fetchUserData(): array
    {
        $accessToken = $this->getAccessTokenFromSession();

        if (null !== $accessToken) {
            return $this->client->fetchUserFromToken($accessToken)?->toArray();
        }

        return [];
    }

    public function getScopes(): array
    {
        $roles = [];
        $accessToken = $this->getAccessTokenFromSession();

        if (null !== $accessToken) {
            return $roles;
        }

        $scopes = explode(' ', $accessToken->getValues()['scope'] ?? '');

        foreach ($scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_' . strtoupper(str_replace('.', '_', $scope));
        }

        return $roles;
    }

    public function getAccessTokenFromSession(): ?AccessToken
    {
        if ($this->session->has(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN)) {
            return $this->session->get(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN);
        }

        return null;
    }
}
