<?php

    
    namespace ObjectivePHP\ServicesFactory\Exception;
    
    

    class ServiceNotFoundException extends ServicesFactoryException
    {
        const UNREGISTERED_SERVICE_REFERENCE = 0x20;
    }