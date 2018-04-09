<?php

namespace Tests\ObjectivePHP\Package\Config;

use Codeception\Test\Unit;
use ObjectivePHP\ServicesFactory\Config\ServiceDefinition;

class ServiceDefinitionTest extends Unit
{


    public function testSpecificationsSetter()
    {
        $serviceConfig = new ServiceDefinition();
        $serviceConfig->setSpecifications(['firstParam' => 'first', 'secondParam' => ['second']]);

        $this->assertAttributeEquals(['firstParam' => 'first', 'secondParam' => ['second']], 'specifications', $serviceConfig);
    }


}
