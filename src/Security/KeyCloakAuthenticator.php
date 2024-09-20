<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use T3G\Bundle\Keycloak\Service\RedirectService;
use T3G\Bundle\Keycloak\Service\TokenService;

class KeyCloakAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    public const SESSION_KEYCLOAK_ACCESS_TOKEN = 'keycloak_access_token';
    private OAuth2ClientInterface $client;
    private SessionInterface $session;
    private RouterInterface $router;
    private UserProviderInterface $userProvider;
    private TokenService $tokenService;
    private RedirectService $redirectService;
    private ?string $routeAuthentication;
    private ?string $routeSuccess;

    /**
     * @param KeyCloakUserProvider $userProvider
     */
    public function __construct(ClientRegistry $clientRegistry, RequestStack $requestStack, RouterInterface $router, UserProviderInterface $userProvider, TokenService $tokenService, RedirectService $redirectService, ?string $routeAuthentication = null, ?string $routeSuccess = null)
    {
        $this->client = $clientRegistry->getClient('keycloak');
        $this->session = $requestStack->getSession();
        $this->router = $router;
        $this->userProvider = $userProvider;
        $this->tokenService = $tokenService;
        $this->redirectService = $redirectService;
        $this->routeAuthentication = $routeAuthentication;
        $this->routeSuccess = $routeSuccess;
    }

    public function supports(Request $request): ?bool
    {
        return $this->routeAuthentication === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->fetchAccessToken($this->client);
        $this->session->set(self::SESSION_KEYCLOAK_ACCESS_TOKEN, $accessToken);
        $userData = $this->tokenService->fetchUserData();

        return new SelfValidatingPassport(
            new UserBadge($userData['preferred_username'], fn () => $this->userProvider->loadUserByIdentifier(
                $userData['preferred_username'],
                $userData['realm_access']['roles'] ?? [],
                $this->tokenService->getScopes(),
                $userData['email'] ?? null,
                $userData['name'] ?? null,
                true
            ))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if (null === $this->routeSuccess) {
            return null;
        }

        $redirectUrl = $this->getPreviousUrl($request, $firewallName);
        if (null === $redirectUrl || '' === $redirectUrl) {
            $redirectUrl = $this->router->generate($this->routeSuccess);
        }

        return new RedirectResponse(
            $redirectUrl,
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return $this->redirectService->generateLoginRedirectResponse();
    }
}
