<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
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
    public function getAlias(): string
    {
        return 't3g_keycloak';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('t3g_keycloak.keycloak.user_provider_class', $config['keycloak']['user_provider_class']);
        $container->setParameter('t3g_keycloak.keycloak.default_roles', $config['keycloak']['default_roles']);
        $container->setParameter('t3g_keycloak.keycloak.role_mapping', $config['keycloak']['role_mapping']);
        $container->setParameter('t3g_keycloak.keycloak.clientId', $config['keycloak']['clientId']);
        $container->setParameter('t3g_keycloak.routes.authentication', $config['routes']['authentication']);
        $container->setParameter('t3g_keycloak.routes.success', $config['routes']['success']);
        $container->setParameter('t3g_keycloak.routes.logout_route', $config['routes']['logout_route']);

        if ($container->hasExtension($this->getAlias())) {
            $container->prependExtensionConfig($this->getAlias(), ['keycloak' => [], 'routes' => []]);
        }

        if ($container->hasExtension('knpu_oauth2_client')) {
            $container->prependExtensionConfig(
                'knpu_oauth2_client',
                [
                    'clients' => [
                        'keycloak' => [
                            'redirect_route' => '%t3g_keycloak.routes.authentication%',
                        ],
                    ],
                ]
            );
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
    }
}
