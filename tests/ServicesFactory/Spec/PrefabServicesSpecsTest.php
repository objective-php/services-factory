<?php
/**
 * This file is part of the Objective PHP project
 *
 * More info about Objective PHP on www.objective-php.org
 *
 * @license http://opensource.org/licenses/GPL-3.0 GNU GPL License 3.0
 */

namespace Tests\ServicesFactory\Spec;


use ObjectivePHP\ServicesFactory\Specs\PrefabServiceSpecs;

class PrefabServicesSpecsTest extends \PHPUnit_Framework_TestCase
{
    public function testAutoAliasing()
    {
        $specs = new PrefabServiceSpecs('service.test', new \stdClass());
        $this->assertEquals(['\stdClass'], $specs->getAliases());
        
        $specs->disableAutoAliasing();
        $this->assertEquals([], $specs->getAliases());
    }
}
