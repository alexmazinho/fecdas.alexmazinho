<?php
namespace Fecdas\PartesBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fecdas\PartesBundle\Classes\TcpdfBridge;
use Fecdas\PartesBundle\Entity\EntityLlicencia;

class PDFController extends BaseController {

	public function facturatopdfAction() {
		/* Factura parte */
		$request = $this ->getRequest();
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
		
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($reqId);
		
			if ($parte != null) {
				$pagat = ($parte->getDatapagament() != null); // Parte pagat?
				$valida = ($parte->isFacturaValida() == true); // Factura vàlida?
				$detall = $parte->getDetallFactura(); // Get detall
				$totals = $this->getTotalsFactura($detall); // Get totals
				
				$response = $this->facturatopdf($parte->getNumfactura(), $parte->getDatafacturacio(), 'Factura Llista llicències ' . date("Y"),  
											$parte->getClub(), $parte->getTipus()->getIva(), $detall, $totals, $pagat, $valida);
				
				$this->logEntryAuth('PRINT FACT PARTE', $reqId);
				
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT FACT PARTE KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir la factura, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	}
	
	public function facturapeticioAction() {
		/* Factura petició */
		$request = $this ->getRequest();
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$duplicat = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityDuplicat')->find($reqId);
	
			if ($duplicat != null) {
				$pagat = ($duplicat->getPagament() != null); // Petició duplicat pagada?
				$detall = $duplicat->getDetallFactura(); // Get detall
				$totals = $this->getTotalsFactura($detall); // Get totals
	
				$response = $this->facturatopdf($duplicat->getFactura()->getNumfactura(), $duplicat->getFactura()->getDatafactura(), 'Factura petició de duplicat',  
											$duplicat->getClub(), 0, $detall, $totals, $pagat, true);
	
				$this->logEntryAuth('PRINT FACT DUPLI', $reqId);
	
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT FACT DUPLI KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir la factura, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	}
	
	private function facturatopdf($numFactura, $dataFactura, $titol, $club, $iva, $detall, $totals, $pagat, $valida) {
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
		$pdf->init($params = array('author' => 'FECDAS', 'title' => $titol));
			
		$pdf->AddPage();
		
		// set color for background
		$pdf->SetFillColor(255, 255, 255); //Blanc
		// set color for text
		$pdf->SetTextColor(0, 0, 0); // Negre
		
		$y_ini = $pdf->getY();
		$x_ini = $pdf->getX();
		
		$y = $y_ini;
		$x = $x_ini;
		
		$pdf->SetFont('dejavusans', '', 16, '', true);
		$text = '<b>FACTURA</b>';
		$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'L', true);
		$pdf->Ln(5);
		
		$pdf->SetFont('dejavusans', '', 10, '', true);
		
		$tbl = '<table border="0" cellpadding="5" cellspacing="0">';
		$tbl .= '<tr><td width="250"><b>' . $club->getNom() . '</b></td></tr>';
		$tbl .= '<tr><td>' . $club->getAddradreca() . '</td></tr>';
		$tbl .= '<tr><td>' . $club->getAddrcp() . " - " . $club->getAddrpob() . '</td></tr>';
		$tbl .= '<tr><td>' . $club->getAddrprovincia() . '</td></tr>';
		$tbl .= '<tr><td>Telf: ' . $club->getTelefon()  . '</td></tr>';
		$tbl .= '</table>';
		
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$y = $pdf->getY();
		$pdf->setY($y_ini);
		$pdf->setX($pdf->getPageWidth() - 80);
		
		$pdf->SetFont('dejavusans', '', 8, '', true);
		$tbl = '<table border="0" cellpadding="5" cellspacing="0">';
		$tbl .= '<tr><td width="250" align="right"><b>FEDERACIÓ CATALANA <br/>D\'ACTIVITATS SUBAQUÀTIQUES</b></td></tr>';
		$tbl .= '<tr><td align="right">Moll de la Vela 1 (Zona Forum)<br/>';
		$tbl .= '08930 Sant Adrià de Besòs<br/>';
		$tbl .= 'Tel: 93 356 05 43 Fax: 93 356 30 73<br/>';
		$tbl .= 'NIF: Q5855006B</td></tr>';
		$tbl .= '</table>';
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$pdf->SetFont('dejavusans', '', 10, '', true);
		$pdf->setY($y);
		$pdf->setX($pdf->getPageWidth() - 80);
			
		$tbl = '<table border="0" cellpadding="5" cellspacing="0">';
		$tbl .= '<tr><td width="150" align="right" style="color:#555555;">Factura número:</td><td width="120" align="left"><b>' . $numFactura . '</b></td></tr>';
		$tbl .= '<tr><td align="right" style="color:#555555;">Data:</td><td align="left"><b>' . $dataFactura->format('d/m/Y') . '</b></td></tr>';
		$tbl .= '<tr><td align="right" style="color:#555555;">CIF:</td><td align="left"><b>' . $club->getCif() . '</b></td></tr>';
		$tbl .= '</table>';
		
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$pdf->Ln(5);
		$pdf->setX($x_ini);
		
		$pdf->SetFont('dejavusans', '', 8, '', true);
		
		$tbl = '<table border="1" cellpadding="5" cellspacing="0">
				<tr style="background-color:#CCCCCC;">
				<td width="80" align="center">REFERÈNCIA</td>
				<td width="280" align="left">CONCEPTE</td>
				<td width="50" align="center">QUANT.</td>
				<td width="50" align="center">PREU</td>
				<td width="70" align="center">IMPORT</td>
				<td width="60" align="center">I.V.A<br/>(' . number_format($iva, 2, ',', '.') . '%)</td>
				<td width="80" align="right">TOTAL</td>
				</tr>';
		
		$tblref = "";
		$tblconc = "";
		$tblquant = "";
		$tblpreu = "";
		$tblimp = "";
		$tbliva = "";
		$tbltotal = "";
		
		foreach ($detall as $c => $lineafactura) {
			$tblref .= $lineafactura['codi'] . '<br/><br/>';
			$tblconc .= $lineafactura['desc'] . '<br/><br/>';
			$tblquant .= $lineafactura['quant'] . '<br/><br/>';
			$tblpreu .= number_format($lineafactura['preuunitat'], 2, ',', '.') .  '€<br/><br/>';
			$tblimp .=  number_format($lineafactura['preusiva'], 2, ',', '.') .  '€<br/><br/>';
			$tbliva .=  number_format($lineafactura['iva'], 2, ',', '.') .  '€<br/><br/>';
			$tbltotal .= number_format($lineafactura['totaldetall'], 2, ',', '.') .  '€<br/><br/>';
		}
		
		$tbl .= '<tr>';
		$tbl .= '<td align="center">' . $tblref;
		$tbl .= '<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/></td>';
		$tbl .= '<td align="left">' . $tblconc .  '</td>';
		$tbl .= '<td align="center">' . $tblquant .  '</td>';
		$tbl .= '<td align="right">' . $tblpreu . '</td>';
		$tbl .= '<td align="right">' . $tblimp . '</td>';
		$tbl .= '<td align="right">' . $tbliva . '</td>';
		$tbl .= '<td align="right">' . $tbltotal . '</td>';
		$tbl .= '</tr>';
		
		$tbl .= '<tr>';
		$tbl .= '<td colspan="4" style="background-color:#EEEEEE;">&nbsp;</td>';
		$tbl .= '<td align="center">IMPORT<br/>' . number_format($totals['totalparcial'], 2, ',', '.') . ' €</td>';
		$tbl .= '<td align="center">I.V.A<br/>' . number_format($totals['iva'], 2, ',', '.') . ' €</td>';
		$tbl .= '<td align="center">TOTAL<br/>' . number_format($totals['total'], 2, ',', '.') .  ' €</td>';
		$tbl .= '</tr>';
		$tbl .= '<tr border="0">';
		$tbl .= '<td colspan="6" style="background-color:#EEEEEE;">&nbsp;</td>';
		$tbl .= '<td align="center">A PAGAR<br/><b>' . number_format($totals['total'], 2, ',', '.') .  ' €</b></td>';
		$tbl .= '</tr>';
		
		$tbl .= '</table>';
		
		$pdf->writeHTML($tbl, true, false, false, false, '');
		
		if ($iva == 0) {
			// set color for text
			$pdf->SetTextColor(50, 50, 50); // Gris
			$pdf->SetFont('dejavusans', '', 8, '', true);
			$text = '<p>Factura exempta d\'I.V.A. segons la llei 49/2002</p>';
			$pdf->writeHTML($text, true, false, false, false, '');
			//$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'L', true);
		}
		$pdf->Ln(5);
		
		$pdf->SetTextColor(100, 100, 100); // Gris
		$pdf->SetFont('dejavusans', '', 16, '', true);
		
		if ($pagat == true) {
			$text = '<b>FACTURA PAGADA</b>';
			$pdf->writeHTML($text, true, false, false, false, '');
				
			if ($valida == false) {
				// Ha canviat la factura, mostra avís factura obsoleta
				$pdf->SetFont('dejavusans', '', 14, '', true);
				$y = $y_ini + 120;
				$x = $x_ini;
				$text = 'Aquesta factura ha quedat obsoleta per modificacions posteriors al pagament de la llista.<br/>';
				$text .= 'Per a obtenir la factura original, poseu-vos en contacte amb la federació.';
					
				//$pdf->writeHTML($text, true, false, false, false, 'L');
				$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'C', true);
			}
		
				
		} else {
			$text = '<b>FACTURA PENDENT DE PAGAMENT</b>';
			$pdf->writeHTML($text, true, false, false, false, '');
		}
		
		
		
		// reset pointer to the last page
		$pdf->lastPage();
			
		$nomfitxer = "factura_" .  str_replace("/", "-", $numFactura) . "_" . $club->getCodi() . ".pdf";
		
		
		// Close and output PDF document
		$response = new Response($pdf->Output($nomfitxer, "D"));
		$response->headers->set('Content-Type', 'application/pdf');
		return $response;
	}
	
	public function albaratopdfAction() {
		/* Albarà parte */
		$request = $this ->getRequest();
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
		
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($reqId);
		
			if ($parte != null) {
				$pagat = ($parte->getDatapagament() != null); // Parte pagat?
				$detall = $parte->getDetallFactura(); // Get detall
				$totals = $this->getTotalsFactura($detall); // Get totals
				
				$response = $this->albaratopdf(self::PREFIX_ALBARA_LLICENCIES.$parte->getId(), $parte->getDataentrada(), 'Albarà Llista llicències ' . date("Y"),  
											$parte->getClub(), $parte->getTipus()->getIva(), $detall, $totals, $pagat);
				
				$this->logEntryAuth('PRINT ALBARA PARTE', $reqId);
				
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT ALBARA PARTE KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir l\'albarà, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	}
	
	public function albarapeticioAction() {
		/* Albarà petició */
		$request = $this ->getRequest();
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$duplicat = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityDuplicat')->find($reqId);
	
			if ($duplicat != null) {
				$pagat = ($duplicat->getPagament() != null); // Petició duplicat pagada?
				$detall = $duplicat->getDetallFactura(); // Get detall
				$totals = $this->getTotalsFactura($detall); // Get totals
	
				$response = $this->albaratopdf(self::PREFIX_ALBARA_DUPLICATS.str_pad($duplicat->getId(),5,'0',STR_PAD_LEFT), $duplicat->getDatapeticio(), 'Albarà petició de duplicat',
						$duplicat->getClub(), 0, $detall, $totals, $pagat);
	
				$this->logEntryAuth('PRINT ALBARA DUPLI', $reqId);
	
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT ALBARA DUPLI KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir l\'albarà, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	}
	
	private function albaratopdf($numAlbara, $dataAlbara, $titol, $club, $iva, $detall, $totals, $pagat) { 
		/* Printar albarà */
		 
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
					
		$pdf->init($params = array('author' => 'FECDAS', 'title' => $titol)); 
					
		$pdf->AddPage();
	
		// set color for background
		$pdf->SetFillColor(255, 255, 255); //Blanc
		// set color for text
		$pdf->SetTextColor(0, 0, 0); // Negre
	
		$y_ini = $pdf->getY();
		$x_ini = $pdf->getX();
	
		$y = $y_ini;
		$x = $x_ini;
	
		$pdf->SetFont('dejavusans', '', 16, '', true);
		$text = '<b>ALBARÀ #'. $numAlbara .'#</b>';
		$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'L', true);
		$pdf->Ln(5);
	
		$pdf->SetFont('dejavusans', '', 10, '', true);
	
		$tbl = '<table border="0" cellpadding="5" cellspacing="0">';
		$tbl .= '<tr><td width="250"><b>' . $club->getNom() . '</b></td></tr>';
		$tbl .= '<tr><td>' . $club->getCif() . '</td></tr>';
		$tbl .= '<tr><td>' . $club->getAddradreca() . '</td></tr>';
		$tbl .= '<tr><td>' . $club->getAddrcp() . " - " . $club->getAddrpob() . '</td></tr>';
		$tbl .= '<tr><td>' . $club->getAddrprovincia() . '</td></tr>';
		$tbl .= '<tr><td>Telf: ' . $club->getTelefon()  . '</td></tr>';
		$tbl .= '</table>';
	
		$pdf->writeHTML($tbl, false, false, false, false, '');
	
		$y = $pdf->getY();
		$pdf->setY($y_ini);
		$pdf->setX($pdf->getPageWidth() - 82);
	
		$pdf->SetFont('dejavusans', '', 8, '', true);
		$tbl = '<table border="0" cellpadding="5" cellspacing="0">';
		$tbl .= '<tr><td width="250" align="right"><b>FEDERACIÓ CATALANA <br/>D\'ACTIVITATS SUBAQUÀTIQUES</b></td></tr>';
		$tbl .= '<tr><td align="right">Moll de la Vela 1 (Zona Forum)<br/>';
		$tbl .= '08930 Sant Adrià de Besòs<br/>';
		$tbl .= 'Tel: 93 356 05 43  Fax: 93 356 30 73<br/>';
		$tbl .= 'NIF: Q5855006B</td></tr>';
		$tbl .= '</table>';
		$pdf->writeHTML($tbl, false, false, false, false, '');

		$pdf->SetFont('dejavusans', '', 10, '', true);
		$pdf->setY($y - 13); 
		$pdf->setX($pdf->getPageWidth() - 79);
					
		$tbl = '<table border="0" cellpadding="5" cellspacing="0">';
		$tbl .= '<tr><td width="150" align="right" style="color:#555555;">Número d\'albarà:</td><td width="90" align="right"><b>' . $numAlbara  . '</b></td></tr>';
		$tbl .= '<tr><td align="right" style="color:#555555;">Data de la comanda:</td><td align="right"><b>' . $dataAlbara->format('d/m/Y') . '</b></td></tr>';				
		$tbl .= '</table>';
	
		$pdf->writeHTML($tbl, false, false, false, false, '');
	
		$pdf->Ln(5);
		$pdf->setX($x_ini);
	
		$pdf->SetFont('dejavusans', '', 8, '', true);
	
		$tbl = '<table border="1" cellpadding="5" cellspacing="0">
				<tr style="background-color:#CCCCCC;">
				<td width="75" align="center">REFERÈNCIA</td>
				<td width="270" align="left">CONCEPTE</td>
				<td width="50" align="center">QUANT.</td>
				<td width="50" align="center">PREU</td>
				<td width="70" align="center">IMPORT</td>
				<td width="55" align="center">I.V.A<br/>(' . number_format($iva, 2, ',', '.') . '%)</td>
				<td width="80" align="right">TOTAL</td>
				</tr>';
	
		$tblref = "";
		$tblconc = "";
		$tblquant = "";
		$tblpreu = "";
		$tblimp = "";
		$tbliva = "";
		$tbltotal = "";
	
		foreach ($detall as $c => $lineafactura) {
			$tblref .= $lineafactura['codi'] . '<br/><br/>';
			$tblconc .= $lineafactura['desc'] . '<br/><br/>';
			$tblquant .= $lineafactura['quant'] . '<br/><br/>';
			$tblpreu .= number_format($lineafactura['preuunitat'], 2, ',', '.') .  '€<br/><br/>';
			$tblimp .=  number_format($lineafactura['preusiva'], 2, ',', '.') .  '€<br/><br/>';
			$tbliva .=  number_format($lineafactura['iva'], 2, ',', '.') .  '€<br/><br/>';
			$tbltotal .= number_format($lineafactura['totaldetall'], 2, ',', '.') .  '€<br/><br/>';
		}
	
		$tbl .= '<tr>';
		$tbl .= '<td align="center">' . $tblref;
		$tbl .= '<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/></td>';
		$tbl .= '<td align="left">' . $tblconc .  '</td>';
		$tbl .= '<td align="center">' . $tblquant .  '</td>';
		$tbl .= '<td align="right">' . $tblpreu . '</td>';
		$tbl .= '<td align="right">' . $tblimp . '</td>';
		$tbl .= '<td align="right">' . $tbliva . '</td>';
		$tbl .= '<td align="right">' . $tbltotal . '</td>';
		$tbl .= '</tr>';
		
		$tbl .= '<tr>';
		$tbl .= '<td colspan="4" style="background-color:#EEEEEE;">&nbsp;</td>';
		$tbl .= '<td align="center">IMPORT<br/>' . number_format($totals['totalparcial'], 2, ',', '.') . ' €</td>';
		$tbl .= '<td align="center">I.V.A<br/>' . number_format($totals['iva'], 2, ',', '.') . ' €</td>';
		$tbl .= '<td align="center">TOTAL<br/>' . number_format($totals['total'], 2, ',', '.') .  ' €</td>';
		$tbl .= '</tr>';
		$tbl .= '<tr border="0">';
		$tbl .= '<td colspan="6" style="background-color:#EEEEEE;">&nbsp;</td>';
		$tbl .= '<td align="center">A PAGAR<br/><b>' . number_format($totals['total'], 2, ',', '.') .  ' €</b></td>';
		$tbl .= '</tr>';
	
		$tbl .= '</table>';
	
		$pdf->writeHTML($tbl, true, false, false, false, '');
	
		if ($iva == 0) {
			// set color for text
			$pdf->SetTextColor(50, 50, 50); // Gris
			$pdf->SetFont('dejavusans', '', 8, '', true);
			$text = '<p>Exempt d\'I.V.A. segons la llei 49/2002</p>';
			$pdf->writeHTML($text, true, false, false, false, '');
			//$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'L', true);
		}
		$pdf->Ln(5);
	
		$pdf->SetTextColor(100, 100, 100); // Gris
		$pdf->SetFont('dejavusans', '', 16, '', true);
	
		if ($pagat == true) {
			$text = '<b>-- ALBARÀ PAGAT --</b>';
			$pdf->writeHTML($text, true, false, false, false, '');
		}
	
		// reset pointer to the last page
		$pdf->lastPage();
					
		// Close and output PDF document
		$response = new Response($pdf->Output("albara_" . $numAlbara . "_" . $club->getCodi() . ".pdf", "D"));
		$response->headers->set('Content-Type', 'application/pdf');
		return $response;
		
	}
	
	public function  asseguratstopdfAction() {
		/* Llistat d'assegurats vigents */
		$request = $this ->getRequest();
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
		
		$club = $this->getCurrentClub();
		$currentDNI = "";
		if ($request->query->has('dni')) $currentDNI = $request->query->get('dni');
		$currentNom = "";
		if ($request->query->has('nom')) $currentNom = $request->query->get('nom');
		$currentCognoms = "";
		if ($request->query->has('cognoms')) $currentCognoms = $request->query->get('cognoms');
		$currentVigent = true;
		if ($request->query->has('vigents')) $currentVigent = ($request->query->get('vigents')==1);
		$currentTots = false;
		if ($this->isCurrentAdmin()) { // Admins poden cerca tots els clubs
			if ($request->query->has('tots')) $currentTots = ($request->query->get('tots')==1);
		}
		
		$this->logEntryAuth('PRINT ASSEGURATS', "club: ". $club->getCodi()." ".$currentNom.", ".$currentCognoms . "(".$currentDNI. ") ".$currentTots);
		
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
		$pdf->init($params = array('author' => 'FECDAS', 'title' => "Llista d'assegurats"),
				true, "Club " . $club->getNom());
			
		$pdf->AddPage();
		
		// set color for background
		$pdf->SetFillColor(255, 255, 255); //Blanc
		// set color for text
		$pdf->SetTextColor(0, 0, 0); // Negre
		
		$y_ini = $pdf->getY();
		$x_ini = $pdf->getX();
		
		$y = $y_ini;
		$x = $x_ini;
		
		$pdf->SetFont('dejavusans', '', 12, '', true);
		// Titol segons filtre
		if ($currentVigent == true) $text = '<b>Llista d\'assegurats en data '. date("d/m/Y") .'</b>';
		else $text = '<b>Històric d\'assegurats</b>';
		$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, '', true);

		if ($currentDNI != "" or $currentNom != "" or $currentCognoms != "") {
			// Afegir dades del filtre
			$y += 10;
			$pdf->SetFont('dejavusans', 'I', 10, '', true);
			$pdf->writeHTMLCell(0, 0, $x, $y, 'Opcions de filtre', 'B', 1, 1, true, '', true);
			$pdf->SetFont('dejavusans', '', 9, '', true);
			if ($currentDNI != "") {
				$y += 7;
				$pdf->writeHTMLCell(0, 0, $x, $y, 'DNI\'s que contenen "'.$currentDNI.'"', '', 1, 1, true, '', true);
			}
			if ($currentNom != "") {
				$y += 7;
				$pdf->writeHTMLCell(0, 0, $x, $y, 'Noms que contenen "'.$currentNom.'"', '', 1, 1, true, '', true);
			}
			if ($currentCognoms != "") {
				$y += 7;
				$pdf->writeHTMLCell(0, 0, $x, $y, 'Cognoms que contenen "'.$currentCognoms.'"', '', 1, 1, true, '', true);
			}
			$y += 2;
			$pdf->writeHTMLCell(0, 0, $x, $y, '', 'B', 1, 1, true, '', true);
			
		} else {
			$y += 15;
		}
		
		$pdf->SetFont('dejavusans', '', 9, '', true);

		$tbl = '<table border="0.5" cellpadding="7" cellspacing="0">
				  <tr style="background-color:#DDDDDD;font-weight: bold;font-size: medium;">
				  <td width="35">&nbsp;</td>
				  <td width="155" align="left">Nom</td>
				  <td width="75" align="center">DNI</td>
				  <td width="240" align="left">Llicència / assegurança</td>
				  <td width="135" align="center">Vigència</td>
				 </tr>';
		
		$total = 0;

		$persones = $this->consultaAssegurats($currentTots, $currentDNI, $currentNom, $currentCognoms);
		
		foreach ($persones as $c => $persona) {
			$llicencia = $persona->getLlicenciaVigent();
			if ($currentVigent != true or ($currentVigent == true and $llicencia != null)) {
				if ($llicencia == null) $llicencia = $persona->getLastLlicencia(); 
	
				$total++;
				$tbl .= '<tr nobr="true" style="font-size: small;"><td align="center">' . $total . '</td>';
				$tbl .= '<td align="left">' . $persona->getCognoms() . ', ' . $persona->getNom() . '</td>';
				$tbl .= '<td align="center">' . $persona->getDni() .  '</td>';
				if ($llicencia != null) {
					
					$tbl .= '<td align="left">' . $llicencia->getCategoria()->getDescripcio() . '</td>';
					$tbl .= '<td align="center">' . $llicencia->getParte()->getDataalta()->format('d/m/Y')
					. ' - ' . $llicencia->getParte()->getDatacaducitat($this->getLogMailUserData("asseguratstopdfAction"))->format('d/m/Y') .  '</td>';
				} else {
					$tbl .= '<td align="left" colspan="2">Sense historial de llicències</td>';
				}
				$tbl .= '</tr>';
			} 
		}
		
		$tbl .= '</table>';
		
		$pdf->Ln(10);
		
		$pdf->writeHTML($tbl, false, false, false, false, '');

		$pdf->setPage(1); // Move to first page
		
		$pdf->setY($y_ini);
		$pdf->setX($pdf->getPageWidth() - 100);
		
		$pdf->SetFont('dejavusans', '', 13, '', true);
		$text = '<b>Total : '. $total . '</b>';
		$pdf->writeHTMLCell(0, 0, $pdf->getX(), $pdf->getY(), $text, '', 1, 1, true, 'R', true);
		
		// reset pointer to the last page
		$pdf->lastPage();

		
		if ($request->query->has('print') and $request->query->get('print') == true) {
			// force print dialog
			$js = 'print(true);';
			// set javascript
			$pdf->IncludeJS($js);
			$response = new Response($pdf->Output("assegurats_" . $club->getCodi() . "_" . date("Ymd") . ".pdf", "I")); // inline
		} else {
		// Close and output PDF document
			$response = new Response($pdf->Output("assegurats_" . $club->getCodi() . "_" . date("Ymd") . ".pdf", "D")); // save as...
		}
		$response->headers->set('Content-Type', 'application/pdf');
		return $response;
		
	}
	
	public function partetopdfAction() {
		$request = $this ->getRequest();
	
		if ($request->query->has('id')) {
			$parte = $this->getDoctrine()
				->getRepository('FecdasPartesBundle:EntityParte')
				->find($request->query->get('id'));
			
			if ($parte == null) return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
			
			if ($parte) {
				$this->logEntry($this->get('session')->get('username'), 'PRINT PARTE',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), $parte->getId());
				
				// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
				$pdf = new TcpdfBridge('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
				$pdf->init($params = array('author' => 'FECDAS', 'title' => 'Llicències ' . date("Y")), 
						true, "Llista número: " . $parte->getId());
							
				$pdf->AddPage();
				
				// set color for background
				$pdf->SetFillColor(255, 255, 255); //Blanc
				// set color for text
				$pdf->SetTextColor(0, 0, 0); // Negre
				
				$y_ini = $pdf->getY();
				$x_ini = $pdf->getX();
				
				$y = $y_ini;
				$x = $x_ini;

				$pdf->SetFont('dejavusans', '', 16, '', true);
				$text = '<b>MODEL ' . $parte->getTipus()->getDescripcio() . '</b>';
				$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'C', true);
				
				if ($parte->getWeb() == true and ($parte->getDatapagament() == null or $parte->getPendent() == true)) {
					// Si no les paguen o confirmen on-line surt el missatge
					$y += 10;
					$pdf->SetTextColor(100, 100, 100); // GRis
					$pdf->SetFillColor(200, 200, 200); //Blanc
					$pdf->SetFont('dejavusans', 'BI', 14, '', true);
					$text = '<p>## Aquestes llicències tindran validesa quan es confirmi el seu pagament ##</p>';
					$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'C', true);
					$pdf->SetTextColor(0, 0, 0); // Negre
					$pdf->SetFillColor(255, 255, 255); //Blanc
				}
				
				$y += 15;
				
				$datainici = $parte->getDataalta();
				$datafi = $parte->getDataCaducitat($this->getLogMailUserData("partetopdfAction  "));

				$pdf->SetFont('dejavusans', '', 10, '', true);
				$text = '<p>Llista d\'esportistes que representen el CLUB:   ';
				$text .= '<b>' . $parte->getClub()->getNom() . '</b></p>';
				$text .= '<p>Vigència de les llicències des del <b>' . $datainici->format("d/m/Y") . '</b>';
				$text .= ' fins el <b>' . $datafi->format("d/m/Y") . '</b></p>';
				$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'L', true);
				
				$pdf->SetFont('dejavusans', '', 12, '', true);
				$text = '<p>Data d\'entrada:  <b>' . $parte->getDataentrada()->format("d/m/Y") . '</b></p>';
				$pdf->writeHTMLCell(0, 0, $x, $y + 20, $text, '', 1, 1, true, 'L', true);

				$pdf->setY($y);
				$pdf->setX($pdf->getPageWidth() - 122);
				
				$factor = ($parte->getTipus()->getIva()/100) + 1;
				
				$pdf->SetFont('dejavusans', '', 9, '', true);
				
				$tbl = '<table border="1" cellpadding="5" cellspacing="0">
				  <tr style="background-color:#DDDDDD;">
				  <td width="100" align="center">CATEGORIA</td>
				  <td width="100" align="center">P. UNITAT<br/>IVA ' . number_format($parte->getTipus()->getIva(), 2, ',', '.') . '&nbsp;%</td>
				  <td width="60" align="center">TOTAL</td>
				  <td width="120" align="center">PREU</td>
				 </tr>';
				
				foreach ($parte->getTipus()->getCategories() as $c => $categoria) {
					$numpercat = $parte->getNumLlicenciesCategoria($categoria->getSimbol());
					$preu = $categoria->getPreuAny($parte->getAny());
					$tbl .= '<tr><td width="100" align="right">' . $categoria->getCategoria() . '</td>';
					$tbl .= '<td align="right">' . number_format($preu * $factor, 2, ',', '.') .  '&nbsp;€</td>';
					$tbl .= '<td align="center">' . $numpercat . '</td>';
					$tbl .= '<td align="right">' . number_format($preu * $numpercat * $factor, 2, ',', '.') .  '&nbsp;€</td></tr>';
				}
				$tbl .= '<tr><td colspan="2" align="right"><b>Total</b></td>';
				$tbl .= '<td align="center">' . $parte->getNumLlicencies() . '</td>';
				$tbl .= '<td align="right">' .  number_format($parte->getPreuTotalIVA(), 2, ',', '.') . '&nbsp;€</td></tr>';
				$tbl .= '</table>';
				
				$pdf->writeHTML($tbl, false, false, false, false, '');
				
				$pdf->Ln(10);	
				
				$pdf->SetFont('dejavusans', '', 11, '', true);
				
				$tbl = '<table border="1" cellpadding="5" cellspacing="0">
				 <tr style="background-color:#DDDDDD;font-weight: bold;font-size: medium;">
				  <td width="94" align="center">DNI</td>
				  <td width="172" align="center">COGNOMS</td>
				  <td width="100" align="center">NOM</td>
				  <td width="80" align="center">D NAIX</td>
				  <td width="176" align="center">DOMICILI</td>
				  <td width="60" align="center">CP</td>
				  <td width="108" align="center">POBLACIO</td>
				  <td width="40" align="center">CAT</td>
				  <td width="120" align="center">ACTIVITATS</td>
				 </tr>';
				
				$pdf->SetFont('dejavusans', '', 9, '', true);
				//foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
				$llicenciesSorted = $parte->getLlicenciesSortedByName();
				foreach ($llicenciesSorted as $c => $llicencia_iter) {
					if ($llicencia_iter->getDatabaixa() == null) {
						$persona = $llicencia_iter->getPersona();
						$tbl .= '<tr nobr="true">';
						$tbl .= '<td align="center">' . $persona->getDni() .  '</td>';
						$tbl .= '<td align="left">' . $persona->getCognoms() .  '</td>';
						$tbl .= '<td align="left">' . $persona->getNom() .  '</td>';
						$tbl .= '<td align="center">' . $persona->getDatanaixement()->format("d/m/y") .  '</td>';
						$tbl .= '<td align="left">' . $persona->getAddradreca() .  '</td>';
						$tbl .= '<td align="center">' . $persona->getAddrcp() .  '</td>';
						$tbl .= '<td align="left">' . $persona->getAddrpob() .  '</td>';
						$tbl .= '<td align="center">' . $llicencia_iter->getCategoria()->getSimbol() .  '</td>';
						$tbl .= '<td align="center" style="font-size: small;">' . $llicencia_iter->getActivitats() . '</td>';
						$tbl .= '</tr>';
					}
				}
				$tbl .= '</table>';
				
				$pdf->writeHTML($tbl, true, false, false, false, '');
				
				$y = $pdf->getY();
				
				$pdf->SetFont('dejavusans', '', 8, '', true);
				$tbl = '<table border="1" cellpadding="5" cellspacing="0">
				<tr nobr="true">
				<td width="130" align="left"><b>A</b>: Apnea</td>
				<td width="130" align="left"><b>E</b>: Escafandrisme CMAS</td>
				<td width="130" align="left"><b>FA</b>: Foto Sub Apnea</td>
				<td width="130" align="left"><b>FS</b>: Fotografia Submarina</td>
				<td width="130" align="left"><b>HS</b>: Hoquei Sub</td>
				</tr>';
				$tbl .= '<tr nobr="true">
				<td align="left"><b>O</b>: Orientació</td>
				<td align="left"><b>P</b>: Pesca Submarina</td>
				<td align="left"><b>VS</b>: Video Subaquàtic</td>
				<td align="left"><b>RG</b>: Rugbi Subaquàtic</td>
				<td align="left"><b>BP</b>: Busseig Esportiu Piscina</td>
				</tr>';
				$tbl .= '<tr nobr="true">
				<td align="left"><b>N</b>: Natació amb Aletes</td>
				<td align="left"><b>B</b>: Biologia</td>
				<td align="left"><b>BA</b>: Busseig amb Ampolles</td>
				<td align="left">&nbsp;</td>
				<td align="left">&nbsp;</td>
				</tr>';
				$tbl .= '</table>';
				
				$pdf->writeHTML($tbl, false, false, false, false, '');
				
				// reset pointer to the last page
				$pdf->lastPage();
			
				// Close and output PDF document
				$response = new Response($pdf->Output("llicencies_" . $parte->getClub()->getCodi() . "_" . $parte->getId() . ".pdf", "D"));
				$response->headers->set('Content-Type', 'application/pdf');
				return $response;
			}
			
		}
		return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	}
	
	public function licensetopdfAction() {
		$request = $this ->getRequest();
	
		if ($request->query->has('id')) {
			$llicencia = $this->getDoctrine()
							->getRepository('FecdasPartesBundle:EntityLlicencia')
							->find($request->query->get('id'));
	
			if ($llicencia == null) return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
			
			if ($llicencia->getDatabaixa() != null) $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
			
			if ($llicencia) {
				$this->logEntry($this->get('session')->get('username'), 'PRINT LLICENCIA',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), $llicencia->getId());
				
				// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
				$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	
				$pdf->init($params = array('author' => 'FECDAS',
						'title' => 'Llicència FECDAS' . date("Y")));
				
				// Add a page
				$pdf->AddPage();
				
				// get current vertical position
				$y_ini = $pdf->getY();
				$x_ini = $pdf->getX();
				
				$y = $y_ini;
				$x = $x_ini;
				
				$pdf->writeHTMLCell(0, 0, $x, $y, "Aquesta llicència és provisional i té una validesa màxima de 30 dies", 0, 0, 0, true, 'C', true);
				
				$x += 45;
				$y += 10;
				
				$width = 85; //Original
				$height = 54; //Original
				
				
				// Border dashed
				$pdf->SetLineStyle(array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(0, 0, 0)));
				//$pdf->writeHTMLCell($width + 4, 2*($height + 2), $x - 2, $y - 2, '', 1, 0, 0, true, 'L', true);

				$pdf->Image('images/federativa-cara.png', $x, $y, 
						$width, $height , 'png', '', '', true, 150, 
						'', false, false, 1, false, false, false);
				
				$pdf->Image('images/federativa-dors.png', $x, $y + $height,
						$width, $height , 'png', '', '', true, 150,
						'', false, false, 1, false, false, false);
				
				// set color for text and font
				$pdf->SetTextColor(240, 240, 240); // Gris
				$pdf->SetFillColor(255, 255, 255); // 
				/*
				$pdf->SetFont('dejavusans', 'B', 5.5, '', true);
				
				$x = $x_ini + 47;
				$y = $y_ini + 35;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Nom: " . $llicencia->getPersona()->getNom() . " " . $llicencia->getPersona()->getCognoms(), 0, 0, 0, true, 'L', true);
				
				$y += 5;
				$pdf->writeHTMLCell(0, 0, $x, $y, "DNI/Passaport: " . $llicencia->getPersona()->getDni(), 0, 0, 0, true, 'L', true);
				
				$y += 5;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Categoria/Nivell: " . $llicencia->getCategoria()->getCategoria(), 0, 0, 0, true, 'L', true);
				
				$y += 5;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Data naixement: " . $llicencia->getPersona()->getDatanaixement()->format('d/m/Y'), 0, 0, 0, true, 'L', true);
				
				$y += 5;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Entitat: " . $llicencia->getParte()->getClub()->getNom(), 0, 0, 0, true, 'L', true);
				
				$y += 5;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Telf. entitat: " . $llicencia->getParte()->getClub()->getTelefon(), 0, 0, 0, true, 'L', true);*/

				$pdf->SetFont('dejavusans', 'B', 4.5, '', true);
				
				$x = $x_ini + 54.2;
				$y = $y_ini + 39.2;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getPersona()->getNom() . " " . $llicencia->getPersona()->getCognoms(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 62.5;
				$y = $y_ini + 43;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getPersona()->getDni(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 65;
				$y = $y_ini + 46.7;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getCategoria()->getCategoria(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 63.6;
				$y = $y_ini + 50.7;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getPersona()->getDatanaixement()->format('d/m/Y'), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 56;
				$y = $y_ini + 54.5;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getParte()->getClub()->getNom(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 60;
				$y = $y_ini + 58.3;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getParte()->getClub()->getTelefon(), 0, 0, 0, true, 'L', true);
				
				//$datacaduca = $llicencia->getParte()->getDataalta();
				// Caducat 30 dies des de data impressió
				$datacaduca = $this->getCurrentDate();
				$datacaduca->add(new \DateInterval('P30D'));  // 30 dies
				
				if ($datacaduca > $llicencia->getParte()->getDatacaducitat($this->getLogMailUserData("licensetopdfAction 1 "))) $datacaduca = $llicencia->getParte()->getDatacaducitat($this->getLogMailUserData("licensetopdfAction 2 "));
				/*
				$x += 32;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Carnet provisional vàlid fins al " . $datacaduca->format('d/m/Y'), 0, 0, 0, true, 'L', true);*/
				$x += 41;
				$pdf->writeHTMLCell(0, 0, $x, $y, $datacaduca->format('d/m/Y'), 0, 0, 0, true, 'L', true);
				
				
				/* Tipus de llicència
				 * Taula TipoParte LL_L1 + LL_L1 + LL_L3 */
				$titolsPlastic = array();
				$anyLlicencia = $datacaduca->format('Y');
				$titolsPlastic[1] = "LLICÈNCIA FEDERATIVA\nTIPUS A (HABILITADA)\n".$anyLlicencia;
				$titolsPlastic[2] = "ASSEGURANÇA\nTIPUS B\n".$anyLlicencia;
				$titolsPlastic[4] = "LLICÈNCIA FEDERATIVA\nTIPUS C (HABILITADA)\n".($anyLlicencia-1)."-".$anyLlicencia;	
				$titolsPlastic[5] = "LLICÈNCIA FEDERATIVA\nTIPUS A (HABILITADA)\n".$anyLlicencia;
				$titolsPlastic[6] = "LLICÈNCIA FEDERATIVA\nTIPUS B\n".$anyLlicencia;
				$titolsPlastic[7] = "LLICÈNCIA FEDERATIVA\nTIPUS E (HABILITADA)\n(365 dies)";	
				$titolsPlastic[8] = "LLICÈNCIA FEDERATIVA\nTIPUS F (ASSEGURANÇA)\n(365 dies)";
				$titolsPlastic[9] = "LLICÈNCIA FEDERATIVA\nTIPUS G (ASSEGURANÇA)\nCURS ESCOLAR";
				$titolsPlastic[10] = "ASSEGURANÇA\nTIPUS B\n(365 dies)";
				$titolsPlastic[11] = "ASSEGURANÇA\nUN DIA";
				$titolsPlastic[12] = "LLICÈNCIA FEDERATIVA\nTIPUS G 2n (ASSEGURANÇA)\n".($anyLlicencia-1)."-".$anyLlicencia;

				if (isset($titolsPlastic[$llicencia->getParte()->getTipus()->getId()])) {
					$pdf->SetFont('helvetica', 'B', 9.5, '', true);
					$pdf->SetTextColor(230, 230, 230); // Gris
					$y = $y_ini + 24;
					$x = $x_ini + 62;

					$pdf->SetY($y);
					$pdf->SetX($x);
					$pdf->MultiCell($height,$width,$titolsPlastic[$llicencia->getParte()->getTipus()->getId()],0,'C',1);
				}
				
				// reset pointer to the last page
				$pdf->lastPage();
				
				// Close and output PDF document
				$response = new Response($pdf->Output("llicencia_" . $llicencia->getPersona()->getNom() . " " . $llicencia->getPersona()->getCognoms(). ".pdf", "D"));
				$response->headers->set('Content-Type', 'application/pdf');
				return $response;
			}
		}
		return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	}
}