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
    // services specifications
    const INVALID_SERVICE_SPECS = 0x10;
    const INCOMPATIBLE_SERVICE_DEFINITION = 0x11;
    const INCOMPLETE_SERVICE_SPECS = 0x12;
    const UNKNOWN_SERVICE_SPECS = 0x13;
    const AMBIGUOUS_SERVICE_SPECS = 0x14;

    // run-time services related errors
    const UNREGISTERED_SERVICE_REFERENCE = 0x20;
    const FINAL_SERVICE_OVERRIDING_ATTEMPT = 0x21;


}