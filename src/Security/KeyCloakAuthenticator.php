<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use T3G\Bundle\Keycloak\Service\JWTService;

class KeyCloakAuthenticator extends AbstractGuardAuthenticator
{
    protected SessionInterface $session;

    protected JWTService $JWTService;

    public function __construct(SessionInterface $session, JWTService $JWTService)
    {
        $this->session = $session;
        $this->JWTService = $JWTService;
    }

    /**
     * @param Request $request The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse('/', Response::HTTP_TEMPORARY_REDIRECT);
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('X-Auth-Token')
            && $request->headers->has('X-Auth-Username')
            && $request->headers->has('X-Auth-Userid');
    }

    /**
     * @param Request $request
     * @return Request
     */
    public function getCredentials(Request $request): Request
    {
        return $request;
    }

    /**
     * @param Request $credentials
     * @param UserProviderInterface|KeyCloakUserProvider $userProvider
     * @return KeyCloakUser|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?KeyCloakUser
    {
        $this->session->set('JWT_TOKEN', $credentials->headers->get('X-Auth-Token'));
        $roles = $this->getRolesFromToken($credentials->headers->get('X-Auth-Token'));
        $scopes = $this->getScopesFromToken($credentials->headers->get('X-Auth-Token'));

        return $userProvider->loadUserByUsername(
            $credentials->headers->get('X-Auth-Username'),
            $roles,
            $scopes,
            $this->getEmailFromToken($credentials->headers->get('X-Auth-Token')),
            $this->getFullNameFromToken($credentials->headers->get('X-Auth-Token'))
        );
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey The provider (i.e. firewall) key
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        return null;
    }

    /**
     * @param Request $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        // Gatekeeper takes care of credential validation
        return true;
    }

    /**
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }

    protected function decodeJwtToken(string $token): array
    {
        $this->JWTService->verify($token);

        return json_decode($this->JWTService->getPayload(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function getScopesFromToken(string $token): array
    {
        $roles= [];
        $scopes = explode(' ', $this->decodeJwtToken($token)['scope']);

        foreach ($scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_' . strtoupper(str_replace('.', '_', $scope));
        }

        return $roles;
    }

    protected function getRolesFromToken(string $token): array
    {
        return $this->decodeJwtToken($token)['realm_access']['roles'] ?? [];
    }

    public function getFullNameFromToken(string $token): ?string
    {
        $data = $this->decodeJwtToken($token);

        return $data['name'] ?? null;
    }

    public function getEmailFromToken(string $token): ?string
    {
        $data = $this->decodeJwtToken($token);

        return $data['email'] ?? null;
    }
}
