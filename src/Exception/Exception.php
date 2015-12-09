<?php

namespace ObjectivePHP\ServicesFactory\Exception;


use Interop\Container\Exception\ContainerException;

class Exception extends \Exception implements ContainerException
{
    // services specifications
    const INVALID_SERVICE_SPECS = 0x10;
    const INCOMPATIBLE_SERVICE_DEFINITION = 0x11;
    const INCOMPLETE_SERVICE_SPECS = 0x12;
    const UNKNOWN_SERVICE_SPECS = 0x13;
    const AMBIGUOUS_SERVICE_SPECS = 0x14;

    // run-time services related errors
    const FINAL_SERVICE_OVERRIDING_ATTEMPT = 0x21;


}