<?php

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class T3GKeycloakExtension extends Extension
{
    public function getAlias()
    {
        return 't3g_keycloak';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');

        $container->prependExtensionConfig('knpu_oauth2_client', [
            'clients' => [
                'keycloak' => [
                    'type' => 'keycloak',
                    'auth_server_url' => '%t3g_keycloak.keycloak.auth_server_url%',
                    'realm' => 'TYPO3',
                    'client_id' => '%t3g_keycloak.keycloak.oauth.client_id%',
                    'client_secret' => '%t3g_keycloak.keycloak.oauth.client_secret%',
                    'redirect_route' => '%t3g_keycloak.keycloak.keycloak.redirect_route%',
                ],
            ]
        ]);

        $container->prependExtensionConfig('httplug', [
            'plugins' => [
                'cache' => [
                    'cache_pool' => 'cache.app',
                    'config' => [
                        'default_ttl' => 1800
                    ]
                ],
                'retry' => [
                    'retry' => 1
                ]
            ],
            'discovery' => [
                'client' => 'auto'
            ],
            'clients' => [
                'login_typo3_com' => [
                    'factory' => 'httplug.factory.curl',
                    'plugins' => ['httplug.plugin.cache']
                ]
            ],
            'classes' => [
                'message_factory' => 'Nyholm\Psr7\Factory\Psr17Factory',
                'stream_factory' => 'Nyholm\Psr7\Factory\Psr17Factory',
            ]
        ]);

        $container->prependExtensionConfig('jose', [
            'key_sets' => [
                'login_typo3_com' => [
                    'jku' => [
                        'url' => '%t3g_keycloak.keycloak.jku_url%',
                        'is_public' => true
                    ]
                ]
            ],
            'jws' => [
                'verifiers' => [
                    'login_typo3_com' => [
                        'signature_algorithms' => ['RS256'],
                        'is_public' => true
                    ]
                ]
            ],
            'jku_factory' => [
                'enabled' => true,
                'client' => 'httplug.client.login_typo3_com',
                'request_factory' => 'httplug.message_factory'
            ]
        ]);
    }
}
