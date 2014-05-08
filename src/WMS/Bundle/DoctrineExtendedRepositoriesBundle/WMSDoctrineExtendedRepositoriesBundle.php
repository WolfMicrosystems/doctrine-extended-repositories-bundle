<?php
namespace WMS\Bundle\DoctrineExtendedRepositoriesBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WMS\Bundle\DoctrineExtendedRepositoriesBundle\DependencyInjection\Compiler\RepositoryRegistrationPass;

/**
 * @author Andrew Moore <me@andrewmoore.ca>
 */
class WMSDoctrineExtendedRepositoriesBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RepositoryRegistrationPass());
    }

}