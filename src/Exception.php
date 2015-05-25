<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 20/05/15
 * Time: 17:59
 */

namespace ObjectivePHP\ServicesFactory;


class Exception extends \Exception
{
    // services definitions
    const INVALID_SERVICE_DEFINITION = 0x10;
    const INCOMPATIBLE_SERVICE_DEFINITION = 0x11;
}