<?php
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FecdasBundle\Classes\TcpdfBridge;

include_once (__DIR__.'/../../../vendor/tecnickcom/tcpdf/include/tcpdf_static.php');

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
	
	public function dadespersonalstopdfAction($persones, $print = false, $desde = null, $fins = null, $vigents = false, $dni = '', $nom = '', $cognoms = '') {
		/* PDF Llistat de dades personals filtrades */
		$club = $this->getCurrentClub();
		
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->init(array('author' => 'FECDAS', 'title' => "Llista d'assegurats"),
		    true, ($this->isCurrentAdmin()?'ADMINISTRADOR - ':'').$club->getNom());
			
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
		if ($vigents) $text = '<b>Llistat de persones en data '. date("d/m/Y") .'</b>';
		else $text = '<b>Històric de persones '.BaseController::getInfoTempsNomFitxer($desde, $fins, " ", "/").' </b>';
		$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, '', true);
		
		$pdf->Ln();

		if ($dni != "" || $nom != "" || $cognoms != "") {
			// Afegir dades del filtre
			$y += 10;
			$pdf->SetFont('dejavusans', 'I', 10, '', true);
			$pdf->writeHTMLCell(0, 0, $x, $y, 'Opcions de filtre', 'B', 1, 1, true, '', true);
			$pdf->SetFont('dejavusans', '', 9, '', true);
			if ($dni != "") {
				$y += 7;
				$pdf->writeHTMLCell(0, 0, $x, $y, 'DNI\'s que contenen "'.$dni.'"', '', 1, 1, true, '', true);
			}
			if ($nom != "") {
				$y += 7;
				$pdf->writeHTMLCell(0, 0, $x, $y, 'Noms que contenen "'.$nom.'"', '', 1, 1, true, '', true);
			}
			if ($cognoms != "") {
				$y += 7;
				$pdf->writeHTMLCell(0, 0, $x, $y, 'Cognoms que contenen "'.$cognoms.'"', '', 1, 1, true, '', true);
			}
			$y += 2;
			$pdf->writeHTMLCell(0, 0, $x, $y, '', 'B', 1, 1, true, '', true);
			
			$pdf->Ln();
		} else {
			$y += 15;
		}
		
		//$w = array(8, 44, 20, 26, 82); // Amplades
		$w = array(8, 37, 16, 22, 67, 30); // Amplades
		$this->dadespersonalsHeader($pdf, $w);
		$pdf->SetFillColor(255, 255, 255); //Blanc
		$pdf->SetFont('dejavusans', '', 7, '', true);
		
		$total = 0;
		
		foreach ($persones as $persona) {			
			$total++;
				
			$num_pages = $pdf->getNumPages();
			$pdf->startTransaction();
				
			$this->dadespersonalsRow($pdf, $persona, $desde, $fins, $total, $w);
					
			if($num_pages < $pdf->getNumPages()) {
	
				//Undo adding the row.
				$pdf->rollbackTransaction(true);
				
				$pdf->AddPage();
				$this->dadespersonalsHeader($pdf, $w);
				$pdf->SetFillColor(255, 255, 255); //Blanc
				$pdf->SetFont('dejavusans', '', 9, '', true);
				
				$this->dadespersonalsRow($pdf, $persona, $desde, $fins, $total, $w);
					
			} else {
				//Otherwise we are fine with this row, discard undo history.
				$pdf->commitTransaction();
			}
			
		}
		
		$pdf->Ln(10);



		// Afegir llista abreviatures titols
		$pdf->SetFont('dejavusans', '', 4.5, '', true);
		$pdf->SetTextColor(100, 100, 100); // Gris
		$pdf->SetDrawColor(150, 150, 150); // Gris

		$h_llista = 85;	
		//if ($pdf->getY() > ( ($pdf->getPageHeight()-PDF_MARGIN_BOTTOM-PDF_MARGIN_TOP)/3) ) {
		if ($pdf->getY() + $h_llista > ( $pdf->getPageHeight()-PDF_MARGIN_BOTTOM) ) {  // La llista abreviatures mesura unes 80 unitats	
			$pdf->AddPage();
		}
		
		$pdf->setY($pdf->getPageHeight()-PDF_MARGIN_BOTTOM - $h_llista - 5);

		$titolscmas 	= $this->getTitolsByOrganisme(BaseController::ORGANISME_CMAS);
		$altrestitols 	= $this->getTitolsByOrganisme('', BaseController::ORGANISME_CMAS);
		
		$html  = '<h3>Abreviatures títols:</h3><ul>';
		$pdf->writeHTMLCell(0, 0, $pdf->getX(), $pdf->getY(), $html, '', 1, false, true, 'L', true);
		
		// 4 cols
		$x_tit 	= $pdf->getX();
		$y_tit 	= $pdf->getY();
		$w_col 	= 45 - 1; // 180/4
		$i 		= 0;
		$html  	= '';
		$titols = array_merge(array(array( 'organisme' => BaseController::ORGANISME_CMAS )), $titolscmas[BaseController::ORGANISME_CMAS]);
		foreach ($altrestitols as $org => $titolsorg) $titols = array_merge($titols, array(array( 'empty' => '' ), array( 'organisme' => $org )), $titolsorg);

		$pdf->Rect($x_tit, $y_tit-3,$pdf->getPageWidth()-PDF_MARGIN_LEFT*2, $h_llista,  'DF', '', '');

		$t_col 	= round(count($titols)/4);
		foreach ($titols as $titol) {
			
			if (isset($titol['organisme'])) {
				$html  .= '<span style="font-size: medium;"><b>'.$titol['organisme'].'</b></span><br/>';
			} else {
				if (isset($titol['empty'])) $html  .= '<span>&nbsp;</span><br/>';
				else $html  .= '<span>'.$titol['codi'].' - '.$titol['titol'].'</span><br/>';
			}
				
			if ($i == $t_col) {
				$pdf->writeHTMLCell($w_col, 0, $x_tit, $y_tit, $html, '', 0, false, true, 'L', true);
				$x_tit += $w_col + 1;
				$i = 0;
				$html = '';
			} else {
				$i++;	
			}
		}
		if ($i < $t_col) $pdf->writeHTMLCell($w_col, 0, $x_tit, $y_tit, $html, '', 0, false, true, 'L', true);
		$pdf->SetTextColor(0, 0, 0); // Negre
		
		$pdf->setPage(1); // Move to first page
		
		$pdf->setY($y_ini);
		$pdf->setX($pdf->getPageWidth() - 100);
		
		$pdf->SetFont('dejavusans', '', 13, '', true);
		$text = '<b>Total : '. $total . '</b>';
		$pdf->writeHTMLCell(0, 0, $pdf->getX(), $pdf->getY(), $text, '', 1, 1, true, 'R', true);
		
		// reset pointer to the last page
		$pdf->lastPage();
					
		$filename = "dadespersonals_".BaseController::getInfoTempsNomFitxer($desde, $fins).".pdf";
		
		if ($print) {
			// force print dialog
			$js = 'print(true);';
			// set javascript
			$pdf->IncludeJS($js);
			$response = new Response($pdf->Output($filename, "D")); // inline
		} else {
		// Close and output PDF document
			$response = new Response($pdf->Output($filename, "D")); // save as...
		}
		$response->headers->set('Content-Type', 'application/pdf');
		return $response;
		
	}
	
	private function dadespersonalsRow($pdf, $persona, $desde, $fins, $total, $w) {
		$llicencia = $persona->getLlicenciaVigent();
		
		$pdf->Cell($w[0], 6, $total, 'LRB', 0, 'C', 0, '', 1);  // Ample, alçada, text, border, ln, align, fill, link, strech, ignore_min_heigh, calign, valign
		$pdf->Cell($w[1], 6, $persona->getCognomsNom(), 'LRB', 0, 'L', 0, '', 1);
		$pdf->Cell($w[2], 6, ($persona->getDatanaixement()!=null?$persona->getDatanaixement()->format('d/m/Y'):''), 'LRB', 0, 'C', 0, '', 1);
		$pdf->Cell($w[3], 6, $persona->getDni(), 'LRB', 0, 'C', 0, '', 1);
		if ($llicencia != null && $llicencia->getParte() != null) {
			$text = $llicencia->getCategoria()->getDescripcio().". ";
			$text .= $llicencia->getParte()->getDataalta()->format('d/m/Y'). ' - ';
			$text .= $llicencia->getParte()->getDatacaducitat()->format('d/m/Y');
		} else {
			$text =  $persona->getInfoHistorialLlicencies($this->isCurrentAdmin(), $desde != null?$desde->format("Y-m-d"):'', $fins != null?$fins->format("Y-m-d"):'');
		}
		$pdf->Cell($w[4], 6, $text , 'LRB', 0, 'L', 0, '', 1);
		$pdf->Cell($w[5], 6, $persona->getInfoHistorialTitulacions() , 'LRB', 0, 'C', 0, '', 1);
			
		$pdf->Ln();
		
	}
	
	private function dadespersonalsHeader($pdf, $w) {
		$pdf->SetFont('dejavusans', 'B', 9, '', true);
		$pdf->SetFillColor(221, 221, 221); //Gris
		
		$pdf->Cell($w[0], 7, '', 1, 0, 'C', 1);  // Ample, alçada, text, border, ln, align, fill,
		$pdf->Cell($w[1], 7, 'Nom', 1, 0, 'L', 1);
		$pdf->Cell($w[2], 7, 'Nascut/da', 1, 0, 'C', 1, '', 1);
		$pdf->Cell($w[3], 7, 'DNI', 1, 0, 'C', 1);
		$pdf->Cell($w[4], 7, 'Informació llicència / assegurança', 1, 0, 'C', 1, '', 1);
		$pdf->Cell($w[5], 7, 'Titulacions', 1, 0, 'C', 1, '', 1);
		$pdf->Ln();
	}
	
	
	public function partetopdfAction(Request $request) {
	
		if ($request->query->has('id')) {
			$parte = $this->getDoctrine()
				->getRepository('FecdasBundle:EntityParte')
				->find($request->query->get('id'));
			
			if ($parte == null) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
			
			if ($parte) {
			    $club = $parte->getClubparte();
			    
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
				
				if ($parte->getWeb() && !$parte->esSenseCarrec() && (!$parte->comandaPagada() || $parte->getPendent())) {
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
				$datafi = $parte->getDataCaducitat();

				$pdf->SetFont('dejavusans', '', 10, '', true);
				$text = '<p>Llista d\'esportistes que representen el CLUB:   ';
				$text .= '<b>' . $club->getNom() . '</b></p>';
				$text .= '<p>Vigència de les llicències des del <b>' . $datainici->format("d/m/Y") . '</b>';
				$text .= ' fins el <b>' . $datafi->format("d/m/Y") . '</b></p>';
				$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'L', true);
				
				$pdf->SetFont('dejavusans', '', 12, '', true);
				$text = '<p>Data d\'entrada:  <b>' . $parte->getDataentrada()->format("d/m/Y") . '</b></p>';
				$pdf->writeHTMLCell(0, 0, $x, $y + 20, $text, '', 1, 1, true, 'L', true);

				if ($this->isCurrentAdmin() || !$parte->esSenseCarrec()) {  // Llicències col·laboradors sense mostrar info preus
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
			    }
				
				/*$w = array(26, 44, 28, 26, 50, 16, 30, 10, 35);*/ // Amplades
				$w = array(8, 26+3, 44+6, 28+3, 26+3, 50+8, 16+2, 30+4, 10); // Amplades
				$this->parteHeader($pdf, $w);
				
				$pdf->SetFillColor(255, 255, 255); //Blanc
				
				$pdf->SetFont('dejavusans', '', 9, '', true);
				
				$llicenciesSorted = $parte->getLlicenciesSortedByName();
				
				$row = 1;
				foreach ($llicenciesSorted as $llicencia_iter) {
					$num_pages = $pdf->getNumPages();
					$pdf->startTransaction();
					
					$this->parteRow($pdf, $w, $llicencia_iter, $row);
					
					if($num_pages < $pdf->getNumPages()) {
						//Undo adding the row.
						$pdf->rollbackTransaction(true);
						
						$pdf->AddPage();
						$this->parteHeader($pdf, $w);
						$pdf->SetFillColor(255, 255, 255); //Blanc
						$pdf->SetFont('dejavusans', '', 9, '', true);
						
						$this->parteRow($pdf, $w, $llicencia_iter, $row);
						
					} else {
						//Otherwise we are fine with this row, discard undo history.
						$pdf->commitTransaction();
					}
					$row++;
				}
				
				$pdf->Ln();
				
				$y = $pdf->getY();
				
				/* Treure activitats
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
				*/
				  
				// reset pointer to the last page
				$pdf->lastPage();
			
				// Close and output PDF document
				$response = new Response($pdf->Output("llicencies_" . $club->getCodi() . "_" . $parte->getId() . ".pdf", "D"));
				$response->headers->set('Content-Type', 'application/pdf');
				return $response;
			}
			
		}
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	private function parteHeader($pdf, $w) { 
		$pdf->SetFont('dejavusans', 'B', 10, '', true);
		$pdf->SetFillColor(221, 221, 221); //Gris
		$pdf->Cell($w[0], 7, '#', 1, 0, 'C', 1); 
		$pdf->Cell($w[1], 7, 'DNI', 1, 0, 'C', 1);  // Ample, alçada, text, border, ln, align, fill,
		$pdf->Cell($w[2], 7, 'COGNOMS', 1, 0, 'C', 1);
		$pdf->Cell($w[3], 7, 'NOM', 1, 0, 'C', 1);
		$pdf->Cell($w[4], 7, 'D NAIXEMENT', 1, 0, 'C', 1, '', 1);
		$pdf->Cell($w[5], 7, 'DOMICILI', 1, 0, 'C', 1);
		$pdf->Cell($w[6], 7, 'CP', 1, 0, 'C', 1);
		$pdf->Cell($w[7], 7, 'POBLACIO', 1, 0, 'C', 1);
		$pdf->Cell($w[8], 7, 'CAT', 1, 0, 'C', 1);
		/*$pdf->Cell($w[8], 7, 'ACTIVITATS', 1, 0, 'C', 1);*/
		$pdf->Ln();
	}
	
	private function parteRow($pdf, $w, $llicencia, $row) {
		
		$persona = $llicencia->getPersona();
		
		$pdf->Cell($w[0], 6, $row, 'LRB', 0, 'C', 0, '', 1);  // Ample, alçada, text, border, ln, align, fill, link, strech, ignore_min_heigh, calign, valign 
		$pdf->Cell($w[1], 6, $persona->getDni(), 'LRB', 0, 'C', 0, '', 1); 
		$pdf->Cell($w[2], 6, $persona->getCognoms(), 'LRB', 0, 'L', 0, '', 1); 
		$pdf->Cell($w[3], 6, $persona->getNom(), 'LRB', 0, 'L', 0, '', 1);  
		$pdf->Cell($w[4], 6, $persona->getDatanaixement()->format("d/m/y") , 'LRB', 0, 'C', 0, '', 1); 
		$pdf->Cell($w[5], 6, $persona->getAddradreca(), 'LRB', 0, 'L', 0, '', 1); 
		$pdf->Cell($w[6], 6, $persona->getAddrcp(), 'LRB', 0, 'C', 0, '', 1);  
		$pdf->Cell($w[7], 6, $persona->getAddrpob(), 'LRB', 0, 'L', 0, '', 1);
		$pdf->Cell($w[8], 6, $llicencia->getCategoria()->getSimbol(), 'LRB', 0, 'C', 0, '', 1);  
		/*$pdf->Cell($w[8], 6, $llicencia->getActivitats(), 'LRB', 0, 'C', 0, '', 1);*/ 
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
			    $parte = $llicencia->getParte();
			    $club = $parte->getClubparte();
			    $persona = $llicencia->getPersona();
			    
				$this->logEntry($this->get('session')->get('username'), 'PRINT LLICENCIA',
						$this->get('session')->get('remote_addr'),
						$request->server->get('HTTP_USER_AGENT'), $llicencia->getId());
				
				// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
				$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	
				$pdf->init(array('author' => 'FECDAS',
				    'title' => 'Llicència FECDAS ' . date("Y")), true, $club->getNom());
				
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
				
				$pdf->Image(BaseController::IMATGE_ANVERS_GENERAL, $x, $y, 
						$width, $height , 'jpg', '', '', false, 320, 
						'', false, false, 1, false, false, false);
				
						$pdf->Image(BaseController::IMATGE_REVERS_GENERAL, $x, $y + $height,
						$width, $height , 'jpg', '', '', false, 320,
						'', false, false, 1, false, false, false);
				
				// set color for text and font
				//$pdf->SetTextColor(240, 240, 240); // Gris
				$pdf->SetTextColor(255, 255, 255); // Blanc
				$pdf->SetFillColor(255, 255, 255); // 
				
				$pdf->SetFont('dejavusans', 'B', 5.5, '', true);
				
				$x = $x_ini + 47;
				$y = $y_ini + 35;
			
				$pdf->writeHTMLCell(0, 0, $x, $y, "Nom: " . $persona->getNom()." ".$persona->getCognoms(), 0, 0, 0, true, 'L', true);
				
				$y += 4;
				$pdf->writeHTMLCell(0, 0, $x, $y, "DNI/Passaport: " . $persona->getDni(), 0, 0, 0, true, 'L', true);
				
				$y += 4;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Categoria/Nivell: " . $llicencia->getCategoria()->getCategoria(), 0, 0, 0, true, 'L', true);
				
				$y += 4;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Data naixement: " . $persona->getDatanaixement()->format('d/m/Y'), 0, 0, 0, true, 'L', true);
				
				$y += 4;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Entitat: " . $club->getNom(), 0, 0, 0, true, 'L', true);
				
				$y += 4;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Telf. entitat: " . $club->getTelefon(), 0, 0, 0, true, 'L', true);

				//$pdf->SetFont('dejavusans', 'B', 4.5, '', true);
				
				/*$x = $x_ini + 54.2;
				$y = $y_ini + 38.6; // 39.2
				$pdf->writeHTMLCell(0, 0, $x, $y, $persona->getNom() . " " . $persona->getCognoms(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 62.5;
				$y = $y_ini + 42.4;
				$pdf->writeHTMLCell(0, 0, $x, $y, $persona->getDni(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 65;
				$y = $y_ini + 46.1;
				$pdf->writeHTMLCell(0, 0, $x, $y, $llicencia->getCategoria()->getCategoria(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 63.6;
				$y = $y_ini + 49.9;
				$pdf->writeHTMLCell(0, 0, $x, $y, $persona->getDatanaixement()->format('d/m/Y'), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 56;
				$y = $y_ini + 53.7;
				$pdf->writeHTMLCell(0, 0, $x, $y, $club->getNom(), 0, 0, 0, true, 'L', true);
				
				$x = $x_ini + 60;
				$y = $y_ini + 57.5;
				$pdf->writeHTMLCell(0, 0, $x, $y, $club->getTelefon(), 0, 0, 0, true, 'L', true);*/
				
				//$datacaduca = $llicencia->getParte()->getDataalta();
				// Caducat 30 dies des de data impressió
				$datacaduca = $this->getCurrentDate();
				$datacaduca->add(new \DateInterval('P30D'));  // 30 dies
				
				if ($datacaduca > $parte->getDatacaducitat()) $datacaduca = $parte->getDatacaducitat();
				
				$x += 28;
				$pdf->writeHTMLCell(0, 0, $x, $y + 4, "Carnet provisional vàlid fins al " . $datacaduca->format('d/m/Y'), 0, 0, 0, true, 'L', true);
				/*$x += 41;
				$pdf->writeHTMLCell(0, 0, $x, $y, $datacaduca->format('d/m/Y'), 0, 0, 0, true, 'L', true);*/
				
				/* Tipus de llicència
				 * Taula TipoParte LL_L1 + LL_L1 + LL_L3 */
				$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);

				$pdf->SetFont('helvetica', 'B', 9, '', true);
				//$pdf->SetTextColor(230, 230, 230); // Gris
				$y = $y_ini + 17;
				$x = $x_ini + 62;

				$pdf->SetY($y);
				$pdf->SetX($x);
				$pdf->MultiCell($height,$width,$titolPlastic,0,'C',1);
				
				
				// Alex 20/12/2014 Afegir texte legal llicències tipus F
				if ($parte->getTipus()->getId() == 8) {
					
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
				$response = new Response($pdf->Output("llicencia_" . $persona->getNom() . " " . $persona->getCognoms(). ".pdf", "D"));
				$response->headers->set('Content-Type', 'application/pdf');
				return $response;
			}
		}
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
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
	
	public function llicenciaDigitalAction(Request $request) {
		$llicenciaid = $request->query->get("id", 0);
			
		try {	
			if ($this->isCurrentAdmin() != true) throw new \Exception('L\'usuari no pot realitzar aquesta opció, es desarà al registre');
	
			$llicencia = $this->getDoctrine()->getRepository('FecdasBundle:EntityLlicencia')->find($llicenciaid);
		
			if ($llicencia == null) throw new \Exception('No s\'ha trobat la llicència.');
			
			$parte = $llicencia->getParte();
			
			if ($parte == null) throw new \Exception('No s\'ha trobat la llista de la llicència.');
			
			$template = $parte->getTipus()->getTemplate();
			$curs = $parte->getCurs();
				
			$method = "printLlicencia".$template."pdf";
		
			if (!method_exists($this, $method)) throw new \Exception("Error generant la llicència. No existeix la plantilla"); 		

			$pdf = $this->$method( $llicencia );
			
			$this->logEntryAuth('LLICENCIA DIGITAL OK ', $llicenciaid);
				
			$nom =  "llicencia_".$curs."_".$llicencia->getId().".pdf";

			// Close and output PDF document
			$response = new Response($pdf->Output($nom, "D"));
			$response->headers->set('Content-Type', 'application/pdf');
			return $response;
			
		} catch (\Exception $e) {
			
			$this->logEntryAuth('LLICENCIA DIGITAL KO', 'llicencia  ' . $llicenciaid . ' error: '.$e->getMessage() );
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);							
		}

		return $response;

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
				
		//$width = 86; //Original
		//$height = 54; //Original

		// 	Image ($file, $x='', $y='', 
		//			$w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, 
		// 			$palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, 
		//			$hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
		
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
		/*$wLogo = 8;
		$hLogo = 0;
		$xLogo = 75;
		$yLogo = 1;		
		$xLogoC = 75;
		$yLogoC = 10;*/		
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


	public function actacurspdfAction(Request $request) {
			
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$id = $request->query->get('id', 0);
		
		$curs = $this->getDoctrine()->getRepository('FecdasBundle:EntityCurs')->find($id);
	
		if ($curs == null) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));

		$this->logEntryAuth('CURS PDF', ' curs id: '.$id);

		$club 			= $curs->getClub();
		$titolCurs		= $curs->getTitol()->getTitol();
		$participants 	= $curs->getParticipantsSortedByCognomsNom();
		$director 		= $curs->getDirector();
		$codirector 	= $curs->getCodirector();
		$docents  		= $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR);
		$collaboradors 	= $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_COLLABORADOR);
			
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
		$pdf->init(array('author' => 'FECDAS', 'title' => 'Curs ' .$titolCurs.' '. date("Y")), 
						false, "Acta número: " . $curs->getNumActa()."<br/>".$club->getNom(), "footerActaCurs");

		
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
				
		$pdf->AddPage();
				
		$y_ini = $pdf->getY();
		$x_ini = $pdf->getX();
				
		//$y = $y_ini;
		//$x = $x_ini;

		$pageWidth = $pdf->getPageWidth()-PDF_MARGIN_LEFT-PDF_MARGIN_RIGHT;

		$rowH = 12;
		$cellH = 8;
		//$tableRowH = 7.5;
		$alumneRowH = 8;
		$wSmall = 15;
		$wMed = 30;
		$wDates = 35;
		$wLarge = 95;
		
		$xTitol = $x_ini;
		$yTitol = $y_ini;
		
		$xEtiquetes1 = $xTitol;
		$xCampActa = $xEtiquetes1 + 22;
		$xEtiqCurs = $xEtiquetes1 + 57;
		$xCamps2 = $xEtiquetes1 + 85;
		$xEtiqDiaFi = $xCamps2 + $wMed + 10;
		$xCampDiaFi = $xEtiqDiaFi + 20;
		
		$yHeaderFila1 = $yTitol + $rowH + 2;
		$yHeaderFila2 = $yHeaderFila1 + $rowH;
		$yHeaderFila3 = $yHeaderFila2 + $rowH;
		$yHeaderFila4 = $yHeaderFila3 + $rowH;
		$yTitolDocents = $yHeaderFila4 + $rowH + 6;
		
		$yFilaDirector = $yFilaCoDirector = $yTitolDocents + $rowH;
		if ($codirector != null) $yFilaCoDirector = $yFilaDirector + $cellH;
		$xCampDirector = $xEtiquetes1 + 35;
		$xEtiqCarnet = $xCampDirector + $wLarge + 7;
		$xCampCarnet = $xEtiqCarnet + 13;
		
		$ySubtitolInstructors = $yFilaCoDirector + $rowH;
		$yTaulaInstructors = $ySubtitolInstructors + $rowH;
		$nFilesInstructors = max(count($docents) + 1, 5);
		
		//$ySubtitolCollaboradors = $yTaulaInstructors + ($nFilesInstructors * $tableRowH) + $rowH + $rowH + 10;  /* $rowH ~ capçalera taula docents */
		//$yTaulaCollaboradors = $ySubtitolCollaboradors + $rowH;
		$nFilesCollaboradors = max(count($collaboradors) + 1, 3);
		
		//$yTitolAlumnes = 0;  // Pàgina següent

		$styleSeparador = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => '', 'phase' => 10, 'color' => array(0, 0, 0)); // Gris
		
		$pdf->SetLineStyle($styleSeparador);
		
		
		if (!$curs->finalitzat()) {
			// MArca d'aigua
			$pdf->SetAlpha(0.5);
			$pdf->SetTextColor(220, 220, 220); // Gris
			$pdf->SetFont('dejavusans', '', 64, '', true);
			$pdf->SetXY($x_ini+60, $y_ini+60);
			$pdf->StartTransform();
			// $pdf->Rotate(D, X, Y); rotar D graus contrari agulles del rellotge amb centre a X, Y 
			$pdf->Rotate(-45, $x_ini+60, $y_ini+60);
			$pdf->Cell(0, 0, 'ACTA PROVISIONAL', 0, 0, 'C');
			$pdf->StopTransform();
			$pdf->SetAlpha(1);
		}
		
		
		// set color for background
		$pdf->SetFillColor(255, 255, 255); //Blanc
		// set color for text
		$pdf->SetTextColor(0, 0, 0); // Negre

		// CAPÇALERA
		$pdf->SetFont('dejavusans', 'B', 16, '', true);
		
		$pdf->SetXY($xTitol, $yTitol);
		// Cell( $w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M' )
		$pdf->Cell(0, 0, 'FEDERACIÓ CATALANA D\'ACTIVITATS SUBAQUÀTIQUES', 0, 0, 'C');
		
		// $x1, $y1, $x2, $y2, $style = array() 
		$pdf->Line(PDF_MARGIN_LEFT, $yTitol+$rowH-2, $pageWidth+PDF_MARGIN_LEFT, $yTitol+$rowH-2, $styleSeparador);

		$pdf->SetTextColor(0, 0, 128); // Blau
		$pdf->SetFont('dejavusans', '', 12, '', true);
		
		$pdf->SetXY($xCamps2, $yHeaderFila2);
		$pdf->Cell($wLarge, $cellH, $club->getNom(), 1, 0, 'L', false, '', 1);

		$pdf->SetXY($xCampActa, $yHeaderFila1);
		$pdf->Cell($wMed, $cellH, $curs->getNumActa(), 1, 0, 'C', false, '', 1);
		
		$pdf->SetXY($xCamps2, $yHeaderFila1);
		$pdf->Cell($wLarge, $cellH, $titolCurs, 1, 0, 'L', false, '', 1);
		
		$pdf->SetXY($xCamps2, $yHeaderFila3);
		$pdf->Cell($wDates, $cellH, $curs->getDatadesde()->format('d/m/Y'), 1, 0, 'C');
		
		$pdf->SetXY($xCampDiaFi, $yHeaderFila3);
		$pdf->Cell($wDates, $cellH, $curs->getDatafins()->format('d/m/Y'), 1, 0, 'C');
		
		$pdf->SetXY($xCamps2, $yHeaderFila4);
		$pdf->Cell($wSmall, $cellH, count($participants), 1, 0, 'C');

		// EQUIP DOCENT
		$pdf->SetXY($xCampDirector,$yFilaDirector);	
		$pdf->Cell($wLarge, $cellH, ($director!=null?$director->getMetadocent()->getDni().' - '.$director->getMetadocent()->getNomCognoms():'') , 1, 0, 'L', false, '', 1);
		
		$pdf->SetXY($xCampCarnet, $yFilaDirector);
		$pdf->Cell($wMed, $cellH, ($director!=null?$director->getCarnet():''), 1, 0, 'C', false, '', 1);
		
		if ($codirector != null) {
			$pdf->SetXY($xCampDirector, $yFilaCoDirector);
			$pdf->Cell($wLarge, $cellH, ($codirector!=null?$codirector->getMetadocent()->getDni().' - '.$codirector->getMetadocent()->getNomCognoms():''), 1, 0, 'L', false, '', 1);
	
			$pdf->SetXY($xCampCarnet, $yFilaCoDirector);
			$pdf->Cell($wMed, $cellH, ($director!=null?$director->getCarnet():''), 1, 0, 'C', false, '', 1);
		}
		
		$pdf->SetTextColor(0, 0, 0); // Negre
		
		$pdf->Line($x_ini, $yHeaderFila4 + $rowH, $pageWidth+PDF_MARGIN_LEFT, $yHeaderFila4 + $rowH, $styleSeparador);

		$pdf->SetXY($xEtiquetes1, $yHeaderFila1);	
		$pdf->Cell(0, $cellH, 'ACTA Nº:', 0, 0, 'L');
		
		$pdf->SetXY($xEtiqCurs, $yHeaderFila1);
		$pdf->Cell(0, $cellH, 'DEL CURS:', 0, 0, 'L');

		$pdf->SetXY($xEtiquetes1,$yHeaderFila2);
		$pdf->Cell(0, $cellH, 'REALITZAT PEL CLUB:', 0, 0, 'L');

		$pdf->SetXY($xEtiquetes1,$yHeaderFila3);
		$pdf->Cell(0, $cellH, 'EN LES DATES COMPRESSES DEL DIA:', 0, 0, 'L');

		$pdf->SetXY($xEtiqDiaFi, $yHeaderFila3);
		$pdf->Cell(0, $cellH, 'AL DIA:', 0, 0, 'L');
		
		$pdf->SetXY($xEtiquetes1,$yHeaderFila4);
		$pdf->Cell(0, $cellH, 'AMB LA PARTICIPACIÓ DE:', 0, 0, 'L');

		$pdf->SetXY($xCamps2 + $wSmall + 2, $yHeaderFila4);
		$pdf->Cell(0, $cellH, 'ALUMNES', 0, 0, 'L');
		
		$pdf->SetFont('dejavusans', 'UB', 15, '', true);
		$pdf->SetXY($xEtiquetes1,$yTitolDocents);
		$pdf->Cell(0, 0, 'EQUIP DOCENT DEL CURS', 0, 0, 'C');	
		
		$pdf->SetFont('dejavusans', '', 12, '', true);
		$pdf->SetXY($xEtiquetes1, $yFilaDirector);	
		$pdf->Cell(0, $cellH, 'DIRECTOR:', 0, 0, 'L');
		
		$pdf->SetXY($xEtiqCarnet, $yFilaDirector);
		$pdf->Cell(0, $cellH, 'Nº:', 0, 0, 'L');
		
		if ($codirector != null) {
			$pdf->SetXY($xEtiquetes1, $yFilaCoDirector);	
			$pdf->Cell(0, $cellH, 'CO-DIRECTOR:', 0, 0, 'L');
			
			$pdf->SetXY($xEtiqCarnet, $yFilaCoDirector);
			$pdf->Cell(0, $cellH, 'Nº:', 0, 0, 'L');
		}
			
		// INSTRUCTORS i COL·LABORADORS
		$pdf->SetFont('dejavusans', 'U', 13, '', true);
		$pdf->SetXY($xEtiquetes1,$ySubtitolInstructors);
		$pdf->Cell(0, 0, 'INSTRUCTORS QUE HAN IMPARTIT CLASSES', 0, 0, 'C');	
		
		$pdf->SetFont('dejavusans', '', 8, '', true);
		$pdf->SetY($yTaulaInstructors);
		
		$tbl = $this->getHtmlTaulaDocents($nFilesInstructors, $docents);
		$pdf->writeHTML($tbl, false, false, false, false, '');

		$pdf->Ln(5);
		$pdf->SetFont('dejavusans', 'U', 13, '', true);
		$pdf->SetXY($xEtiquetes1, $pdf->getY());
		$pdf->Cell(0, 0, 'HAN COL·LABORAT COM A EQUIP DE SEGURETAT', 0, 0, 'C');
		
		$pdf->SetFont('dejavusans', '', 8, '', true);
		$pdf->SetY($pdf->getY()+$rowH);
		
		$tbl = $this->getHtmlTaulaDocents($nFilesCollaboradors, $collaboradors);
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$pdf->Ln(10);
		$pdf->SetFont('dejavusans', 'I', 7, '', true);
		$pdf->Cell(0, 0, 'Signat', 0, 0, 'L');
		$pdf->Cell(0, 0, 'Segellat', 0, 0, 'R');
		$pdf->Ln();
		$pdf->SetFont('dejavusans', '', 9, '', true);
		$pdf->Cell(0, 0, 'El Director del Curs', 0, 0, 'L');
		$pdf->Cell(0, 0, 'La Federació', 0, 0, 'R');
		$pdf->Ln(5);
		$pdf->Line(PDF_MARGIN_LEFT, $pdf->getY(), $pageWidth+PDF_MARGIN_LEFT, $pdf->getY(), $styleSeparador);
		
		
		if (count($participants) > 0) {
			$pdf->AddPage();			
			
			$pdf->SetFont('dejavusans', 'BU', 13, '', true);
			$pdf->SetXY($xEtiquetes1, $pdf->getY());
			$pdf->Cell(0, 0, 'RELACIÓ D\'ALUMNES APTES', 0, 0, 'C');
			
			$pdf->Ln(10);
			
			$i = 0;
			foreach ($participants as $participant) {
				
				$this->printAlumneCurs($pdf, $curs, $participant); 
				
				$i++;
				if ($i%3 == 0) {  // 3 per pàgina
					$i = 0;
					$pdf->AddPage();
					$pdf->SetXY($xEtiquetes1, $pdf->getY());
					$pdf->Ln(10);
				} else {	
					$pdf->SetY($pdf->getY() + $alumneRowH * 9);
				}	
			}
		}
		
		// reset pointer to the last page
		$pdf->lastPage();
			
		// Close and output PDF document
		$response = new Response($pdf->Output("Acta_Curs_".$titolCurs."_".$curs->getId()."_".$club->getCodi().".pdf", "D"));
		$response->headers->set('Content-Type', 'application/pdf');
		return $response;
	}

	private function printAlumneCurs($pdf, $curs, $participant) {
	
		$club = $curs->getClub();
		$metapersona = $participant->getMetapersona();
		$persona = $metapersona->getPersona($club);
		$llistaTitols = $metapersona->getInfoHistorialTitulacions();
	
		$xEtiquetes1 = PDF_MARGIN_LEFT;
		$xCamps1 = $xEtiquetes1 + 33;
		$cellH = 7;
		$ln = 9;
		$wEtiquetes = 30;
		$wLarge = 112;
		$wDNI = $wNaixement = 35;
		$wPoblacio = 62;
		$wNacionalitat = 20;
		$xEtiqNaixement = $xEtiquetes1 +$wEtiquetes+ $wDNI + 12;
		$xNaixement = $xEtiqNaixement + $wEtiquetes+3;
		$xEtiqNacio = $xEtiquetes1 + +$wEtiquetes+$wPoblacio + 10;
		$xNacionalitat = $xEtiqNacio + $wEtiquetes-8 + 1;
		
		$xFoto = $pdf->getPageWidth() - 45;
		$wFoto = 30; 	
		$fMargin = 2;	

		$yFila1 = $pdf->getY();

		$pdf->SetFont('dejavusans', '', 11, '', true);		
		$pdf->SetTextColor(0, 0, 0); // Negre
		
		$pdf->SetXY($xEtiquetes1, $yFila1);	
		$pdf->Cell($wEtiquetes, $cellH, 'NOM i COGNOMS:', 0, 0, 'L', false, '', 1);
		
		$pdf->Ln($ln);
		$pdf->Cell($wEtiquetes, $cellH, 'ADREÇA ELECTRÓNICA:', 0, 0, 'L', false, '', 1);
		
		$pdf->Ln($ln);
		$pdf->Cell(0, $cellH, 'TELÈFON/S:', 0, 0, 'L');
		
		$pdf->Ln($ln);
		$pdf->Cell(0, $cellH, 'DNI:', 0, 0, 'L');
		
		$pdf->SetX($xEtiqNaixement);
		$pdf->Cell($wEtiquetes, $cellH, 'DATA NAIXEMENT:', 0, 0, 'L', false, '', 1);
		
		$pdf->Ln($ln);
		$pdf->SetX($xEtiquetes1);
		$pdf->Cell(0, $cellH, 'POBLACIÓ:', 0, 0, 'L');
		
		$pdf->SetX($xEtiqNacio);
		$pdf->Cell($wEtiquetes-8, $cellH, 'NACIONALITAT:', 0, 0, 'L', false, '', 1);
		
		if ($llistaTitols != '') {
			$pdf->Ln($ln);
			$pdf->SetX($xEtiquetes1);
			$pdf->Cell(0, $cellH, 'TITULACIONS:', 0, 0, 'L');
		}
		
		$pdf->SetTextColor(0, 0, 128); // Blau
		
		$pdf->SetXY($xCamps1, $yFila1);	
		$pdf->Cell($wLarge, $cellH, $metapersona->getNomCognoms(), 1, 0, 'L', false, '', 1);
		
		$pdf->Ln($ln);
		$pdf->SetX($xCamps1);
		$pdf->Cell($wLarge, $cellH, ($persona != null?$persona->getMail():''), 1, 0, 'L', false, '', 1);

		$pdf->Ln($ln);
		$pdf->SetX($xCamps1);
		$pdf->Cell($wLarge, $cellH, ($persona != null?$persona->getTelefons():''), 1, 0, 'L');

		$pdf->Ln($ln);
		$pdf->SetX($xCamps1);
		$pdf->Cell($wDNI, $cellH, $metapersona->getDni(), 1, 0, 'C');
		
		$pdf->SetX($xNaixement);
		$pdf->Cell($wNaixement, $cellH, ($persona != null?$persona->getDatanaixement()->format('d/m/Y'):''), 1, 0, 'C');
		
		$pdf->Ln($ln);
		$pdf->SetX($xCamps1);
		$pdf->Cell($wPoblacio, $cellH, ($persona != null?$persona->getAddrpob():''), 1, 0, 'L', false, '', 1);
		
		$pdf->SetX($xNacionalitat);
		$pdf->Cell($wNacionalitat, $cellH, ($persona != null?$persona->getAddrnacionalitat():''), 1, 0, 'C');
		
		
		if ($llistaTitols != '') {
			$pdf->Ln($ln);
			$pdf->SetX($xCamps1);
			$pdf->Cell($wLarge, $cellH, $llistaTitols, 1, 0, 'L', false, '', 1);
		}	
		
		
		$pdf->SetTextColor(0, 0, 0); // Negre
				
		$pdf->Ln($ln);
		$pdf->SetX($xCamps1);
		$pdf->Cell($wLarge, 2*$cellH, 'ETIQUETA DE CONTROL', 1, 0, 'C');
					
		$pdf->SetXY($xFoto, $yFila1);	
		$pdf->Cell($wFoto, 6*$cellH, 'FOTO', 1, 0, 'C');
		
		if ($persona != null && $persona->getFoto() != null && $persona->getFoto()->getWidth() > 0 && $persona->getFoto()->getHeight() > 0) {
		
			$pdf->Image($persona->getFoto()->getAbsolutePath(), $xFoto+$fMargin, $yFila1+$fMargin, $wFoto-2*$fMargin, 0, $persona->getFoto()->getExtension(), '', 'CT', false, 150, '', false, false, array(), 'LT', false, false, false, array());
		
		}
		$pdf->Ln($cellH);
		
	}

	private function getHtmlTaulaDocents($files, $docencies) {
		$color = 'color="#000080"';
		$bordercolor = '#000000';
		$border = 'border:0.5px solid '.$bordercolor.';';
		$tbl = '<table border="0" cellpadding="5" cellspacing="0" nobr="true">
				  <tr style="">
				  	<td width="100" align="center" rowspan="3" style="'.$border.' font-size: large; line-height:50px;">DNI</td>
				  	<td width="220" align="center" rowspan="3"  style="'.$border.' font-size: large; line-height:50px;">NOM i COGNOMS</td>
				  	<td width="200" align="center" colspan="4" style="'.$border.' font-size: small;">CLASSES</td>
				  	<td width="120" align="center" rowspan="3"  style="'.$border.' font-size: large; line-height:50px;">SIGNATURES</td>
				  </tr>
				  <tr>
				  	<td width="50" align="center" rowspan="2" style="'.$border.' font-size: x-small; line-height:30px;">TEÒRIQUES</td>
				  	<td width="150" align="center" colspan="3" style="'.$border.' font-size: small;">PRÀCTIQUES</td>
				  </tr>
				  <tr>
				  	<td width="50" align="center" style="'.$border.' font-size: small;">AULA</td>
				  	<td width="50" align="center" style="'.$border.' font-size: small;">PISCINA</td>
				  	<td width="50" align="center" style="'.$border.' font-size: small;">MAR</td>
				  </tr>';
		
		$ht = $hp = $hm = $ha = 0;
		$fila = 0;
		foreach ($docencies as $docencia) {
			$meta = $docencia->getMetadocent();
			$ht += $docencia->getHteoria();
			$hp += $docencia->getHpiscina();
			$hm += $docencia->getHmar();
			$ha += $docencia->getHaula();
			
			$tbl .= '<tr>
				  		<td style="'.$border.'" '.$color.' align="center">'.$meta->getDni().'</td>
				  		<td style="'.$border.'" '.$color.' align="left">'.$meta->getCognomsNom().'</td>
				  		<td style="'.$border.'" '.$color.' align="center">'.$docencia->getHteoria().'</td>
				  		<td style="'.$border.'" '.$color.' align="center">'.$docencia->getHaula().'</td>
				  		<td style="'.$border.'" '.$color.' align="center">'.$docencia->getHpiscina().'</td>
				  		<td style="'.$border.'" '.$color.' align="center">'.$docencia->getHmar().'</td>
				  		<td style="'.$border.'"><span style="font-size: large;"></span></td>
					</tr>';
			$fila++;
		}
				
		for ($i=$fila; $i < $files; $i++) { 
			$tbl .= '<tr>
				  		<td style="'.$border.'" align="center"></td>
				  		<td style="'.$border.'" align="left"></td>
				  		<td style="'.$border.'" align="center"></td>
				  		<td style="'.$border.'" align="center"></td>
				  		<td style="'.$border.'" align="center"></td>
				  		<td style="'.$border.'" align="center"></td>
				  		<td style="'.$border.'"><span style="font-size: large;"></span></td>
					</tr>';	
		}		
		$tbl .= '<tr>
				  	<td colspan="2" style="border-bottom: 0.1em solid #ffffff; border-left: 0.1em solid #ffffff;" align="right">Totals</td>
			  		<td align="center" style="'.$border.'" '.$color.'>'.$ht.'</td>
			  		<td align="center" style="'.$border.'" '.$color.'>'.$ha.'</td>
			  		<td align="center" style="'.$border.'" '.$color.'>'.$hp.'</td>
			  		<td align="center" style="'.$border.'" '.$color.'>'.$hm.'</td>
			  		<td style="border-bottom: 0.1em solid #ffffff; border-right: 0.1em solid #ffffff; "></td>
				</tr>
			  </table>';
			  
		return $tbl;	  
	}
}
