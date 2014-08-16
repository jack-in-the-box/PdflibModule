<?php
/**
 * Base module for integration of Pdflib projects with ZF2 applications
 *
 * @license MIT
 * @link    http://www.jack.fr/
 * @author  Martin Supiot <msupiot@jack.fr>
 */

namespace PdflibModule\Options;

use Zend\Stdlib\AbstractOptions;

/**
 * Options for Pdflib connection
 */
class Configuration extends AbstractOptions
{
    /**
     * @var string
     */
    protected $license = null;

    /**
     * @param string $license
     */
    public function setLicense($license)
    {
        $this->license = $license;
    }

    /**
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }
}
