<?php


    namespace Tests\ObjectivePHP\ServicesFactory\Builder;


    use Helpers\TestService;
    use ObjectivePHP\PHPUnit\TestCase;
    use ObjectivePHP\Primitives\Collection;
    use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
    use ObjectivePHP\ServicesFactory\Definition\ClassServiceDefinition;
    use ObjectivePHP\ServicesFactory\Exception;
    use ObjectivePHP\ServicesFactory\Factory;
    use ObjectivePHP\ServicesFactory\Reference;

    class ClassServiceBuilderTest extends TestCase
    {

        
        public function testBuildingUnsupportedServiceThrowsAnException()
        {
            $builder = new ClassServiceBuilder();
            
            $serviceDefinition = new ClassServiceDefinition('test.service', TestService::class);

            // change handled services definition
            $this->setObjectAttribute($builder, 'handledDefinitions', ['Whatever']);

            $this->assertFalse($builder->doesHandle($serviceDefinition));

            $this->expectsException(function() use ($serviceDefinition, $builder) {
                $builder->build($serviceDefinition);
            }, Exception::class, null, Exception::INCOMPATIBLE_SERVICE_DEFINITION);

        }

        public function testSimpleServiceIsBuilt()
        {
            $builder = new ClassServiceBuilder();
            $serviceDefinition = new ClassServiceDefinition('test.service', TestService::class);

            $this->assertTrue($builder->doesHandle($serviceDefinition));

            $service = $builder->build($serviceDefinition);

            $this->assertInstanceOf(TestService::class, $service);

            $serviceDefinition->setParams(['first' => 'x', 'second' => 'y']);

            // add params to service definition
            $service = $builder->build($serviceDefinition);
            $this->assertInstanceOf(TestService::class, $service);
            $this->assertAttributeEquals(Collection::cast(['arg1' => 'x', 'arg2' => 'y']), 'args', $service);

            // override service definition params at runtime
            $service = $builder->build($serviceDefinition, ['first' => 'OVERRIDDEN']);
            $this->assertInstanceOf(TestService::class, $service);
            $this->assertAttributeEquals(Collection::cast(['arg1' => 'OVERRIDDEN', 'arg2' => 'y']), 'args', $service);

        }
        
        public function testFactoryAccessors()
        {
            $factory = $this->getMock(Factory::class);

            $builder = new ClassServiceBuilder();
            $setterReturn = $builder->setFactory($factory);

            $this->assertAttributeSame($factory, 'factory', $builder);
            $this->assertSame($factory, $builder->getFactory());

            $this->assertSame($builder, $setterReturn);


        }

        public function testClassBuilderSubstitutesServiceReferences()
        {

            $dependency = new \stdClass;

            $factory = $this->getMock(Factory::class);
            $factory->expects($this->once())->method('get')->with('dependency.id')->willReturn($dependency);

            $builder = new ClassServiceBuilder();
            $builder->setFactory($factory);

            $serviceDefinition = new ClassServiceDefinition('main.service', 'stdClass');
            $serviceDefinition->setParams(['dependency' => new Reference('dependency.id')]);

            $builder->build($serviceDefinition);

        }

    }


    namespace Helpers;

    use ObjectivePHP\Primitives\Collection;

    class TestService
    {

        protected $args = [];

        public function __construct($arg1 = null, $arg2 = null)
        {
            $this->args         = Collection::cast($this->args);
            $this->args['arg1'] = $arg1;
            $this->args['arg2'] = $arg2;
     }
 }