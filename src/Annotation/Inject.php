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
     * @var string
     */
    public $param;

    /**
     * @var string Default param value
     */
    public $default;

    /**
     * @var
     */
    protected $baseNamespace;

    /**
     * @return string
     */
    public function getDependency($config = null)
    {

        if($this->param) {
            $type = 'param';
        }
        else {
            $type = ($this->class || !$this->service) ? 'instance' : 'service';
        }

        switch($type)
        {
            case 'instance':
                return $this->class;

            case 'service':
                return new ServiceReference($this->service);

            case 'param':
                return 'param.value';

        }

    }


}