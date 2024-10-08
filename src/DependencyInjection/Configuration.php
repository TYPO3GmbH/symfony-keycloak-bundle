<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use T3G\Bundle\Keycloak\Security\KeyCloakUserProvider;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('t3g_keycloak');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('keycloak')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('user_provider_class')
                            ->defaultValue(KeyCloakUserProvider::class)
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('default_roles')
                            ->defaultValue(['ROLE_USER', 'ROLE_OAUTH_USER'])
                            ->requiresAtLeastOneElement()
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('role_mapping')
                            ->defaultValue([])
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('clientId')
                            ->defaultValue('%env(KEYCLOAK_CLIENT_ID)%')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('routes')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('authentication')
                            ->defaultValue('t3g_keycloak_oauthCallback')
                        ->end()
                        ->scalarNode('success')
                            ->defaultValue('home')
                            ->end()
                        ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
