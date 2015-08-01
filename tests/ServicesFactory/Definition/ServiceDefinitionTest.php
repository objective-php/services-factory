<?php

namespace Tests\ObjectivePHP\ServicesFactory\Definition;


use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\ServicesFactory\Definition\ClassServiceDefinition;
use ObjectivePHP\ServicesFactory\Definition\AbstractServiceDefinition;
use ObjectivePHP\ServicesFactory\Exception;
use ObjectivePHP\ServicesFactory\Definition\ObjectServiceDefinition;


class ServiceDefinitionTest extends TestCase
{

    /**
     * @var ClassServiceDefinition
     */
    protected $instance;

    public function setUp()
    {
        $this->instance = new ClassServiceDefinition('service.test', 'stdClass');
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

        $serviceDefinition = AbstractServiceDefinition::factory($rawDefinition);

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
        // id is always mandatory, class is mandatory for ClassServiceDefinition only (the default one)
        $rawDefinition = [
        ];
        $this->expectsException(function () use ($rawDefinition) {
            AbstractServiceDefinition::factory($rawDefinition);
        }, Exception::class, '\'id\'', Exception::INCOMPLETE_SERVICE_DEFINITION);

    }

    public function testClassServiceDefinitionFactorySanityChecks()
    {
        // class parameter is missing
        $rawDefinition = [
            'id' => 'service.id'
        ];
        $this->expectsException(function () use ($rawDefinition) {
            ClassServiceDefinition::factory($rawDefinition);
        }, Exception::class, '\'class\'', Exception::INCOMPLETE_SERVICE_DEFINITION);

        // class parameter is not a string
        $rawDefinition = [
            'id' => 'service.id',
            'class' => ['I am not a string']
        ];
        $this->expectsException(function () use ($rawDefinition) {
            ClassServiceDefinition::factory($rawDefinition);
        }, Exception::class, '\'class\'', Exception::INVALID_SERVICE_DEFINITION);

    }

    public function testAnyServiceDefinitionCanBeBuiltUsingFactory()
    {
        $rawDefinition = [
            'id'       => 'service.id',
            'type'     => ObjectServiceDefinition::class,
            'instance' => $this
        ];

        $service = AbstractServiceDefinition::factory($rawDefinition);

        $this->assertInstanceOf(ObjectServiceDefinition::class, $service);

    }

    public function testObjectServiceDefinitionFactorySanityChecks()
    {
        // missing instance parameter

        $rawDefinition = [
            'id' => 'service.id'
        ];
        $this->expectsException(function () use ($rawDefinition) {
            ObjectServiceDefinition::factory($rawDefinition);
        }, Exception::class, '\'instance\'', Exception::INCOMPLETE_SERVICE_DEFINITION);


        // invalid instance parameter value
        $rawDefinition = [
            'id'       => 'service.id',
            'instance' => 'I am not an object'
        ];
        $this->expectsException(function () use ($rawDefinition) {
            ObjectServiceDefinition::factory($rawDefinition);
        }, Exception::class, 'object', Exception::INVALID_SERVICE_DEFINITION);

    }

}
