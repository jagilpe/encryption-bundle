<?php

namespace Module7\EncryptionBundle\DependencyInjection;

use Module7\EncryptionBundle\Service\EncryptionService;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class Module7EncryptionExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if ($config['enabled']) {
            if (!in_array($config['settings']['default_mode'], EncryptionService::getSupportedEncryptionModes())) {
                throw new InvalidConfigurationException('Wrong encryption mode given');
            }
            if ($config['settings']['per_user_encryption_enabled']) {
                if (empty($config['settings']['user_classes'])) {
                    throw new InvalidConfigurationException('If Per User Encryption is enabled the user classes must be specified.');
                }
            }
            $container->setParameter('module7_encryption.settings', $config['settings']);
            $container->setAlias('module7_encryption.access_checker', $config['access_checker']);
            $loader->load('services.yml');
            if ($config['settings']['per_user_encryption_enabled']) {
                $loader->load('per_user_encryption_services.yml');
            }
        }
    }
}
