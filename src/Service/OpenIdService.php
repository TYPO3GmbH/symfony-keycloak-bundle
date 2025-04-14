<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Service;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class OpenIdService
{
    public function getOpenIdConfiguration(string $realmUrl)
    {
        $cacheChain = new ChainAdapter([
            new ArrayAdapter(),
            new FilesystemAdapter(),
        ]);

        return $cacheChain->get('keycloak-openid-' . hash('xxh3', $realmUrl), function (ItemInterface $item) use ($realmUrl) {
            $item->expiresAfter(3600);

            $client = new Client();
            $response = $client->request('GET', $realmUrl . '/.well-known/openid-configuration');

            return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        });
    }
}
