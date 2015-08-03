<?php

namespace Tests\ObjectivePHP\ServicesFactory\Specs;

use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;


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


}
