WMSDoctrineExtendedRepositoriesBundle
=====================================

The WMSDoctrineExtendedRepositoriesBundle allows you to use dependency injection within Doctrine Repositories by registering them as services.

This document contains information on how to download, install and use this bundle.

1) Installing the Bundle
------------------------

### Using Composer

As Symfony uses [Composer][1] to manage its dependencies, the recommended way to install this bundle is to use it.

If you don't have Composer yet, download it following the instructions on
[http://getcomposer.org/][1] or just run the following command:

    curl -s http://getcomposer.org/installer | php

Then, use the `require` command to download this bundle:

    php composer.phar require wms/doctrine-extended-repository-bundle:~1.0@dev

Finally, edit your `AppKernel.php` file and add the bundle:

    WMS\Bundle\DoctrineExtendedRepositoriesBundle\WMSDoctrineExtendedRepositoriesBundle()

2) Usage
------------------------

In order to create a repository, simply create a new class using the following template:

```php
use Doctrine\ORM;

class MyCustomRepository extends EntityRepository {
	private $dep;

    /**
     * Initializes a new EntityRepository.
     *
     * @param EntityManager         $em    The EntityManager to use.
     * @param Mapping\ClassMetadata $class The class descriptor.
     * @param MyDependency          $dep   The rest of the arguments are yours to choose!
     */
	public function __construct($em, Mapping\ClassMetadata $class, MyDependency $dep) {
		parent::__construct($em, $class);

		$this->dep = $dep;
	}
}
```

**IMPORTANT:** In order to be compatible with Doctrine repositories, the first two arguments of your repository are reserved to the entity manager and the `ClassMetadata` of your entity. These will be automatically injected.

Then, simply define your service:

**XML: **

```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <service id="my_super_repository" class="MyCustomRepository">
            <argument type="service" id="my_dependency" />
			<tag name="wms.doctrine_extended_repository" entity="ACMEBundle:SuperEntity" connection="default" />
        </service>
    </services>
</container>
```

**YAML: **

```yaml
services:
	my_super_repository:
		class: MyCustomRepository
		arguments: [ @my_dependency ]
		tags:
			- { name: wms.doctrine_extended_repository, entity: "ACMEBundle:SuperEntity", connection: default }
```

You may omit the connection attribute on the tag. If so, it will use the default connection/entity manager.

The WMS Doctrine Extended Repository Bundle is released under the MIT license.

Enjoy!

[1]:  http://getcomposer.org/