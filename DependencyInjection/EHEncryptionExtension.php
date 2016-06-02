<?php

namespace EHEncryptionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EHEncryptionExtension extends Extension
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
            $container->setParameter('eh_encryption.settings', $config['settings']);
            $container->setAlias('eh_encryption.access_checker', $config['access_checker']);
            $loader->load('services.yml');
        }

        $bundles = $container->getParameter('kernel.bundles');

        // Load the metadata directories
        $directories = array();
        if ($config['metadata']['auto_detection']) {
            foreach ($bundles as $name => $class) {
                $ref = new \ReflectionClass($class);

                $directories[$ref->getNamespaceName()] = dirname($ref->getFileName()).'/Resources/config/eh_encryption';
            }
        }
        foreach ($config['metadata']['directories'] as $directory) {
            $directory['path'] = rtrim(str_replace('\\', '/', $directory['path']), '/');

            if ('@' === $directory['path'][0]) {
                $bundleName = substr($directory['path'], 1, strpos($directory['path'], '/') - 1);

                if (!isset($bundles[$bundleName])) {
                    throw new RuntimeException(sprintf('The bundle "%s" has not been registered with AppKernel. Available bundles: %s', $bundleName, implode(', ', array_keys($bundles))));
                }

                $ref = new \ReflectionClass($bundles[$bundleName]);
                $directory['path'] = dirname($ref->getFileName()).substr($directory['path'], strlen('@'.$bundleName));
            }

            $directories[rtrim($directory['namespace_prefix'], '\\')] = rtrim($directory['path'], '\\/');
        }

        $container
            ->getDefinition('eh_encryption.metadata.file_locator')
            ->replaceArgument(0, $directories)
        ;
    }
}
