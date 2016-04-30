<?php

namespace Tests\ObjectivePHP\ServicesFactory;


use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\ServicesFactory\ServiceReference;

class ServiceReferenceTest extends TestCase
{

    public function testToStringImplementation()
    {
        $ref = new ServiceReference('test.service.id');

        $this->assertEquals('test.service.id', (string) $ref);
    }

}