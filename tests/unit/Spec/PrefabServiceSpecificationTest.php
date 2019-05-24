<?php
/**
 * This file is part of the Objective PHP project
 *
 * More info about Objective PHP on www.objective-php.org
 *
 * @license http://opensource.org/licenses/GPL-3.0 GNU GPL License 3.0
 */

namespace Tests\ServicesFactory\Spec;


use Codeception\Test\Unit;
use ObjectivePHP\ServicesFactory\Specification\PrefabServiceSpecification;

class PrefabServiceSpecificationTest extends Unit
{
    public function testAliasing()
    {
        $specs = new PrefabServiceSpecification('service.test', new \stdClass());
        $this->assertEquals([\stdClass::class], $specs->getAutoAliases());

    }
}
