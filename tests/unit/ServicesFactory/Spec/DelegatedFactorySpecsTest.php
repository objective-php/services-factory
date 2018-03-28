<?php

namespace Tests\ObjectivePHP\ServicesFactory\Specs;

use Codeception\Test\Unit;
use ObjectivePHP\ServicesFactory\Specification\ClassServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\DelegatedFactorySpecification;


class DelegatedFactorySpecsTest extends Unit
{

    /**
     * @var ClassServiceSpecification
     */
    protected $instance;

    public function testFactoryIsCastedToInvokable()
    {
        $specs = new DelegatedFactorySpecification('service.id', function () {
        });
        $this->assertTrue(is_callable($specs->getFactory()));
    }

}
