<?php

namespace Ricky\TagCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tag_cache');
        $rootNode
            ->children()
                ->scalarNode('driver')->defaultValue('File')->end()
                ->scalarNode('namespace')->defaultValue('%kernel.cache_dir%')->end()
                ->arrayNode('options')->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enable_largeobject')->defaultValue(false)->end()
                        ->booleanNode('hashkey')->defaultValue(false)->end()
                        ->scalarNode('cache_dir')->defaultValue('%kernel.cache_dir%'.DIRECTORY_SEPARATOR.'Ricky.TagCacheBundle')->end()
                        ->arrayNode('servers')
                            ->useAttributeAsKey('servers')->prototype('scalar')->end()
                        ->end()
                    ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
