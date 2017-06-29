<?php

namespace Tests\ObjectivePHP\ServicesFactory\Specs;

use ObjectivePHP\Invokable\InvokableInterface;
use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\DelegatedFactorySpecs;


class DelegatedFactorySpecsTest extends TestCase
{
    
    /**
     * @var ClassServiceSpecs
     */
    protected $instance;
    
    public function testFactoryIsCastedToInvokable()
    {
        $specs = new DelegatedFactorySpecs('service.id', function () {
        });
        $this->assertInstanceOf(InvokableInterface::class, $specs->getFactory());
    }
    
}
