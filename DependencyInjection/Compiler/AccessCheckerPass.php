<?php

namespace Jagilpe\EncryptionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Loads the services tagged as Access Checkers
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 */
class AccessCheckerPass implements CompilerPassInterface
{
    /**
     *
     * {@inheritDoc}
     * @see \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface::process()
     */
    public function process(ContainerBuilder $container)
    {
        // Check if the list factory is defined
        if (!$container->has('jagilpe_encryption.security.access_checker.chained')) {
            return;
        }

        $definition = $container->findDefinition('jagilpe_encryption.security.access_checker.chained');

        $taggedServices = $container->findTaggedServiceIds('jagilpe_encryption.access_checker');

        if (!empty($taggedServices)) {
            foreach ($taggedServices as $id => $tags) {
                $definition->addMethodCall('addAccessChecker', array(new Reference($id)));
            }
        }
        else {
            $container->setAlias('jagilpe_encryption.access_checker', 'jagilpe_encryption.security.access_checker.default');
        }
    }
}