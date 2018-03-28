<?php

namespace Tests\ObjectivePHP\Package\Config;

use Codeception\Test\Unit;
use ObjectivePHP\ServicesFactory\Config\ServiceDefinition;

class ClassServiceConfigTest extends Unit
{
    public function testIdSetter()
    {
        $serviceConfig = new ServiceDefinition();
        $serviceConfig->setId('identifier');

        $this->assertAttributeEquals('identifier', 'id', $serviceConfig);
    }

    public function testClassSetter()
    {
        $serviceConfig = new ServiceDefinition();
        $serviceConfig->setClass('My\\Service\\Class');

        $this->assertAttributeEquals('My\\Service\\Class', 'class', $serviceConfig);
    }


    public function testParamsSetter()
    {
        $serviceConfig = new ServiceDefinition();
        $serviceConfig->setSpecifications(['firstParam' => 'first', 'secondParam' => 'second']);

        $this->assertAttributeEquals(['firstParam' => ['first'], 'secondParam' => ['second']], 'params', $serviceConfig);
    }

    public function testAddParam()
    {
        $serviceConfig = new ServiceDefinition();
        $serviceConfig->addParam('firstParam', 'fake-params');

        $this->assertAttributeEquals(['firstParam' => ['fake-params']], 'params', $serviceConfig);
    }

}
