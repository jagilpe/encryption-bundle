<?php

/**
 * This file holds the definition of the main bundle of the application
 */
namespace Jagilpe\EncryptionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Jagilpe\EncryptionBundle\DependencyInjection\Compiler\AccessCheckerPass;

class JagilpeEncryptionBundle extends Bundle
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