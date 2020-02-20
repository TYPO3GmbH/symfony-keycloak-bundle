<?php

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('t3g_keycloak');
//        $treeBuilder->getRootNode()
//            ->children()
//                ->arrayNode('keycloak')
//                    ->children()
//                        ->scalarNode('redirect_route')->example('Set this to the target route to handle the result')->isRequired()->end()
//                        ->arrayNode('oauth')
//                            ->children()
//                                ->scalarNode('client_id')->isRequired()->end()
//                                ->scalarNode('client_secret')->isRequired()->end()
//                            ->end()
//                    ->end()
//                ->end() // twitter
//            ->end()
//        ;

        return $treeBuilder;
    }
}
