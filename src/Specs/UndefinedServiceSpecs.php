<?php
    /**
     * This file is part of the Objective PHP project
     *
     * More info about Objective PHP on www.objective-php.org
     *
     * @license http://opensource.org/licenses/GPL-3.0 GNU GPL License 3.0
     */
    
    namespace ObjectivePHP\ServicesFactory\Specs;
    
    
    use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;

    class UndefinedServiceSpecs extends AbstractServiceSpecs
    {


        /**
         * @param       $id
         * @param array $params
         *
         */
        public function __construct($id, $params = [])
        {
            parent::__construct($id);

            $this->setParams($params);
        }

        static function factory($rawDefinition)
        {
            $id = $rawDefinition['id'];
            unset($rawDefinition['id']);
            $params = $rawDefinition;


            return new static($id, $params);
        }

    }
