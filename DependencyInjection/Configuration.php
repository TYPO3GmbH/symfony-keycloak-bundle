<?php

namespace T3G\Bundle\Keycload\DependencyInjection;

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