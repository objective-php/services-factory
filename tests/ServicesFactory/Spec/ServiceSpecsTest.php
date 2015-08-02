<?php

namespace Tests\ObjectivePHP\ServicesFactory\Specs;

use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\ServicesFactory\Exception;
use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\PrefabServiceSpecs;


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

    public function testSimpleFactory()
    {
        $rawDefinition = [
            'id'      => 'service.id',
            'static'  => false,
            'alias'   => 'my.service',
            'aliases' => ['my.own.service', 'yes.it.is'],
            'class'   => 'Service\Class',
            'params'  => ['param' => 'value']
        ];

        $serviceDefinition = AbstractServiceSpecs::factory($rawDefinition);

        $this->assertEquals('service.id', $serviceDefinition->getId());
        $this->assertEquals('Service\Class', $serviceDefinition->getClass());
        $this->assertFalse($serviceDefinition->isStatic());
        $this->assertInstanceOf(Collection::class, $params = $serviceDefinition->getParams());
        $this->assertEquals(['param' => 'value'], $params->toArray());
        $this->assertInstanceOf(Collection::class, $aliases = $serviceDefinition->getAliases());
        $this->assertEquals(['my.service', 'my.own.service', 'yes.it.is'], $aliases->toArray());
    }

    public function testAbstractServiceDefinitionFactorySanityChecks()
    {
        // id is always mandatory, class is mandatory for ClassServiceSpecs only (the default one)
        $rawDefinition = [
        ];
        $this->expectsException(function () use ($rawDefinition)
        {
            AbstractServiceSpecs::factory($rawDefinition);
        }, Exception::class, '\'id\'', Exception::INCOMPLETE_SERVICE_DEFINITION);

    }

    public function testClassServiceDefinitionFactorySanityChecks()
    {
        // class parameter is missing
        $rawDefinition = [
            'id' => 'service.id'
        ];
        $this->expectsException(function () use ($rawDefinition)
        {
            ClassServiceSpecs::factory($rawDefinition);
        }, Exception::class, '\'class\'', Exception::INCOMPLETE_SERVICE_DEFINITION);

        // class parameter is not a string
        $rawDefinition = [
            'id'    => 'service.id',
            'class' => ['I am not a string']
        ];
        $this->expectsException(function () use ($rawDefinition)
        {
            ClassServiceSpecs::factory($rawDefinition);
        }, Exception::class, '\'class\'', Exception::INVALID_SERVICE_DEFINITION);

    }

    public function testAnyServiceDefinitionCanBeBuiltUsingFactory()
    {
        $rawDefinition = [
            'id'       => 'service.id',
            'type'     => PrefabServiceSpecs::class,
            'instance' => $this
        ];

        $service = AbstractServiceSpecs::factory($rawDefinition);

        $this->assertInstanceOf(PrefabServiceSpecs::class, $service);

    }

    public function testObjectServiceDefinitionFactorySanityChecks()
    {
        // missing instance parameter

        $rawDefinition = [
            'id' => 'service.id'
        ];
        $this->expectsException(function () use ($rawDefinition)
        {
            PrefabServiceSpecs::factory($rawDefinition);
        }, Exception::class, '\'instance\'', Exception::INCOMPLETE_SERVICE_DEFINITION);

    }

}
