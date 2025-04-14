<?php declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use T3G\Bundle\Keycloak\Service\RedirectService;

class LoginController extends AbstractController
{
    private RedirectService $redirectService;
    private string $logoutRoute;

    public function __construct(RedirectService $redirectService, string $logoutRoute)
    {
        $this->redirectService = $redirectService;
        $this->logoutRoute = $logoutRoute;
    }

    public function login(): RedirectResponse
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute($this->getParameter('t3g_keycloak.routes.success'));
        }

        return $this->redirectService->generateLoginRedirectResponse();
    }

    public function oauthCallback(): RedirectResponse
    {
        // fallback in case the authenticator does not redirect
        return $this->redirectToRoute($this->getParameter('t3g_keycloak.routes.success'));
    }

    public function oauthLogout(): RedirectResponse
    {
        return $this->redirectService->generateLogoutRedirectResponse($this->logoutRoute);
    }
}
