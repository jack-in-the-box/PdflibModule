<?php
/**
 * Base class for integration of Pdflib projects with ZF2 applications
 *
 * @license MIT
 * @link    http://www.jack.fr/
 * @author  Corentin Deniaud <cdeniaud@jack-ext.fr>
 */

namespace Jitb\PdflibModule\Service;

use \Pdflib as Pdflib;
use \PDFlibException as PDFlibException;

use Jitb\PdflibModule\Service\Document;
use Jitb\PdflibModule\Service\Template;

class Pdf extends Pdflib
{

    /** @var integer */
    protected $textFlow;

    /** @var Document */
    public $document;

    /** @var Template */
    public $template;

    /** @var string */
    public $currentColumn;

    public function __construct($license)
    {
        parent::__construct($license);

        $this->textFlow = 0;
        $this->setOptions(array(
            'errorpolicy' => 'return',
            'stringformat' => 'utf8',
            'escapesequence' => 'true',
        ));
        $this->document = new Document($this);
        $this->template = new Template($this);
        $this->currentColumn = 0;
    }

    /**
     * Get the last occurred error from pdflib and return it as a string
     * @see pdflib::get_errmsg()
     * @return string
     */
    public function getErrMsg()
    {
        return 'Error: ' . $this->get_errmsg();
    }

    /**
     * Set options
     * @param   Array $options
     * @return  void
     */
    public function setOptions(array $options)
    {
        foreach ($options as $option => $value) {
            $this->set_option($option.'='.$value);
        }
    }

    /**
     * fill text blocks from an array of strings and blocknames
     * @param  array $blocks 'blockame' => 'content'
     * @param  string $optlist options
     * @return void
     */
    public function fillTextBlocks($blocks, $optlist = '')
    {
        // Override Block properties
        if ($optlist === '') {
            $optlist = 'fontname=Helvetica-Bold encoding=unicode';
        }
        foreach ($blocks as $block => $value) {
            $this->textFlow = $this->fill_textblock($this->template->currentPage, $block, $value, $optlist);
        }
    }

    /**
     * fill images blocks from an array of paths and blocknames
     * @param  array $blocks 'blockame' => 'path'
     * @return void
     */
    public function fillImageBlocks($blocks)
    {
        foreach ($blocks as $block => $value) {
            $image = $this->load_image('auto', $value, '');
            if ($image == 0) {
                trigger_error('Warning ImageBlocks: ' . $this->getErrMsg());
            }
            $this->fill_imageblock($this->template->currentPage, $block, $image, '');
        }
    }

    /**
     * Must be called in a fill_text_block context
     * @return void
     */
    private function deleteTextFlow()
    {
        if ($this->textFlow === 0) {
            trigger_error('Warning TextFlow: ' . $this->getErrMsg());
            return false;
        }
        $this->delete_textflow($this->textFlow);
        return true;
    }

    /**
     * return current scope for debugging
     * @return string
     */
    public function getCurrentScope()
    {
        return $this->get_string($this->get_option('scope', ''), '');
    }
}
