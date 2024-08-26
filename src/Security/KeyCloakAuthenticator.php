<?php
declare(strict_types = 1);

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
use League\OAuth2\Client\Token\AccessToken;
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

class KeyCloakAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    public const SESSION_KEYCLOAK_ACCESS_TOKEN = 'keycloak_access_token';
    private OAuth2ClientInterface $client;
    private SessionInterface $session;
    private RouterInterface $router;
    private UserProviderInterface $userProvider;

    /**
     * @param KeyCloakUserProvider $userProvider
     */
    public function __construct(ClientRegistry $clientRegistry, RequestStack $requestStack, RouterInterface $router, UserProviderInterface $userProvider)
    {
        $this->client = $clientRegistry->getClient('keycloak');
        $this->session = $requestStack->getSession();
        $this->router = $router;
        $this->userProvider = $userProvider;
    }

    public function supports(Request $request): ?bool
    {
        // @TODO: make configurable
        return 'oauth_callback' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->fetchAccessToken($this->client);
        /** @var array{realm_access: ?array{roles: ?string[]}, name?: ?string, preferred_username: string, email?: ?string} $userData */
        $userData = $this->client->fetchUserFromToken($accessToken)?->toArray();
        $this->session->set(self::SESSION_KEYCLOAK_ACCESS_TOKEN, $accessToken);

        return new SelfValidatingPassport(
            new UserBadge($userData['preferred_username'], function() use ($accessToken, $userData) {
                return $this->userProvider->loadUserByIdentifier(
                    $userData['preferred_username'],
                    $userData['realm_access']['roles'] ?? [],
                    $this->getScopesFromToken($accessToken),
                    $userData['email'] ?? null,
                    $userData['name'] ?? null,
                    true
                );
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // @TODO: make configurable
        return new RedirectResponse(
            $this->router->generate('dashboard'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // @TODO: make configurable
        return new RedirectResponse(
            $this->router->generate('login'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // @TODO: make configurable
        return new RedirectResponse(
            $this->router->generate('login'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    private function getScopesFromToken(AccessToken $token): array
    {
        $roles = [];
        $scopes = explode(' ', $token->getValues()['scope'] ?? '');

        foreach ($scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_' . strtoupper(str_replace('.', '_', $scope));
        }

        return $roles;
    }
}
