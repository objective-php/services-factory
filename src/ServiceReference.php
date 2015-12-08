<?php

namespace ObjectivePHP\ServicesFactory;


class ServiceReference
{

    /**
     * @var string Referenced service id (or alias)
     */
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function __toString()
    {
        return (string) $this->id;
    }

}
