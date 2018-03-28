<?php

namespace Tests\ObjectivePHP\ServicesFactory;


use Codeception\Test\Unit;
use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\ServicesFactory\ServiceReference;

class ServiceReferenceTest extends Unit
{

    public function testToStringImplementation()
    {
        $ref = new ServiceReference('test.service.id');

        $this->assertEquals('test.service.id', (string) $ref);
    }

}