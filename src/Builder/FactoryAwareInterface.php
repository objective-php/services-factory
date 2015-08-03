<?php

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\ServicesFactory\ServicesFactory;

interface FactoryAwareInterface
{

    public function setFactory(ServicesFactory $factory);

    public function getFactory();

}