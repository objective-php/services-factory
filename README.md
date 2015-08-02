# Objective PHP / Services Factory [![Build Status](https://secure.travis-ci.org/objective-php/services-factory.png?branch=master)](http://travis-ci.org/objective-php/services-factory)

## Description

Services Factory is an attempt to handle dependencies injection in an easier and more readable way compared to other available implementations. 

The main main focus of this component is put on:

 - reducing the code and configuration needed for DI mechanism
 - preserving code-insight for components built and getting their dependencies injected

The Services Factory is split in several components. Understanding each of these helps a lot in understanding the whole logic of the component:

 - Service Specs
    - defined by ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface
    - this how the service definitions are normalized, so that the Factory understands them
    - there are two default specs types provided with the component:
        - PrefabServiceSpecs
            - the most simple services ever!
            - stores a pre-instantiated object (or any other value)
        - ClassServiceSpecs
            - this one allow to define a class as template of a service
            - can get constructor arguments ("params" property)
            - optional dependencies can be set using setters ("setters" property)
            - those dependencies can be other services, by passing an ObjectivePHP\ServicesFactory\Reference as dependency value
    - both types require an "id" parameter
    - both types also support a "static" property, to indicate whether the same instance should be returned each time the service is requested or not
 - Service Builders
    - associated to the ServiceSpecs types, builders are in charge of actually building the service according to its specs
    - there also two builders bundled with the component, one for each type:
        - PrefabServiceBuilder
        - ClassServiceBuilder
 - Factory
    - central object, it's used to register either service specs and builders
    - once setup, the Factory provide the application with services through its `get(string $serviceId)`method


## What's next

The next major feature of the Services Factory will be to allow automated dependency injection using Injectors and the Matcher component.
 

## Installation

### Manual

You can clone our Github repository by running:

```
git clone http://github.com/objective-php/services-factory
```

If you're to proceed this way, you probably don't need more explanation about how to use the library :)

### Composer

The easiest way to install the component and get ready to play with it is by using Composer. Run the following command in an empty folder you just created for Services Factory:

```
composer require --dev objective-php/services-factory:dev-master 
```

Then, you can start coding by requiring Composer's `autoload.php` located in `vendor` directory.

Hmm, before starting coding, please take the time to read this file till the end :)

## How to test the work in progress?

### Run unit tests

First of all, before playing around with our services factory, please always run the unit tests suite. Our tests are written using PHPUnit, and can be run as follow:

```
cd [clone directory]/tests
./phpunit .
```

### Write some code

No actual documentation has been written yet. Some usage examples below will help you getting started, but reading the unit tests and library source remains the best way to understand the Services Factory

```php
<?php

use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\ServicesFactory\Factory;
use ObjectivePHP\ServicesFactory\Reference;
use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\PrefabServiceSpecs;

require 'vendor/autoload.php';


// no dependency is required to instantiate the Factory
$factory = new Factory();

// storing an existing object in the service factory
$config = new Collection(['directive' => 'value']);
$serviceSpecs = new PrefabServiceSpecs('config', $config);
$factory->registerService($serviceSpecs);
$configService = $factory->get('config');

// same as above but using the ClassServiceSpecs
$serviceSpecs = (new ClassServiceSpecs('config', Collection::class))
                ->setParams(['directive' => 'value']);
$factory->registerService($serviceSpecs);
$configService = $factory->get('config');

// same again, using specs factory
$serviceSpecs = AbstractServiceSpecs::factory([
                                                'id' => 'config',
                                                'class' => Collection::class,
                                                'params' => [['directive' => 'value']]
                                              ]
);
// or with
$serviceSpecs = AbstractServiceSpecs::factory([
        'id'     => 'config',
        'instance'  => $config,
        'type'  => PrefabServiceSpecs::class
    ]
);

$factory->registerService($serviceSpecs);
$configService = $factory->get('config');

// injecting dependency using setter
$serviceSpecs = (new ClassServiceSpecs('config', Collection::class))
    ->setSetters(['setDirective' => ['directive','value']]);
$factory->registerService($serviceSpecs);
$configService = $factory->get('config');

// using references

// create a first service
$directiveServiceSpecs = new PrefabServiceSpecs('config.directive.value', 'value');
$factory->registerService($directiveServiceSpecs);

// use it as reference for the second service
$serviceSpecs = (new ClassServiceSpecs('config', Collection::class))
    ->setSetters(['setDirective' => ['directive', new Reference('config.directive.value')]]);
$factory->registerService($serviceSpecs);
$configService = $factory->get('config');

```

## Restoring code-assist with a service container

If like us you think that code-assist in an IDE is not a toy, but definitely a must-have, you're heavily encouraged to create some
gateways to get your services documented and understood by your IDE:

```php
<?php


use ObjectivePHP\ServicesFactory\Factory;

class Services
{

    static protected $factory;

    /**
     * @return Config;
     */
    static public function getConfig()
    {
        return self::$factory->get('config');
    }

    /**
     * @return mixed
     */
    public static function getFactory()
    {
        return self::$factory;
    }

    /**
     * @param mixed $factory
     */
    public static function setFactory(Factory $factory)
    {
        self::$factory = $factory;
    }
}

```
