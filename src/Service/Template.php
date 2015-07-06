<?php

namespace Jitb\PdflibModule\Service;

use \PDFlibException as PDFlibException;

class Template
{
    /** @var Pdf */
    protected $pdf;

    /** @var integer */
    public $height;

    /** @var integer */
    public $width;

    /** @var integer */
    public $currentPageNumber;

    /** @var integer */
    public $currentPage;

     /** @var integer filedescriptor*/
    public $fd;

    /** @var string filepath*/
    public $path;

    /**
     * Init and open template
     * @param Pdf $pdf          pdf instance
     * @param string $path path to pdf template file
     */
    public function __construct($pdf)
    {
        $this->pdf = $pdf;
        $this->path = '';
        $this->fd = 0;
        $this->width = 0;
        $this->height = 0;
        $this->currentPage = 0;
        $this->currentPageNumber = 0;
    }

    /**
     * Destructor : close template
     */
    public function __destruct()
    {
        if ($this->fd != 0 && $this->pdf->getCurrentScope() != 'object') {
            $this->close();
        }
    }

    /**
     * Open template
     * @param  string $optlist (table 8.2 from the api document)
     * @return void
     */
    public function open($path, $optlist = '')
    {
        $this->path = $path;
        if (($this->fd = $this->pdf->open_pdi_document($this->path, $optlist)) == 0) {
            throw new PDFlibException($this->pdf->getErrMsg());
        }
        $this->width = $this->getWidth();
        $this->height = $this->getHeight();
    }
    
    /**
     * Open a specific page
     * @param  integer $pageNumber
     * @param  string $optlist (table 8.3 from the api document)
     * @return currentPage handler
     */
    public function setCurrentPage($pageNumber, $optlist = '')
    {
        $this->currentPageNumber = $pageNumber;
        $this->currentPage = $this->pdf->open_pdi_page($this->fd, $pageNumber, $optlist);
        if ($this->currentPage === 0) {
            throw new PDFlibException($this->pdf->getErrMsg());
        }
        return $this->currentPage;
    }

    /**
     * @return integer
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @return void
     */
    public function closeCurrentPage()
    {
        $this->pdf->close_pdi_page($this->currentPage);
    }

    /**
     * Close current page and template
     * @return void
     */
    public function close()
    {
        if ($this->pdf->getCurrentScope() === "page") {
            $this->closeCurrentPage();
        }
        $this->pdf->close_pdi_document($this->fd);
    }

    /**
     * Used for getPropertyFromBlock
     * TODO : save all blocks in an array ?
     * TODO : check if a method from pdflib already exists
     * @param  string $name
     * @return integer
     */
    public function getBlockNumberFromName($name, $pageNumber = 0)
    {
        $blockcount = $this->pdf->pcos_get_number($this->fd, 'length:pages['.$pageNumber.']/blocks');
        if ($blockcount == 0) {
            throw new PDFlibException('Error: Does not contain any PDFlib blocks');
        }
        for ($i = 0; $i < $blockcount; $i++) {
            $blockname = $this->pdf->pcos_get_string($this->fd, 'pages['.$pageNumber.']/blocks[' . $i . ']/Name');
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
     * @param  string   $mode
     * @return string
     */
    public function getPropertyFromBlock($name, $property, $pagenumber = 0, $mode = 'string')
    {
        $pagenumber = ($pagenumber != 0 ? $pagenumber : $this->currentPage);
        if ($mode === 'number') {
            if ($name === '') {
                return $this->pdf->pcos_get_number($this->fd, 'length:pages['.$pagenumber.']/blocks');
            }
            return $this->pdf->pcos_get_number($this->fd, 'length:pages['.$pagenumber.']/blocks[' . $this->getBlockNumberFromName($name) . ']/'. $property);
        }
        return $this->pdf->pcos_get_string($this->fd, 'pages['.$pagenumber.']/blocks[' . $this->getBlockNumberFromName($name) . ']/'. $property);
    }

    /**
     * Return property from template
     * @param  string $property
     * @return string
     */
    public function getProperty($property, $mode = 'string')
    {
        if ($mode == 'number') {
            return $this->pdf->pcos_get_number($this->fd, $property);
        }
        return $this->pdf->pcos_get_string($this->fd, $property);
    }

    /**
     * get custom properties from template file
     * @param  string  $blockname
     * @param  integer $pageNumber
     * @return array   custom properties
     */
    public function getCustomProperties($blockname, $pageNumber = 0)
    {
        $customBlocksNumber = $this->getPropertyFromBlock($blockname, 'Custom', $pageNumber, 'number');
        for ($i = 0; $i < $customBlocksNumber; ++$i) {
            $key = $this->getPropertyFromBlock($blockname, 'Custom['.$i.'].key');
            $val = $this->getPropertyFromBlock($blockname, 'Custom['.$i.'].val');
            $blocks[$key] = $val;
        }
        return $blocks;
    }

    /**
     * template width in pt
     * @param  integer $pageNumber
     * @return integer
     */
    public function getWidth($pageNumber = 0)
    {
        return $this->pdf->pcos_get_number($this->fd, 'pages['.$pageNumber.']/width');
    }

    /**
     * template height in pt
     * @param  integer $pageNumber
     * @return integer
     */
    public function getHeight($pageNumber = 0)
    {
        return $this->pdf->pcos_get_number($this->fd, 'pages['.$pageNumber.']/height');
    }
}
