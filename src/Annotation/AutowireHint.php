<?php


namespace ObjectivePHP\ServicesFactory\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class AutowireHint
{

    /** @Required() */
    public $mapping;


}
