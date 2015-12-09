<?php


namespace Tests\ObjectivePHP\ServicesFactory\Builder;


use Helpers\DependencyService;
use Helpers\TestService;
use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\ServiceReference;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;

class PrefabServiceBuilderTest extends TestCase
{


    public function testBuildingUnsupportedServiceThrowsAnException()
    {
        $builder = new ClassServiceBuilder();

        $serviceDefinition = new ClassServiceSpecs('test.service', TestService::class);

        // change handled services definition
        $this->setObjectAttribute($builder, 'handledSpecs', ['Whatever']);

        $this->assertFalse($builder->doesHandle($serviceDefinition));

        $this->expectsException(function () use ($serviceDefinition, $builder)
        {
            $builder->build($serviceDefinition);
        }, Exception::class, null, Exception::INCOMPATIBLE_SERVICE_DEFINITION);

    }

}