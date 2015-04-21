<?php
/**
 * Base module for integration of Pdflib projects with ZF2 applications
 *
 * @license MIT
 * @link    http://www.jack.fr/
 * @author  Martin Supiot <msupiot@jack.fr>
 */

namespace Jitb\PdflibModule\Service;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Initiate a pdflib service
 */
class PdflibServiceFactory extends AbstractServiceFactory
{
    /**
     * @return Pdflib\
     */
    public function createService(ServiceLocatorInterface $sl)
    {
        $license = $this->getPdflibOptions($sl)->getLicense();
        $pdf = new Pdf($license);
        return $pdf;
    }

    /**
     * @return string
     */
    public function getOptionsClass()
    {
        return 'Jitb\PdflibModule\Options\Configuration';
    }
}
