<?php
namespace FecdasBundle\Classes;
/**
* TCPDF Bridge 
*/


class TcpdfBridge extends \TCPDF{
	protected $pagenum;
	protected $rightheader;
	
    public function init($params = null, $pagenum = false, $rightheader = "")
    {
    	// set document information
    	$this->SetCreator(PDF_CREATOR);
    	$this->SetAuthor($params['author']);
    	$this->SetTitle($params['title']);
    	$this->SetSubject($params['title']);
    	
    	$this->pagenum = $pagenum;
    	$this->rightheader = $rightheader;
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
        $this->SetProtection(array('modify', 'copy'), '', null, 2, null);
    }
    
    public function Header() {
    	parent::Header();
    	if ($this->rightheader != "") $this->Cell(0, 15, $this->rightheader, 0, false, 'R', 0, '', 0, false, 'M', 'M');
    }
    
    
    public function Footer() {
    	// Position at 15 mm from bottom
    	$this->SetY(-15);
    	// Set font
    	//$this->SetFont('helvetica', 'I', 8);
    	// Page number
    	$footer = '<a href="http://www.fecdas.cat">FECDAS</a> - FEDERACIÓ CATALANA D\'ACTIVITATS SUBAQUÀTIQUES - NIF: Q5855006B<br/>';
    	$footer .= 'Moll de la Vela 1 (Zona Forum) - 08930 Sant Adrià de Besòs<br/>';
    	$footer .= 'Tel: 93 356 05 43  Fax: 93 356 30 73 Adreça electrònica: info@fecdas.cat';
    	
    	$this->writeHTMLCell('', '', '', '', $footer, 0, 0, 0, true, 'C', true);
    	if ($this->pagenum == true) $this->Cell(0, 10, 'Pàgina '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}