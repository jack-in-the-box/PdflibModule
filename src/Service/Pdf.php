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
    protected $currentTemplatePageNumber;
    /**
     * @var integer
     */
    protected $currentTemplatePage;
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

    /**
     * @var integer
     */
    public $starttop;
    /**
     * @var integer
     */
    public $startbot;
    /**
     * @var integer
     */
    public $startleft;
    /**
     * @var integer
     */
    public $startright;

    public function __construct($license)
    {
        parent::__construct($license);

        $this->infile = 0;
        $this->outfile = 0;
        $this->currentTemplatePage = 0;
        $this->inpath = '';
        $this->outpath = '';
        $this->currentTemplatePageNumber = 0;
        $this->textFlow = 0;
        $this->set_option('errorpolicy=return');
        $this->set_option('stringformat=utf8');
        $this->set_option('escapesequence=true');
        $this->templateWidth = 0;
        $this->templateHeight = 0;
        $this->startleft = 0;
        $this->startright = 0;
        $this->starttop = 0;
        $this->startbot = 0;
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
        foreach ($infos as $info => $value) {
            $this->set_info($info, $value);
        }
    }

    /**
     * Set options
     * @param   Array $infos
     * @return  void
     */
    public function setOptions($options)
    {
        foreach ($options as $option => $value) {
            $this->set_option($option.'='.$value);
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
     * Open a specific page from current template
     * @param  integer $pageNumber
     * @param  string $optlist (table 8.3 from the api document)
     * @return void
     */
    public function setCurrentTemplatePage($pageNumber, $optlist = '')
    {
        // Close last page until we handle pages in an array
        if ($this->currentTemplatePage != 0) {
            $this->close_pdi_page($this->currentTemplatePage);
        }

        $this->currentTemplatePageNumber = $pageNumber;
        $this->currentTemplatePage = $this->open_pdi_page($this->infile, $pageNumber, $optlist);
        if ($this->currentTemplatePage == 0) {
            throw new PDFlibException($this->getErrMsg());
        }
    }

    /**
     * @return integer
     */
    public function getCurrentTemplatePage()
    {
        return $this->currentTemplatePage;
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
            throw new PDFlibException('Error: Does not contain any PDFlib blocks');
        }
        for ($i = 0; $i < $blockcount; $i++) {
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
     * @param  string   $mode
     * @return string
     */
    public function getPropertyFromBlock($name, $property, $pagenumber = 0, $mode = 'string')
    {
        $pagenumber = ($pagenumber != 0 ? $pagenumber : $this->currentTemplatePage - 1);
        if ($mode === 'number') {
            return $this->pcos_get_number($this->infile, 'length:pages[0]/blocks[' . $this->getBlockNumberFromName($name) . ']/'. $property);
        }
        return $this->pcos_get_string($this->infile, 'pages[0]/blocks[' . $this->getBlockNumberFromName($name) . ']/'. $property);
    }

    /**
     * Return property from template
     * @param  string $property
     * @return string
     */
    public function getPropertyFromTemplate($property)
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
        $this->fit_pdi_page($this->currentTemplatePage, $x, $y, $optlist);
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
            $this->textFlow = $this->fill_textblock($this->currentTemplatePage, $block, $value, $optlist);
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
            $this->fill_imageblock($this->currentTemplatePage, $block, $image, '');
        }
    }

    /**
     * Allways call this method before a new call to addNewPageForDocument
     * @return void
     */
    public function closeCurrentPageFromDocument()
    {
        //if ($this->currentPage != 0) {
            $this->end_page_ext('');
        //}
    }

    /**
     * Allways call this method before a new call to addNewPageForDocument
     * @return void
     */
    public function closeCurrentPageFromTemplate()
    {
        $this->closeCurrentPageFromDocument();
        $this->close_pdi_page($this->currentTemplatePage);
    }

    /**
     * Cleans document before closing it
     * @return void
     */
    public function endDocument()
    {
        if ($this->getCurrentScope() === "page") {
            $this->closeCurrentPageFromDocument();
        }
        $this->end_document('');
    }

    /**
     * Close current page and template
     * @return void
     */
    public function closeTemplate()
    {
        $this->close_pdi_document($this->infile);
        $this->currentTemplatePage = 0;
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

    /**
     * drawLine draw a line
     * @param  double $startx
     * @param  double $starty
     * @param  double $endx
     * @param  double $endy
     * @return void
     */
    public function drawLine($startx, $starty, $endx, $endy)
    {
        $this->moveto($startx, $starty);
        $this->lineto($endx, $endy);
        $this->stroke();
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
        $this->startbot = $bot;
        $this->startleft = $left;
        $this->startright = $right;
    }

    /**
     * Return values of images properties
     * @param  pdflib_image
     * @return array properties
     */
    public function getImageInfo($image)
    {
        $width = $this->info_image($image, 'imagewidth', '');
        $height = $this->info_image($image, 'imageheight', '');
        $filename = $this->get_string($this->info_image($image, 'filename', ''), '');
        $imagetype = $this->get_string($this->info_image($image, 'imagetype', ''), '');
        $dpix = $this->info_image($image, 'resx', '');
        $dpiy = $this->info_image($image, 'resy', '');
        $dpix = ($dpix <= 0) ? 72 : $dpix;
        $dpiy = ($dpiy <= 0) ? 72 : $dpiy;

        $ratio = $width / $height;
        return compact(
            'width',
            'height',
            'filename',
            'imagetype',
            'imagetype',
            'dpix',
            'dpiy',
            'ratio'
        );
    }

    /**
     * Create an image
     * If you want to align your image, you need to set constants HEIGHT and WIDTH corresponding to your page in your class
     * @param  string $path         filename
     * @param  double $x           x
     * @param  double $y           y
     * @param  array  $customValues height, width, fitmethod, nomargin
     * @param  array  $alignement   center, top, right, left, bottom
     * @return array  right-bottom corner position (x,y)
     */
    public function createImage($path, $x, $y, array $customValues = array(), array $alignement = array())
    {
        $image = $this->load_image("auto", $path, "");
        $infos = $this->getImageInfo($image);
        if (!isset($customValues['height']) && !isset($customValues['width'])) {
            $customValues['height'] = ($infos['height'] / $infos['dpix']) * 72;
            $customValues['width'] = ($infos['width'] / $infos['dpiy']) * 72;
        } else if (!isset($customValues['width'])) {
            $customValues['width'] = $customValues['height'] * $infos['ratio'];
        } else if (!isset($customValues['height'])) {
            $customValues['height'] = $customValues['width'] / $infos['ratio'];
        } else {
            $customValues['height'] = $customValues['width'] / $infos['ratio'];
        }
        $fitmethod = (!isset($customValues['fitmethod'])) ? 'fitmethod=entire' : 'fitmethod='.$customValues['fitmethod'];
        $buf = 'boxsize={'.$customValues['width'].' '.$customValues['height'].'} '.$fitmethod;
        $align = $this->alignImage($customValues['width'], $this->get_option('pagewidth', ''), $alignement);
        if (!in_array('nomargin', $customValues)) {
            $x += $this->startleft;
            $y += $this->starttop;
        }
        $x += $align[0];
        $y += $align[1];
        // Le + $customValues['height'] sert à placer le point d'origine en haut à gauche
        // de l'image au lieu du point en bas à gauche qui est le comportement par défaut.
        $this->fit_image($image, $x, $y + $customValues['height'], $buf);
        return array(
            'x' => $x + $customValues['width'],
            'y' => $y + $customValues['height'],
            );
    }

    private function alignImage($imageWidth, $containerWidth, array $alignement = array())
    {
        $x = 0;
        $y = 0;
        if (in_array('center', $alignement)) {
            $x = (1/2 * $containerWidth) - (1/2 * $imageWidth);
        }
        if (in_array('left', $alignement)) {
            $x = 0;
        }
        if (in_array('right', $alignement)) {
            $x = (2/3 * $containerWidth) - (1/2 * $imageWidth);
        }
        return array($x, $y);
    }
}
