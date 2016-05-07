<?php

namespace ObjectivePHP\ServicesFactory\Annotation;
use ObjectivePHP\ServicesFactory\ServiceReference;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Inject
{

    /**
     * @var string
     */
    public $setter;

    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $service;

    /**
     * @var
     */
    protected $baseNamespace;

    /**
     * @return string
     */
    public function getDependency()
    {

        $type = ($this->class || !$this->service) ? 'instance' : 'service';

        switch($type)
        {
            case 'instance':
                return $this->class;

            case 'service':
                return new ServiceReference($this->service);

        }

    }



}