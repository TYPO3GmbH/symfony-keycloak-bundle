<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class T3GKeycloakExtension extends Extension implements PrependExtensionInterface
{
    public function getAlias()
    {
        return 't3g_keycloak';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('t3g_keycloak.keycloak.jku_url', $config['keycloak']['jku_url']);
        $container->setParameter('t3g_keycloak.keycloak.logout_url', $config['keycloak']['logout_url']);
        $container->setParameter('t3g_keycloak.keycloak.user_provider_class', $config['keycloak']['user_provider_class']);
        $container->setParameter('t3g_keycloak.keycloak.default_roles', $config['keycloak']['default_roles']);
        $container->setParameter('t3g_keycloak.keycloak.role_mapping', $config['keycloak']['role_mapping']);
        $container->setParameter('t3g_keycloak.keycloak.logout_redirect_route', $config['keycloak']['logout_redirect_route']);

        if ($container->hasExtension($this->getAlias())) {
            $container->prependExtensionConfig($this->getAlias(), ['keycloak' => []]);
        }

        if ($container->hasExtension('httplug')) {
            $container->prependExtensionConfig(
                'httplug',
                [
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
                        'app' => [
                            'http_methods_client' => true,
                            'plugins' => ['httplug.plugin.content_length', 'httplug.plugin.redirect']
                        ],
                        'login_typo3_com' => [
                            'factory' => 'httplug.factory.curl',
                            'plugins' => ['httplug.plugin.cache']
                        ]
                    ],
                    'classes' => [
                        'message_factory' => 'Nyholm\Psr7\Factory\Psr17Factory',
                        'stream_factory' => 'Nyholm\Psr7\Factory\Psr17Factory',
                    ]
                ]
            );
        }

        if ($container->hasExtension('jose')) {
            $container->prependExtensionConfig(
                'jose',
                [
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
                                'signature_algorithms' => ['HS256', 'RS256'],
                                'is_public' => true
                            ]
                        ]
                    ],
                    'jku_factory' => [
                        'enabled' => true,
                        'client' => 'httplug.client.login_typo3_com',
                        'request_factory' => 'httplug.message_factory'
                    ]
                ]
            );
        }
    }
}
