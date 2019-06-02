# Objective PHP / Services Factory [![Build Status](https://secure.travis-ci.org/objective-php/services-factory.png?branch=master)](http://travis-ci.org/objective-php/services-factory)

## Description

Services Factory is an attempt to handle dependencies injection in an easier and more readable way compared to other available implementations. 

The main main focus of this component is put on:

 - reducing the code and configuration needed for DI mechanism
 - preserving code-insight for components built and getting their dependencies injected

The Services Factory is split in several components. Understanding each of these helps a lot in understanding the whole logic of the component:

 - Service Specifications
    - defined by ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface
    - this how the service definitions are normalized, so that the Factory understands them
    - there are two default specs types provided with the component:
        - PrefabServiceSpecification
            - the most simple services ever!
            - stores a pre-instantiated object (or any other value)
        - ClassServiceSpecification
            - this one allow to define a class as template of a service
            - can get constructor arguments ("params" property)
            - optional dependencies can be set using setters ("setters" property)
            - **is autowired by default** 
    - both types require an "id" parameter
    - the latter also supports a "static" property, to indicate whether the same instance should be returned each time the service is requested or not
 - Service Builders
    - associated to the ServiceSpecs types, builders are in charge of actually building the service according to its specs
    - there also two builders bundled with the component, one for each type:
        - PrefabServiceBuilder
        - ClassServiceBuilder
 - Factory
    - central object, it's used to register either service specs and builders
    - once setup, the Factory provide the application with services through its `get(string $serviceId)`method

### Documentation

The component documentation is located in the [docs](docs/index.md) subfolder


