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
			$factura = $this->getDoctrine()->getRepository('FecdasBundle:EntityFactura')->find($reqId);
		
			if ($factura != null) {
				$comanda = $factura->getComanda();
				if ($factura->esAnulacio() == true) $comanda = $factura->getComandaAnulacio();
				
				if ($comanda->comandaConsolidada() != true) return new Response('encara no es pot imprimir la factura');
				
				if ($comanda->getClub() != null) {
				
					$response = $this->facturatopdf($factura, $comanda);
					
					$this->logEntryAuth('PRINT FACTURA OK', $reqId);
					
					return $response;
				}
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT FACTURA KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir la factura, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	private function facturatopdf($factura, $comanda) {
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$club = $comanda->getClub();
		
		// Per veure-ho => acroread 86,3 %
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
		$pdf->init(array('author' => 'FECDAS', 'title' => $factura->getConcepte()));
			
		$pdf->setPrintFooter(false);
		$pdf->setPrintHeader(false);
		
		// set image scale factor
		//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->AddPage();
		$pdf->setCellPaddings(0,0,0,0);
		$pdf->SetFont('freesans');	
		// set color for background
		$pdf->SetFillColor(255, 255, 255); //Blanc
		// set color for text
		$pdf->SetTextColor(0, 51, 102); // Blau fosc 003366		
		
		$y_margin = 18;
		$l_margin = 26;
		$r_margin = 21;
		
		//$dim = $pdf->getPageDimensions();  //$dim['w'] = 595.276  $dim['h']
		//$w_half = $dim['w']/2; // 300 aprox
		
		$w_half = 105 - $l_margin;
		
		$y_logos = $y_margin;
		$x_logos = $l_margin;
		$h_logos = 23;
		$h_fedelogo = 24;
		$w_fedelogo = 20;
		$h_genelogo = 12;
		$w_genelogo = 35;
		$h_esportlogo = 12;
		$w_esportlogo = 30;
		
		$y_fedeinfo = $y_margin;
		$x_fedeinfo = $l_margin + $w_half;
		$h_fedeinfo = 42;
		$y_clubinfo = $y_margin + $h_logos;
		$x_clubinfo = $l_margin;
		$h_clubinfo = 41;
		$y_factuinfo = $y_margin + $h_fedeinfo;
		$offset_factuinfo = 30;
		$x_factuinfo = $l_margin + $w_half+$offset_factuinfo;
		$h_factuinfo = 22;
		$y_taula = $y_margin + 64;
		$x_taula = $l_margin;
		$h_taula = 145;
		$y_taula2 = $y_taula + 80;
		$y_rebut = $y_factuinfo + $h_factuinfo + $h_taula;
		$x_rebut = $l_margin;
		$h_rebut = 55;

		$y = $y_clubinfo; //$pdf->getY();
		$x = $x_clubinfo; //$pdf->getX();

		
		//$showTemplate = !$this->isCurrentAdmin(); // Remei no mostrar elements fixes
		$showTemplate = ($this->get('session')->get('username', '') != self::MAIL_FACTURACIO) &&
						($this->get('session')->get('username', '') != self::MAIL_FACTURACIO2);  
		
		//$pdf->Rect($x_logos, $y_logos, $w_half, $h_logos - 1, 'F', '', array(255, 0, 0) );  // red 				
		//$pdf->Rect($x_fedeinfo, $y_fedeinfo, $w_half+5, $h_fedeinfo - 1, 'F', '', array(0, 255, 0) ); // green
		//$pdf->Rect($x_clubinfo, $y_clubinfo, $w_half, $h_clubinfo - 1, 'F', '', array(0, 0, 255) ); // blue
		//$pdf->Rect($x_factuinfo, $y_factuinfo, $w_half - $offset_factuinfo, $h_factuinfo - 1, 'F', '', array(255, 255, 0) ); // groc
		//$pdf->Rect($x_taula, $y_taula, $w_half*2, $h_taula - 1, 'F', '', array(0, 255, 255) ); // cyan
		//$pdf->Rect($x_rebut, $y_rebut + 1, $w_half*2, $h_rebut, 'F', '', array(240, 240, 240) ); // cyan
		
		
		$pdf->SetMargins($l_margin, $y_margin, $r_margin);
		$pdf->SetAutoPageBreak 	(false, 15);
		$pdf->SetFontSize(12);
		
		if($showTemplate == true) {
			/* LOGOS */		
			// file, x, y, w, h, format, link alineacio, resize, dpi, palign, mask, mask, border, fit, hidden, fitpage, alt, altimg			
			$pdf->Image('images/fecdaslogopdf.gif', $x_logos, $y_logos, 
						$w_fedelogo, 0 , 'gif', '', 'LT', true, 320, 
						'', false, false, array(''),
						'LT', false, false);
			$pdf->Image('images/logo-generalitat.jpg', $x_logos+$w_fedelogo+2, $y_logos, 
						$w_genelogo, 0 , 'jpeg', '', 'T', true, 320, 
						'', false, false, array(''),
						'CT', false, false);
			$pdf->Image('images/esport-logo.jpg', $x_logos+$w_fedelogo+4.5, $y_logos+$h_genelogo, 
						$w_esportlogo, 0 , 'jpeg', '', 'B', true, 320, 
						'', false, false, array(''),
						'CB', false, false);
				
			/* FEDE INFO */
			
			$tbl = '<p align="right" style="padding:0;"><span style="font-size:16px;">FEDERACIÓ CATALANA<br/>D\'ACTIVITATS SUBAQUÀTIQUES</span><br/>';
			$tbl .= '<span style="font-size:11px;">Moll de la Vela, 1 (Zona Forum)<br/>';
			$tbl .= '08930 Sant Adrià de Besòs<br/>';
			$tbl .= 'Tel: 93 356 05 43 / Fax: 93 356 30 73<br/>';
			$tbl .= 'Adreça electrònica: info@fecdas.cat<br/>';
			$tbl .= 'www.fecdas.cat<br/>';
			$tbl .= 'NIF: Q5855006B</span></p>';
			$pdf->writeHTMLCell($w_half+5, $h_fedeinfo, $x_fedeinfo, $y_fedeinfo, $tbl, '', 1, false, true, 'R', false);
		}	
		
		/* CLUB INFO */	
		$pdf->SetTextColor(0, 0, 0); // Negre	
		$pdf->SetFontSize(16);
		$text = '<br/><b>FACTURA '.($factura->esAnulacio()?'ANUL·LACIÓ':'').'</b>';
		
		$pdf->writeHTMLCell($w_half, 0, $x_clubinfo, $y_clubinfo, $text, '', 1, false, true, 'L', false);
		
		$pdf->SetFontSize(11);
		$tbl = '<p align="left" style="padding:0;"><b>' . $club->getNom(). '</b><br/>';
		$tbl .= '' . $club->getAddradreca() . '<br/>';
		$tbl .= '' . $club->getAddrcp() . " - " . $club->getAddrpob() . '<br/>';
		$tbl .= '' . $club->getAddrprovincia() . '<br/>';
		$tbl .= 'Telf: ' . $club->getTelefon();

		$pdf->writeHTMLCell($w_half, 0, $x_clubinfo, $y_clubinfo + 10, $tbl, '', 1, false, true, 'L', false);
		
		/* FACTU INFO */	
		$pdf->SetFontSize(8);
		if ($showTemplate == true) {
			$tbl = '<p align="left" style="padding:0; color: #003366;">Factura número:</p><br/>';
			$tbl .= '<p align="left" style="padding:0; color: #003366;">Data:<br/>';
			$tbl .= 'NIF:</p>';
			$pdf->writeHTMLCell($w_half - $offset_factuinfo, 0, $x_factuinfo, $y_factuinfo, $tbl, '', 1, false, true, 'R', false);
		}
		
		$tbl  = '<p align="right" style="padding:0;"><b>' . $factura->getNumfactura(). '</p><br/>';
		$tbl .= '<p align="right" style="padding:0;">' . $factura->getDatafactura()->format('d/m/Y') . '<br/>';
		$tbl .= $club->getCif() . '</p>';
		$pdf->writeHTMLCell($w_half - $offset_factuinfo-8, 0, $x_factuinfo+8, $y_factuinfo, $tbl, '', 1, false, true, 'R', false);
		
		/* TAULA DETALL */
		$pdf->SetFontSize(8);
		$facturaSenseIVA = true;
		if ($factura->getDetalls() != null) {
			
			if ($showTemplate == true) {	
				$tbl = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
						<tr>
						<td width="96" align="center" style="border: 1px solid #003366; color:#003366;">REFERÈNCIA</td>
						<td width="240" align="center" style="border: 1px solid #003366; color:#003366;">CONCEPTE</td>
						<td width="58" align="center" style="border: 1px solid #003366; color:#003366;">QUANT.</td>
						<td width="81" align="center" style="border: 1px solid #003366; color:#003366;">PREU</td>
						<td width="86" align="center" style="border: 1px solid #003366; color:#003366;">IMPORT</td>
						</tr>';
				
				// En blanc
				$tbl .= '<tr>';
				$tbl .= '<td style="height: 262px; border: 1px solid #003366;" align="center">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="left">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="center">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="right">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="right">&nbsp;</td>';
				$tbl .= '</tr>';
			
				$tbl .= '</table>';
				
				$pdf->writeHTMLCell(0, 0, $x_taula, $y_taula, $tbl, '', 2, false, true, 'L', false);
				
				$tbl = '<table border="0" cellpadding="2" cellspacing="0" style="border-color: #003366; border-collapse: collapse; ">
						<tr>
						<td width="96" align="left" style="height: 41px;border: 1px solid #003366; color:#003366;">&nbsp;&nbsp;&nbsp;TOTAL</td>
						<!-- 240 entre els dos -->
						<td width="95" align="center" style="border: 1px solid #003366; color:#003366;">DTE</td>
						<td width="145" align="center" style="border: 1px solid #003366; color:#003366;">BASE IMP.</td>
						<!-- 58 + 81 + 86 = 225 entre els dos -->
						<td width="90" align="center" style="border: 1px solid #003366; color:#003366;">IVA</td>
						<td width="135" align="center" style="border: 1px solid #003366; color:#003366;">TOTAL FACTURA</td>
						</tr>';
				$tbl .= '<tr><td colspan="4" align="left" style="height: 41px; border: 1px solid #003366; color:#003366;">&nbsp;&nbsp;&nbsp;Altres càrrecs</td>';
				$tbl .= '<td align="center" style="border: 1px solid #003366; color:#003366;">Import</td></tr>';
				$tbl .= '<tr style="border-bottom: none;"><td colspan="4" style="height: 41px;">&nbsp;</td>';
				$tbl .= '<td align="center" style="border: 1px solid #003366; color:#003366;">TOTAL A PAGAR<span style="font-weight:bold;font-size:12px;">&nbsp;</span></td></tr>';
				$tbl .= '</table>';
				
				//$y_taula2 = $pdf->getY();
				$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 1, false, true, 'L', false);
			}
			
			// Sistema nou del 2015.
			//$detallsArray = json_decode($factura->getDetalls(), false, 512, JSON_UNESCAPED_UNICODE);
			$detallsArray = json_decode($factura->getDetalls(), true);
			
			$pdf->SetTextColor(0, 0, 0); // Negre	
			$pdf->setY($y_taula + 10);
			$pdf->SetFontSize(10);
			foreach ($detallsArray as $lineafactura) {
				if ($lineafactura['ivaunitat'] > 0) $facturaSenseIVA = false;
					
				$preuSenseIVA = $lineafactura['total'] * $lineafactura['preuunitat'];
				
				$strExtra = '';	
				if (isset($lineafactura['extra']) && is_array($lineafactura['extra'])) {  // Noms persones llicències
					foreach ($lineafactura['extra'] as $extra) {
						$strExtra .= '<br/> -&nbsp;'.$extra;
					}
				}	
					
				$tbl = '<table border="0" cellpadding="5" cellspacing="0"><tr>
					<td width="96" align="center">'.$lineafactura['codi'].'</td>
					<td width="240" align="left">'.$lineafactura['producte'].$strExtra.'</td>
					<td width="58" align="center">'.$lineafactura['total'].'</td>
					<td width="81" align="center">'.number_format($lineafactura['preuunitat'], 2, ',', '.').'€</td>
					<td width="86" align="center"><span style="font-weight:bold;">'.number_format($lineafactura['import'], 2, ',', '.').'€</span></td>
					</tr></table>';	
				
			
				$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 2, false, true, 'L', false);
			
			}
			$pdf->SetFontSize(12);
			// PEU 1 TOTAL PARCIAL
			$pdf->setY($y_taula2+3);
			$tbl = '<table border="0" cellpadding="6" cellspacing="0"><tr>
					<td width="96" align="center">'.number_format($factura->getImport(), 2, ',', '.').' €</td>
					<td width="95" align="center">--</td>
					<td width="145" align="center">&nbsp;</td>
					<td width="90" align="center">&nbsp;</td>
					<td width="135" align="center"><span style="font-weight:bold;">'.number_format($factura->getImport(), 2, ',', '.').' €</span></td>
					</tr></table>';	
			
			$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 2, false, true, 'L', false);
			
			$pdf->SetFontSize(16);
			// PEU 2 - TOTAL FINAL
			$pdf->setY($y_taula2+26);
			$tbl = '<table border="0" cellpadding="6" cellspacing="0"><tr>
					<td width="96">&nbsp;</td><td width="95">&nbsp;</td><td width="145">&nbsp;</td><td width="90">&nbsp;</td>
					<td width="135" align="center"><span style="font-weight:bold;">'.number_format($factura->getImport(), 2, ',', '.').' €</span></td>
					</tr></table>';	
			$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 2, false, true, 'L', false);

			$pdf->SetTextColor(0, 51, 102); // Blau fosc 003366	
			
		} else {
			
			$pdf->SetFontSize(16);	
				
			$tbl = '<table border="1" cellpadding="50" cellspacing="0" style="color:#555555;">';
			$tbl .= '<tr><td><p style="line-height: 2; color:#000000;"><b>Factura corresponent a la llista de llicències ' . $comanda->getNumComanda();
			$tbl .= ' amb un import de '.number_format($factura->getImport(), 2, ',', '.') .  ' €</b></p></td></tr>';
			$tbl .= '</table>';
		
			$pdf->writeHTMLCell(0, 0, $x_taula, $y_taula, $tbl, '', 1, false, true, 'L', false);
		}
		
		if ($facturaSenseIVA == true) {
			// set color for text
			$pdf->SetTextColor(0, 51, 102); // Blau
			$pdf->SetFont('dejavusans', '', 7.5, '', true);
			$text = '<p>Factura exempta d\'I.V.A. segons la llei 49/2002</p>';
			$pdf->writeHTMLCell($w_half*2, 0, $x_taula, $y_taula2+26, $text, '', 1, false, true, 'L', false);
		}
		
		$pdf->SetTextColor(0, 0, 0); // Negre
		$pdf->SetFontSize(8);

		if ($factura->esAnulacio()) {
			$text = 'ANUL·LACIÓ FACTURA ORIGINAL '.$comanda->getFactura()->getNumFactura().'';
		} else {
			$text = 'FACTURA CORRESPONENT A LA COMANDA '.$comanda->getNumComanda().'. ';
		}
		
		$pdf->writeHTMLCell($w_half*2, 0, $x_taula, $y_taula2+31, $text, '', 1, false, true, 'L', false);

		
		/* ESPAI REBUT */	
		if ($comanda->comandaPagada() == true) {
			$pdf = $this->rebuttopdf($comanda->getRebut(), $pdf);
			//$pdf->writeHTMLCell($w_half*2, $h_rebut, $x_rebut, $y_rebut, '', '', 1, false, true, 'L', false);
		}

		// reset pointer to the last page
		$pdf->lastPage();
			
		$nomfitxer = "factura_" .  str_replace("/", "-", $factura->getNumfactura()) . "_" . $club->getCodi() . ".pdf";
		
		// Close and output PDF document
		$response = new Response($pdf->Output($nomfitxer, "D"));
		$response->headers->set('Content-Type', 'application/pdf');
		return $response;
	}
	
	public function rebuttopdfAction(Request $request) {
		/* Rebut comanda */
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$reqId = $request->query->get('id', 0);
		$rebut = $this->getDoctrine()->getRepository('FecdasBundle:EntityRebut')->find($reqId);
		if ($rebut != null || $rebut->getClub() == null) {
			$pdf = $this->rebuttopdf($rebut);
			
			if ($pdf != null) {	
				// Close and output PDF document
				$response = new Response($pdf->Output("rebut_" . $rebut->getNumRebut() . "_" . $rebut->getClub()->getCodi() . ".pdf", "D"));
				$response->headers->set('Content-Type', 'application/pdf');
				
				$this->logEntryAuth('PRINT REBUT OK', $reqId);
				return $response;
			}
		}
		/* Error */
		$this->logEntryAuth('PRINT REBUT KO', $reqId);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut imprimir el rebut, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	private function rebuttopdf($rebut, $pdf = null) {
		/* Printar rebut */
		$club = $rebut->getClub();
		
		// Per veure-ho => acroread 86,3 %
		if ($pdf == null) {
			// Nou rebut format 1/3 de A4 =>  array(  210,  297);
			// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
			// Papers => 		/vendor/tcpdf/includes/tcpdf_static.php
			$format = array(210, 99);
			$pdf = new TcpdfBridge('L', PDF_UNIT, $format, true, 'UTF-8', false);
				
			$pdf->init(array('author' => 'FECDAS', 'title' => $rebut->getConcepteRebutLlarg()));
				
			$pdf->setPrintFooter(false);
			$pdf->setPrintHeader(false);
			
			// set image scale factor
			//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
			//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
			$pdf->AddPage();
			
			$y_margin = 5; // 18 -> 10

		} else {

			$y_margin = 15 + $pdf->getY(); 
		}
		
		$r_margin = 26;
		$l_margin = 35; // 26 -> 32
		
		//$dim = $pdf->getPageDimensions();  //$dim['w'] = 595.276  $dim['h']
		//$w_half = $dim['w']/2; // 300 aprox
		
		//$w_half = 105 - $l_margin;
		
		$y_corp = $y_margin;
		$x_corp = 12;
		$ry_corp = 45;
		$rx_corp = 20;
		
		$h_fedelogo = 12;
		$w_fedelogo = 10;
		$h_genelogo = 6;
		$w_genelogo = 16;
		$h_esportlogo = 6;
		$w_esportlogo = 13;
		$w_fedeinfo = 80;
		$h_fedeinfo = 20;
		
		$y_header_row1 = $y_margin; 				// 					Núm: xxx
		$y_header_row2 = $y_margin + 8;				// Hem rebut: xxxx	NIF: xxxx
		$x_header_row1 = $l_margin;
		//$x_header_col2 = $pdf->getPageWidth() - ($r_margin - 20);
		$x_header_col2 = $l_margin +115;
		
		$y_quantitat = $y_header_row2 + 11;
		$x_quantitat = $l_margin;
		$x_quantitat_offset = 26;
		$y_quantitat_offset = -3;
		$h_quantitat = 13;
		$w_quantitat = 122;
		
		$y_concepte = $y_quantitat + 16;
		$x_concepte = $l_margin;
		$x_concepte_offset = 29;
		$y_concepte_offset = -3;
		$h_concepte = 22;
		$w_concepte = 119;
		
		$y_total = $y_concepte + $h_concepte + 3; 		// Mitjançant
		$x_total = $l_margin;
		$y_total_offset_1 = 6; 						// Import
		$x_total_col2 = $l_margin +60;
		$y_total_offset_2 = 10; 					// Lloc i data
		
		//$pdf->Rect($x_rebut, $y_rebut + 1, $w_half*2, $h_rebut, 'F', '', array(240, 240, 240) ); // cyan
		
		$pdf->setCellPaddings(0,0,0,0);
		$pdf->SetFont('freesans');	
		// set color for background
		$pdf->SetFillColor(255, 255, 255); //Blanc
		// set color for text
		$pdf->SetTextColor(0, 51, 102); // Blau fosc 003366		
		
		$pdf->SetMargins($l_margin, $y_margin, $r_margin);
		$pdf->SetAutoPageBreak 	(false, 5);
		$pdf->SetFontSize(7.8);
		
		//$showTemplate = !$this->isCurrentAdmin(); // Remei no mostrar elements fixes
		$showTemplate = ($this->get('session')->get('username', '') != self::MAIL_FACTURACIO) &&
						($this->get('session')->get('username', '') != self::MAIL_FACTURACIO2);  
		
		if ($showTemplate == true) {
			/* LOGOS */		
			// Start Transformation
	    	$pdf->StartTransform();
	    	// Rotate 90 degrees
	   		$pdf->Rotate(90, $l_margin + $rx_corp , $y_corp + $ry_corp);
			
			// file, x, y, w, h, format, link,  alineacio, resize, dpi, palign, mask, mask, border, fit, hidden, fitpage, alt, altimg			
			$pdf->Image('images/fecdaslogopdf.gif', $x_corp, $y_corp, 
							$w_fedelogo, 0 , 'gif', '', 'LT', true, 320, 
							'', false, false, array(''),
							'LT', false, false);
			$pdf->Image('images/logo-generalitat.jpg', $x_corp+$w_fedelogo+2, $y_corp, 
							$w_genelogo, 0 , 'jpeg', '', 'T', true, 320, 
							'', false, false, array(''),
							'CT', false, false);
			$pdf->Image('images/esport-logo.jpg', $x_corp+$w_fedelogo+4.5, $y_corp+$h_genelogo, 
							$w_esportlogo, 0 , 'jpeg', '', 'B', true, 320, 
							'', false, false, array(''),
							'CB', false, false);
			
			/* FEDE INFO */
			$txt = '<p align="left" style="padding:0;line-height: 1"><span style="font-size:12px;">FEDERACIÓ CATALANA<br/>D\'ACTIVITATS SUBAQUÀTIQUES</span><br/>';
			$txt .= '<span style="font-size:6.5px;">Moll de la Vela, 1 (Zona Forum)<br/>';
			$txt .= '08930 Sant Adrià de Besòs<br/>';
			$txt .= 'Tel: 93 356 05 43 / Fax: 93 356 30 73<br/>';
			$txt .= 'Adreça electrònica: info@fecdas.cat<br/>';
			$txt .= 'www.fecdas.cat<br/>';
			$txt .= 'NIF: Q5855006B</span></p>';
			$pdf->writeHTMLCell($w_fedeinfo, $h_fedeinfo, $x_corp+$w_fedelogo+$w_genelogo+10, $y_corp, $txt, '', 1, false, true, 'L', false);
			
			
	   		// Stop Transformation
	   		$pdf->StopTransform();
	   	}
		
		//$pdf->setFontSpacing(0.5);
    	//$pdf->setFontStretching(105);
		
		/* REBUT INFO */	
		$hideText = '';
		if ($showTemplate != true) $hideText = 'color:white;'; 
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Rebut núm.&nbsp;&nbsp;&nbsp;';
		$txt .= '<span style="color:#000000; font-size:12px;">'.$rebut->getNumRebut().'</span></p>';
		$pdf->writeHTMLCell(50, 0, $x_header_col2, $y_header_row1, $txt, '', 1, false, true, 'L', false);
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Hem rebut de:&nbsp;&nbsp;&nbsp;';
		$txt .= '<span style="color:#000000; font-size:12px;">'.$club->getNom().'</span></p>';
		$pdf->writeHTMLCell(0, 0, $x_header_row1, $y_header_row2, $txt, '', 1, false, true, 'L', false);
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">NIF:&nbsp;&nbsp;&nbsp;';
		$txt .= '<span style="color:#000000; font-size:12px;">'.$club->getCif().'</span></p>';
		$pdf->writeHTMLCell(50, 0, $x_header_col2, $y_header_row2, $txt, '', 1, false, true, 'L', false);
		
		/* REBUT QUANTITAT */	
		$f = new \NumberFormatter("ca_ES.utf8", \NumberFormatter::SPELLOUT);
    	$importFloor = floor($rebut->getImport());
    	$importDec = floor(($rebut->getImport() - $importFloor)*100);
    	$importTxt = $f->format($importFloor);// . ($importDec < 0.001)?'':' amb '. $f->format($importDec*100);
    	$importTxt .= ($importDec == 0)?'':' amb '. $f->format($importDec);
		$importTxt .= ' Euros';
		
		if ($showTemplate == true) {
			$txt = '<p align="left">la quantitat de</p>';
			$pdf->writeHTMLCell(0, 0, $x_quantitat, $y_quantitat, $txt, '', 1, false, true, 'L', false);
			$pdf->Rect($x_quantitat + $x_quantitat_offset, $y_quantitat + $y_quantitat_offset, $w_quantitat, $h_quantitat, '', 
					array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 51, 102))), '' );
		}
		$txt = '<p style="color:#000000; font-size:14px; ">'.ucfirst($importTxt).'</p>';
		$pdf->writeHTMLCell($w_quantitat-10, 0, $x_quantitat + $x_quantitat_offset + 5, $y_quantitat + $y_quantitat_offset + 2, $txt, '', 1, false, true, 'L', false);
		
		/* REBUT CONCEPTE */	
		$concepte = '';
		$comandes = $rebut->getComandes(); 
		if (count($comandes) == 0 || $rebut->esAnulacio()) {  // Rebut no associat a cap comanda
			if ($rebut->esAnulacio()) $concepte = 'Anul·lació rebut, import acumulat al saldo del club';
			else $concepte = 'Ingrés acumulat al saldo del club';
		} else {
			if ($rebut->getNumFactures() == 1) $concepte = 'Liquidació FACTURA: ';
			else $concepte = 'Liquidació FACTURES: ';
			$concepte .= $rebut->getLlistaNumsFactures();

			if ($rebut->getRomanent() > 0) {
				$concepte .= '<br/>Amb un romanent acumulat a favor del club de ';
				$concepte .= '<b>'.number_format($rebut->getRomanent(), 2, ',', '.').' €</b>';
			}
		}
	
		if ($rebut->getComentari()!=null && $rebut->getComentari() != '') 
				$concepte .= '<br/><i>'.$rebut->getComentari().'</i>';
		
		if ($showTemplate == true) {
			$txt = '<p align="left" style="padding:0;">en concepte de:</p>';
			$pdf->writeHTMLCell(0, 0, $x_concepte, $y_concepte, $txt, '', 1, false, true, 'L', false);
			$pdf->Rect($x_concepte + $x_concepte_offset, $y_concepte + $y_concepte_offset, $w_concepte , $h_concepte , '', 
					array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 51, 102))), '' );
		}
		$txt = '<p style="color:#000000; font-size:14px; ">'.$concepte.'</p>';
		$pdf->writeHTMLCell($w_concepte - 10, 0, $x_concepte + $x_concepte_offset + 5, $y_concepte + $y_concepte_offset + 2, $txt, '', 1, false, true, 'L', false);
		
		
		/* REBUT FOOTER */	
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Mitjançant:&nbsp;&nbsp;&nbsp;';
		$txt .= '<span style="color:#000000; font-size:14px;">'.BaseController::getTextTipusPagament($rebut->getTipuspagament()) .'</span></p>';
		$pdf->writeHTMLCell(0, 0, $x_total, $y_total, $txt, '', 1, false, true, 'L', false);
				
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Són:</p>';
		$pdf->writeHTMLCell(10, 0, $x_total + $x_total_col2, $y_total + $y_total_offset_1, $txt, '', 1, false, true, 'L', false);
		$txt = '<p align="right" style="padding:0;'.$hideText.'"><span style="color:#000000; font-size:14px;">'.number_format($rebut->getImport(), 2, ',', '.');
		$txt .= '</span>&nbsp;&nbsp;&nbsp; Euros</p>';
		$pdf->writeHTMLCell(0, 0, $x_total + $x_total_col2+11, $y_total + $y_total_offset_1-2, $txt, '', 1, false, true, 'L', false);
		
		$oldLocale = setlocale(LC_TIME, 'ca_ES.utf8');
		$mesData = $rebut->getDatapagament()->format('m');
		$litDe = 'de ';
		if ($mesData == 4 || $mesData == 8 || $mesData == 10) $litDe = 'd\'';
		
		$dateFormated = utf8_encode( strftime('%A %e '.$litDe.'%B de %Y', $rebut->getDatapagament()->format('U') ) );
		setlocale(LC_TIME, $oldLocale);
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Sant Adrià del Besòs, &nbsp;&nbsp;&nbsp;';
		$txt .= '<span style="color:#000000; font-size:14px;">'. $dateFormated .'</span></p>';
		$pdf->writeHTMLCell(0, 0, $x_total, $y_total+$y_total_offset_2, $txt, '', 1, false, true, 'L', false);
		
		
		
		/*
		
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
	
		//$pdf->SetFont('dejavusans', '', 16, '', true);
		//$text = '<b>REBUT #'. $rebut->getNumRebut() .'#</b>';
		//$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'L', true);
		//$pdf->Ln(5);
	
		
		
		
		
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
	
	
		$titol = 'REBUT '.($rebut->esAnulacio()?'ANUL·LACIÓ':'');
		$tbl = '<table cellpadding="5" cellspacing="0" style="border: 0.3em solid #333333;">';
		$tbl .= '<tr style="border: 0.2em solid #333333;"><td colspan="2" width="250" align="center" style="color:#555555;border: 0.2em solid #555555;">';
		$tbl .= '<span style="font-weight:bold;font-size:16px;">'.$titol.'</span></td></tr>';
		$tbl .= '<tr style="border: 0.2em solid #333333;"><td width="125" align="right" style="color:#555555; border: 0.2em solid #333333;">Número:</td>';
		$tbl .= '<td align="center" style="border: 0.2em solid #333333;"><b>' . $rebut->getNumRebut() . '</b></td></tr>';
		$tbl .= '<tr style="border: 0.2em solid #333333;"><td align="right" style="color:#555555; border: 0.2em solid #333333;">Data:</td>';
		$tbl .= '<td align="center" style="border: 0.2em solid #333333;"><b>' . $rebut->getDatapagament()->format('d/m/Y') . '</b></td></tr>';
		$tbl .= '<tr style="border: 0.2em solid #333333;"><td align="right" style="color:#555555; border: 0.2em solid #333333;">Import:</td>';
		$tbl .= '<td align="center" style="border: 0.2em solid #333333;"><b>' . number_format($rebut->getImport(), 2, ',', '.') . '€	</b></td></tr>';
		$tbl .= '</table>';
		
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$pdf->setX($x_ini);
		
		$pdf->Ln(12);
		
		$text = ($rebut->esAnulacio()?'Anul·lació de rebut a favor de:':'Ha rebut de:');
		$pdf->SetFont('dejavusans', '', 11, '', true);
		$pdf->SetTextColor(60, 60, 60); // Gris
		$tbl = '<h3 style="border-bottom: 0.2em solid #333333;">'.$text.'</h3>';
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
		
		$comandes = $rebut->getComandes(); 
		if (count($comandes) == 0 || $rebut->esAnulacio()) {  // Rebut no associat a cap comanda
			$pdf->SetFont('dejavusans', '', 14, '', true);
			$pdf->SetTextColor(0, 0, 0);
			
			$pdf->Ln(20);
			
			if ($rebut->esAnulacio()) $tbl = '<h4><i>Anul·lació rebut, import acumulat al saldo del club per valor de: </i><b>'.number_format($rebut->getImport()*(-1), 2, ',', '.').' €</b></h4>';
			else $tbl = '<h4><i>Ingrés acumulat al saldo del club per valor de: </i><b>'.number_format($rebut->getImport(), 2, ',', '.').' €</b></h4>';
			
			$pdf->writeHTML($tbl, false, false, false, false, '');
			
			$pdf->Ln(40);
			
			$pdf->SetFont('dejavusans', '', 11, '', true);
			$pdf->SetTextColor(120, 120, 120); // Gris
			$tbl = '<p style="border-bottom: 0.2em solid #333333;">&nbsp;</p>';
			$pdf->writeHTML($tbl, false, false, false, false, '');
			
		} else {
			if ($rebut->getNumFactures() == 1) { // Rebut per import íntegre de comanda

				$tbl = '<h4>'.'Liquidació FACTURA: '.$rebut->getLlistaNumsFactures().', corresponent a la comanda següent</h4>';
			
				$pdf->writeHTML($tbl, true, false, false, false, '');
			
				$pdf->Ln(20);
			
				$pdf->SetFont('dejavusans', '', 9, '', true);
				$pdf->SetTextColor(0, 0, 0); 	
					
				$comanda = $comandes[0]; 
				$tbl = '<table border="1" cellpadding="5" cellspacing="0" style="border-color: #000000; border-collapse: collapse;">
						<tr style="background-color:#EEEEEE; border-color: #000000;">
						<td width="75" align="center">Referència</td>
						<td width="195" align="left">Concepte</td>
						<td width="40" align="center">Uds.</td>
						<td width="60" align="center">Preu<br/>unitat</td>
						<td width="70" align="center">Subtotal</td>
						<td width="40" align="center">IVA</td>
						<td width="60" align="center">Import<br/>IVA</td>
						<td width="100" align="right">TOTAL</td>
						</tr>';
			
				$mindetalls = 6;
			
				foreach ($comanda->getDetallsAcumulats(true) as $lineafactura) {
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
				$tbl .= '<td align="right"><span style="font-weight:bold;font-size:14px; padding:0;"><br/>' . number_format($rebut->getImport(), 2, ',', '.') .  ' €</span></td>';
				$tbl .= '</tr>';
			
				$tbl .= '</table>';
				
				$pdf->writeHTML($tbl, true, false, false, false, '');
			} else {  
				// Un rebut vàries comandes
				$tbl = '<h4>'.'Liquidació FACTURES: '.$rebut->getLlistaNumsFactures().'</h4>';
				$tbl .= '<h4>Corresponents a les comandes:</h4>';
			
				$pdf->writeHTML($tbl, true, false, false, false, '');
			
				$pdf->Ln(5);

				$pdf->SetFont('dejavusans', '', 10, '', true);
				$pdf->SetTextColor(0, 0, 0); 	

				$tbl = '<table border="1" cellpadding="10" cellspacing="0" style="border: 0.2em solid #333333; border-collapse: collapse;">';
						
				foreach ($comandes as $comanda) {
					$tbl .= '<tr style="border-bottom: none;">';
					$tbl .= '<td align="left"  width="505">';
					$tbl .= 'Número: '.$comanda->getNumComanda().' en data '.$comanda->getDataentrada()->format('Y-m-d').'<br/>';
					$tbl .= ' - <i>'.$comanda->getInfoLlistat().'</i></td>';
					$tbl .= '<td align="right"  width="130">';
					$tbl .= '<b>'.number_format($comanda->getTotalDetalls(), 2, ',', '.').' €</b></td>';
					$tbl .= '</tr>';
				}
				
				if ($rebut->getRomanent() > 0) {
					$tbl .= '<tr style="border-bottom: none;">';
					$tbl .= '<td align="left">';
					$tbl .= 'Amb un romanent acumulat a favor del club de </td>';
					$tbl .= '<td align="right">';
					$tbl .= '<b>'.number_format($rebut->getRomanent(), 2, ',', '.').' €</b></td>';
					$tbl .= '</tr>';
				}
				$tbl .= '<tr>';
				$tbl .= '<td align="right" style="background-color:#EEEEEE; height: 50px;  padding:10px 5px;"><span style="font-size:12px;"><br/>IMPORT DEL REBUT:</span></td>';
				$tbl .= '<td align="right"><span style="font-weight:bold;font-size:14px;"><br/>' . number_format($rebut->getImport(), 2, ',', '.') .  ' €</span></td>';
				$tbl .= '</tr>';
				$tbl .= '</table>';
				
				$pdf->writeHTML($tbl, true, false, false, false, '');

				$pdf->Ln(10);
			
				$pdf->SetFont('dejavusans', '', 11, '', true);
				$pdf->SetTextColor(120, 120, 120); // Gris
				$tbl = '<p style="border-bottom: 0.2em solid #333333;">&nbsp;</p>';
				$pdf->writeHTML($tbl, false, false, false, false, '');
				
			}
			
		}
	
		
		if ($rebut->getComentari()!=null && $rebut->getComentari() != '') {
			$pdf->Ln(10);
	
			$pdf->SetTextColor(100, 100, 100); // Gris
			$pdf->SetFont('dejavusans', '', 11, '', true);

			$tbl = '<p>Comentaris: '.$rebut->getComentari().'</p>';
			$pdf->writeHTML($tbl, false, false, false, false, '');
		}
	
	
		*/
		  
		// reset pointer to the last page
		$pdf->lastPage();
		
		return $pdf;
	
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
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php

		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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
				$tbl .= '<td align="right">' .  number_format($parte->getPreuTotal(), 2, ',', '.') . '&nbsp;€</td></tr>';
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

			// Printer EVOLIS PEBBLE 4 - ISO 7810, paper size CR80 BUSINESS_CARD_ISO7810 => 54x86 mm 2.13x3.37 in
			// Altres opcions BUSINESS_CARD_ES   55x85 mm ; 2.17x3.35 in ¿?
			// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
			// Papers => 		/vendor/tcpdf/includes/tcpdf_static.php
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
			$pdf->SetAutoPageBreak 	(false, 5);
			//$pdf->SetMargins(0, 0, 0);
			//$pdf->SetAutoPageBreak 	(false, 0);
			$pdf->SetTextColor(0, 0, 0); 
			
			$width = 86; //Original
			$height = 54; //Original
			
			// Posicions
			/*$xTit = 0;
			$yTit =	12;		
			$xNom = 10;
			$yNom =	28;		
			$xDni = 18;
			$yDni =	32;		
			$xCat = 20.5;
			$yCat =	35.5;		
			$xNai = 19.5;
			$yNai =	39.5;		
			$xClu = 12;
			$yClu =	43;		
			$xTlf = 15;
			$yTlf =	47.2;		
			$xCad = 57;
			$yCad =	47;*/		
			$xTit = 0;
			$yTit =	12;		
			$xNom = 10;
			$yNom =	27.4;		
			$xDni = 18;
			$yDni =	32.1;		
			$xCat = 20.5;
			$yCat =	36.7;		
			$xNai = 19.5;
			$yNai =	41.1;		
			$xClu = 12;
			$yClu =	45.6;		
			$xTlf = 15;
			$yTlf =	50.1;		
			$xCad = 61;
			$yCad =	50.1;		
								
			foreach ($llicenciesSorted as $llicencia) {

				$persona = $llicencia->getPersona();
				if ( $persona == null) continue;				
				// Add a page
				$pdf->AddPage('L', 'BUSINESS_CARD_ISO7810');

	 			//$pdf->setVisibility('view'); // or screen

	 			/*$pdf->Image('images/federativa-cara.jpg', 0, 0, 
						$width, $height, 'jpg', '', '', false, 150, 
						'', false, false, 0, false, false, false);*/
				
	 			//$pdf->Rect(0, 0, $width, $height, 'DF', array(), array(220, 220, 200));
				
				//$pdf->setVisibility('all');

				$datacaduca = $parte->getDatacaducitat('printparte');
				$titolPlastic = $this->getTitolPlastic($llicencia->getParte(), $datacaduca);

				$pdf->SetFont('helvetica', 'B', 10, '', true);
				
				$pdf->SetXY($xTit, $yTit);
				$pdf->MultiCell(0,0,$titolPlastic,0,'C',false);

				$pdf->SetFont('dejavusans', 'B', 8);

				$pdf->SetXY($xNom, $yNom);
				$pdf->Cell(0, 0, $persona->getNomCognoms(), 0, 1, 'L');

				$pdf->SetXY($xDni, $yDni);
				$pdf->Cell(0, 0, $persona->getDni(), 0, 1, 'L');

				$pdf->SetXY($xCat, $yCat);
				$pdf->Cell(0, 0, $llicencia->getCategoria()->getCategoria(), 0, 1, 'L');
				
				$pdf->SetXY($xNai, $yNai);
				$pdf->Cell(0, 0, $persona->getDatanaixement()->format('d/m/Y'), 0, 1, 'L');
				
				$pdf->SetXY($xClu, $yClu);
				$pdf->Cell(0, 0, $parte->getClub()->getNom(), 0, 1, 'L');

				$pdf->SetXY($xTlf, $yTlf);
				$pdf->Cell(0, 0, $parte->getClub()->getTelefon(), 0, 1, 'L');
				
				$pdf->SetXY($xCad, $yCad);
				$pdf->Cell(0, 0, $datacaduca->format('d/m/Y'), 0, 1, 'L');

			}
			// reset pointer to the last page
			$pdf->lastPage();

			$current = $this->getCurrentDate();
				
			$parte->setDatamodificacio($current);
			$parte->setImpres(1);
					
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
