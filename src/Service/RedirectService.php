<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Service;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RedirectService
{
    public const DEFAULT_SCOPES = ['openid', 'profile', 'roles', 'email'];
    private ClientRegistry $clientRegistry;
    private RouterInterface $router;
    private OpenIdService $openIdService;
    private string $clientId;

    public function __construct(ClientRegistry $clientRegistry, RouterInterface $router, OpenIdService $openIdService, string $clientId)
    {
        $this->clientRegistry = $clientRegistry;
        $this->clientId = $clientId;
        $this->openIdService = $openIdService;
        $this->router = $router;
    }

    /**
     * @param string[] $scopes
     */
    public function generateLoginRedirectResponse(array $scopes = self::DEFAULT_SCOPES): RedirectResponse
    {
        /** @var OAuth2Client $client */
        $client = $this->clientRegistry->getClient('keycloak');

        return $client->redirect($scopes);
    }

    public function generateLogoutRedirectResponse($logoutRoute): RedirectResponse
    {
        $redirectAfterOAuthLogout = rtrim($this->router->generate($logoutRoute, [], UrlGeneratorInterface::ABSOLUTE_URL), '/');
        /** @var Keycloak $provider */
        $provider = $this->clientRegistry->getClient('keycloak')->getOAuth2Provider();
        $openIdConfiguration = $this->openIdService->getOpenIdConfiguration(sprintf('%s/realms/%s', $provider->authServerUrl, $provider->realm));
        $redirectTarget = sprintf(
            '%s?client_id=%s&post_logout_redirect_uri=%s',
            $openIdConfiguration['end_session_endpoint'],
            $this->clientId,
            urlencode($redirectAfterOAuthLogout)
        );

        return new RedirectResponse($redirectTarget, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
