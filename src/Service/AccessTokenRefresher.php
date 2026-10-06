<?php declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Service;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use T3G\Bundle\Keycloak\Security\KeyCloakAuthenticator;

/**
 * Provides the access token of the session, refreshed in time.
 *
 * Refreshing only once the token has expired is too late: a token that is about to expire is still accepted when the
 * request starts, but has expired by the time it is sent to another service, which then rejects it. Therefore the
 * token is refreshed as soon as it expires within the leeway.
 */
final class AccessTokenRefresher
{
    public const DEFAULT_LEEWAY = 15;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RequestStack $requestStack,
        private readonly int $leeway = self::DEFAULT_LEEWAY,
    ) {
    }

    /**
     * Returns null if there is no access token, or if Keycloak rejects refreshing it. In the latter case, the session is
     * invalidated, as the Keycloak session has ended.
     */
    public function getAccessToken(): ?AccessToken
    {
        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }

        $accessToken = $session->get(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN);
        if (!$accessToken instanceof AccessToken) {
            return null;
        }

        if (!$this->expiresSoon($accessToken)) {
            return $accessToken;
        }

        $client = $this->clientRegistry->getClient('keycloak');
        if (!$client instanceof OAuth2Client) {
            throw new \LogicException('The "keycloak" client must be able to refresh access tokens', 1791363001);
        }

        try {
            $accessToken = $client->refreshAccessToken((string)$accessToken->getRefreshToken());
        } catch (IdentityProviderException $e) {
            if ('invalid_grant' === ($this->decodeResponseBody($e)['error'] ?? null)) {
                // User had a Keycloak session, but refreshing the access token failed. Enforce logout in Symfony.
                $session->invalidate();

                return null;
            }

            throw $e;
        }

        $session->set(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN, $accessToken);

        return $accessToken;
    }

    private function expiresSoon(AccessToken $accessToken): bool
    {
        $expires = $accessToken->getExpires();

        return null !== $expires && $expires - $this->leeway <= time();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponseBody(IdentityProviderException $exception): array
    {
        $body = $exception->getResponseBody();
        if (is_string($body)) {
            $body = json_decode($body, true) ?? [];
        }

        return is_array($body) ? $body : [];
    }
}
