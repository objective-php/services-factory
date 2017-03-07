<?php


namespace Tests\ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\PrefabServiceSpecs;

class AbstractServiceBuilderTest extends TestCase
{

    public function testSimpleFactory()
    {
        $rawSpecs = [
            'id'      => 'service.id',
            'static'  => false,
            'alias'   => 'my.service',
            'aliases' => ['my.own.service', 'yes.it.is'],
            'class'   => 'Service\SomeService',
            'params'  => ['param' => 'value'],
            'setters' => ['setAnything' => 'value']
        ];

        $serviceSpecs = AbstractServiceSpecs::factory($rawSpecs);
        $serviceSpecs->disableAutoAliasing();
        $this->assertEquals('service.id', $serviceSpecs->getId());
        $this->assertEquals('Service\SomeService', $serviceSpecs->getClass());
        $this->assertFalse($serviceSpecs->isStatic());
        $this->assertInstanceOf(Collection::class, $params = $serviceSpecs->getParams());
        $this->assertEquals(['param' => 'value'], $params->toArray());
        $this->assertEquals(['my.service', 'my.own.service', 'yes.it.is'], $serviceSpecs->getAliases());
    }

    public function testAbstractServiceSpecsFactorySanityChecks()
    {
        // id is always mandatory, class is mandatory for ClassServiceSpecs only (the default one)
        $rawSpecs = [
        ];
        $this->expectsException(function () use ($rawSpecs)
        {
            AbstractServiceSpecs::factory($rawSpecs);
        }, Exception::class, '\'id\'', Exception::INCOMPLETE_SERVICE_SPECS);

    }

    public function testClassServiceSpecsFactorySanityChecks()
    {
        // class parameter is missing
        $rawSpecs = [
            'id' => 'service.id'
        ];
        $this->expectsException(function () use ($rawSpecs)
        {
            ClassServiceSpecs::factory($rawSpecs);
        }, Exception::class, '\'class\'', Exception::INCOMPLETE_SERVICE_SPECS);

        // class parameter is not a string
        $rawSpecs = [
            'id'    => 'service.id',
            'class' => ['I am not a string']
        ];
        $this->expectsException(function () use ($rawSpecs)
        {
            ClassServiceSpecs::factory($rawSpecs);
        }, Exception::class, '\'class\'', Exception::INVALID_SERVICE_SPECS);

    }

    public function testAnyServiceSpecsCanBeBuiltUsingFactory()
    {
        $rawSpecs = [
            'id'       => 'service.id',
            'type'     => PrefabServiceSpecs::class,
            'instance' => $this
        ];

        $service = AbstractServiceSpecs::factory($rawSpecs);

        $this->assertInstanceOf(PrefabServiceSpecs::class, $service);

    }

    public function testObjectServiceSpecsFactorySanityChecks()
    {
        // missing instance parameter

        $rawSpecs = [
            'id' => 'service.id'
        ];
        $this->expectsException(function () use ($rawSpecs)
        {
            PrefabServiceSpecs::factory($rawSpecs);
        }, Exception::class, '\'instance\'', Exception::INCOMPLETE_SERVICE_SPECS);

    }
    
    public function testFactoryCanGuessTypeForDefaultSpecs()
    {
        // class
        $rawSpecs = [
            'id'      => 'service.id',
            'class'   => 'Service\Class',
        ];

        $serviceSpecs = AbstractServiceSpecs::factory($rawSpecs);
        $this->assertInstanceOf(ClassServiceSpecs::class, $serviceSpecs);

        // prefab
        $rawSpecs = [
            'id'      => 'service.id',
            'instance'   => new \stdClass(),
        ];

        $serviceSpecs = AbstractServiceSpecs::factory($rawSpecs);
        $this->assertInstanceOf(PrefabServiceSpecs::class, $serviceSpecs);


        // ambiguous
        $rawSpecs = [
            'id'       => 'service.id',
            'class' => 'Service\Class',
            'instance' => new \stdClass()
        ];

        $this->expectsException(function () use($rawSpecs)
        {
            $serviceSpecs = AbstractServiceSpecs::factory($rawSpecs);
        }, Exception::class, '', Exception::AMBIGUOUS_SERVICE_SPECS);

        // incomplete
        $rawSpecs = [
        ];

        $this->expectsException(function () use($rawSpecs)
        {
            $serviceSpecs = AbstractServiceSpecs::factory($rawSpecs);
        }, Exception::class, '', Exception::INCOMPLETE_SERVICE_SPECS);
    }

}

namespace Service;

class SomeService
{
    public function setAnything($value)
    {

    }
}
