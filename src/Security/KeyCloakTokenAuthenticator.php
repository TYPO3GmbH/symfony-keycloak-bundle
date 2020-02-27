<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\JsonResponse;
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

class KeyCloakTokenAuthenticator extends AbstractGuardAuthenticator
{
    private ClientRegistry $clientRegistry;

    private JWTService $JWTService;
    
    private string $redirectRoute;
    
    private SessionInterface $session;

    public function __construct(
        ClientRegistry $clientRegistry,
        JWTService $JWTService,
        string $redirectRoute,
        SessionInterface $session
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->JWTService = $JWTService;
        $this->redirectRoute = $redirectRoute;
        $this->session = $session;
    }

    /**
     * @param Request $request The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization');
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * @return mixed Any non-null value
     */
    public function getCredentials(Request $request)
    {
        $tokenString = $request->headers->get('Authorization');
        $tokenString = str_replace('Bearer ', '', $tokenString);
        return $tokenString;
    }

    /**
     * @param mixed $credentials
     *
     * @param UserProviderInterface $userProvider
     * @return OAuthUser|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials) {
            // The token header was empty, authentication fails with 401
            return;
        }

        $token = new AccessToken([
            'access_token' => $credentials
        ]);

        try {
            // @TODO: token expire must be checkend!
            // @TODO: scopes and resource_access->account->roles must be mapped
            if ($this->JWTService->verify($token)) {
                $payload = json_decode($this->JWTService->getPayload(), false, 512, JSON_THROW_ON_ERROR);
                $this->session->set('JWT_PAYLOAD', $payload);
                return $userProvider->loadUserByUsername($payload->preferred_username, $payload->realm_access->roles);
            }
        } catch (IdentityProviderException $e) {
            throw new AuthenticationException($e->getMessage());
        }

        return null;
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
}
