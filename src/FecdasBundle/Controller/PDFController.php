<?php
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use FecdasBundle\Classes\TcpdfBridge;
use FecdasBundle\Entity\EntityLlicencia;

include_once (__DIR__.'/../../../vendor/tcpdf/include/tcpdf_static.php');

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
	
	
	public function facturatopdfAction(Request $request) {
		/* Factura parte */
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$reqId = 0;
		if ($request->query->has('id')) {
			$reqId = $request->query->get('id');
			$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($reqId);
		
			if ($comanda != null && $comanda->getFactura() != null) {
				
				$response = $this->facturatopdf($comanda);
				
				$this->logEntryAuth('PRINT FACTURA OK', $reqId);
				
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT FACTURA KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir la factura, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	private function facturatopdf($comanda) {
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$factura = $comanda->getFactura();
		$club = $comanda->getClub();
		
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
		$pdf->init(array('author' => 'FECDAS', 'title' => $factura->getConcepte()));
			
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
		if ($comanda->facturaComptabilitzada() == false) $text = '<b>FACTURA PROVISIONAL PENDENT COMPTABILITZAR</b>';
		else $text = '<b>FACTURA</b>';
		
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
		$tbl .= '<tr><td width="150" align="right" style="color:#555555;">Factura número:</td><td width="120" align="left"><b>' . $factura->getNumfactura() . '</b></td></tr>';
		$tbl .= '<tr><td align="right" style="color:#555555;">Data:</td><td align="left"><b>' . $factura->getDatafactura()->format('d/m/Y') . '</b></td></tr>';
		$tbl .= '<tr><td align="right" style="color:#555555;">CIF:</td><td align="left"><b>' . $club->getCif() . '</b></td></tr>';
		$tbl .= '</table>';
		
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$pdf->Ln(5);
		$pdf->setX($x_ini);
		
		$pdf->SetFont('dejavusans', '', 8, '', true);
		
		$tbl = '<table border="1" cellpadding="5" cellspacing="0" style="border-color: #000000; border-collapse: collapse;">
				<tr style="background-color:#CCCCCC; border-color: #000000;">
				<td width="65" align="center">Referència</td>
				<td width="215" align="left">Concepte</td>
				<td width="40" align="center">Uds.</td>
				<td width="60" align="center">Preu<br/>unitat</td>
				<td width="70" align="center">Subtotal</td>
				<td width="40" align="center">IVA</td>
				<td width="60" align="center">Import<br/>IVA</td>
				<td width="100" align="right">TOTAL</td>
				</tr>';
		
		$facturaSenseIVA = true;
		$mindetalls = 10;
		
		foreach ($comanda->getDetallsAcumulats() as $lineafactura) {
			if ($lineafactura['ivaunitat'] > 0) $facturaSenseIVA = false;
			
			error_log("===>".$lineafactura['ivaunitat']);
			
			$preuSenseIVA = $lineafactura['total'] * $lineafactura['preuunitat'];
			$valorIVA = $preuSenseIVA * $lineafactura['ivaunitat'];
			
			$tbl .= '<tr style="border-bottom: none;">';
			$tbl .= '<td style="border-right: 1px solid black;" align="center">' . $lineafactura['codi'].'</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="left">' . $lineafactura['producte'] .'</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="center">' . $lineafactura['total'] .'</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($lineafactura['preuunitat'], 2, ',', '.') . '€</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($preuSenseIVA, 2, ',', '.') . '€</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($lineafactura['ivaunitat']*100, 0, ',', '.') . '%</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($valorIVA, 2, ',', '.') . '€</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right"><span style="font-weight:bold;">';
			$tbl .= number_format($lineafactura['import'], 2, ',', '.') . '€</span></td>';
			$tbl .= '</tr>';
			
			$mindetalls--;
		}
		
		while ($mindetalls > 0) {
			$tbl .= '<tr style="border-bottom: none;">';
			for ($i = 0; $i < 8; $i++) $tbl .= '<td style="border-right: 1px solid black;">&nbsp;</td>';
			$tbl .= '</tr>';
			$mindetalls--;
		}
		
		$tbl .= '<tr style="background-color:#CCCCCC; ">';
		$tbl .= '<td colspan="7" align="right" style="background-color:#EEEEEE; height: 50px;  padding:10px 5px;"><span style="font-size:12px;"><br/>TOTAL FACTURA:</span></td>';
		$tbl .= '<td align="right"><span style="font-weight:bold;font-size:12px;"><br/>' . number_format($comanda->getTotalDetalls(), 2, ',', '.') .  ' €</span></td>';
		$tbl .= '</tr>';
		
		$tbl .= '</table>';
		
		$pdf->writeHTML($tbl, true, false, false, false, '');
		
		if ($facturaSenseIVA == 0) {
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

		if ($comanda->facturaComptabilitzada() == false) $text = '<b>FACTURA PROVISIONAL PENDENT COMPTABILITZAR';
		else $text = '<b>FACTURA';

		if ($comanda->comandaPagada() == true) {
			$text .= ' PAGADA</b>';
		} else {
			$text .= ' PENDENT DE PAGAMENT</b>';
		}
		
		$pdf->writeHTML($text, true, false, false, false, '');

		if ($comanda->comandaPagada() == true && $comanda->isFacturaValida() == false) {
			// Ha canviat la factura, mostra avís factura obsoleta
			$text = 'Aquesta factura ha quedat obsoleta per modificacions posteriors al pagament de la llista.<br/>';
			$text .= 'Per a obtenir la factura original, poseu-vos en contacte amb la federació.';
			$pdf->SetFont('dejavusans', '', 14, '', true);
			$y = $y_ini + 120;
			$x = $x_ini;
		
			//$pdf->writeHTML($text, true, false, false, false, 'L');
			$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'C', true);
		}
		
		
		
		// reset pointer to the last page
		$pdf->lastPage();
			
		$nomfitxer = "factura_" .  str_replace("/", "-", $factura->getNumfactura()) . "_" . $club->getCodi() . ".pdf";
		
		
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
			$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($reqId);
		
			if ($comanda != null) {
			
				$response = $this->albaratopdf($comanda);
			
				$this->logEntryAuth('PRINT ALBARA', $reqId);
			
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT ALBARA KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir l\'albarà, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	private function albaratopdf($comanda) { 
		/* Printar albarà */
		$club = $comanda->getClub();
		
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
					
		$pdf->init(array('author' => 'FECDAS', 'title' => $comanda->getConcepteComanda())); 
					
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
		$text = '<b>ALBARÀ #'. $comanda->getNumComanda() .'#</b>';
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
		$tbl .= '<tr><td width="150" align="right" style="color:#555555;">Comanda número:</td><td width="120" align="left"><b>' . $comanda->getNumComanda() . '</b></td></tr>';
		$tbl .= '<tr><td align="right" style="color:#555555;">Data:</td><td align="left"><b>' . $comanda->getDataentrada()->format('d/m/Y') . '</b></td></tr>';
		$tbl .= '</table>';
		
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$pdf->Ln(5);
		$pdf->setX($x_ini);
		
		$pdf->SetFont('dejavusans', '', 8, '', true);
		
		$tbl = '<table border="1" cellpadding="5" cellspacing="0" style="border-color: #000000; border-collapse: collapse;">
				<tr style="background-color:#CCCCCC; border-color: #000000;">
				<td width="65" align="center">Referència</td>
				<td width="215" align="left">Concepte</td>
				<td width="40" align="center">Uds.</td>
				<td width="60" align="center">Preu<br/>unitat</td>
				<td width="70" align="center">Subtotal</td>
				<td width="40" align="center">IVA</td>
				<td width="60" align="center">Import<br/>IVA</td>
				<td width="100" align="right">TOTAL</td>
				</tr>';
		
		$mindetalls = 10;
		
		foreach ($comanda->getDetallsAcumulats() as $lineafactura) {
			$preuSenseIVA = $lineafactura['total'] * $lineafactura['preuunitat'];
			$valorIVA = $preuSenseIVA * $lineafactura['ivaunitat'];
				
			$tbl .= '<tr style="border-bottom: none;">';
			$tbl .= '<td style="border-right: 1px solid black;" align="center">' . $lineafactura['codi'].'</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="left">' . $lineafactura['producte'] .'</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="center">' . $lineafactura['total'] .'</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($lineafactura['preuunitat'], 2, ',', '.') . '€</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($preuSenseIVA, 2, ',', '.') . '€</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($lineafactura['ivaunitat']*100, 0, ',', '.') . '%</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($valorIVA, 2, ',', '.') . '€</td>';
			$tbl .= '<td style="border-right: 1px solid black;" align="right"><span style="font-weight:bold;">';
			$tbl .= number_format($lineafactura['import'], 2, ',', '.') . '€</span></td>';
			$tbl .= '</tr>';
				
			$mindetalls--;
		}
		
		while ($mindetalls > 0) {
			$tbl .= '<tr style="border-bottom: none;">';
			for ($i = 0; $i < 8; $i++) $tbl .= '<td style="border-right: 1px solid black;">&nbsp;</td>';
			$tbl .= '</tr>';
			$mindetalls--;
		}
		
		$tbl .= '<tr style="background-color:#CCCCCC; ">';
		$tbl .= '<td colspan="7" align="right" style="background-color:#EEEEEE; height: 50px;  padding:10px 5px;"><span style="font-size:12px;"><br/>TOTAL ALBARÀ:</span></td>';
		$tbl .= '<td align="right"><span style="font-weight:bold;font-size:12px;"><br/>' . number_format($comanda->getTotalDetalls(), 2, ',', '.') .  ' €</span></td>';
		$tbl .= '</tr>';
		
		$tbl .= '</table>';
		
		$pdf->writeHTML($tbl, true, false, false, false, '');
		
		
		$pdf->Ln(5);
		
		$pdf->SetTextColor(100, 100, 100); // Gris
		$pdf->SetFont('dejavusans', '', 16, '', true);
		
		if ($comanda->comandaPagada() == true) {
			$text = '<b>-- ALBARÀ PAGAT --</b>';
			$pdf->writeHTML($text, true, false, false, false, '');
		
		} 
	
		// reset pointer to the last page
		$pdf->lastPage();
					
		// Close and output PDF document
		$response = new Response($pdf->Output("albara_" . $comanda->getNumComanda() . "_" . $club->getCodi() . ".pdf", "D"));
		$response->headers->set('Content-Type', 'application/pdf');
		return $response;
		
	}
	
	public function rebuttopdfAction(Request $request) {
		/* Rebut comanda */
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
			$reqId = 0;
			if ($request->query->has('id')) {
				$reqId = $request->query->get('id');
				$rebut = $this->getDoctrine()->getRepository('FecdasBundle:EntityRebut')->find($reqId);
	
				if ($rebut != null) {
					$this->rebuttopdf($rebut);
				}
			}
			/* Error */
			$this->logEntryAuth('PRINT REBUT KO', $reqId);
			$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir el rebut, poseu-vos en contacte amb la Federació' );
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	private function rebuttopdf($rebut) {
		/* Printar albarà */
		$club = $rebut->getClub();
		$comanda = null;
		if ($rebut->getComanda() != null) $comanda = $rebut->getComanda();
		
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
		$pdf->init(array('author' => 'FECDAS', 'title' => $rebut->getConcepteRebutLlarg()));
			
		$pdf->AddPage();
	
		// set color for background
		$pdf->SetFillColor(255, 255, 255); //Blanc
		// set color for text
		$pdf->SetTextColor(0, 0, 0); // Negre
	
		$y_ini = $pdf->getY();
		$x_ini = $pdf->getX();
	
		/*$pdf->SetFont('dejavusans', '', 16, '', true);
		$text = '<b>REBUT #'. $rebut->getNumRebut() .'#</b>';
		$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'L', true);
		$pdf->Ln(5);
	*/
		
		$pdf->setX($x_ini - 1.5);
		
		$pdf->SetFont('dejavusans', '', 9, '', true);
		$tbl = '<table border="0" cellpadding="5" cellspacing="0">';
		$tbl .= '<tr><td width="250" align="left"><b>FEDERACIÓ CATALANA <br/>D\'ACTIVITATS SUBAQUÀTIQUES</b></td></tr>';
		$tbl .= '<tr><td align="left">Moll de la Vela 1 (Zona Forum)<br/>';
		$tbl .= '08930 Sant Adrià de Besòs<br/>';
		$tbl .= 'Tel: 93 356 05 43  Fax: 93 356 30 73<br/>';
		$tbl .= 'NIF: Q5855006B</td></tr>';
		$tbl .= '</table>';
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$pdf->setY($y_ini + 5);
		$pdf->setX($pdf->getPageWidth() - 87);
	
		$tbl = '<table cellpadding="5" cellspacing="0" style="border: 0.3em solid #333333;">';
		$tbl .= '<tr style="border: 0.2em solid #333333;"><td colspan="2" width="250" align="center" style="color:#555555;border: 0.2em solid #555555;">';
		$tbl .= '<span style="font-weight:bold;font-size:16px;">REBUT</span></td></tr>';
		$tbl .= '<tr style="border: 0.2em solid #333333;"><td width="125" align="right" style="color:#555555; border: 0.2em solid #333333;">Número:</td>';
		$tbl .= '<td align="left" style="border: 0.2em solid #333333;"><b>' . $rebut->getNumRebut() . '</b></td></tr>';
		$tbl .= '<tr style="border: 0.2em solid #333333;"><td align="right" style="color:#555555; border: 0.2em solid #333333;">Data:</td>';
		$tbl .= '<td align="left" style="border: 0.2em solid #333333;"><b>' . $rebut->getDatapagament()->format('d/m/Y') . '</b></td></tr>';
		$tbl .= '</table>';
		
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$pdf->setX($x_ini);
		
		$pdf->Ln(12);
		
		$pdf->SetFont('dejavusans', '', 11, '', true);
		$pdf->SetTextColor(60, 60, 60); // Gris
		$tbl = '<h3 style="border-bottom: 0.2em solid #333333;">Ha rebut de:</h3>';
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$pdf->Ln(10);
		$pdf->setX($x_ini);
		
		$pdf->SetFont('dejavusans', '', 10, '', true);
		$pdf->SetTextColor(0, 0, 0);
		
		$tbl = '<p><b>'.$club->getNom().'</b>, amb CIF <i>'.$club->getCif().'</i> i adreça ';
		$tbl .= '<i>'.$club->getAddradreca().',  '.$club->getAddrpob().'. '.$club->getAddrcp().' ('.$club->getAddrprovincia().')</i></p>';
		
		$pdf->writeHTML($tbl, false, false, false, false, '');
		$pdf->Ln(15);
		
		$pdf->SetFont('dejavusans', '', 11, '', true);
		$pdf->SetTextColor(60, 60, 60); // Gris
		$tbl = '<h3 style="border-bottom: 0.2em solid #333333;">En concepte de:</h3>';
		$pdf->writeHTML($tbl, false, false, false, false, '');
		$pdf->Ln(10);
		
		if ($comanda == null) {
			$pdf->SetFont('dejavusans', '', 14, '', true);
			$pdf->SetTextColor(0, 0, 0);
			
			$tbl = '<p><i>'.($rebut->getComentari()==null || $rebut->getComentari() == ''?'Ingrés a compte':$rebut->getComentari()).'</i></p>';
			
			$pdf->writeHTML($tbl, false, false, false, false, '');
			
			$pdf->Ln(20);
			
			$pdf->SetFont('dejavusans', '', 11, '', true);
			$pdf->SetTextColor(120, 120, 120); // Gris
			$tbl = '<p style="border-bottom: 0.2em solid #333333;">&nbsp;</p>';
			$pdf->writeHTML($tbl, false, false, false, false, '');
			
		} else {
		
			$pdf->SetFont('dejavusans', '', 8, '', true);
			$pdf->SetTextColor(0, 0, 0); 	
			$tbl = '<table border="1" cellpadding="5" cellspacing="0" style="border-color: #000000; border-collapse: collapse;">
					<tr style="background-color:#EEEEEE; border-color: #000000;">
					<td width="65" align="center">Referència</td>
					<td width="205" align="left">Concepte</td>
					<td width="40" align="center">Uds.</td>
					<td width="60" align="center">Preu<br/>unitat</td>
					<td width="70" align="center">Subtotal</td>
					<td width="40" align="center">IVA</td>
					<td width="60" align="center">Import<br/>IVA</td>
					<td width="100" align="right">TOTAL</td>
					</tr>';
		
			$mindetalls = 10;
		
			foreach ($comanda->getDetallsAcumulats() as $lineafactura) {
				$preuSenseIVA = $lineafactura['total'] * $lineafactura['preuunitat'];
				$valorIVA = $preuSenseIVA * $lineafactura['ivaunitat'];
		
				$tbl .= '<tr style="border-bottom: none;">';
				$tbl .= '<td style="border-right: 1px solid black;" align="center">' . $lineafactura['codi'].'</td>';
				$tbl .= '<td style="border-right: 1px solid black;" align="left">' . $lineafactura['producte'] .'</td>';
				$tbl .= '<td style="border-right: 1px solid black;" align="center">' . $lineafactura['total'] .'</td>';
				$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($lineafactura['preuunitat'], 2, ',', '.') . '€</td>';
				$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($preuSenseIVA, 2, ',', '.') . '€</td>';
				$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($lineafactura['ivaunitat']*100, 0, ',', '.') . '%</td>';
				$tbl .= '<td style="border-right: 1px solid black;" align="right">' . number_format($valorIVA, 2, ',', '.') . '€</td>';
				$tbl .= '<td style="border-right: 1px solid black;" align="right"><span style="font-weight:bold;">';
				$tbl .= number_format($lineafactura['import'], 2, ',', '.') . '€</span></td>';
				$tbl .= '</tr>';
		
				$mindetalls--;
			}
		
			while ($mindetalls > 0) {
				$tbl .= '<tr style="border-bottom: none;">';
				for ($i = 0; $i < 8; $i++) $tbl .= '<td style="border-right: 1px solid black;">&nbsp;</td>';
				$tbl .= '</tr>';
				$mindetalls--;
			}
		
			$tbl .= '<tr>';
			$tbl .= '<td colspan="7" align="right" style="background-color:#EEEEEE; height: 50px;  padding:10px 5px;"><span style="font-size:12px;"><br/>IMPORT DEL REBUT:</span></td>';
			$tbl .= '<td align="right"><span style="font-weight:bold;font-size:12px;"><br/>' . number_format($rebut->getImport(), 2, ',', '.') .  ' €</span></td>';
			$tbl .= '</tr>';
		
			$tbl .= '</table>';
		
			$pdf->writeHTML($tbl, true, false, false, false, '');
		}
	
		$pdf->Ln(10);
	
		$pdf->SetTextColor(100, 100, 100); // Gris
		$pdf->SetFont('dejavusans', '', 16, '', true);
	
		// reset pointer to the last page
		$pdf->lastPage();
			
		// Close and output PDF document
		$response = new Response($pdf->Output("rebut_" . $rebut->getNumRebut() . "_" . $club->getCodi() . ".pdf", "D"));
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
				$titolPlastic = $this->getTitolPlastic($llicencia->getParte(), $datacaduca);

				$pdf->SetFont('helvetica', 'B', 9.5, '', true);
				$pdf->SetTextColor(230, 230, 230); // Gris
				$y = $y_ini + 24;
				$x = $x_ini + 62;

				$pdf->SetY($y);
				$pdf->SetX($x);
				$pdf->MultiCell($height,$width,$titolPlastic,0,'C',1);
				
				
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


	public function imprimirparteAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$em = $this->getDoctrine()->getManager();
	
		$parteid = $request->query->get("id");
	
		$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
	
		if ($parte != null) {
			$llicenciesSorted = $parte->getLlicenciesSortedByName();

			// Printer EVOLIS PEBBLE 4 - ISO 7810, paper size CR80 BUSINESS_CARD_ISO7810 => 54x86 mm
			// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
			$format = \TCPDF_STATIC::getPageSizeFromFormat('BUSINESS_CARD_ISO7810');
			$pdf = new TcpdfBridge('L', PDF_UNIT, $format, true, 'UTF-8', false);
				
			$pdf->init(array('author' => 'FECDAS',
						'title' => 'Llicència FECDAS' . date("Y")));

			$pdf->setPrintFooter(false);
			$pdf->setPrintHeader(false);
				
			// zoom - layout - mode
			$pdf->SetDisplayMode('real', 'SinglePage', 'UseNone');
			$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
			$pdf->SetMargins(5, 5, 5);
			$pdf->SetAutoPageBreak 	(true, 5);
			$pdf->SetFont('dejavusans', 'B', 6, '', true);
			$pdf->SetTextColor(255, 255, 255); 

			$width = 86; //Original
			$height = 54; //Original
			
			foreach ($llicenciesSorted as $llicencia) {

				$persona = $llicencia->getPersona();
				if ( $persona == null) continue;				
				// Add a page
				$pdf->AddPage('L', 'BUSINESS_CARD_ISO7810');

				$y = $pdf->getY() + 10;
	
	 			$pdf->setVisibility('screen'); // or screen
				$pdf->Rect(0, 0, $width, $height, 'DF', array(), array(220, 220, 200));
				
				$pdf->setVisibility('all');
	
				$pdf->SetX(5);
				$pdf->SetY($y);
				$pdf->Cell(0, 0, $persona->getNomCognoms(), 0, 1, 'L');
				$y += 5;	

				$pdf->SetY($y);
				$pdf->Cell(0, 0, $persona->getDni(), 0, 1, 'L');
				$y += 5;

				$pdf->SetY($y);
				$pdf->Cell(0, 0, $llicencia->getCategoria()->getCategoria(), 0, 1, 'L');
				$y += 5;
				
				$pdf->SetY($y);
				$pdf->Cell(0, 0, $persona->getDatanaixement()->format('d/m/Y'), 0, 1, 'L');
				$y += 5;
				
				$pdf->SetY($y);
				$pdf->Cell(0, 0, $parte->getClub()->getNom(), 0, 1, 'L');
				$y += 5;
				
				$pdf->SetY($y);
				$pdf->Cell(0, 0, $parte->getDatacaducitat('printparte')->format('d/m/Y'), 0, 1, 'L');

				
				// get current vertical position
/*				$y_ini = $pdf->getY();
				$x_ini = $pdf->getX();
				
				$y = $y_ini;
				$x = $x_ini;

				$x += 45;
				$y += 10;
				
				$width = 86; //Original
				$height = 54; //Original
				
				// set color for text and font
				$pdf->SetTextColor(10, 10, 10); // Gris
				//$pdf->SetFillColor(0, 0, 0); //
				$pdf->SetFont('dejavusans', 'B', 4.5, '', true);
				
				$x = $x_ini + 54.2;
				$y = $y_ini + 38.6; // 39.2
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getPersona()->getNomCognoms(), 0, 0, 0, true, 'L', true);
				
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
				$datacaduca = $parte->getDatacaducitat('printparte');
				$x += 41;
				$pdf->writeHTMLCell(0, 0, $x, $y, $datacaduca->format('d/m/Y'), 0, 0, 0, true, 'L', true);
				
				// Tipus de llicència
				// Taula TipoParte LL_L1 + LL_L1 + LL_L3 
				$titolPlastic = $this->getTitolPlastic($llicencia->getParte(), $datacaduca);
				
				$pdf->SetFont('helvetica', 'B', 9.5, '', true);
				$pdf->SetTextColor(20, 20, 20); // Gris
				$y = $y_ini + 24;
				$x = $x_ini + 62;
				
				$pdf->SetY($y);
				$pdf->SetX($x);
				$pdf->MultiCell($height,$width,$titolPlastic,0,'C',1);
				*/
			}
			// reset pointer to the last page
			$pdf->lastPage();

			$current = $this->getCurrentDate();
				
			$parte->setDatamodificacio($current);
			//$parte->setImpres($current);
					
			$em->flush();
	
			$this->logEntryAuth('IMPRES PARTE', $parteid);
			
			// Close and output PDF document
			$response = new Response($pdf->Output("llicencies_impressio_parte_".$parte->getId(). ".pdf", "D"));
			$response->headers->set('Content-Type', 'application/pdf');
			return $response;
				
		} else {
			$this->logEntryAuth('IMPRES PARTE ERROR', $parteid);
		}
	
		return $this->redirect($this->generateUrl('FecdasBundle_recents'));
	}
	
	private function getTitolPlastic($parte, $datacaduca = null) {
		if ($parte == null) return '';
		$anyLlicencia = $parte->getDataalta()->format('Y');
		if ($datacaduca == null) $datacaduca = $parte->getDatacaducitat('titolPlastic');
		$anyFinalLlicencia = $datacaduca->format('Y');
		$tipus = $parte->getTipus();
	
		$titolPlastic = mb_strtoupper($tipus->getTitol(), 'UTF-8');
	
		$titolPlastic = str_replace("__DESDE__", $anyLlicencia, $titolPlastic);
		$titolPlastic = str_replace("__FINS__", $anyFinalLlicencia, $titolPlastic);
	
		return $titolPlastic;
	}
	
}
