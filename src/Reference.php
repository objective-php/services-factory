<?php

namespace ObjectivePHP\ServicesFactory;


class Reference
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

}
