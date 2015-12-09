<?php

    
    namespace ObjectivePHP\ServicesFactory\Exception;
    
    

    class ServiceNotFoundException extends Exception
    {
        const UNREGISTERED_SERVICE_REFERENCE = 0x20;
    }