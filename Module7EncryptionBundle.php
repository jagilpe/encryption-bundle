<?php

/**
 * This file holds the definition of the main bundle of the application
 */
namespace Module7\EncryptionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Module7\EncryptionBundle\DependencyInjection\Compiler\AccessCheckerPass;

class Module7EncryptionBundle extends Bundle
{
    /**
     *
     * {@inheritDoc}
     * @see \Symfony\Component\HttpKernel\Bundle\Bundle::build()
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AccessCheckerPass());
    }
}