<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 01/08/15
 * Time: 19:08
 */

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

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }


}