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
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RedirectService
{
    private ClientRegistry $clientRegistry;
    private RouterInterface $router;
    private string $clientId;

    public function __construct(ClientRegistry $clientRegistry, RouterInterface $router, string $clientId)
    {
        $this->clientRegistry = $clientRegistry;
        $this->router = $router;
        $this->clientId = $clientId;
    }

    /**
     * @param string[] $scopes
     */
    public function generateLoginRedirectResponse(array $scopes): RedirectResponse
    {
        /** @var OAuth2Client $client */
        $client = $this->clientRegistry->getClient('keycloak');

        return $client->redirect($scopes);
    }

    public function generateLogoutRedirectResponse(): RedirectResponse
    {
        $redirectAfterOAuthLogout = rtrim($this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');
        /** @var Keycloak $provider */
        $provider = $this->clientRegistry->getClient('keycloak')->getOAuth2Provider();
        $redirectTarget = sprintf(
            '%s/realms/%s/protocol/openid-connect/logout?client_id=%s&post_logout_redirect_uri=%s',
            $provider->authServerUrl,
            $provider->realm,
            $this->clientId,
            urlencode($redirectAfterOAuthLogout)
        );

        return new RedirectResponse($redirectTarget, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
