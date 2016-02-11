<?php
    /**
     * This file is part of the Objective PHP project
     *
     * More info about Objective PHP on www.objective-php.org
     *
     * @license http://opensource.org/licenses/GPL-3.0 GNU GPL License 3.0
     */
    
    namespace ObjectivePHP\ServicesFactory;


    /**
     * Interface ServicesFactoryAwareInterface
     *
     * @package ObjectivePHP\ServicesFactory
     */
    interface ServicesFactoryAwareInterface
    {
        /**
         * @param ServicesFactory $servicesFactory
         *
         * @return $this
         */
        public function setServicesFactory(ServicesFactory $servicesFactory);
    }
