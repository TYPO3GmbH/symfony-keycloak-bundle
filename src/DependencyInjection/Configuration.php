<?php

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use T3G\Bundle\Keycloak\Security\KeyCloakUserProvider;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('t3g_keycloak');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('keycloak')
                    ->children()
                        ->scalarNode('redirect_route')->defaultValue('home')->example('Set this to the target route to handle the result')->isRequired()->end()
                        ->scalarNode('auth_server_url')->defaultValue('https://login.typo3.com/auth')->isRequired()->end()
                        ->scalarNode('jku_url')->defaultValue('https://login.typo3.com/auth/realms/TYPO3/protocol/openid-connect/certs')->isRequired()->end()
                        ->scalarNode('user_provider_class')->defaultValue(KeyCloakUserProvider::class)->isRequired()->end()
                        ->arrayNode('default_roles')
                        ->scalarPrototype()->end()
                        ->defaultValue(['ROLE_USER'])
                        ->end()
                        ->arrayNode('role_mapping')
                        ->scalarPrototype()->end()
                        ->defaultValue(['typo3.gmbh.member' => 'ROLE_ADMIN'])
                        ->end()
                        ->arrayNode('oauth')
                            ->children()
                                ->scalarNode('client_id')->isRequired()->end()
                                ->scalarNode('client_secret')->isRequired()->end()
                            ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}