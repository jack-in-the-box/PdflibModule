<?php
/**
 * Base module for integration of Pdflib projects with ZF2 applications
 *
 * @license MIT
 * @link    http://www.jack.fr/
 * @author  Martin Supiot <msupiot@jack.fr>
 */

namespace Jitb\PdflibModule\Service;

use RuntimeException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use PDFlib as PdfLib;

/**
 * Return the Pdflib options
 */
abstract class AbstractServiceFactory implements FactoryInterface
{
    /**
     * @var \Zend\Stdlib\AbstractOptions
     */
    protected $options;

    /**
     * @param ServiceLocatorInterface $sl
     * @param string $key
     * @param null|string $name
     * @return \Zend\Stdlib\AbstractOptions
     * @throws \RuntimeException
     */
    public function getPdflibOptions(ServiceLocatorInterface $sl)
    {
        $options = $sl->get('Config');
        $options = $options['pdflib'];

        if (null === $options) {
            throw new RuntimeException('Options could not be found in "pdflib".');
        }

        $pdflibOptionsClass = $this->getOptionsClass();

        return new $pdflibOptionsClass($options);
    }

    /**
     * @abstract
     * @return string
     */
    abstract public function getOptionsClass();
}
