<?php

namespace Tests\ObjectivePHP\ServicesFactory\Specs;

use Codeception\Test\Unit;
use ObjectivePHP\ServicesFactory\Specification\ClassServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\UndefinedServiceSpecification;

class ClassServiceSpecificationTest extends Unit
{

    /**
     * @var ClassServiceSpecification
     */
    protected $instance;


    public function testConstructor()
    {
        $specs = new ClassServiceSpecification('service.test', 'stdClass');
        $this->assertAttributeEquals('service.test', 'id', $specs);
    }


    public function testAliasesSetting()
    {
        $specs = new ClassServiceSpecification('service.test', 'stdClass');
        $specs->setAliases(['service.alias']);
        $this->assertAttributeEquals(['service.alias'], 'aliases', $specs);
        $this->assertEquals(['service.alias'], $specs->getAliases());
        $this->assertEquals([\stdClass::class], $specs->getAutoAliases());
    }

    public function testSingleAliasSetting()
    {
        $specs = new ClassServiceSpecification('service.test', \stdClass::class);
        $specs->setAliases('service.alias');
        $this->assertEquals(['service.alias'], $specs->getAliases());
        $this->assertEquals([\stdClass::class], $specs->getAutoAliases());
    }


}
