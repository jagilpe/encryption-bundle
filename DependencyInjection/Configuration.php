<?php

namespace EHEncryptionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use EHEncryptionBundle\Service\EncryptionService;

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
                ->scalarNode('access_checker')->defaultValue('eh_encryption.security.access_checker.default')->end()
                ->arrayNode('metadata')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('auto_detection')->defaultTrue()->end()
                        ->arrayNode('directories')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('path')->isRequired()->end()
                                    ->scalarNode('namespace_prefix')->defaultValue('')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('settings')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('user_classes')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('mode')->defaultValue(EncryptionService::MODE_PER_USER_SHAREABLE)->end()
                        ->scalarNode('encrypt_on_backend')->defaultValue(true)->end()
                        ->scalarNode('decrypt_on_backend')->defaultValue(true)->end()
                        ->scalarNode('digest_method')->defaultValue('SHA256')->end()
                        ->scalarNode('symmetric_key_length')->defaultValue('16')->end()
                        ->arrayNode('private_key')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('digest_method')->defaultValue('SHA512')->end()
                                ->scalarNode('bits')->defaultValue('1024')->end()
                                ->scalarNode('type')->defaultValue(OPENSSL_KEYTYPE_RSA)->end()
                            ->end()
                        ->end()
                        ->arrayNode('cipher_method')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('property')->defaultValue('AES-128-CBC')->end()
                                ->scalarNode('file')->defaultValue('AES-128-CFC')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
