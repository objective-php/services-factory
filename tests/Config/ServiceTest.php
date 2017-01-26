<?php
namespace Tests\ObjectivePHP\Package\Config;

use ObjectivePHP\ServicesFactory\Config\Service;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testIdSetter()
    {
        $service = new Service();
        $service->setId('identifier');

        $this->assertAttributeEquals(['id' => 'identifier'], 'value', $service);
    }

    public function testClassSetter()
    {
        $service = new Service();
        $service->setClass('My\\Service\\Class');

        $this->assertAttributeEquals(['class' => 'My\\Service\\Class'], 'value', $service);
    }

    public function testSettersSetter()
    {
        $service = new Service();
        $service->setSetters(['firstSetter' => 'first', 'secondSetter' => 'second']);

        $this->assertAttributeEquals(['setters' => ['firstSetter' => 'first', 'secondSetter' => 'second']], 'value', $service);
    }

    public function testAddSetter()
    {
        $service = new Service();
        $service->addSetter('firstSetter', 'fake-params');

        $this->assertAttributeEquals(['setters' => ['firstSetter' => 'fake-params']], 'value', $service);
    }

    public function testParamsSetter()
    {
        $service = new Service();
        $service->setParams(['firstParam' => 'first', 'secondParam' => 'second']);

        $this->assertAttributeEquals(['params' => ['firstParam' => 'first', 'secondParam' => 'second']], 'value', $service);
    }

    public function testAddParam()
    {
        $service = new Service();
        $service->addParam('firstParam', 'fake-params');

        $this->assertAttributeEquals(['params' => ['firstParam' => 'fake-params']], 'value', $service);
    }

}
