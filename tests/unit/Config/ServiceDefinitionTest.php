<?php

namespace Tests\ObjectivePHP\Package\Config;

use Codeception\Test\Unit;
use ObjectivePHP\ServicesFactory\Config\ServiceDefinition;

class ServiceDefinitionTest extends Unit
{
    public function testSpecificationsSetter()
    {
        $serviceConfig = new ServiceDefinition();
        $serviceConfig->setId('test')
            ->setClass('test')
            ->setSetters([
                'test' => ['test']
            ])
            ->setParams(['test1', 'test2'])
            ->setAliases(['test1', 'test2'])
            ->setStatic(false);

        $this->assertEquals([
            'class' => 'test',
            'setters' => ['test' => ['test']],
            'params' => ['test1', 'test2'],
            'aliases' => ['test1', 'test2'],
            'static' => false
        ], $serviceConfig->getSpecifications());
    }
}
