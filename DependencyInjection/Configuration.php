<?php

namespace Module7\EncryptionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Module7\EncryptionBundle\Service\EncryptionService;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('module7_encryption');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('enabled')->defaultValue(false)->end()
                ->scalarNode('access_checker')->defaultValue('module7_encryption.security.access_checker.default')->end()
                ->arrayNode('settings')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('user_classes')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('security_check_routes')
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
                                ->scalarNode('file')->defaultValue('AES-128-CBC')->end()
                                ->scalarNode('private_key')->defaultValue('AES-256-CBC')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
