<?php declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\EventSubscriber;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use T3G\Bundle\Keycloak\Security\KeyCloakAuthenticator;

class RequestSubscriber implements EventSubscriberInterface
{
    private OAuth2ClientInterface $client;
    private RouterInterface $router;

    public function __construct(ClientRegistry $clientRegistry, RouterInterface $router)
    {
        $this->client = $clientRegistry->getClient('keycloak');
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['refreshAccessToken', 10],
        ];
    }

    public function refreshAccessToken(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ('logout' === $request->attributes->get('_route')) {
            // Don't try to refresh access token on logout page
            return;
        }

        $session = $request->getSession();
        /** @var ?AccessToken $accessToken */
        $accessToken = $session->get(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN);
        if ($accessToken?->hasExpired()) {
            try {
                $accessToken = $this->client->refreshAccessToken((string)$accessToken->getRefreshToken());
                $session->set(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN, $accessToken);
            } catch (IdentityProviderException $e) {
                if (is_string($e->getResponseBody())) {
                    /** @var array $body */
                    $body = json_decode($e->getResponseBody(), true, 512, JSON_THROW_ON_ERROR);
                } else {
                    $body = $e->getResponseBody();
                }

                if ('invalid_grant' === $body['error']) {
                    // User had a keycloak session, but refreshing the access token failed. Enforce logout.
                    $response = new RedirectResponse(
                        $this->router->generate('logout'),
                        Response::HTTP_TEMPORARY_REDIRECT
                    );
                    $event->setResponse($response);
                    return;
                }

                throw $e;
            }
        }
    }
}
