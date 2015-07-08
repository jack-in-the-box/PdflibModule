<?php

namespace Jitb\PdflibModule\Service;

use \PDFlibException as PDFlibException;

class Document
{
    /** @var Pdf */
    protected $pdf;

    /** @var integer */
    public $startleft;

    /** @var integer */
    public $startright;

    /** @var integer */
    public $starttop;

    /** @var integer */
    public $startbottom;

    /** @var integer */
    public $currentColumn;

     /** @var integer filedescriptor*/
    public $fd;

    /** @var string filepath*/
    public $path;

    public function __construct($pdf)
    {
        $this->pdf = $pdf;
        $this->startleft = 0;
        $this->startright = 0;
        $this->starttop = 0;
        $this->startbottom = 0;
        $this->currentColumn = 0;
        $this->fd = 0;
        $this->path = '';
    }

    /**
     * Destructor : close document
     */
    public function __destruct()
    {
        if ($this->fd != 0 && $this->pdf->getCurrentScope() != 'object') {
            $this->close();
        }
    }

    /**
     * Set metadata
     * Creator, Author, Title, etc...
     * @param   Array $infos
     * @return  void
     */
    public function setInfos($infos)
    {
        foreach ($infos as $info => $value) {
            $this->pdf->set_info($info, $value);
        }
    }

    /**
     * Open document with its path and options
     * @param  string $path
     * @param  string $optlist (table 2.3 from the api document)
     * @return void
     */
    public function open($path, $optlist = '')
    {
        $this->path = $path;
        if (($this->fd = $this->pdf->begin_document($this->path, $optlist)) == 0) {
            throw new PDFlibException($this->pdf->getErrMsg());
        }
    }

    /**
     * Allways close last page from document before this method
     * @return void
     */
    public function newPage($height = 0, $width = 0, $optlist = '')
    {
        if ($width == 0 && $height == 0 && $optlist == '') {
            $optlist = "width=a4.width height=a4.height";
        }
        $this->pdf->begin_page_ext($height, $width, $optlist);
    }

    /**
     * Allways call this method before a new call to addNewPageForDocument
     * @return void
     */
    public function closeCurrentPage()
    {
        $this->pdf->end_page_ext('');
    }

    /**
     * Cleans document before closing it
     * @return void
     */
    public function close()
    {
        if ($this->pdf->getCurrentScope() === "page") {
            $this->closeCurrentPage();
        }
        $this->pdf->end_document('');
    }

    /**
     * setStartPos Set positions where the content can be print (border + margin)
     * @param integer $top
     * @param integer $bot
     * @param integer $left
     * @param integer $right
     */
    public function setStartPos($top, $bot, $left, $right)
    {
        $this->starttop = $top;
        $this->startbottom = $bot;
        $this->startleft = $left;
        $this->startright = $right;
    }

    /**
     * setCurrentColumn
     * @param integer $columnNumber
     */
    public function setCurrentColumn($columnNumber = 0)
    {
        $this->currentColumn = $columnNumber;
    }

     /**
     * Set template to the output document (for updown mode, set y to template height)
     * @param   string $optlist Can be 'blind' to hide images/nonblocks
     * @return void
     */
    public function setTemplate($templatePage, $x = 0, $y = 0, $optlist = '')
    {
        $this->pdf->fit_pdi_page($templatePage, $x, $y, $optlist);
    }
}
