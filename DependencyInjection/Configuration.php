<?php

namespace NBO\Bundle\AjaxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('nbo_ajax');

        $rootNode->children()
                        ->arrayNode('pre_config')
                            ->requiresAtLeastOneElement()
                            ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('parameters')
                                            ->requiresAtLeastOneElement()
                                            ->useAttributeAsKey("name")
                                                ->prototype('array')
                                                    ->children()
                                                        ->integerNode('type')->isRequired()->defaultValue(1)->validate()->ifNotInArray(array(0,1,2,3,4,5))->thenInvalid('Invalid parameter type "%s"')->end()->end()
                                                        ->booleanNode('require')->defaultTrue()->end()
                                                        ->booleanNode('empty')->defaultFalse()->end()
                                                        ->integerNode('min')->defaultValue(null)->end()
                                                        ->integerNode('max')->defaultValue(null)->end()
                                                        ->scalarNode('regex')->defaultValue(null)->end()
                                                        ->scalarNode('datetimeFormat')->defaultValue(null)->end()
                                                        ->arrayNode('defaultValue')->prototype('scalar')->end()->end()
                                                        ->arrayNode('disabledValue')->prototype('scalar')->end()->end()
                                                        ->arrayNode('restrictedValue')->prototype('scalar')->end()->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->arrayNode('files')
                                            ->useAttributeAsKey("name")
                                                ->prototype('array')
                                                    ->children()
                                                        ->booleanNode('require')->defaultTrue()->end()
                                                        ->booleanNode('empty')->defaultFalse()->end()
                                                        ->arrayNode('mimeType')->prototype('scalar')->end()->end()
                                                        ->integerNode('maxSize')->min(0)->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end();

        return $treeBuilder;
    }

}
