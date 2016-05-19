<?php

namespace EHEncryptionBundle\DependencyInjection;

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
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('eh_encryption');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('enabled')->defaultValue(false)->end()
                ->arrayNode('settings')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('encrypt_on_backend')->defaultValue(true)->end()
                        ->scalarNode('decrypt_on_backend')->defaultValue(true)->end()
                        ->scalarNode('digest_method')->defaultValue('SHA256')->end()
                        ->scalarNode('key_length')->defaultValue('16')->end()
                        ->arrayNode('cipher_method')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('property')->defaultValue('AES-256-CBC')->end()
                                ->scalarNode('file')->defaultValue('AES-256-CFC')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
