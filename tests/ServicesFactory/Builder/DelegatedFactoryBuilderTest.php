<?php


namespace Tests\ObjectivePHP\ServicesFactory\Builder;


use Helpers\DependencyService;
use Helpers\TestService;
use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\DelegatedFactoryBuilder;
use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\ServiceReference;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\DelegatedFactorySpecs;

class DelegatedFactoryBuilderTest extends TestCase
{


    public function testBuildingAServiceUsingDelegatedFactoryBuilder()
    {
        $builder = new DelegatedFactoryBuilder();
    
        $service = new DelegatedFactoryBuilderTestService();
        $serviceDefinition = new DelegatedFactorySpecs('test.*', function($serviceId) use($service) { return $service->setId($serviceId);});


        $this->assertSame($service, $builder->build($serviceDefinition, [], 'test.service'));

        $this->assertEquals('test.service', $service->getId());
    }

}

// Helper classes

class DelegatedFactoryBuilderTestService
{
    protected $id;
    
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        
        return $this;
    }
    
    
}
