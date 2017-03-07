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


    public function testConstructor()
    {
        $specs = new ClassServiceSpecs('service.test', 'stdClass');
        $this->assertAttributeEquals('service.test', 'id', $specs);
    }

    
    public function testAutoAliasing()
    {
        $specs = new ClassServiceSpecs('service.test', 'stdClass');
        $this->assertEquals(['\stdClass'], $specs->getAliases());
        $this->assertEquals('\stdClass', $specs->getAutoAlias());
    }
    
    public function testAliasesSetting()
    {
        $specs = new ClassServiceSpecs('service.test', 'stdClass');
        $specs->setAliases(['service.alias']);
        $this->assertAttributeEquals(['service.alias'], 'aliases', $specs);
        $this->assertEquals(['service.alias', '\stdClass'], $specs->getAliases());
    }

    public function testSingleAliasSetting()
    {
        $specs = new ClassServiceSpecs('service.test', 'stdClass');
        $specs->setAliases('service.alias');
        $this->assertAttributeEquals(['service.alias'], 'aliases', $specs);
    }

    public function testAbstractServiceSpecsReturnsAnUndefinedServiceSpecsIfNoActualSpecsIsMatchesServiceDefinition()
    {
        $serviceSpec = AbstractServiceSpecs::factory(['id' => 'test.service']);

        $this->assertInstanceOf(UndefinedServiceSpecs::class, $serviceSpec);
    }
    
}
