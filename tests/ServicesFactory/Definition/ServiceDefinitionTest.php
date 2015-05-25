<?php

   namespace Tests\ObjectivePHP\ServicesFactory\Definition;


   use ObjectivePHP\PHPUnit\TestCase;
   use ObjectivePHP\Primitives\Collection;
   use ObjectivePHP\ServicesFactory\Definition\ServiceDefinition;

   class ServiceDefinitionTest extends TestCase
   {

       /**
        * @var ServiceDefinition
        */
       protected $instance;

       public function setUp()
       {
           $this->instance = new ServiceDefinition('service.test');
       }

       public function testConstructor()
       {
           $this->assertAttributeEquals('service.test', 'serviceId', $this->instance);
       }

       public function testAliasesSetting()
       {
           $this->instance->setAliases(['service.alias']);
           $this->assertAttributeEquals(Collection::cast(['service.alias']), 'aliases', $this->instance);
       }

       public function testSingleAliasSetting()
       {
           $this->instance->setAliases('service.alias');
           $this->assertAttributeEquals(Collection::cast(['service.alias']), 'aliases', $this->instance);
       }
   }