<?php


namespace Tests\ObjectivePHP\ServicesFactory\Builder;


use Codeception\Test\Unit;
use ObjectivePHP\ServicesFactory\Builder\DelegatedFactoryBuilder;
use ObjectivePHP\ServicesFactory\Specification\DelegatedFactorySpecification;

class DelegatedFactoryBuilderTest extends Unit
{


    public function testBuildingAServiceUsingDelegatedFactoryBuilder()
    {
        $builder = new DelegatedFactoryBuilder();

        $service = new DelegatedFactoryBuilderTestService();
        $serviceDefinition = new DelegatedFactorySpecification('test.*', function ($serviceId) use ($service) {
            return $service->setId($serviceId);
        });


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
