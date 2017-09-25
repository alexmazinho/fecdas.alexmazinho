<?php
namespace FecdasBundle\Classes;


if (!defined('K_PATH_IMAGES')) {
	define ('K_PATH_IMAGES', __DIR__.'/../../../web/images/');
}
/**
* TCPDF Bridge 
*/
include_once (__DIR__.'/../../../vendor/tecnickcom/tcpdf/tcpdf.php');


class TcpdfBridge extends \TCPDF {
	protected $pagenum;
	protected $rightheader;
	protected $footer;
	
    public function init($params = null, $pagenum = false, $rightheader = "", $footer= "")
    {
    	// set document information
    	$this->SetCreator(PDF_CREATOR);
    	$this->SetAuthor($params['author']);
    	$this->SetTitle($params['title']);
    	$this->SetSubject($params['title']);
    	
    	$this->pagenum = $pagenum;
    	$this->rightheader = $rightheader;
		$this->footer = $footer;
    	//$this->SetKeywords('TCPDF, PDF, example, test, guide');
    	
    	// set default header data
    	$this->SetHeaderData("fecdaslogopdf.gif", 19, "Federació Catalana d`Activitats Subaquàtiques", $params['title']);
    	
    	// set header and footer fonts
    	$this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    	$this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    	
    	// set default monospaced font
    	$this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    	
    	//set margins
    	$this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    	$this->SetHeaderMargin(PDF_MARGIN_HEADER);
    	$this->SetFooterMargin(PDF_MARGIN_FOOTER);
    	
    	//set auto page breaks
    	$this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    	
    	//set image scale factor
    	$this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    	
        // set default font subsetting mode
        $this->setFontSubsetting(true);
        
        //Document Encryption / Security. http://www.tcpdf.org/examples/example_016.phps
        //$this->SetProtection(array('modify', 'copy'), '', null, 2, null);
    }
    
    public function Header() {
    	parent::Header();
    	if ($this->rightheader != "") {
    		/*$this->SetY(PDF_MARGIN_HEADER + 2.6);
			// Cell( $w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' )
    		$this->Cell(0, 0, $this->rightheader, 0, true, 'R', 0, '', 0, false, 'C', 'C');*/
			
			// ( $w, $h, $x, $y, $html = '', $border = 0, $ln = 0, $fill = false, $reseth = true, $align = '', $autopadding = true )
			$this->writeHTMLCell(0, 0, 0, PDF_MARGIN_HEADER, $this->rightheader, 0, true, 0, true, 'R', true);

			$this->SetFont('dejavusans', 'I', 8, '', true);
			/*$this->SetY(PDF_MARGIN_HEADER + 7);
			$this->Cell(0, 0, date("d/m/Y"), 0, false, 'R', 0, '', 0, false, 'C', 'C');*/
			$this->writeHTMLCell(0, 0, 0, $this->getY(), date("d/m/Y"), 0, true, 0, true, 'R', true);
		}
    }
    
    
    public function Footer() {
		
		if ($this->footer != "" && method_exists($this,$f=$this->footer)) {
			// Comprovar si existeix el mètode Footer concret
			
			call_user_func(array($this,$f));

		} else {
    		// Position at 15 mm from bottom
    		$this->SetY(-15);	
    		
    		$this->footer = '<a href="http://www.fecdas.cat">FECDAS</a> - FEDERACIÓ CATALANA D\'ACTIVITATS SUBAQUÀTIQUES - NIF: Q5855006B<br/>';
    		$this->footer .= 'Moll de la Vela 1 (Zona Fòrum) - 08930 Sant Adrià de Besòs<br/>';
    		$this->footer .= 'Tel: 93 356 05 43  Fax: 93 356 30 73 Adreça electrònica: info@fecdas.cat';

	    	$this->writeHTMLCell('', '', '', '', $this->footer, 0, 0, 0, true, 'C', true);
    	
    		// Page number
    		if ($this->pagenum == true) $this->Cell(0, 10, 'Pàgina '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');

		}
    	
    }
	
	public function footerActaCurs() {
    	$this->SetY(-24);
		// 	Image ($file, $x='', $y='', 
		//			$w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, 
		// 			$palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, 
		//			$hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
		
		$this->Image(K_PATH_IMAGES.'logo_fedas.jpg', PDF_MARGIN_LEFT, $this->getY(), 0, 13, 'jpg', '', 'CT', false, 150, '', false, false, array(), 'LT', false, false, false, array());
		$this->Image(K_PATH_IMAGES.'gene_esport.jpg', ($this->getPageWidth() - 60)/2, $this->getY()+1, 0, 11, 'jpg', '', 'CT', false, 150, '', false, false, array(), 'CT', false, false, false, array());
		$this->Image(K_PATH_IMAGES.'cmas_logo.jpg', PDF_MARGIN_LEFT+$this->getPageWidth()-60, $this->getY(), 0, 13, 'jpg', '', 'RT', false, 300, '', false, false, array(), 'CT', false, false, false, array());
    }
	
}