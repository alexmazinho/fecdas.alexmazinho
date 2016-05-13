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
				$comanda = $factura->getComandaFactura();
				
				if ($comanda == null || $comanda->comandaConsolidada() != true) return new Response('encara no es pot imprimir la factura');
				
				if ($comanda->getClub() != null) {
				
					$pdf = $this->facturatopdf($factura);
					
					$nomfitxer = "factura_" .  str_replace("/", "-", $factura->getNumfactura()) . "_" . $comanda->getClub()->getCodi() . ".pdf";

					// Close and output PDF document
					$response = new Response($pdf->Output($nomfitxer, "D")); // D: send to the browser and force a file download with the name given by name.
					$response->headers->set('Content-Type', 'application/pdf');

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
	
	public function  asseguratstopdfAction(Request $request) {
		/* Llistat d'assegurats vigents */
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$club = $this->getCurrentClub();
		
		$currentDNI = $request->query->get('dni', '');
		$currentNom = $request->query->get('nom', '');
		$currentCognoms = $request->query->get('cognoms', '');
		
		$interval = $this->intervalDatesPerDefecte($request);
		$desde = (isset($interval['desde']) && $interval['desde'] != null?$interval['desde']:null);
		$fins = (isset($interval['fins']) && $interval['fins'] != null?$interval['fins']:null);
		
		$currentVigent = false;
		if ($request->query->has('vigent') && $request->query->get('vigent') == 1) $currentVigent = true;
		
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
		else $text = '<b>Històric d\'assegurats '.($desde != null?'des de '.$desde->format('d/m/Y').' ':'').' '.($fins != null?'fins '.$fins->format('d/m/Y'):'').' </b>';
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
		 
		$query = $this->consultaAssegurats($currentTots, $currentDNI, $currentNom, $currentCognoms, $desde, $fins, $currentVigent, $strOrderBY); 
		$persones = $query->getResult();
		
		
		foreach ($persones as $persona) {
			$total++;
			
			$num_pages = $pdf->getNumPages();
			$pdf->startTransaction();
			
			$this->asseguratsRow($pdf, $persona, $desde, $fins, $total, $w);
				
			if($num_pages < $pdf->getNumPages()) {

				//Undo adding the row.
				$pdf->rollbackTransaction(true);
			
				$pdf->AddPage();
				$this->asseguratsHeader($pdf, $w);
				$pdf->SetFillColor(255, 255, 255); //Blanc
				$pdf->SetFont('dejavusans', '', 9, '', true);
				
				$this->asseguratsRow($pdf, $persona, $desde, $fins, $total, $w);
				
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
	
	private function asseguratsRow($pdf, $persona, $desde, $fins, $total, $w) {
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
			$text =  $persona->getInfoAssegurats($this->isCurrentAdmin(), $desde, $fins);
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
							
				$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
				
				// set color for background
				$pdf->SetFillColor(255, 255, 255); //Blanc
				// set color for text
				$pdf->SetTextColor(0, 0, 0); // Negre
				
				$pdf->AddPage();
				
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
					
					$this->parteRow($pdf, $w, $llicencia_iter);
					
					if($num_pages < $pdf->getNumPages()) {
						//Undo adding the row.
						$pdf->rollbackTransaction(true);
						
						$pdf->AddPage();
						$this->parteHeader($pdf, $w);
						$pdf->SetFillColor(255, 255, 255); //Blanc
						$pdf->SetFont('dejavusans', '', 9, '', true);
						
						$this->parteRow($pdf, $w, $llicencia_iter);
						
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
	
	private function parteRow($pdf, $w, $llicencia) {
		
		$persona = $llicencia->getPersona();
		 
		$pdf->Cell($w[0], 6, $persona->getDni(), 'LRB', 0, 'C', 0, '', 1);  // Ample, alçada, text, border, ln, align, fill, link, strech, ignore_min_heigh, calign, valign
		$pdf->Cell($w[1], 6, $persona->getCognoms(), 'LRB', 0, 'L', 0, '', 1); 
		$pdf->Cell($w[2], 6, $persona->getNom(), 'LRB', 0, 'L', 0, '', 1);  
		$pdf->Cell($w[3], 6, $persona->getDatanaixement()->format("d/m/y") , 'LRB', 0, 'C', 0, '', 1); 
		$pdf->Cell($w[4], 6, $persona->getAddradreca(), 'LRB', 0, 'L', 0, '', 1); 
		$pdf->Cell($w[5], 6, $persona->getAddrcp(), 'LRB', 0, 'C', 0, '', 1);  
		$pdf->Cell($w[6], 6, $persona->getAddrpob(), 'LRB', 0, 'L', 0, '', 1);
		$pdf->Cell($w[7], 6, $llicencia->getCategoria()->getSimbol(), 'LRB', 0, 'C', 0, '', 1);  
		$pdf->Cell($w[8], 6, $llicencia->getActivitats(), 'LRB', 0, 'C', 0, '', 1); 
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


	public function imprimirpendentsAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		// Cerca
		$currentBaixa = false; // Inclou Baixes
		if ($request->query->has('baixa') && $request->query->get('baixa') == 1) $currentBaixa = true;
		$currentNoPagat = false;// No pagats
		if ($request->query->has('nopagat') && $request->query->get('nopagat') == 1) $currentNoPagat = true;
		$currentNoImpres = false;// No impres
		if ($request->query->has('noimpres') && $request->query->get('noimpres') == 1) $currentNoImpres = true;
		$currentCompta = false;// Pendents compta
		if ($request->query->has('compta') && $request->query->get('compta') == 1) $currentCompta = true;

		$currentNumfactura = $request->query->get('numfactura', '');
		$currentNumrebut = $request->query->get('numrebut', '');
		$currentAnyfactura = $request->query->get('anyfactura', '');
		$currentAnyrebut = $request->query->get('anyrebut', '');
		
		//$currentClub = null;
		$em = $this->getDoctrine()->getManager();
		$currentClub = $em->getRepository('FecdasBundle:EntityClub')->find($request->query->get('clubs', ''));
		
		$defaultEstat = self::TOTS_CLUBS_DEFAULT_STATE; // Tots normal
		if ($this->get('session')->get('username', '') == self::MAIL_FACTURACIO)  $defaultEstat = self::CLUBS_DEFAULT_STATE; // Diferits Remei
		$currentEstat = $request->query->get('estat', $defaultEstat);
		
		$sort = $request->query->get('sort', 'p.dataentrada');
		$direction = $request->query->get('direction', 'asc');
		
		
		$query = $this->consultaPartesRecents($currentClub, $currentEstat, $currentBaixa, 
											$currentNoPagat, $currentNoImpres, $currentCompta, 
											$currentNumfactura, $currentAnyfactura, $currentNumrebut, $currentAnyrebut, $sort.' '.$direction);
	
		$partes = $query->getResult();
		//$partes = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->findByImpres(0);
	
		if (count($partes) > 0) {
			$current = $this->getCurrentDate();
		
			$llicencies = array();
			$ids = array();
			foreach ($partes as $parte) {
				if (count($partes) > 1 && 
					($parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_1 ||
					 $parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_2 )) {
					 	
					 // Només imprimir Tecnocampus si s'envia només el parte sol. Cal canviar la targeta de plàstic
				} else {
					if ($parte->getTipus()->getTemplate() != '') {
					
						$llicencies = array_merge($llicencies, $parte->getLlicenciesSortedByName());
		
						$parte->setDatamodificacio($current);
						$parte->setImpres(1);
						
						$ids[] = $parte->getId();	
					}
				}
			}
		
			$pdf = $this->printLlicencies( $llicencies );
					
			$em->flush();

			$this->logEntryAuth('IMPRES PARTES OK', print_r($ids, true));

			// Close and output PDF document
			$response = new Response($pdf->Output("llicencies_impressio_partes_".$current->format('YmdHis'). ".pdf", "D"));
			$response->headers->set('Content-Type', 'application/pdf');
			return $response;
				
		} else {
			$this->get('session')->getFlashBag()->add('sms-notice', 'No hi ha cap llista pendent d\'imprimir');
			
			$this->logEntryAuth('IMPRES PARTES CAP', '');
		}
	
		return $this->redirect($this->generateUrl('FecdasBundle_recents'));
	}

	public function imprimirparteAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$parteid = $request->query->get("id");
	
		$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
	
		if ($parte != null) {
			$em = $this->getDoctrine()->getManager();
			
			$pdf = $this->printLlicencies( $parte->getLlicenciesSortedByName() );
			
			$parte->setDatamodificacio($this->getCurrentDate());
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
	
	public function imprimirllicenciaAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$llicenciaid = $request->query->get("id");
	
		$llicencia = $this->getDoctrine()->getRepository('FecdasBundle:EntityLlicencia')->find($llicenciaid);
	
		if ($llicencia != null) {
			$em = $this->getDoctrine()->getManager();
				
			$pdf = $this->printLlicencies( array( $llicencia ) );
			
			$llicencia->setDatamodificacio($this->getCurrentDate());
					
			$em->flush();
	
			$this->logEntryAuth('IMPRES LLICENCIA', $llicenciaid);
			
			// Close and output PDF document
			$response = new Response($pdf->Output("llicencia_impressio_".$llicenciaid. ".pdf", "D"));
			$response->headers->set('Content-Type', 'application/pdf');
			return $response;

		} else {
			$this->logEntryAuth('IMPRES LLICENCIA ERROR', $llicenciaid);
		}
	
		$response = new Response('No s\'ha pogut imprimir la llicència');
		$response->setStatusCode(500);
		
		return $response;
	}
	
	private function printLlicencies( $llicencies ) {
	
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

		foreach ($llicencies as $llicencia) {
			$parte = $llicencia->getParte();
								
			// Add a page
			$pdf->AddPage('L', 'BUSINESS_CARD_ISO7810');

			if ($parte->getTipus()->getTemplate() == BaseController::TEMPLATE_GENERAL) $this->printPlasticGeneral($pdf, $llicencia);
				
			if ($parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_1 ||
				$parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_2) {
					//$this->printPlasticGeneral($pdf, $llicencia);
					$this->printPlasticTecnocampus($pdf, $llicencia);
			}
		}
		// reset pointer to the last page
		$pdf->lastPage();
			
		return $pdf;
	}
	
	private function printPlasticTecnocampus($pdf, $llicencia) {
		// Posicions
		$xPol = 0;
		$yPol =	2;		
		
		$xTit = 0;
		$yTit =	12;		
		$xNom = 10;
		$yNom =	27.4;		
		$xDni = 18+5;
		$yDni =	32.1;		
		$xCat = 20.5+6;
		$yCat =	36.7;		
		$xNai = 19.5+7;
		$yNai =	41.1;		
		$xClu = 12;
		$yClu =	45.6;		
		$xTlf = 15+4;
		$yTlf =	49.1+0.5;		
		$xCad = 61+7;
		$yCad =	49.1+0.5;
		
		$parte = $llicencia->getParte();
		$polissa = $parte->getTipus()->getPolissa();
		
		// Dades
		$pdf->SetFont('helvetica', 'B', 9, '', true);
		$pdf->setFontStretching(90);		
		$pdf->SetXY($xPol, $yPol);
		$pdf->MultiCell(0,0,'Número de pòlissa: '.$polissa,0,'C',false);		
		
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat('printparte');
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);
				
		$pdf->SetFont('helvetica', 'B', 10, '', true);
		$pdf->setFontStretching(100);		
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

		$pdf->SetFont('dejavusans', 'B', 7);
		$pdf->SetXY($xTlf, $yTlf);
		$pdf->Cell(0, 0, $parte->getClub()->getTelefon(), 0, 1, 'L');
				
		$pdf->SetXY($xCad, $yCad);
		$pdf->Cell(0, 0, $datacaduca->format('d/m/Y'), 0, 1, 'L');

		// Títols
		$xTit = 0;
		$yTit =	12;		
		$y_offset = 0.3;
		$xCad_tit = 37;
		$x_titols = 0.5;

		$pdf->SetFont('helvetica', 'B', 10, '', true);
				
		$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C',false);

		$pdf->SetFont('dejavusans', 'B', 7);
		//$pdf->setFontSpacing(0.5);
    	//$pdf->setFontStretching(80);

		$pdf->SetXY($x_titols, $yNom+$y_offset);
		$pdf->Cell(0, 0, 'Nom:', 0, 1, 'L');

		$pdf->SetXY($x_titols, $yDni+$y_offset);
		$pdf->Cell(0, 0, 'DNI/Passaport:', 0, 1, 'L');

		$pdf->SetXY($x_titols, $yCat+$y_offset);
		$pdf->Cell(0, 0, 'Categoria/Nivell:', 0, 1, 'L');
				
		$pdf->SetXY($x_titols, $yNai+$y_offset);
		$pdf->Cell(0, 0, 'Data Naixement:', 0, 1, 'L');
				
		$pdf->SetXY($x_titols, $yClu+$y_offset);
		$pdf->Cell(0, 0, 'Entitat:', 0, 1, 'L');

		$pdf->SetXY($x_titols, $yTlf+$y_offset-0.3);
		$pdf->Cell(0, 0, 'Telf. Entitat:', 0, 1, 'L');
				
		$pdf->SetXY($xCad_tit, $yCad+$y_offset-0.3);
		$pdf->Cell(0, 0, 'Vàlida fins/Valid until:', 0, 1, 'L');
		
	}
	
	private function printPlasticGeneral($pdf, $llicencia) {
		// Posicions
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
		
		$parte = $llicencia->getParte();
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat('printparte');
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);

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
	
	public function carnettopdfAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$carnets = $request->query->get('carnets', json_encode(array()));
		
		$carnetsArray = json_decode($carnets, true);
		
		$current = $this->getCurrentDate();
		
		$format = \TCPDF_STATIC::getPageSizeFromFormat('BUSINESS_CARD_ISO7810');
		$pdf = new TcpdfBridge('L', PDF_UNIT, $format, true, 'UTF-8', false);
					
		$pdf->init(array('author' => 'FECDAS',
					'title' => 'Carnet \'Commercial Diving\' ' . date("Y")));
	
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

		// 	Image ($file, $x='', $y='', 
		//			$w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, 
		// 			$palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, 
		//			$hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
		/*$pdf->Image('images/background_carnet.jpg', 0, 0, 
							$width, $height, 'jpg', '', 'LT', true, 150, 
							'', false, false, array(), true,
							false, false, false, array());*/
		
		foreach ($carnetsArray as $dades) {
			// Add a page
			$pdf->AddPage('L', 'BUSINESS_CARD_ISO7810');
			
			$this->printPlasticCarnet($pdf, $dades);	
		}		
				
		// reset pointer to the last page
		$pdf->lastPage();
	
		// Generació del PDF 
		$this->logEntryAuth('CARNET OK', json_encode($carnets));
		
		// Close and output PDF document
		$response = new Response($pdf->Output("carnets_".$current->format('Y-m-d').".pdf", "D"));
		$response->headers->set('Content-Type', 'application/pdf');
		return $response;
	}
	
	private function printPlasticCarnet($pdf, $dades) {
		// Posicions
		$wLogo = 8;
		$hLogo = 0;
		$xLogo = 75;
		$yLogo = 1;		
		$xLogoC = 75;
		$yLogoC = 10;		
		$xNom = 13;
		$yNom =	4.4;		
		$xCognoms = 15;
		$yCognoms =	13.2;		
		$xDni = 15;
		$yDni =	22.4;		
		$xEmi = 20;
		$yEmi =	29;	
		$xCad = 15;
		$yCad =	38;
		$xNum = 25;
		$yNum =	47;	
		$gap = 1.6;	
		
		// 	Image ($file, $x='', $y='', 
		//			$w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, 
		// 			$palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, 
		//			$hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
		/*$pdf->Image('images/fecdaslogopdf.gif', $xLogo, $yLogo,
							$wLogo, $hLogo , 'gif', '', 'CT', false, 300, 
							'', false, false, array(), 'CT',
							false, false, false, array());*/
		
		// Dades
		$pdf->SetFont('dejavusans', 'B', 8);

		$pdf->SetXY($xNom, $yNom+$gap);
		$pdf->Cell(0, 0, isset($dades['nom'])?$dades['nom']:'', 0, 1, 'L');
		
		$pdf->SetXY($xCognoms, $yCognoms+$gap);
		$pdf->Cell(0, 0, isset($dades['cognoms'])?$dades['cognoms']:'', 0, 1, 'L');

		$pdf->SetXY($xDni, $yDni);
		$pdf->Cell(0, 0, isset($dades['dni'])?$dades['dni']:'', 0, 1, 'L');

		$pdf->SetXY($xEmi, $yEmi+$gap);
		$pdf->Cell(0, 0, isset($dades['emissio'])?$dades['emissio']:'', 0, 1, 'L');
				
		$pdf->SetXY($xCad, $yCad+$gap);
		$pdf->Cell(0, 0, isset($dades['caducitat'])?$dades['caducitat']:'', 0, 1, 'L');
				
		$pdf->SetXY($xNum, $yNum+$gap);
		$pdf->Cell(0, 0, isset($dades['expedicio'])?$dades['expedicio']:'', 0, 1, 'L');
		
		// Logo club
		/*$pdf->SetXY($xLogoC, $yLogoC);
		$logoClubPath = $this->getTempUploadDir().'/'.$dades['logo'];*/
		
		// 	Image ($file, $x='', $y='', 
		//			$w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, 
		// 			$palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, 
		//			$hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
		/*$pdf->Image($logoClubPath, $xLogoC, $yLogoC, 
							$wLogo, $hLogo , isset($dades['extension'])?$dades['extension']:'jpg', '', 'CT', false, 300, 
							'', false, false, array(), 'CT',
							false, false, false, array());*/
		
	}
}
