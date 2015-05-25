<?php


    namespace Tests\ObjectivePHP\ServicesFactory\Builder;


    use Helpers\TestService;
    use ObjectivePHP\PHPUnit\TestCase;
    use ObjectivePHP\Primitives\Collection;
    use ObjectivePHP\ServicesFactory\Builder\DefaultServiceBuilder;
    use ObjectivePHP\ServicesFactory\Definition\ServiceDefinition;
    use ObjectivePHP\ServicesFactory\Exception;

    class DefaultServiceBuilderTest extends TestCase
    {

        /**
         * @var DefaultServiceBuilder
         */
        protected $instance;

        public function setUp()
        {
            $this->instance = new DefaultServiceBuilder();
        }

        public function testBuildingUnsupportedServiceThrowsAnException()
        {
            $serviceDefinition = (new ServiceDefinition('test.service'))->setClassName(TestService::class);

            // change handled services definition
            $this->setObjectAttribute($this->instance, 'handledDefinitions', ['Whatever']);

            $this->assertFalse($this->instance->doesHandle($serviceDefinition));

            $this->expectsException(function() use ($serviceDefinition) {
                $this->instance->build($serviceDefinition);
            }, Exception::class, null, Exception::INCOMPATIBLE_SERVICE_DEFINITION);

        }

        public function testSimpleServiceIsBuilt()
        {
            $serviceDefinition = (new ServiceDefinition('test.service'))->setClassName(TestService::class);

            $this->assertTrue($this->instance->doesHandle($serviceDefinition));

            $service = $this->instance->build($serviceDefinition);

            $this->assertInstanceOf(TestService::class, $service);

            $serviceDefinition->setParams(['first' => 'x', 'second' => 'y']);

            // add params to service definition
            $service = $this->instance->build($serviceDefinition);
            $this->assertInstanceOf(TestService::class, $service);
            $this->assertAttributeEquals(Collection::cast(['arg1' => 'x', 'arg2' => 'y']), 'args', $service);

            // override service definition params at runtime
            $service = $this->instance->build($serviceDefinition, ['first' => 'OVERRIDDEN']);
            $this->assertInstanceOf(TestService::class, $service);
            $this->assertAttributeEquals(Collection::cast(['arg1' => 'OVERRIDDEN', 'arg2' => 'y']), 'args', $service);

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