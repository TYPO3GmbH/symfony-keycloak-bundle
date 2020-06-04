<?php

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    private string $logoutUrl;
    private UrlGeneratorInterface $router;
    private string $returnRoute;

    public function __construct(UrlGeneratorInterface $router, string $returnRoute, string $logoutUrl)
    {
        $this->logoutUrl = $logoutUrl;
        $this->router = $router;
        $this->returnRoute = $returnRoute;
    }

    /**
     * Creates a Response object to send upon a successful logout.
     *
     * @param Request $request
     *
     * @return RedirectResponse never null
     */
    public function onLogoutSuccess(Request $request): RedirectResponse
    {
        /*
         * This is a gatekeeper route
         * @see https://github.com/keycloak/keycloak-documentation/blob/master/securing_apps/topics/oidc/keycloak-gatekeeper.adoc#logout-endpoint
         */
        return new RedirectResponse(
            '/oauth/logout?redirect='
                . urlencode(rtrim($this->logoutUrl, '/')
                . '?redirect_uri='
                . urlencode($this->router->generate($this->returnRoute, [], UrlGeneratorInterface::ABSOLUTE_URL)))
        );
    }
}
