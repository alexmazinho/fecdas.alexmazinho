<?php
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use FecdasBundle\Classes\TcpdfBridge;
use FecdasBundle\Entity\EntityLlicencia;

class PDFController extends BaseController {
	const TITOL_ARMES = "INFORMACIÓ IMPORTANT PER ALS PESCADORS SUBMARINS";
	const TEXT_ARMES = '<p>La FECDAS informa que:</p>
						<p>1. Per al transport d’un fusell de pesca subaquàtica i per a la pràctica de la pesca 
						subaquàtica cal estar en possessió de la llicència federativa, que actua com a permís 
						d’armes segons el reglament d’Armes de l’Estat espanyol (categoria  7º.5 de l’article 3 
						i article 54.4 del Reial Decret 137/1993, de 29 de gener / BOE 55/1993).</p>
						<p>La llicència federativa, a més d’actuar com a permís d’armes, inclou l’assegurança 
						d’accidents obligatòria i l’assegurança de Responsabilitat civil obligatòria.
						<p>Aquesta llicència es pot tramitar en qualsevol dels clubs federats a què s’accedeix 
						a través d’aquest enllaç:</p>
						<p><a style="font-size:1.1em;" href="http://www.fecdas.cat/clubs.php?user_text=&agenda_modalitat=28&boton.x=38&boton.y=10" target="_blank">
						http://www.fecdas.cat/clubs.php?user_text=&agenda_modalitat=28&boton.x=38&boton.y=10</a></p>   
						<p>2. Per practicar la pesca subaquàtica cal estar en possessió de la llicència administrativa 
						de pesca subaquàtica que té prevista l’administració catalana en la llei 25/1998, de 31 de desembre, 
						i el decret 100/2000, de 6 de març, pel qual s’unifiquen les llicències de pesca recreativa.</p>
						<p>Aquesta llicència administrativa és la que li permet extreure recursos –peixos- del medi natural.</p>
						<p>Aquesta llicència administrativa l’expedeix la Generalitat de Catalunya i l’ha de gestionar 
						el pescador subaquàtic a través d’internet des del portal del departament d\'Agricultura, 
						Ramaderia, Pesca, Alimentació i Medi Natural o de manera presencial a les seves oficines.</p>
						<p>Aquesta llicència administrativa es pot tramitar en línia a través d’aquest enllaç:</p>
						<p><a style="font-size:0.9em;" href="https://www14.gencat.cat/mediamb_sgll_public/AppJava/llicencies/gestioLlicenciesTitular.do?reqCode=prepareLocale&set-locale=es_CA" 
						target="_blank">https://www14.gencat.cat/mediamb_sgll_public/AppJava/llicencies/gestioLlicenciesTitular.do?reqCode=prepareLocale&set-locale=es_CA</a></p> 
						<p>3. Per practicar la pesca subaquàtica es recomanable tenir els coneixements que permeten 
						dur a terme l’activitat amb seguretat i conèixer i complir la legislació vigent que hi està relacionada.</p>
						<p>A Catalunya, aquesta formació es pot adquirir en un dels clubs esportius federats a què s’accedeix 
						a través d’aquest enllaç:</p>
						<p><a style="font-size:1.1em;" href="http://www.fecdas.cat/clubs.php?user_text=&agenda_modalitat=28&boton.x=38&boton.y=10" 
						target="_blank">http://www.fecdas.cat/clubs.php?user_text=&agenda_modalitat=28&boton.x=38&boton.y=10</a></p>
						<p>4. Els practicants de la pesca subaquàtica han de comptar amb un certificat mèdic vigent amb 
						una antiguitat inferior a un any que els serà sol·licitat en el moment de tramitar la llicència administrativa 
						de pesca subaquàtica.</p>
						';
	
	
	public function rebuttopdfAction(Request $request) {
		/* Rebut comanda */
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$rebut = $this->getDoctrine()->getRepository('FecdasBundle:EntityRebut')->find($reqId);
	
			if ($rebut != null) {
					return new Response("print rebut");
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT REBUT KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir el rebut, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	public function facturatopdfAction(Request $request) {
		/* Factura parte */
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($reqId);
		
			if ($parte != null) {
				$pagat = ($parte->getDatapagament() != null); // Parte pagat?
				$valida = ($parte->isFacturaValida() == true); // Factura vàlida?
				$detall = $parte->getDetallFactura(); // Get detall
				$totals = $this->getTotalsFactura($detall); // Get totals
				
				$response = $this->facturatopdf($parte->getNumfactura(), $parte->getDatafactura(), 'Factura Llista llicències ' . date("Y"),  
											$parte->getClub(), $parte->getTipus()->getIva(), $detall, $totals, $pagat, $valida);
				
				$this->logEntryAuth('PRINT FACT PARTE', $reqId);
				
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT FACT PARTE KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir la factura, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	public function facturapeticioAction(Request $request) {
		/* Factura petició */
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$duplicat = $this->getDoctrine()->getRepository('FecdasBundle:EntityDuplicat')->find($reqId);
	
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
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	private function facturatopdf($numFactura, $dataFactura, $titol, $club, $iva, $detall, $totals, $pagat, $valida) {
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
		$pdf->init(array('author' => 'FECDAS', 'title' => $titol));
			
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
		
		foreach ($detall as $lineafactura) {
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
	
	public function albaratopdfAction(Request $request) {
		/* Albarà parte */
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($reqId);
		
			if ($parte != null) {
				$pagat = ($parte->getDatapagament() != null); // Parte pagat?
				$detall = $parte->getDetallFactura(); // Get detall
				$totals = $this->getTotalsFactura($detall); // Get totals
				
				$response = $this->albaratopdf($parte->getNumAlbara(), $parte->getDataentrada(), 'Albarà Llista llicències ' . date("Y"),  
											$parte->getClub(), $parte->getTipus()->getIva(), $detall, $totals, $pagat);
				
				$this->logEntryAuth('PRINT ALBARA PARTE', $reqId);
				
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT ALBARA PARTE KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir l\'albarà, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	public function albarapeticioAction(Request $request) {
		/* Albarà petició */
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$duplicat = $this->getDoctrine()->getRepository('FecdasBundle:EntityDuplicat')->find($reqId);
	
			if ($duplicat != null) {
				$pagat = ($duplicat->getPagament() != null); // Petició duplicat pagada?
				$detall = $duplicat->getDetallFactura(); // Get detall
				$totals = $this->getTotalsFactura($detall); // Get totals
	
				$response = $this->albaratopdf($duplicat->getNumAlbara(), $duplicat->getDatapeticio(), 'Albarà petició de duplicat',
						$duplicat->getClub(), 0, $detall, $totals, $pagat);
	
				$this->logEntryAuth('PRINT ALBARA DUPLI', $reqId);
	
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT ALBARA DUPLI KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir l\'albarà, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	private function albaratopdf($numAlbara, $dataAlbara, $titol, $club, $iva, $detall, $totals, $pagat) { 
		/* Printar albarà */
		 
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
					
		$pdf->init(array('author' => 'FECDAS', 'title' => $titol)); 
					
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
	
		foreach ($detall as $lineafactura) {
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
	
	public function  asseguratstopdfAction(Request $request) {
		/* Llistat d'assegurats vigents */
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$club = $this->getCurrentClub();
		
		$currentDNI = $request->query->get('dni', '');
		$currentNom = $request->query->get('nom', '');
		$currentCognoms = $request->query->get('cognoms', '');
		
		$currentVigent = true;
		if ($request->query->has('vigent') && $request->query->get('vigent') == 0) $currentVigent = false;
		
		$currentTots = false;
		if ($this->isCurrentAdmin() && $this->get('request')->query->has('tots') && $this->get('request')->query->get('tots') == 1) $currentTots = true;
		
		$this->logEntryAuth('PRINT ASSEGURATS', $club->getCodi()." ".$currentNom.", ".$currentCognoms . "(".$currentDNI. ") ".$currentTots);
		error_log("0 TCPDF");
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		error_log("1 TCPDF");
		$pdf->init(array('author' => 'FECDAS', 'title' => "Llista d'assegurats"),
				true, $club->getNom());
			
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
		
		$pdf->Ln();

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
			
			$pdf->Ln();
		} else {
			$y += 15;
		}
		
		$w = array(8, 44, 20, 26, 82); // Amplades
		$this->asseguratsHeader($pdf, $w);
		$pdf->SetFillColor(255, 255, 255); //Blanc
		$pdf->SetFont('dejavusans', '', 8, '', true);
		
		$total = 0;

		$strOrderBY = $this->get('request')->query->get('sort', 'e.cognoms, e.nom'); // e.cognoms, e.nom per defecte
		 
		$query = $this->consultaAssegurats($currentTots, $currentDNI, $currentNom, $currentCognoms, $currentVigent, $strOrderBY); 
		$persones = $query->getResult();
		
		
		foreach ($persones as $persona) {
			$total++;
			
			$num_pages = $pdf->getNumPages();
			$pdf->startTransaction();
			
			$this->asseguratsRow($pdf, $persona, $total, $w);
				
			if($num_pages < $pdf->getNumPages()) {

				//Undo adding the row.
				$pdf->rollbackTransaction(true);
			
				$pdf->AddPage();
				$this->asseguratsHeader($pdf, $w);
				$pdf->SetFillColor(255, 255, 255); //Blanc
				$pdf->SetFont('dejavusans', '', 9, '', true);
				
				$this->asseguratsRow($pdf, $persona, $total, $w);
				
			} else {
				//Otherwise we are fine with this row, discard undo history.
				$pdf->commitTransaction();
			}
		}
		
		$pdf->Ln(10);
		
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
			$response = new Response($pdf->Output("assegurats_" . $club->getCodi() . "_" . date("Ymd") . ".pdf", "D")); // inline
		} else {
		// Close and output PDF document
			$response = new Response($pdf->Output("assegurats_" . $club->getCodi() . "_" . date("Ymd") . ".pdf", "D")); // save as...
		}
		error_log("2 TCPDF");
		$response->headers->set('Content-Type', 'application/pdf');
		return $response;
		
	}
	
	private function asseguratsRow($pdf, $persona, $total, $w) {
		$llicencia = $persona->getLlicenciaVigent();
		
		$pdf->Cell($w[0], 6, $total, 'LRB', 0, 'C', 0, '', 1);  // Ample, alçada, text, border, ln, align, fill, link, strech, ignore_min_heigh, calign, valign
		$pdf->Cell($w[1], 6, $persona->getCognoms() . ', ' . $persona->getNom(), 'LRB', 0, 'L', 0, '', 1);
		$pdf->Cell($w[2], 6, ($persona->getDatanaixement()!=null?$persona->getDatanaixement()->format('d/m/Y'):''), 'LRB', 0, 'C', 0, '', 1);
		$pdf->Cell($w[3], 6, $persona->getDni(), 'LRB', 0, 'C', 0, '', 1);
		if ($llicencia != null && $llicencia->getParte() != null) {
			$text = $llicencia->getCategoria()->getDescripcio().". ";
			$text .= $llicencia->getParte()->getDataalta()->format('d/m/Y'). ' - ';
			$text .= $llicencia->getParte()->getDatacaducitat($this->getLogMailUserData("asseguratstopdfAction"))->format('d/m/Y');
		} else {
			$text =  $persona->getInfoAssegurats($this->isCurrentAdmin());
		}
		$pdf->Cell($w[4], 6, $text , 'LRB', 0, 'L', 0, '', 1);
			
		$pdf->Ln();
		
	}
	
	private function asseguratsHeader($pdf, $w) {
		$pdf->SetFont('dejavusans', 'B', 9, '', true);
		$pdf->SetFillColor(221, 221, 221); //Gris
		
		$pdf->Cell($w[0], 7, '', 1, 0, 'C', 1);  // Ample, alçada, text, border, ln, align, fill,
		$pdf->Cell($w[1], 7, 'Nom', 1, 0, 'L', 1);
		$pdf->Cell($w[2], 7, 'Nascut/da', 1, 0, 'C', 1);
		$pdf->Cell($w[3], 7, 'DNI', 1, 0, 'C', 1);
		$pdf->Cell($w[4], 7, 'Informació llicència / assegurança', 1, 0, 'C', 1, '', 1);
		$pdf->Ln();
	}
	
	
	public function partetopdfAction(Request $request) {
	
		if ($request->query->has('id')) {
			$parte = $this->getDoctrine()
				->getRepository('FecdasBundle:EntityParte')
				->find($request->query->get('id'));
			
			if ($parte == null) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
			
			if ($parte) {
				$this->logEntry($this->get('session')->get('username'), 'PRINT PARTE',
						$this->get('session')->get('remote_addr'),
						$request->server->get('HTTP_USER_AGENT'), $parte->getId());
				
				// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
				$pdf = new TcpdfBridge('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
				$pdf->init(array('author' => 'FECDAS', 'title' => 'Llicències ' . date("Y")), 
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
				
				foreach ($parte->getTipus()->getCategories() as $categoria) {
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
				
				$w = array(26, 44, 28, 26, 50, 16, 30, 10, 35); // Amplades
				$this->parteHeader($pdf, $w);
				
				$pdf->SetFillColor(255, 255, 255); //Blanc
				
				$pdf->SetFont('dejavusans', '', 9, '', true);
				
				$llicenciesSorted = $parte->getLlicenciesSortedByName();
				
				foreach ($llicenciesSorted as $llicencia_iter) {
					$num_pages = $pdf->getNumPages();
					$pdf->startTransaction();
					
					$persona = $llicencia_iter->getPersona();
					
					$pdf->Cell($w[0], 6, $persona->getDni(), 'LRB', 0, 'C', 0, '', 1);  // Ample, alçada, text, border, ln, align, fill, link, strech, ignore_min_heigh, calign, valign
					$pdf->Cell($w[1], 6, $persona->getCognoms(), 'LRB', 0, 'L', 0, '', 1); 
					$pdf->Cell($w[2], 6, $persona->getNom(), 'LRB', 0, 'L', 0, '', 1);  
					$pdf->Cell($w[3], 6, $persona->getDatanaixement()->format("d/m/y") , 'LRB', 0, 'C', 0, '', 1); 
					$pdf->Cell($w[4], 6, $persona->getAddradreca(), 'LRB', 0, 'L', 0, '', 1); 
					$pdf->Cell($w[5], 6, $persona->getAddrcp(), 'LRB', 0, 'C', 0, '', 1);  
					$pdf->Cell($w[6], 6, $persona->getAddrpob(), 'LRB', 0, 'L', 0, '', 1);
					$pdf->Cell($w[7], 6, $llicencia_iter->getCategoria()->getSimbol(), 'LRB', 0, 'C', 0, '', 1);  
					$pdf->Cell($w[8], 6, $llicencia_iter->getActivitats(), 'LRB', 0, 'C', 0, '', 1); 
					$pdf->Ln();
					
					if($num_pages < $pdf->getNumPages()) {
						//Undo adding the row.
						$pdf->rollbackTransaction(true);
						
						$pdf->AddPage();
						$this->parteHeader($pdf, $w);
						$pdf->SetFillColor(255, 255, 255); //Blanc
						$pdf->SetFont('dejavusans', '', 9, '', true);
					} else {
						//Otherwise we are fine with this row, discard undo history.
						$pdf->commitTransaction();
					}
				}
				
				$pdf->Ln();
				
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
				
				$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
				
				// reset pointer to the last page
				$pdf->lastPage();
			
				// Close and output PDF document
				$response = new Response($pdf->Output("llicencies_" . $parte->getClub()->getCodi() . "_" . $parte->getId() . ".pdf", "D"));
				$response->headers->set('Content-Type', 'application/pdf');
				return $response;
			}
			
		}
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	private function parteHeader($pdf, $w) { 
		$pdf->SetFont('dejavusans', 'B', 10, '', true);
		$pdf->SetFillColor(221, 221, 221); //Gris
		$pdf->Cell($w[0], 7, 'DNI', 1, 0, 'C', 1);  // Ample, alçada, text, border, ln, align, fill,
		$pdf->Cell($w[1], 7, 'COGNOMS', 1, 0, 'C', 1);
		$pdf->Cell($w[2], 7, 'NOM', 1, 0, 'C', 1);
		$pdf->Cell($w[3], 7, 'D NAIXEMENT', 1, 0, 'C', 1, '', 1);
		$pdf->Cell($w[4], 7, 'DOMICILI', 1, 0, 'C', 1);
		$pdf->Cell($w[5], 7, 'CP', 1, 0, 'C', 1);
		$pdf->Cell($w[6], 7, 'POBLACIO', 1, 0, 'C', 1);
		$pdf->Cell($w[7], 7, 'CAT', 1, 0, 'C', 1);
		$pdf->Cell($w[8], 7, 'ACTIVITATS', 1, 0, 'C', 1);
		$pdf->Ln();
	}
	
	public function licensetopdfAction(Request $request) {
	
		if ($request->query->has('id')) {
			$llicencia = $this->getDoctrine()
							->getRepository('FecdasBundle:EntityLlicencia')
							->find($request->query->get('id'));
	
			if ($llicencia == null) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
			
			if ($llicencia->getDatabaixa() != null) $this->redirect($this->generateUrl('FecdasBundle_homepage'));
			
			if ($llicencia) {
				$this->logEntry($this->get('session')->get('username'), 'PRINT LLICENCIA',
						$this->get('session')->get('remote_addr'),
						$request->server->get('HTTP_USER_AGENT'), $llicencia->getId());
				
				// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
				$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	
				$pdf->init(array('author' => 'FECDAS',
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

				$pdf->Image('images/federativa-cara.jpg', $x, $y, 
						$width, $height , 'jpg', '', '', false, 320, 
						'', false, false, 1, false, false, false);
				
				$pdf->Image('images/federativa-dors.jpg', $x, $y + $height,
						$width, $height , 'jpg', '', '', false, 320,
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
				$y = $y_ini + 38.6; // 39.2
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getPersona()->getNom() . " " . $llicencia->getPersona()->getCognoms(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 62.5;
				$y = $y_ini + 42.4;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getPersona()->getDni(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 65;
				$y = $y_ini + 46.1;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getCategoria()->getCategoria(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 63.6;
				$y = $y_ini + 49.9;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getPersona()->getDatanaixement()->format('d/m/Y'), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 56;
				$y = $y_ini + 53.7;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getParte()->getClub()->getNom(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 60;
				$y = $y_ini + 57.5;
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
				
				
				// Alex 20/12/2014 Afegir texte legal llicències tipus F
				if ($llicencia->getParte()->getTipus()->getId() == 8) {
					
					$padding = 15;
					$x = $x_ini + $padding; // Padding
					$y = $y_ini + 123;
					
					$pdf->SetFont('Helvetica', '', 7, '', true);
					$tbl = '<h2><span style="text-align:justify;">'.self::TITOL_ARMES.'</h2></span>';
					$pdf->SetTextColor(80, 80, 80); // Gris
					$pdf->writeHTMLCell($pdf->getPageWidth(0) - (2*$x), 0, $x, $y, $tbl, '', 1, 1, true, 'L', true);
					
					$tbl = '<span style="text-align:justify;">'.self::TEXT_ARMES.'</span>';
					$pdf->SetTextColor(90, 90, 90); // Gris
					$pdf->writeHTMLCell($pdf->getPageWidth(0) - (2*$x), 0, $x, $y, $tbl, '', 1, 1, true, 'L', true);
					 
					
					$pdf->SetAutoPageBreak(FALSE, 0);
				
				}				
				// Fi Alex 20/12/2014 Afegir texte legal llicències tipus F 
				
				// reset pointer to the last page
				$pdf->lastPage();
				
				// Close and output PDF document
				$response = new Response($pdf->Output("llicencia_" . $llicencia->getPersona()->getNom() . " " . $llicencia->getPersona()->getCognoms(). ".pdf", "D"));
				$response->headers->set('Content-Type', 'application/pdf');
				return $response;
			}
		}
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
}
