<?php
namespace WMS\Bundle\DoctrineExtendedRepositoriesBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RepositoryFactory extends DefaultRepositoryFactory
{
    /** @var ContainerInterface */
    private $container;
    private $registeredServices = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function registerRepositoryService($entityName, $serviceId)
    {
        $this->registeredServices[$entityName] = $serviceId;
    }

    protected function createRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $metadata = $entityManager->getClassMetadata($entityName);

        $serviceId = $this->getServiceForEntityName($entityManager, $metadata->getName());

        if ($serviceId !== null) {
            return $this->container->get($serviceId);
        }

        return parent::createRepository($entityManager, $entityName);
    }

    protected function getServiceForEntityName(EntityManagerInterface $entityManager, $fqcn)
    {
        foreach ($this->registeredServices as $key => $service) {
            try {
                $metadata = $entityManager->getClassMetadata($key);

                if ($metadata->getName() === $fqcn) {
                    return $service;
                }
            } catch (ORMException $ex) {
            }
        }

        return null;
    }
}