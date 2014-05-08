<?php
namespace WMS\Bundle\DoctrineExtendedRepositoriesBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class RepositoryRegistrationPass implements CompilerPassInterface
{
    const TAG_NAME = 'wms.doctrine_extended_repository';
    const REPO_FACTORY_NAMING_TEMPLATE = 'wms.doctrine_orm_extended_repositories.factories.%s_factory';
    const DOCTRINE_ORM_CONFIG_NAMING_TEMPLATE = 'doctrine.orm.%s_configuration';

    /**
     * @param ContainerBuilder $container
     *
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter('doctrine.entity_managers') === false) {
            return;
        }

        $entityManagersIds = $container->getParameter('doctrine.entity_managers');
        $defaultEntityManagerName = $container->getParameter('doctrine.default_entity_manager');

        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        foreach ($entityManagersIds as $entityManagerId) {
            $connectionName = preg_replace('/^doctrine.orm.(.+)_entity_manager$/', '$1', $entityManagerId);

            $repoFactoryName = sprintf(self::REPO_FACTORY_NAMING_TEMPLATE, $connectionName);
            $repoFactoryDef = $this->createRepositoryFactoryForConnection($container, $connectionName, $repoFactoryName);

            if ($repoFactoryDef === null) {
                continue;
            }

            $this->parseTaggedServicesForConnection($container, $defaultEntityManagerName, $taggedServices, $entityManagerId, $connectionName, $repoFactoryDef);
        }
    }

    protected function createRepositoryFactoryForConnection(ContainerBuilder $container, $connectionName, $repoFactoryName)
    {
        $configName = sprintf(self::DOCTRINE_ORM_CONFIG_NAMING_TEMPLATE, $connectionName);

        if ($container->hasDefinition($configName) === false) {
            return null;
        }

        $repoFactoryDef = $container->setDefinition($repoFactoryName, new DefinitionDecorator('wms.doctrine_extended_repositories.factory'));

        $configDef = $container->getDefinition($configName);
        $configDef->addMethodCall(
            'setRepositoryFactory',
            array(
                new Reference($repoFactoryName)
            )
        );

        return $repoFactoryDef;
    }

    protected function parseTaggedServicesForConnection(ContainerBuilder $container, $defaultEntityManagerName, $taggedServices, $entityManagerId, $connectionName, Definition $repoFactoryDef)
    {
        foreach ($taggedServices as $id => $instances) {
            if (count($instances) > 1) {
                throw new \InvalidArgumentException(sprintf('The service "%s" is tagged more than once as a repository.', $id));
            }

            foreach ($instances as $attributes) {
                if (isset($attributes['entity']) === false) {
                    throw new \InvalidArgumentException(sprintf('The service "%s" must identify for which entity it wants to be a service of.', $id));
                }

                if (isset($attributes['connection']) === false) {
                    $attributes['connection'] = $defaultEntityManagerName;
                }

                if ($attributes['connection'] === $connectionName) {
                    $repoFactoryDef->addMethodCall('registerRepositoryService', array($attributes['entity'], $id));

                    $serviceDef = $container->getDefinition($id);
                    $arguments = $serviceDef->getArguments();

                    $classMetadataServiceId = $id . '.class_metadata_factory';

                    $classMetadataDef = $container->setDefinition($classMetadataServiceId, new DefinitionDecorator('wms.doctrine_extended_repositories.class_metadata'));
                    $classMetadataDef->setFactoryService($entityManagerId);
                    $classMetadataDef->addArgument($attributes['entity']);

                    array_unshift($arguments, new Reference($classMetadataServiceId));
                    array_unshift($arguments, new Reference($entityManagerId));

                    $serviceDef->setArguments($arguments);
                }
            }
        }
    }
}