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

class Pdf extends Pdflib
{
    /**
     * File descriptors for input manipulations
     * @var integer
     */
    protected $infile;
    /**
     * File descriptors for output manipulations
     * @var integer
     */
    protected $outfile;

    /**
     * File inpath
     * @var string
     */
    protected $inpath;
    /**
     * File oupath
     * @var string
     */
    protected $outpath;

    /**
     * @var integer
     */
    protected $currentPageNumber;
    /**
     * @var integer
     */
    protected $currentPage;
    /**
     * @var integer
     */
    protected $textFlow;
    /**
     * @var integer
     */
    protected $templateWidth;
    /**
     * @var integer
     */
    protected $templateHeight;

    public function __construct($license)
    {
        parent::__construct($license);

        $this->infile = 0;
        $this->outfile = 0;
        $this->currentPage = 0;
        $this->inpath = '';
        $this->outpath = '';
        $this->currentPageNumber = 0;
        $this->textFlow = 0;
        $this->set_option('errorpolicy=return');
        $this->set_option('stringformat=utf8');
        $this->set_option('escapesequence=true');
        $this->templateWidth = 0;
        $this->templateHeight = 0;
    }

    /**
     * Get the last occurred error from pdflib and return it as a string
     * @see pdflib::get_errmsg()
     * @return string
     */
    public function getErrMsg()
    {
        return 'Error: ' + $this->get_errmsg();
    }

    /**
     * Set metadata
     * Creator, Author, Title, etc...
     * @param   Array $infos
     * @return  void
     */
    public function setInfos($infos)
    {
        foreach ($infos as $info => $value)
        {
            $this->set_info($info, $value);
        }
    }

    /**
     * Open document with its path and options
     * @param  string $path
     * @param  string $optlist (table 2.3 from the api document)
     * @return void
     */
    public function openDocument($path, $optlist = '')
    {
        $this->outpath = $path;
        if (($this->outfile = $this->begin_document($path, $optlist)) == 0) {
            throw new PDFlibException($this->getErrMsg());
        }
    }

    /**
     * Open template with its path and options
     * @param  string $path
     * @param  string $optlist (table 8.2 from the api document)
     * @return void
     */
    public function openTemplate($path, $optlist = '')
    {
        $this->inpath = $path;
        if (($this->infile = $this->open_pdi_document($path, $optlist)) == 0) {
            throw new PDFlibException($this->getErrMsg());
        }
        $this->templateWidth = $this->pcos_get_number($this->infile, 'pages[0]/width');
        $this->templateHeight = $this->pcos_get_number($this->infile, 'pages[0]/height');
    }

    /**
     * @return string path
     */
    public function getInPath()
    {
        return $inpath;
    }
    /**
     * @return string path
     */
    public function getOutPath()
    {
        return $outpath;
    }

    /**
     * [TODO] save all pages in an array to limit open calls
     * Open a specific page from current template
     * @param  integer $pageNumber
     * @param  string $optlist (table 8.3 from the api document)
     * @return void
     */
    public function setCurrentPage($pageNumber, $optlist = '')
    {
        // Close last page until we handle pages in an array
        if ($this->currentPage != 0) {
            $this->close_pdi_page($this->currentPage);
        }

        $this->currentPageNumber = $pageNumber;
        $this->currentPage = $this->open_pdi_page($this->infile, $pageNumber, $optlist);
        if ($this->currentPage == 0) {
            throw new PDFlibException($this->getErrMsg());
        }
    }

    /**
     * @return integer
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Used for getPropertyFromBlock
     * TODO : save all blocks in an array ?
     * TODO : check if a method from pdflib already exists
     * @param  string $name
     * @return integer
     */
    public function getBlockNumberFromName($name)
    {
        $blockcount = $this->pcos_get_number($this->infile, 'length:pages[0]/blocks');
        if ($blockcount == 0) {
            throw new Exception('Error: Does not contain any PDFlib blocks');
        }
        for ($i = 0; $i < $blockcount; $i++)
        {
            $blockname = $this->pcos_get_string($this->infile, 'pages[0]/blocks[' . $i . ']/Name');
            if ($blockname == $name) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * Return property from block
     * @param  string   $name
     * @param  string   $property
     * @param  integer  $pagenumber
     * @return string
     */
    public function getPropertyFromBlock($name, $property, $pagenumber = 0)
    {
        //$pagenumber = ($pagenumber != 0 ? $pagenumber : $this->currentPage);
        return $this->pcos_get_string($this->infile, 'pages[0]/blocks[' . $this->getBlockNumberFromName($name) . ']/'. $property);
    }

    /**
     * Return property from document
     * @param  string $property
     * @return string
     */
    public function getPropertyFromDocument($property)
    {
        return $this->pcos_get_string($this->infile, $property);
    }

    /**
     * Set template to the output document
     * @param   string $optlist Can be 'blind' to hide images/nonblocks
     * @return void
     */
    public function initTemplate($x = 0, $y = 0, $optlist = '')
    {
        if ($x == 0 && $y == 0) {
            $y = $this->templateHeight;
        }
        $this->fit_pdi_page($this->currentPage, $x, $y, $optlist);
    }

    /**
     * Allways close last page from document before this method
     * @return void
     */
    public function addNewPageForDocument($templateSize, $width = 0, $height = 0)
    {
        if ($templateSize == true) {
            $width = $this->templateWidth;
            $height = $this->templateHeight;
        }
        // Add a new page to the document
        $this->begin_page_ext($width, $height, '');
    }

    /**
     * TODO : apply custom font
     * fill text blocks from an array of strings and blocknames
     * @param  array $blocks 'blockame' => 'content'
     * @return void
     */
    public function fillTextBlocks($blocks)
    {
        // Override Block properties
        $optlist = 'fontname=Helvetica-Bold encoding=unicode';// textflowhandle=' . $this->textFlow;
        foreach ($blocks as $block => $value) {
            $this->textFlow = $this->fill_textblock($this->currentPage, $block, $value, $optlist);
        }
        //$this->deleteTextFlow();
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
            $this->fill_imageblock($this->currentPage, $block, $image, '');
        }
    }

    /**
     * Allways call this method before a new call to addNewPageForDocument
     * @return void
     */
    public function closeCurrentPageFromDocument()
    {
        $this->end_page_ext('');
    }

    /**
     * Allways call this method before a new call to addNewPageForDocument
     * @return void
     */
    public function closeCurrentPageFromTemplate()
    {
        $this->closeCurrentPageFromDocument();
        $this->close_pdi_page($this->currentPage);
    }

    /**
     * Cleans document before closing it
     * @return void
     */
    public function endDocument()
    {
        $this->closeCurrentPageFromDocument();
        $this->end_document('');       
    }

    /**
     * Close current page and template
     * @return void
     */
    public function closeTemplate()
    {
        $this->close_pdi_document($this->infile);
        $this->currentPage = 0;
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
    private function getCurrentScope()
    {
        return $this->get_string($this->get_option('scope', ''), '');
    }
}
