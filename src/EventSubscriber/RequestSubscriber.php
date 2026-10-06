<?php declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use T3G\Bundle\Keycloak\Service\AccessTokenRefresher;

class RequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AccessTokenRefresher $accessTokenRefresher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['refreshAccessToken', 10],
        ];
    }

    public function refreshAccessToken(RequestEvent $event): void
    {
        if ('logout' === $event->getRequest()->attributes->get('_route')) {
            // Don't try to refresh access token on logout page
            return;
        }

        $this->accessTokenRefresher->getAccessToken();
    }
}
