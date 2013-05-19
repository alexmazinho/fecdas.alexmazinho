<?php
namespace Fecdas\PartesBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fecdas\PartesBundle\Classes\TcpdfBridge;
use Fecdas\PartesBundle\Entity\EntityLlicencia;

class PDFController extends BaseController {

	public function facturatopdfAction() {
		$request = $this ->getRequest();
	
		if ($request->query->has('id')) {
			$parte = $this->getDoctrine()
			->getRepository('FecdasPartesBundle:EntityParte')
			->find($request->query->get('id'));
			
			if ($parte == null || $parte->getDatapagament() == null)
				return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
			
			if ($parte) {
				$this->logEntry($this->get('session')->get('username'), 'PRINT FACTURA',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), $parte->getId());
				
				
				// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
				$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
					
				$pdf->init($params = array('author' => 'FECDAS', 'title' => 'Factura Llista llicències ' . date("Y")));
					
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
				
				$club = $parte->getClub();
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
				$tbl .= 'Tel: 93 356 05 43  Fax: 93 356 30 73<br/>';
				$tbl .= 'http://www.fecdas.cat<br/>';
				$tbl .= 'E-mail: info@fecdas.cat<br/>';
				$tbl .= 'NIF: Q5855006B</td></tr>';
				$tbl .= '</table>';
				$pdf->writeHTML($tbl, false, false, false, false, '');
				
				$pdf->SetFont('dejavusans', '', 10, '', true);
				$pdf->setY($y);
				$pdf->setX($pdf->getPageWidth() - 80);
			
				$club = $parte->getClub();
				$tbl = '<table border="0" cellpadding="5" cellspacing="0">';
				$numfactura = 'W-' . sprintf("%04d", $parte->getNumfactura()) . '/' . $parte->getDataalta()->format('Y');
				$tbl .= '<tr><td width="150" align="right" style="color:#555555;">Factura número:</td><td width="120" align="left"><b>' . $numfactura . '</b></td></tr>';
				$tbl .= '<tr><td align="right" style="color:#555555;">Data:</td><td align="left"><b>' . $parte->getDatapagament()->format('d/m/Y') . '</b></td></tr>';
				$tbl .= '<tr><td align="right" style="color:#555555;">CIF:</td><td align="left"><b>' . $club->getCif() . '</b></td></tr>';
				$tbl .= '</table>';
				
				$pdf->writeHTML($tbl, false, false, false, false, '');
				
				$pdf->Ln(5);
				$pdf->setX($x_ini);

				// Get factura detall
				$detallfactura = $this->getDetallFactura($parte);
				
				// Get factura totals
				$totalfactura = $this->getTotalsFactura($detallfactura);
				
				$pdf->SetFont('dejavusans', '', 8, '', true);
				
				$tbl = '<table border="1" cellpadding="5" cellspacing="0">
				<tr style="background-color:#CCCCCC;">
				<td width="80" align="center">REFERÈNCIA</td>
				<td width="280" align="left">CONCEPTE</td>
				<td width="50" align="center">QUANT.</td>
				<td width="50" align="center">PREU</td>
				<td width="70" align="center">IMPORT</td>
				<td width="60" align="center">I.V.A<br/>(' . number_format($parte->getTipus()->getIva(), 2, ',', '.') . '%)</td>
				<td width="80" align="right">TOTAL</td>
				</tr>';
				
				$tblref = "";
				$tblconc = "";
				$tblquant = "";
				$tblpreu = "";
				$tblimp = "";
				$tbliva = "";
				$tbltotal = "";
				
				foreach ($detallfactura as $c => $lineafactura) {
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
				$tbl .= '<td align="center">IMPORT<br/>' . number_format($totalfactura['totalparcial'], 2, ',', '.') . ' €</td>';
				$tbl .= '<td align="center">I.V.A<br/>' . number_format($totalfactura['iva'], 2, ',', '.') . ' €</td>';
				$tbl .= '<td align="center">TOTAL<br/>' . number_format($totalfactura['total'], 2, ',', '.') .  ' €</td>';
				$tbl .= '</tr>';
				$tbl .= '<tr border="0">';
				$tbl .= '<td colspan="6" style="background-color:#EEEEEE;">&nbsp;</td>';
				$tbl .= '<td align="center">A PAGAR<br/><b>' . number_format($totalfactura['total'], 2, ',', '.') .  ' €</b></td>';
				$tbl .= '</tr>';
				
				$tbl .= '</table>';
				
				$pdf->writeHTML($tbl, true, false, false, false, '');
				
				if (!$parte->hasIva()) {
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
				$text = '<b>FACTURA PAGADA ON-LINE</b>';
				$pdf->writeHTML($text, true, false, false, false, '');
				
				if (number_format($parte->getPreuTotalIVA(), 2, ',', '.') != number_format($parte->getImportfactura(), 2, ',', '.')) {
					// Ha canviat la factura, mostra avís factura obsoleta
					$pdf->SetFont('dejavusans', '', 14, '', true);
					$y = $y_ini + 120;
					$x = $x_ini;
					$text = 'Aquesta factura ha quedat obsoleta per modificacions posteriors al pagament de la llista.<br/>';
					$text .= 'Per a obtenir la factura original, poseu-vos en contacte amb la federació.';
					
					//$pdf->writeHTML($text, true, false, false, false, 'L');
					$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'C', true);
				}
				
				// reset pointer to the last page
				$pdf->lastPage();
					
				// Close and output PDF document
				$response = new Response($pdf->Output("factura_" . $parte->getClub()->getCodi() . "_" . $parte->getId() . ".pdf", "D"));
				$response->headers->set('Content-Type', 'application/pdf');
				return $response;
			}
					
		}
		return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	}
				
	public function  llistestopdfAction() {
		$request = $this ->getRequest();
		
		$club = null;
		if ($request->query->has('club')) {
			$club = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')
				->find($request->query->get('club'));
		} else {
			$club = $this->getCurrentClub();			
		}
		
		if ($club == null) return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage')); 

		$currentClub = $club->getCodi();
		
		$this->logEntry($this->get('session')->get('username'), 'PRINT LLISTES',
				$this->get('session')->get('remote_addr'),
				$this->getRequest()->server->get('HTTP_USER_AGENT'), $currentClub);

		$partesclub = $this->consultaPartesClub($currentClub);
		
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
		$pdf->init($params = array('author' => 'FECDAS', 'title' => "Llistes de l'any " . date("Y")),
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
		
		$pdf->SetFont('dejavusans', '', 16, '', true);
		$text = '<b>Llistes de comunicacions de l\'any '. date("Y") .'</b>';
		$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, '', true);

		$y += 15;
		
		$pdf->SetTextColor(100, 100, 100);
		
		$pdf->SetFont('dejavusans', '', 12, '', true);
		$text = '<i>Imprimida en data  ' . date("d/m/Y") .'</i>';
		$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, '', true);
		
		$pdf->SetTextColor(0, 0, 0); // Negre
		$pdf->SetFont('dejavusans', '', 10, '', true);
		
		$tbl = '<table border="1" cellpadding="5" cellspacing="0">
				  <tr style="background-color:#DDDDDD;">
				  <td width="60" align="center">Any</td>
				  <td width="110" align="center">Data alta</td>
				  <td width="287" align="center">Tipus de llista</td>
				  <td width="80" align="center">Llicències</td>
				  <td width="100" align="right">Preu</td>
				 </tr>';
		
		$totalLlicencies = 0;
		$totalPreu = 0;
		
		foreach ($partesclub as $c => $parte) {
			$totalLlicencies += $parte->getNumLlicencies();
			$totalPreu += $parte->getPreuTotalIVA();
			$tbl .= '<tr><td align="center">' . $parte->getAny() . '</td>';
			$tbl .= '<td align="center">' . $parte->getDataalta()->format('d/m/Y') .  '</td>';
			$tbl .= '<td align="center">' . $parte->getTipus()->getDescripcio() .  '</td>';
			$tbl .= '<td align="center">' . $parte->getNumLlicencies(). '</td>';
			$tbl .= '<td align="right">' . number_format($parte->getPreuTotalIVA(), 2, ',', '.') .  '&nbsp;€</td></tr>';
		}
		
		$tbl .= '<tr><td colspan="3" align="right"><b>Total</b></td>';
		$tbl .= '<td align="center">' . $totalLlicencies . '</td>';
		$tbl .= '<td align="right">' .  number_format($totalPreu, 2, ',', '.') . '&nbsp;€</td></tr>';
		$tbl .= '</table>';
		
		$pdf->Ln(10);
		
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		// reset pointer to the last page
		$pdf->lastPage();
			
		// Close and output PDF document
		$response = new Response($pdf->Output("comunicacions_" . $currentClub . "_" . date("Ymd") . ".pdf", "D"));
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
				
				if ($parte->isVigent() and $parte->getDatapagament() == null) {
					// Si no les paguen o confirmen on-line surt el missatge
					$y += 10;
					$pdf->SetTextColor(100, 100, 100); // GRis
					$pdf->SetFillColor(200, 200, 200); //Blanc
					$pdf->SetFont('dejavusans', 'BI', 14, '', true);
					$text = '<p>## Aquestes llicències estan pendents de pagament ##</p>';
					$pdf->writeHTMLCell(0, 0, $x, $y, $text, '', 1, 1, true, 'C', true);
					$pdf->SetTextColor(0, 0, 0); // Negre
					$pdf->SetFillColor(255, 255, 255); //Blanc
				}
				
				$y += 15;
				
				$datainici = $parte->getDataalta();
				if ($parte->getTipus()->getEs365() == true) {
					$datafi = $datainici;
					$datafi->add(new \DateInterval('P365D')); // Add 365 dies
				} else {
					if ($parte->getTipus()->getId() == 9) {
						// Un dia
						$datafi = $datainici;
					} else {
						$datafi = \DateTime::createFromFormat("Y-m-d", $parte->getDataalta()->format("Y") . "-12-31");
					}
				}

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
				
				$pdf->writeHTMLCell(0, 0, $x, $y, "Aquesta llicència és provisional i té una validesa màxima de 60 dies", 0, 0, 0, true, 'C', true);
				
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
				$pdf->SetTextColor(255, 255, 255); // Blanc
				$pdf->SetFillColor(255, 255, 255); // 
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
				$pdf->writeHTMLCell(0, 0, $x, $y, "Telf. entitat: " . $llicencia->getParte()->getClub()->getTelefon(), 0, 0, 0, true, 'L', true);

				$datacaduca = $llicencia->getParte()->getDataalta();
				$datacaduca->add(new \DateInterval('P60D'));  // 60 dies
				
				if ($datacaduca > $llicencia->getDatacaducitat()) $datacaduca = $llicencia->getDatacaducitat();
				
				$x += 32;
				$pdf->writeHTMLCell(0, 0, $x, $y, "Carnet provisional vàlid fins al " . $datacaduca->format('d/m/Y'), 0, 0, 0, true, 'L', true);
								
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