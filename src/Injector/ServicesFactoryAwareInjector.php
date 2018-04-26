<?php

namespace ObjectivePHP\ServicesFactory\Injector;

use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\ServicesFactoryAwareInterface;
use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

/**
 * Class ServicesFactoryAwareInjector
 *
 * @package ObjectivePHP\ServicesFactory\Injector
 */
class ServicesFactoryAwareInjector implements InjectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function injectDependencies(
        $instance,
        ServicesFactory $servicesFactory,
        ServiceSpecificationInterface $serviceSpecification = null
    ) {
        if ($instance instanceof ServicesFactoryAwareInterface) {
            $instance->setServicesFactory($servicesFactory);
        }
    }
}
