<?php

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\Specs\PrefabServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

class PrefabServiceBuilder extends AbstractServiceBuilder
{

    /**
     * Service specification types this builder can handle
     *
     * @var array
     */
    protected $handledSpecs = [PrefabServiceSpecs::class];


    /**
     * @param PrefabServiceSpecs|ServiceSpecsInterface $serviceSpecs
     * @param array                                    $params Ignored for this service type
     *
     * @return mixed
     * @throws Exception
     */
    public function build(ServiceSpecsInterface $serviceSpecs, $params = [])
    {
        // check compatibility with the service definition
        if (!$this->doesHandle($serviceSpecs))
        {
            throw new Exception(sprintf('"%s" service spec is not handled by this builder.', get_class($serviceSpecs)), Exception::INCOMPATIBLE_SERVICE_DEFINITION);
        }

        return $serviceSpecs->getInstance();
    }

}
