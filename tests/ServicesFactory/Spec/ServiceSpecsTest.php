<?php

namespace Tests\ObjectivePHP\ServicesFactory\Specs;

use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\UndefinedServiceSpecs;


class ServiceSpecsTest extends TestCase
{

    /**
     * @var ClassServiceSpecs
     */
    protected $instance;

    public function setUp()
    {
        $this->instance = new ClassServiceSpecs('service.test', 'stdClass');
    }

    public function testConstructor()
    {
        $this->assertAttributeEquals('service.test', 'id', $this->instance);
    }

    public function testAliasesSetting()
    {
        $this->instance->setAliases(['service.alias']);
        $this->assertAttributeEquals(Collection::cast(['service.alias']), 'aliases', $this->instance);
    }

    public function testSingleAliasSetting()
    {
        $this->instance->setAliases('service.alias');
        $this->assertAttributeEquals(Collection::cast(['service.alias']), 'aliases', $this->instance);
    }

    public function testAbstractServiceSpecsReturnsAnUndefinedServiceSpecsIfNoActualSpecsIsMatchesServiceDefinition()
    {
        $serviceSpec = AbstractServiceSpecs::factory(['id' => 'test.service']);

        $this->assertInstanceOf(UndefinedServiceSpecs::class, $serviceSpec);
    }

}
