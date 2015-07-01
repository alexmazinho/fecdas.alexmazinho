<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use FecdasBundle\Form\FormProducte;
use FecdasBundle\Entity\EntityProducte;
use FecdasBundle\Form\FormFactura;
use FecdasBundle\Entity\EntityFactura;
use FecdasBundle\Form\FormRebut;
use FecdasBundle\Entity\EntityRebut;
use FecdasBundle\Form\FormComanda;
use FecdasBundle\Entity\EntityComanda;
use FecdasBundle\Entity\EntityPreu;
use FecdasBundle\Form\FormComandaDetall;
use FecdasBundle\Entity\EntityComandaDetall;
use FecdasBundle\Entity\EntityDuplicat;
use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityComptabilitat;


class FacturacioController extends BaseController {
	
	public function traspascomptabilitatAction(Request $request) {
		// http://www.fecdasnou.dev/traspascomptabilitat
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$page = $request->query->get('page', 1);
			
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityComptabilitat c ";
		$strQuery .= " WHERE c.databaixa IS NULL AND c.fitxer <> '' ";
		$strQuery .= " ORDER BY c.dataenviament DESC";
		
		$query = $em->createQuery($strQuery)->setMaxResults( 1 );
		
		//$result = $query->getResult();
		$paginator  = $this->get('knp_paginator');
			
		$enviaments = $paginator->paginate(
			$query,
			$page,
			10/*limit per page*/
		);
		
		
		//$enviament = (count($result) == 0?null:$result[0]);
			
		$formBuilder = $this->createFormBuilder()
			->add('datadesde', 'date', array(
				'disabled' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'format' 		=> 'dd/MM/yyyy'))
			->add('datafins', 'date', array(
							'disabled' 		=> false,
							'widget' 		=> 'single_text',
							'input' 		=> 'datetime',
							'empty_value' 	=> false,
							'format' 		=> 'dd/MM/yyyy',
			));
		
		return $this->render('FecdasBundle:Facturacio:traspas.html.twig',
				$this->getCommonRenderArrayOptions(
						array('form' 		=> $formBuilder->getForm()->createView(),
							  'enviaments' 	=> $enviaments
						)
				));
			
	}
	
	public function anulartraspasAction(Request $request) {
		// http://www.fecdasnou.dev/traspascomptabilitat
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$id = $request->query->get('id', 1);
			
		return new Response("anular".$id);
	}	
	
	public function fitxercomptabilitatAction(Request $request) {
		// http://www.fecdasnou.dev/fitxercomptabilitat?inici=2015-01-01&final=2015-06-22
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$current = date("d/m/Y");
		 
		$inici = $request->query->get('inici', $current);
		$final = $request->query->get('final', $current);
		 
		$datainici = \DateTime::createFromFormat('d/m/Y H:i:s', $inici." 00:00:00");
		$datafinal = \DateTime::createFromFormat('d/m/Y H:i:s', $final." 23:59:59");
		 
		$filename = BaseController::PREFIX_ASSENTAMENTS.'_'.date("Ymd_His").".txt";
	
		$fs = new Filesystem();
		try {
			if (!$fs->exists(__DIR__.BaseController::PATH_TO_COMPTA_FILES)) {
				throw new \Exception("No existeix el directori " .__DIR__.BaseController::PATH_TO_COMPTA_FILES);
			} else {
				$em = $this->getDoctrine()->getManager();
				$enviament = new EntityComptabilitat($filename, $datainici, $datafinal);
				$em->persist($enviament);
				
				$assentaments = $this->generarFitxerAssentaments($enviament); // Array
				 
				if ($enviament->getApunts() == 0) throw new \Exception("No hi ha assentaments per aquests criteris de cerca ");
				
				$fs->dumpFile(__DIR__.BaseController::PATH_TO_COMPTA_FILES.$filename, implode("\r\n",$assentaments));
				
				$em->flush();
			}
		} catch (\Exception $e) {
			$em->detach($enviament);
			$this->logEntryAuth('FITXER COMPTA KO',	'Dates : ' . $inici ." - ".$final);
			
			$response = new Response();
			$response->setStatusCode(500, $e->getMessage());
			return $response;
		}
		$this->logEntryAuth('FITXER COMPTA OK',	'Dates : ' . $inici ." - ".$final);
		
		//$response = new Response(  $this->generateUrl('FecdasBundle_downloadassentaments', array('filename' => $filename), true));  
		//$response = $this->downloadFile(__DIR__.BaseController::PATH_TO_COMPTA_FILES.$filename, $filename, 'Fitxer traspàs assentaments dia '.date("Y-m-d H:i"));
		//$response->prepare($request);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode(array(
				'id'=> $enviament->getId(), 
				'filename' => $enviament->getFitxer(),
				'text' => $enviament->getTextComptabilitat(),
		))); 
		
		return $response;
	}
	
	/**
	 * Get fitxer assentaments per transpassar al programa de comptabilitat
	 *
	 * @return array
	 */
	private function generarFitxerAssentaments($enviament) {

		/**
		 *  
		   
		    Descripció									Longitud	Posició
			-------------------------------------------	----------- -----------
			Clau de l'Assentament							1		1 - 1
			Data de l'Assentament (AAAAMMDD)				8		2 - 9
			Núm. d'Assentament								6		10 - 15
			Línia											4		16 - 19
			Codi del Compte									9		20 - 28
			Descripció del Compte							100		29 - 128
			Concepte de l'Assentament						40		129 - 168
			Núm. de Document								8		169 - 176
			Centre de Cost									4		177 - 180
			Projecte										4		181 - 184
			Import											13		185 - 197
			Signe ( D / H )									1		198 - 198
			Codi del Concepte								2		199 - 200
			Intern											2		201 - 202
			Intern											10		203 - 212
			Intern											1		213 - 213
			
			«Clave de Entrada: clave del Asiento para el Diario de Comprobación. Se debe
			introducir una código existente en el archivo de Claves de Entrada, pulsar el botón
			de selección (o F4) para visualizar las Claves disponibles.»
			
			Número de Asiento:
			
			Para dar un ALTA pondremos un 0 y el programa automáticamente asignará
			el número de asiento que corresponda después de grabar la primera línea

		 	Exemple de DI_00237_CAT191

			0												  5													100												  150												200
			123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123 
			
			02015022710023700014310136  F. 00237/2015  CAT191                                                                               Factura: 00716/2015                     00237/15        0000000105.00H000000000000000  (SALT) 
			x........vvvvvv----+++++++++xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx........................................^^^^^^^^xxxx----+++++++++++++H000000000000000 
						
			02015022710023700025720001  F. 00237/2015  CAT191                                                                               Factura: 00716/2015                     00237/15        0000000105.00D000000000000000  (SALT)
		 * 
		 */
		$apuntsComandesAltes = $this->generarAssentamentsComandes($enviament, false);
		
		$apuntsComandesBaixes = $this->generarAssentamentsComandes($enviament, true);
		
		$apuntsRebutsAltes	= $this->generarAssentamentsRebuts($enviament, false);
		
		$apuntsRebutsBaixes	= $this->generarAssentamentsRebuts($enviament, true);
		
		$assentaments = array_merge($apuntsComandesAltes, $apuntsComandesBaixes, $apuntsRebutsAltes, $apuntsRebutsBaixes);
		
		return $assentaments;
	}

	/**
	 * Comandes	  => Club apunt D + Producte corresponent apunt H	
	 * Anular comanda? => Club apunt D + Producte corresponent apunt H  però els dos amb import negatiu
	 * 
	 * @param unknown $datainici
	 * @param unknown $datafinal
	 * @param string $baixes
	 * @return multitype:string
	 */
	private function generarAssentamentsComandes($enviament, $baixes = false) {
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityComanda c ";
		
		if ($baixes == false) {
			$strQuery .= " WHERE (c.dataentrada >= :ini AND c.dataentrada <= :final) ";
			$strQuery .= " AND (c.comptabilitat IS NULL) ";   // Pendent d'enviar encara 
		} else {
			$strQuery .= " WHERE (c.databaixa >= :ini AND c.databaixa <= :final) ";
			$strQuery .= " AND (c.comptabilitat IS NOT NULL) ";  // Enviat a compta
		}
		$strQuery .= " ORDER BY c.dataentrada";
		
		$query = $em->createQuery($strQuery);
		$query->setParameter('ini', $enviament->getDatadesde()->format('Y-m-d H:i:s'));
		$query->setParameter('final', $enviament->getDatafins()->format('Y-m-d H:i:s'));
		
		$result = $query->getResult();
		
		$comandes = $enviament->getComandes();
		$assentaments = array();
		foreach ($result as $comanda) {
			$importTotal = $comanda->getTotalDetalls();
			
			if ($importTotal > 0) {	
				$comandes++;
				$linia = 1;
				$signe = ($baixes == false?'0':'-');
				
				$data = $comanda->getDataentrada()->format('Ymd');
				
				$numAssenta = str_pad($comanda->getId(), 6, "0", STR_PAD_LEFT);/*str_repeat("0",6);*/
				
				$desc = $this->netejarNom($comanda->getConcepteComanda(), false);
				$conc = $comanda->getConcepteComandaCurt();
				$doc = $comanda->getNumAssentament();
				$import = $signe.str_pad((number_format($importTotal, 2, '.', '').''), 12, "0", STR_PAD_LEFT);
				// Apunt club
				$club = $comanda->getClub();
				$compte = $club->getCompte();
				
				$apuntclub = "0".$data.$numAssenta.str_pad($linia."", 4, "0", STR_PAD_LEFT).str_pad($compte."", 9, " ", STR_PAD_RIGHT);
				$apuntclub .= str_pad($desc, 100, " ", STR_PAD_RIGHT).str_pad($conc, 40, " ", STR_PAD_RIGHT).$doc.str_repeat(" ",4).str_repeat(" ",4);
				$apuntclub .= $import.BaseController::HABER.str_repeat("0",15);
					
				// Validar que quadren imports
				$assentaments[] = $apuntclub;
					
				$linia++;
				
				$detalls = $comanda->getDetallsAcumulats();
				
				foreach ($detalls as $compte => $d) {
					// Apunt/s producte/s
					
					$desc = $d['total']." x ".$this->netejarNom($d['producte'], false);
						
					$import = $signe.str_pad((number_format($d['import'], 2, '.', '').''), 12, "0", STR_PAD_LEFT);
					
					$apunt = "0".$data.$numAssenta.str_pad($linia."", 4, "0", STR_PAD_LEFT).str_pad($compte."", 9, " ", STR_PAD_RIGHT);
					$apunt .= str_pad($desc, 100, " ", STR_PAD_RIGHT).str_pad($conc, 40, " ", STR_PAD_RIGHT).$doc.str_repeat(" ",4).str_repeat(" ",4);
					$apunt .= $import.BaseController::DEBE.str_repeat("0",15);
					
					$assentaments[] = $apunt;
						
					$linia++;
				}
				
				$comanda->setComptabilitat($enviament);
			} else {
				$comanda->addComentari("Comanda amb import 0 no s'envia a comptabilitat");
			}
		}
		$enviament->setComandes($comandes);

		return $assentaments;
	}	

	/**
	 *  Rebuts	=> Club apunt H  + Caixa corresponent apunt D 		
	 *	Anular rebut => Club apunt H  + Caixa corresponent apunt D  però import negatiu
	 *
	 * @param unknown $datainici
	 * @param unknown $datafinal
	 * @param string $baixes
	 * @return multitype:string
	 */
	private function generarAssentamentsRebuts($enviament, $baixes = false) {
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT r FROM FecdasBundle\Entity\EntityRebut r WHERE r.import > 0 AND ";
		
		if ($baixes == false) {
			$strQuery .= " (r.datapagament >= :ini AND r.datapagament <= :final) ";
			$strQuery .= " AND (r.comptabilitat IS NULL) ";	// Pendent d'enviar encara 
		} else {
			$strQuery .= " (r.dataanulacio >= :ini AND r.dataanulacio <= :final) ";
			$strQuery .= " AND (r.comptabilitat IS NOT NULL) ";  // Enviat a compta
		}
		$strQuery .= " ORDER BY r.datapagament";
		
		error_log($enviament->getDatadesde()->format('Y-m-d H:i:s') ."-". $enviament->getDatafins()->format('Y-m-d H:i:s'));
		 
		$query = $em->createQuery($strQuery);
		$query->setParameter('ini', $enviament->getDatadesde()->format('Y-m-d H:i:s'));
		$query->setParameter('final', $enviament->getDatafins()->format('Y-m-d H:i:s'));
		
		$result = $query->getResult();

		$rebuts = $enviament->getRebuts();
		$assentaments = array();
		foreach ($result as $rebut) {
			$rebuts++;
			$linia = 1;
			$signe = ($baixes == false?'0':'-');
			$data = $rebut->getDatapagament()->format('Ymd');
			
			$numAssenta = str_pad($rebut->getId(), 6, "0", STR_PAD_LEFT);/*str_repeat("0",6);*/
			
			$desc = $this->netejarNom($rebut->getConcepteRebutLlarg(), false);
			$conc = $rebut->getConcepteRebutCurt();
			$doc = $rebut->getNumRebutCurt();
			$import = $signe.str_pad((number_format($rebut->getImport(), 2, '.', '').''), 12, "0", STR_PAD_LEFT);
			
			// Apunt club
			$club = $rebut->getClub();	
			$compte = $club->getCompte();

			$apunt = "0".$data.$numAssenta.str_pad($linia."", 4, "0", STR_PAD_LEFT).str_pad($compte."", 9, " ", STR_PAD_RIGHT);
			$apunt .= str_pad($desc, 100, " ", STR_PAD_RIGHT).str_pad($conc, 40, " ", STR_PAD_RIGHT).$doc.str_repeat(" ",4).str_repeat(" ",4);
			$apunt .= $import.BaseController::HABER.str_repeat("0",15);

			$assentaments[] = $apunt;
			
			$linia++;
			// Apunt caixa
			$compte = $rebut->getTipuspagament();
			
			$apunt = "0".$data.$numAssenta.str_pad($linia."", 4, "0", STR_PAD_LEFT).str_pad($compte."", 9, " ", STR_PAD_RIGHT);
			$apunt .= str_pad($desc, 100, " ", STR_PAD_RIGHT).str_pad($conc, 40, " ", STR_PAD_RIGHT).$doc.str_repeat(" ",4).str_repeat(" ",4);
			$apunt .= $import.BaseController::DEBE.str_repeat("0",15);
			
			$assentaments[] = $apunt;
			
			$linia++;
			$rebut->setComptabilitat($enviament);
		}
		$enviament->setRebuts($rebuts);
		
		return $assentaments;
	}
	
	
	
	//private function downloadFile($fitxer, $path, $desc) {
	public function downloadassentamentsAction(Request $request, $filename) {
			
		error_log($filename);
		$response = new BinaryFileResponse(__DIR__.BaseController::PATH_TO_COMPTA_FILES.$filename);
	
		$response->setCharset('UTF-8');
	
		$response->headers->set('Content-Type', 'text/plain');
		$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
		$response->headers->set('Content-Description', 'Fitxer traspàs assentaments '.$filename);
			
		$response->headers->set('Content-Transfer-Encoding', 'binary');
		$response->headers->set('Pragma', 'no-cache');
		$response->headers->set('Expires', '0');
		
		$response->prepare($request);
		return $response;
	}
	
	
	public function ingresosAction(Request $request) {
		// Llistat ingresos a compte dels clubs
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$this->logEntryAuth('VIEW INGRESOS', $this->get('session')->get('username'));
	
		$codi = $request->query->get('cerca', '');
		$nr = $request->query->get('numrebut', '');
	
		$arrayReb = explode("/", $nr);
		$numrebut = $nr;
		$anyrebut = 0;
		if (count($arrayReb) > 1) {
			$numrebut = is_numeric($arrayReb[0])?$arrayReb[0]:0;
			$anyrebut = is_numeric($arrayReb[1])?$arrayReb[1]:0;
		}
	
		$baixes = $request->query->get('baixes', 0);
		$baixes = ($baixes == 1?true:false);
	
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'r.datapagament');
		$direction = $request->query->get('direction', 'desc');
	
		$query = $this->consultaIngresos($codi, $numrebut, $anyrebut, $baixes, $sort, $direction);
					
		$paginator  = $this->get('knp_paginator');
					
		$ingresos = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
	
		$formBuilder = $this->createFormBuilder()
			->add('cerca', 'hidden', array(
				'data' => $codi	))
			->add('numrebut', 'text', array(
				'data' => $nr	))
			->add('baixes', 'checkbox', array(
				'required' => false,
				'data' => $baixes
		));
												
		return $this->render('FecdasBundle:Facturacio:ingresos.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'ingresos' => $ingresos,  'sortparams' => array('sort' => $sort,'direction' => $direction))
		));
	}
	
	public function editarfacturaAction(Request $request) {
		// Edició d'una factura existent
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$em = $this->getDoctrine()->getManager();
		$factura = null;
		
		if ($request->getMethod() != 'POST') {
			$id = $request->query->get('id', 0);
		
			$factura = $this->getDoctrine()->getRepository('FecdasBundle:EntityFactura')->find($id);
		
		} else {
			$data = $request->request->get('factura');
			$id = (isset($data['id'])?$data['id']:0);
		
			if ($id > 0) $factura = $this->getDoctrine()->getRepository('FecdasBundle:EntityFactura')->find($id);
		}

		if ($factura == null) {
			// No trobada
			$this->logEntryAuth('FACTURA EDIT KO',	'Factura : ' . $id);
			$this->get('session')->getFlashBag()->add('error-notice', 'Factura no trobada ');
			return $this->redirect($this->generateUrl('FecdasBundle_comandes'));
		}
		
		$this->logEntryAuth('FACTURA EDIT',	'Factura : ' . $factura->getId().' '.$factura->getConcepte());
		
		$form = $this->createForm(new FormFactura(), $factura);
		
		if ($request->getMethod() == 'POST') {
			try {
				$form->handleRequest($request);
				
				if ($form->isValid()) {
						
					if ($factura->getComanda() == null) {
						$form->get('comanda')->addError(new FormError('Falta la comanda'));
						throw new \Exception('Cal escollir una comanda ' );
					}
					$maxNumFactura = $this->getMaxNumEntity(date('Y'), BaseController::FACTURES) + 1;
					$factura->setNum($maxNumFactura); // Per si canvia
						
					if ($factura->getId() > 0)  $factura->setDatamodificacio(new \DateTime());
						
		
				} else {
					throw new \Exception('Dades incorrectes, cal revisar les dades de la factura ' ); //$form->getErrorsAsString()
				}
		
				$em->flush();
		
				$this->get('session')->getFlashBag()->add('info-notice',	'La factura s\'ha desat correctament');
					
				$this->logEntryAuth('COMANDA SUBMIT',	'producte : ' . $factura->getId().' '.$factura->getInfoComanda());
				// Ok, retorn form sms ok
				return $this->redirect($this->generateUrl('FecdasBundle_factura',
						array( 'id' => $factura->getId() )));
					
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
			}
		}
		
		return $this->render('FecdasBundle:Facturacio:factura.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'factura' => $factura)));
	}
	
	
	public function novafacturaAction(Request $request) {
		// Creació d'una nova factura
		
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$this->logEntryAuth('FACTURA NOVA',	'');
		
		$idcomanda = $request->query->get('comanda', 0);
		
		$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($idcomanda);
		
		if ($comanda == null) {
			// No trobada
			$this->logEntryAuth('FACTURA NOVA KO',	'comanda : ' . $idcomanda);
			$this->get('session')->getFlashBag()->add('error-notice', 'Comanda no trobada ');
			return $this->redirect($this->generateUrl('FecdasBundle_comandes'));
		}
		
		$this->logEntryAuth('FACTURA NOVA',	'Per la comanda : ' . $comanda->getId().' '.$comanda->getInfoComanda());
		
		$em = $this->getDoctrine()->getManager();
		
		$maxNumFactura = $this->getMaxNumEntity(date('Y'), BaseController::FACTURES) + 1;
		$factura = new EntityFactura(new \DateTime(), $maxNumFactura);
		$factura->setComanda($comanda);
		$comanda->setFactura($factura);
		
		$em->persist($factura);
		
		$form = $this->createForm(new FormFactura(), $factura);
		
		return $this->render('FecdasBundle:Facturacio:factura.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'factura' => $factura)));
	}
	 
	public function comandesAction(Request $request) {
		// Llistat de comandes
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$this->logEntryAuth('VIEW COMANDES', $this->get('session')->get('username'));
		
		$codi = $request->query->get('cerca', '');
		$nf = $request->query->get('numfactura', '');
		
		$arrayFact = explode("/", $nf);
		$numfactura = $nf;
		$anyfactura = 0;
		if (count($arrayFact) > 1) {
			$numfactura = is_numeric($arrayFact[0])?$arrayFact[0]:0;
			$anyfactura = is_numeric($arrayFact[1])?$arrayFact[1]:0;
		}
		
		$nr = $request->query->get('numrebut', '');
		
		$arrayReb = explode("/", $nr);
		$numrebut = $nr;
		$anyrebut = 0;
		if (count($arrayReb) > 1) {
			$numrebut = is_numeric($arrayReb[0])?$arrayReb[0]:0;
			$anyrebut = is_numeric($arrayReb[1])?$arrayReb[1]:0;
		}
		
		$baixes = $request->query->get('baixes', 0);
		$baixes = ($baixes == 1?true:false);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.dataentrada');
		$direction = $request->query->get('direction', 'desc');
		
		$query = $this->consultaComandes($codi, $numfactura, $anyfactura, $numrebut, $anyrebut, $baixes, $sort, $direction);
			
		$paginator  = $this->get('knp_paginator');
			
		$comandes = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
		
		$formBuilder = $this->createFormBuilder()
			->add('cerca', 'hidden', array(
				'data' => $codi	))
			->add('numfactura', 'text', array(
				'data' 	=> $nf ))
			->add('numrebut', 'text', array(
				'data' => $nr	))
			->add('baixes', 'checkbox', array(
				'required' => false,
				'data' => $baixes
		));
		
			
		return $this->render('FecdasBundle:Facturacio:comandes.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'comandes' => $comandes,  'sortparams' => array('sort' => $sort,'direction' => $direction))
				));
	}
	
	public function novacomandaAction(Request $request) {
		// Creació d'una nova comanda
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$this->logEntryAuth('COMANDA NOVA',	'');

		$em = $this->getDoctrine()->getManager();
		
		$maxNumFactura = $this->getMaxNumEntity(date('Y'), BaseController::FACTURES) + 1;
		$factura = new EntityFactura(new \DateTime(), $maxNumFactura);
		
		$em->persist($factura);
		
		$maxNumComanda = $this->getMaxNumEntity(date('Y'), BaseController::COMANDES) + 1;
		$comanda = new EntityComanda($maxNumComanda, $factura);
		$detall = new EntityComandaDetall($comanda, null, 0, 0, '');
											
		$comanda->addDetall($detall);// Sempre afegir un detall si comanda nova
		
		$em->persist($comanda);
		
		$form = $this->createForm(new FormComanda(), $comanda);
		
		return $this->render('FecdasBundle:Facturacio:comanda.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'comanda' => $comanda)));
	}
	

	public function editarcomandaAction(Request $request) {
		// Edició d'una nova comanda existent
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
		$comanda = null;
		$maxNumComanda = $this->getMaxNumEntity(date('Y'), BaseController::COMANDES) + 1;
		if ($request->getMethod() != 'POST') {
			$id = $request->query->get('id', 0);
		
			$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($id);
			 
			if ($comanda == null) {
				// No trobada
				$this->logEntryAuth('COMANDA EDIT KO',	'Comanda : ' . $request->query->get('id', 0));
				$this->get('session')->getFlashBag()->add('error-notice', 'Comanda no trobada ');
				return $this->redirect($this->generateUrl('FecdasBundle_comandes'));
			}
		
			$this->logEntryAuth('COMANDA EDIT',	'Comanda : ' . $comanda->getId().' '.$comanda->getInfoComanda());
			
		} else {
			/* Alta o modificació de clubs */
			$data = $request->request->get('comanda');
			$id = (isset($data['id'])?$data['id']:0);
		
			if ($id > 0) $comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($id);
		
			$originalDetalls = new ArrayCollection();
			
			if ($comanda == null) {
				
				// Comanda nova. Crear factura
				$maxNumFactura = $this->getMaxNumEntity(date('Y'), BaseController::FACTURES) + 1;
				$factura = new EntityFactura(new \DateTime(), $maxNumFactura);
				
				$em->persist($factura);
				
				$maxNumComanda = $this->getMaxNumEntity(date('Y'), BaseController::COMANDES) + 1;
				$comanda = new EntityComanda($maxNumComanda, $factura);
				$detall = new EntityComandaDetall($comanda, null, 0, 0, '');
					
				$comanda->addDetall($detall);// Sempre afegir un detall si comanda nova
				
				$em->persist($detall);
				$em->persist($comanda);
			} else {
				// Create an ArrayCollection of the current detalls
				foreach ($comanda->getDetalls() as $detall) {
					$originalDetalls->add($detall);
				}
			}
		}
		
		$comandaPagat = $comanda->comandaPagada(); // Per detectar nous pagaments 
		
		$form = $this->createForm(new FormComanda(), $comanda);
		if ($request->getMethod() == 'POST') {
			try {
				$form->handleRequest($request);
				if ($form->isValid()) {
					
					
					if ($comanda->getClub() == null) {
						$form->get('club')->addError(new FormError('Falta el club'));
						throw new \Exception('Cal escollir un club ' );
					}
					
					if ($comanda->getNumDetalls() <= 0) {
						throw new \Exception('La comanda ha de tenir algún producte'  );
					}
					
					if ($comanda->getTotalDetalls() == 0) {
						throw new \Exception('L\'import de la comanda ha de ser diferent de 0'  );
					}
					
					// remove the relationship between the tag and the Task
					foreach ($originalDetalls as $detall) {
						if (false === $comanda->getDetalls()->contains($detall)) {
					
							$detall->setDatabaixa(new \DateTime());
							$detall->setDatamodificacio(new \DateTime());
							$em->persist($detall);
					
						}
					}
					
					// Nous detalls i validació
					
					$formdetalls = $form->get('detalls');
					
					foreach ($comanda->getDetalls() as $detall) {
						if (false === $originalDetalls->contains($detall)) {
							$em->persist($detall);
						}
						$detall->setDatamodificacio(new \DateTime());
						
						if ($detall->getProducte() == null) {
							$camp = $this->cercarCampColleccio($formdetalls, $detall, 'producte');
							if ($camp != null) $camp->addError(new FormError('Escollir producte'));
							throw new \Exception('Cal escollir algun producte de la llista'  );
						}
						
						if ($detall->getUnitats() == 0) {
							$camp = $this->cercarCampColleccio($formdetalls, $detall, 'unitats');
							if ($camp != null) $camp->addError(new FormError('?'));
							throw new \Exception('Cal afegir mínim una unitat del producte'  );
						}
					}
					
					$comanda->setNum($maxNumComanda); // Per si canvia

					$strDatapagament = (isset($data['datapagament']) && $data['datapagament'] != ''?$data['datapagament']:'');
					$tipusPagament = (isset($data['tipuspagament']) && $data['tipuspagament'] != ''?$data['tipuspagament']:'');
					if (!$comandaPagat && ($strDatapagament != '' || $tipusPagament != '')) {
						
						if ($strDatapagament != '' && $tipusPagament == '') {
							$form->get('tipuspagament')->addError(new FormError('Escollir un valor'));
							throw new \Exception('Cal indicar com s\'ha pagat la comanda'  );
						}
						if ($strDatapagament == '' && $tipusPagament != '') {
							$form->get('datapagament')->addError(new FormError('Indicar una data'));
							throw new \Exception('Cal indicar quan s\'ha pagat la comanda'  );
						}
						
						// Nou pagament, crear rebut
						$datapagament = \DateTime::createFromFormat('d/m/Y H:i:s', $strDatapagament." 00:00:00");
						
						$maxNumRebut = $this->getMaxNumEntity(date('Y'), BaseController::REBUTS) + 1;
						
						$rebut = new EntityRebut($datapagament, $tipusPagament, $maxNumRebut, 0, $comanda); // Import agafat de la comanda
						
						$em->persist($rebut);
					} 
					
					if ($comanda->getId() > 0)  $comanda->setDatamodificacio(new \DateTime());
					
		
				} else {
					throw new \Exception('Dades incorrectes, cal revisar les dades de la comanda ' ); //$form->getErrorsAsString()
				}
		
				$em->flush();
		
				$this->get('session')->getFlashBag()->add('info-notice',	'La comanda s\'ha desat correctament');
				 
				$this->logEntryAuth('COMANDA SUBMIT',	'producte : ' . $comanda->getId().' '.$comanda->getInfoComanda());
				// Ok, retorn form sms ok
				return $this->redirect($this->generateUrl('FecdasBundle_editarcomanda',
						array( 'id' => $comanda->getId() )));
				 
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
			}
		}
		
		return $this->render('FecdasBundle:Facturacio:comanda.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'comanda' => $comanda)));
	}
	
	private function cercarCampColleccio($colleccio, $data, $camp) {
		foreach ($colleccio as $fill) {
			if ($fill->getData() === $data)  {
				$camps = $fill->all();
				
				if (isset($camps[$camp])) return $camps[$camp];
				else return null;
			}
		}
		return null;		
	}
	
	public function baixacomandaAction(Request $request) {
		// Crida per donar de baixa una comanda
		$this->get('session')->getFlashBag()->clear();
			
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		/* De moment administradors */
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_home'));
	
		$em = $this->getDoctrine()->getManager();
	
		$id = $request->get('id', 0);
	
		$comanda = $em->getRepository('FecdasBundle:EntityComanda')->find($id);
	
		if ($comanda == null) {
			$this->logEntryAuth('BAIXA COMANDA KO', 'comanda : ' . $id);
			$this->get('session')->getFlashBag()->add('error-notice', 'Comanda no trobada ');
			return $this->redirect($this->generateUrl('FecdasBundle_comandes'));
		}
	
		// Baixa dels detalls 
		foreach ($comanda->getDetalls() as $detall) {
			if (!$detall->esBaixa()) {
				$detall->setDatabaixa(new \DateTime());
				$detall->setDatamodificacio(new \DateTime());
			}
		}
		
		// Baixa de la factura
		if ($comanda->getFactura() != null) {
			$comanda->getFactura()->setDataanulacio(new \DateTime());
		}			
		$comanda->setDatamodificacio(new \DateTime());
		$comanda->setDatabaixa(new \DateTime());
	
		$em->flush();
	
		$this->logEntryAuth('BAIXA COMANDA OK', 'Comanda: '.$comanda->getId());
		$this->get('session')->getFlashBag()->add('info-notice', 'Comanda '.$comanda->getInfoComanda().' donada de baixa ');
		return $this->redirect($this->generateUrl('FecdasBundle_comandes'));
	}
	
	
	public function productesAction(Request $request) {
		// Llista de productes i edició massiva

		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		if (!$this->isCurrentAdmin()) 
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$idproducte = $request->query->get('cerca', 0);

		$this->logEntryAuth('VIEW PRODUCTES', $this->get('session')->get('username'));
		

		$tipus = $request->query->get('tipus', 0);
		$baixes = $request->query->get('baixes', 0);
		$baixes = ($baixes == 1?true:false);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'p.descripcio');
		$direction = $request->query->get('direction', 'asc');
		
		if ($idproducte > 0) {
			$tipus = 0;
			$baixes = true;
			$page = 1;
		}	

		$query = $this->consultaProductes($idproducte, $tipus, $baixes, $sort, $direction);
			
		$paginator  = $this->get('knp_paginator');
			
		$productes = $paginator->paginate(
			$query,
			$page,
			10/*limit per page*/
		);
			
		$formBuilder = $this->createFormBuilder()
			->add('cerca', 'hidden', array(
				'data' => ($idproducte>0?$idproducte:"")
		));
		
		$formBuilder
			->add('tipus', 'choice', array(
				'choices' => BaseController::getTipusDeProducte(),
				'required'  => false, 
				'empty_value' => 'Qualsevol...',
				'data' => $tipus,
		));
		
		$formBuilder
			->add('baixes', 'checkbox', array(
				'required' => false,
				'data' => $baixes
				
		));
			
		return $this->render('FecdasBundle:Facturacio:productes.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(), 
						'productes' => $productes,  'sortparams' => array('sort' => $sort,'direction' => $direction))
						));
	}
	
	public function editarproducteAction(Request $request) {
		// Formulari d'edició d'un producte
		//$this->get('session')->getFlashBag()->clear();
    	
    	if ($this->isAuthenticated() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_login'));
    
    	/* De moment administradors */
    	if ($this->isCurrentAdmin() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_home'));
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$producte = null;
    	$anypreu = date('y');
    	if ($request->getMethod() != 'POST') {
    		$id = $request->query->get('id', 0);
    		$anypreu = $request->query->get('anypreu', date('y'));
    		
    		$producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->find($id);
    		 
    		if ($producte == null) {
    			// No trobat
    			$this->logEntryAuth('PRODUCTE EDIT KO',	'producte : ' . $request->query->get('id', 0));
    			$this->get('session')->getFlashBag()->add('error-notice', 'Producte no trobat ');
    			return $this->redirect($this->generateUrl('FecdasBundle_productes'));
    		}
    		
    		$this->logEntryAuth('PRODUCTE EDIT',	'producte : ' . $producte->getId().' '.$producte->getDescripcio());
    		
    		$preu = $producte->getPreu($anypreu);
    		if ($preu == null) {
    			// Crear nou
    			$preu = new EntityPreu($anypreu, 0, 0, $producte);
    			$em->persist($preu);
    			$producte->addPreus($preu);
    		}
    	} else {
   			/* Alta o modificació de clubs */
    		$data = $request->request->get('producte');
    		$id = (isset($data['id'])?$data['id']:0);
    		
    		if ($id > 0) $producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->find($id);
    		
    		if ($producte == null) {
    			$producte = new EntityProducte();
    			$em->persist($producte);
    		}
    	}	
    	$form = $this->createForm(new FormProducte($anypreu), $producte);
    	
    	if ($request->getMethod() == 'POST') {
    		try {
    			$form->handleRequest($request);
    			 
    			$anypreu 	= $form->get('anypreus')->getData();
    			$importpreu = $form->get('preu')->getData();
    			$iva 		= $form->get('iva')->getData();
    			
    			/*if ($importpreu == null) {
    				$form->get('preu')->addError(new FormError('Indicar un valor'));
    				throw new \Exception('Cal indicar un preu vàlid 0 '.$importpreu  );
    			}*/
    			
    			if (doubleval($importpreu) < 0) {
    				$form->get('preu')->addError(new FormError('Valor incorrecte'));
    				throw new \Exception('Cal indicar un preu vàlid 1'.$importpreu  );
    			}
    			
    			if ($form->isValid()) {
    				if ($producte->getId() > 0)  $producte->setDatamodificacio(new \DateTime());
    				
    				$codiExistent = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->findOneBy(array('codi' => $producte->getCodi()));
    				
    				if ($codiExistent != null && $codiExistent != $producte) {
    					$form->get('codi')->addError(new FormError('Codi existent'));
    					throw new \Exception('El codi indicat ja existeix pel producte: ' .$codiExistent->getDescripcio() );
    				}
    				
    				if ($producte->getCodi() < 1000000 || $producte->getCodi() > 9999999) {
    					$form->get('codi')->addError(new FormError('Codi incorrecte'));
    					throw new \Exception('El codi ha de tenir 7 dígits ' );
    				}
    				
    				if ($producte->getMinim() < 0) {
    					$form->get('minim')->addError(new FormError('Valor incorrecte'));
    					throw new \Exception('El mínim d\'unitats d\'una comanda és incorrecte ' );
    				}
    				
    				
    				if ($producte->getStockable() == true) {
    					if ($producte->getLimitnotifica() == null || $producte->getLimitnotifica() < 0) {
    						$form->get('limitnotifica')->addError(new FormError('Valor incorrecte'));
    						throw new \Exception('Cal indicar el límit de notificació ' );
    					}
    						
    					if ($producte->getStock() == null || $producte->getStock() < 0) {
    						$form->get('stock')->addError(new FormError('Valor incorrecte'));
    						throw new \Exception('Cal indicar l\'stock disponible ' );
    					}
    				}
    				
    				
    				$producte->setAbreviatura(strtoupper($producte->getAbreviatura()));
    				
    				if ($iva == 0) $iva = null;
    				
    				$preu = $producte->getPreu($anypreu);
    				if ($preu == null) {
    					// Crear nou
    					$preu = new EntityPreu($anypreu, $importpreu, $iva, $producte);
    					$em->persist($preu);
    				} else {
    					$preu->setPreu($importpreu);
    					$preu->setIva($iva);
    				}
    				
    				
    			} else {
    				throw new \Exception('Dades incorrectes, cal revisar les dades del producte ' ); //$form->getErrorsAsString()
    			}

    			$em->flush();
    			 
    			$this->get('session')->getFlashBag()->add('info-notice',	'El producte s\'ha desat correctament');
    			
    			$this->logEntryAuth('PRODUCTE SUBMIT',	'producte : ' . $producte->getId().' '.$producte->getDescripcio());
    			// Ok, retorn form sms ok
    			return $this->redirect($this->generateUrl('FecdasBundle_editarproducte', 
    					array( 'id' => $producte->getId(), 'anypreu' => $anypreu )));
    			
    		} catch (\Exception $e) {
    			// Ko, mostra form amb errors
    			$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
    		}
   		} 
    	return $this->render('FecdasBundle:Facturacio:producte.html.twig', 
    			$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'producte' => $producte))); 
	}
	
	
	public function nouproducteAction(Request $request) {
		// Formulari de creació d'un producte
		$this->get('session')->getFlashBag()->clear();
    	
    	if ($this->isAuthenticated() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_login'));
    
    	/* De moment administradors */
    	if ($this->isCurrentAdmin() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_home'));
    	
    	$this->logEntryAuth('PRODUCTE NOU',	'');
    	
    	$producte = new EntityProducte();
    	
    	$form = $this->createForm(new FormProducte(), $producte);
    	
    	return $this->render('FecdasBundle:Facturacio:producte.html.twig',
    			$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'producte' => $producte)));
	}
	
	
	public function baixaproducteAction(Request $request) {
		// Crida per donar de baixa un producte
		$this->get('session')->getFlashBag()->clear();
			
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		/* De moment administradors */
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_home'));
	
		$em = $this->getDoctrine()->getManager();
	
		$id = $request->get('id', 0);
	
		$producte = $em->getRepository('FecdasBundle:EntityProducte')->find($id);
	
		if ($producte == null) {
			$this->logEntryAuth('BAIXA PRODUCTE KO', 'producte : ' . $id);
			$this->get('session')->getFlashBag()->add('error-notice', 'Producte no trobat ');
			return $this->redirect($this->generateUrl('FecdasBundle_productes'));
		}
	
		$producte->setDatamodificacio(new \DateTime());
		$producte->setDatabaixa(new \DateTime());
	
		$em->flush();
	
		$this->logEntryAuth('BAIXA PRODUCTE OK', 'producte: '.$producte->getId());
		$this->get('session')->getFlashBag()->add('info-notice', 'Producte '.$producte->getDescripcio().' donat de baixa ');
		return $this->redirect($this->generateUrl('FecdasBundle_productes'));
	}
	
	public function baixapreuAction(Request $request) {
		// Crida per donar de baixa un producte
		$response = new Response();
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		/* De moment administradors */
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_home'));

		$em = $this->getDoctrine()->getManager();
		
		$id = $request->get('id', 0);
		
		$preu = $em->getRepository('FecdasBundle:EntityPreu')->find($id);
		
		if ($preu == null) {
			$this->logEntryAuth('BAIXA PREU KO', 'preu : ' . $id);
			
			$response->headers->set('Content-Type', 'application/json');
			$response->setContent(json_encode( array('result' => 'KO', 'sms' => 'Preu no trobat ') ));
			return $response;
			
			/*$this->get('session')->getFlashBag()->add('error-notice', 'Preu no trobat ');
			return $this->redirect($this->generateUrl('FecdasBundle_productes'));*/
		}
		
		$producte = $preu->getProducte();
		$producte->removePreus($preu);
		$em->remove($preu);
		
		$em->flush();

		$this->logEntryAuth('BAIXA PREU OK', 'preu : ' . $id . ' producte: '.$producte->getId());
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode( array('result' => 'OK', 'sms' => 'Preu esborrat correctament') ));
		return $response;
		
		
		/*$this->get('session')->getFlashBag()->add('info-notice', 'Preu esborrat correctament ');
		return $this->redirect($this->generateUrl('FecdasBundle_producte', array('id' => $producte->getId())));*/
	}
	
	
	
	public function jsonpreuAction(Request $request) {
		//foment.dev/jsonpreu?id=32&anypreu=2015
		$response = new Response();
	
		$id = $request->get('id', 0);
		$anypreu = $request->get('anypreu', 0);
		
		$em = $this->getDoctrine()->getManager();
		
		$producte = $em->getRepository('FecdasBundle:EntityProducte')->find($id);
		
		$preu = $producte->getPreu($anypreu);
		
		$preuArray = array('anypreu' => $anypreu, 'preu' => 0, 'iva' => 0 );
		if ($preu != null) {
			$preuArray['anypreu'] = $anypreu;
			$preuArray['preu'] = $preu->getPreu();
			$preuArray['iva'] = ($preu->getIva() == null?0:$preu->getIva());
		}
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($preuArray));
		return $response;
	}
	
	public function jsonproductesAction(Request $request) {
		//foment.dev/jsonproductes?cerca=textcerca
		$response = new Response();
	
		$cerca = $request->get('cerca', '');
		$id = $request->get('id', 0);
		
		$em = $this->getDoctrine()->getManager();
		
		if ($id > 0) {
			$producte = $em->getRepository('FecdasBundle:EntityProducte')->find($id);
			
			if ($producte != null) {
				$response->headers->set('Content-Type', 'application/json');
				
				$response->setContent(json_encode(array("id" => $producte->getId(), "text" => $producte->getDescripcio()) ) );
				return $response;
			}
		}
		
		if ($id == 0 && $cerca == "") {
			$response->headers->set('Content-Type', 'application/json');
			$response->setContent(json_encode(array()));
			return $response;
		}
		
		$strQuery = " SELECT p FROM FecdasBundle\Entity\EntityProducte p ";
		$strQuery .= " WHERE p.databaixa IS NULL ";
		$strQuery .= " AND p.descripcio LIKE :cerca";
		$strQuery .= " ORDER BY p.descripcio";  
	
		$query = $em->createQuery($strQuery);
		$query->setParameter('cerca', '%'.$cerca.'%');
	
		
		$search = array();
		if ($query != null) {
			$result = $query->getResult();
			foreach ($result as $p) {
				$search[] = array("id" => $p->getId(), "text" => $p->getDescripcio());
			}
		}
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($search));
		
		return $response;
	}
	
	protected function consultaProductes($idproducte, $tipus, $baixes, $strOrderBY = 'p.description' , $direction = 'asc' ) {
		$em = $this->getDoctrine()->getManager();
	
		if ($idproducte > 0) {
			$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityProducte p ";
			$strQuery .= "WHERE p.id = :idproducte ";
			$query = $em->createQuery($strQuery);
			$query->setParameter('idproducte', $idproducte);
			return $query;
		}
		
		// Consultar no només les vigents sinó totes
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityProducte p ";
		$strQuery .= "WHERE 1 = 1 ";
		if (! $baixes) $strQuery .= " AND p.databaixa IS NULL ";
		if ($tipus > 0) $strQuery .= " AND p.tipus = :tipus";
		$strQuery .= " ORDER BY " .implode(" ".$direction.", ",explode(",",$strOrderBY)). " ".$direction;
		
		$query = $em->createQuery($strQuery);
		
		if ($tipus > 0) $query->setParameter('tipus', $tipus);
		return $query;
	}
	
	protected function consultaIngresos($codi, $nr, $ar, $baixes, $strOrderBY = 'r.datapagament', $direction = 'desc' ) {
		$em = $this->getDoctrine()->getManager();
	
		$strQuery = "SELECT r, c FROM FecdasBundle\Entity\EntityRebut r LEFT JOIN r.comanda c ";
		$strQuery .= "WHERE c IS NULL "; 								// Ingrés no associat a cap comanda
		if ($codi != '') $strQuery .= " AND r.club = :codi ";
	
		if (is_numeric($nr) && $nr > 0) $strQuery .= " AND r.num = :numrebut ";
	
		if (is_numeric($ar) && $ar > 0) {
			$datainicirebut = \DateTime::createFromFormat('Y-m-d H:i:s', $ar."-01-01 00:00:00");
			$datafinalrebut = \DateTime::createFromFormat('Y-m-d H:i:s', $ar."-12-31 23:59:59");
			$strQuery .= " AND r.datapagament >= :rini AND r.datapagament <= :rfi ";
		}
	
		if (! $baixes) $strQuery .= " AND r.dataanulacio IS NULL ";
	
		$strQuery .= " ORDER BY " .implode(" ".$direction.", ",explode(",",$strOrderBY)). " ".$direction;
		$query = $em->createQuery($strQuery);
	
		if ($codi != '') $query->setParameter('codi', $codi);
		if (is_numeric($nr) && $nr > 0) $query->setParameter('numrebut', $nr);
	
		if (is_numeric($ar) && $ar > 0) {
			$query->setParameter('rini', $datainicirebut);
			$query->setParameter('rfi', $datafinalrebut);
		}
		
		return $query;
	}
	
	
	protected function consultaComandes($codi, $nf, $af, $nr, $ar, $baixes, $strOrderBY = 'c.id', $direction = 'desc' ) {
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = "SELECT c, f, r FROM FecdasBundle\Entity\EntityComanda c LEFT JOIN c.factura f LEFT JOIN c.rebut r ";
		$strQuery .= "WHERE 1 = 1 ";
		if ($codi != '') $strQuery .= " AND c.club = :codi ";
		
		if (is_numeric($nf) && $nf > 0) $strQuery .= " AND f.num = :numfactura ";

		if (is_numeric($af) && $af > 0) {
			$datainicifactura = \DateTime::createFromFormat('Y-m-d H:i:s', $af."-01-01 00:00:00");
			$datafinalfactura = \DateTime::createFromFormat('Y-m-d H:i:s', $af."-12-31 23:59:59");
			$strQuery .= " AND f.datafactura >= :fini AND f.datafactura <= :ffi ";
		}
		
		if (is_numeric($nr) && $nr > 0) $strQuery .= " AND r.num = :numrebut ";

		if (is_numeric($ar) && $ar > 0) {
			$datainicirebut = \DateTime::createFromFormat('Y-m-d H:i:s', $ar."-01-01 00:00:00");
			$datafinalrebut = \DateTime::createFromFormat('Y-m-d H:i:s', $ar."-12-31 23:59:59");
			$strQuery .= " AND r.datapagament >= :rini AND r.datapagament <= :rfi ";
		}
		
		
		if (! $baixes) $strQuery .= " AND c.databaixa IS NULL ";
		
		$pos = strpos($strOrderBY, "r.num");
		if ($pos !== false) $strQuery .= " AND c.rebut IS NOT NULL ";
		
		$pos = strpos($strOrderBY, "f.num");
		if ($pos !== false) $strQuery .= " AND c.factura IS NOT NULL ";
		
		$strQuery .= " ORDER BY " .implode(" ".$direction.", ",explode(",",$strOrderBY)). " ".$direction;
		$query = $em->createQuery($strQuery);
	
		if ($codi != '') $query->setParameter('codi', $codi);
		if (is_numeric($nf) && $nf > 0) $query->setParameter('numfactura', $nf);
		if (is_numeric($nr) && $nr > 0) $query->setParameter('numrebut', $nr);
		if (is_numeric($af) && $af > 0) {
			$query->setParameter('fini', $datainicifactura);
			$query->setParameter('ffi', $datafinalfactura);
		}
		
		if (is_numeric($ar) && $ar > 0) {
			$query->setParameter('rini', $datainicirebut);
			$query->setParameter('rfi', $datafinalrebut);
		}
			
		return $query;
	}
	
	public function getMaxNumEntity($year, $tipus) {
		$em = $this->getDoctrine()->getManager();
	
		$inici = $year."-01-01";
		$final = $year."-12-31";
	
		$strQuery = '';
		switch ($tipus) {
			case BaseController::REBUTS:
				$strQuery = "SELECT MAX(r.num) FROM FecdasBundle\Entity\EntityRebut r ";
				$strQuery .= " WHERE r.datapagament >= '".$inici."' AND r.datapagament <= '".$final."'";
				break;
			case BaseController::FACTURES:
				$strQuery = " SELECT MAX(f.num) FROM FecdasBundle\Entity\EntityFactura f ";
				$strQuery .= " WHERE f.datafactura >= '".$inici."' AND f.datafactura <= '".$final."'";
				break;
			case BaseController::COMANDES:
				$strQuery = " SELECT MAX(c.num) FROM FecdasBundle\Entity\EntityComanda c ";
				$strQuery .= " WHERE c.dataentrada >= '".$inici."' AND c.dataentrada <= '".$final."'";
				break;
			default:
				return -1;
		}
		
		$query = $em->createQuery($strQuery);
		$result = $query->getSingleScalarResult();
	
		if ($result == null) return 0; // Primer de l'any
			
		return $result;
	}
	
	/********************************************************************************************************************/
	/************************************ INICI SCRIPTS CARREGA *********************************************************/
	/********************************************************************************************************************/
	
	public function resetcomandesAction(Request $request) {
		// http://www.fecdasnou.dev/resetcomandes?id=106313&detall=149012&factura=8380
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$id = $request->query->get('id', 106313);	// id històric
		$detall = $request->query->get('detall', 149012);  // detall històric
		$factura = $request->query->get('factura', 8380);  // factures històric
		
		/*$comandes = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->findAll();
	
		foreach ($comandes as $c) {
			foreach ($c->getDetalls() as $d) $em->remove($d);
			$em->remove($c);
		}
		$em->flush();
		*/
		
	
		$sql = "DELETE FROM m_comandadetalls WHERE id > ".$detall;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "UPDATE m_comandes SET factura = NULL, rebut = NULL WHERE id > ".$id;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_factures WHERE id > ".$factura;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_comandes WHERE id > ".$id;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_rebuts WHERE id > 0";
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
		return new Response("");
	}
	
	public function migrahistoricAction(Request $request) {
		// http://www.fecdasnou.dev/migrahistoric?desde=20XX&fins=20XX
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$yeardesde = $request->query->get('desde', 985);
		$yearfins = $request->query->get('fins', 2015);
		$year = $yeardesde;
		
		$batchSize = 20;
		
		$strQuery = "SELECT p.id, p.importparte, p.dataentradadel, p.databaixadel,";
		$strQuery .= " p.clubdel, t.descripcio as tdesc, c.categoria as ccat,";
		$strQuery .= " c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament,";
		$strQuery .= " p.dadespagament, p.importpagament,	p.comentari, p.datafacturacio, p.numfactura, ";
		$strQuery .= " COUNT(l.id) as total FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte ";
		$strQuery .= " INNER JOIN m_tipusparte t ON p.tipus = t.id ";
		$strQuery .= " INNER JOIN m_categories c ON c.tipusparte = t.id ";
		$strQuery .= " WHERE p.dataalta < '".($yearfins+1)."-01-01 00:00:00' ";
		$strQuery .= " AND p.dataalta >= '".$yeardesde."-01-01 00:00:00' ";
		$strQuery .= " GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ";
		$strQuery .= " ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, ";
		$strQuery .= " p.comentari, p.datafacturacio, p.numfactura";
		//$strQuery .= " ORDER BY p.id, csim ";
		$strQuery .= " ORDER BY p.dataentradadel, p.id ";
		
		
		
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		//$partesAbans2015 = $stmt->fetchAll();
		
		
		$strQuery = "SELECT * FROM m_clubs c ORDER BY c.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$clubs = array();
		while (isset($aux[$i])) {
			$clubs[ $aux[$i]['compte'] ] = $aux[$i];
			$i++;
		}
		
		
		//echo "Total partes: " . count($partesAbans2015) . PHP_EOL;
			
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
		
		// Tractar duplicats primer
		$maxnums = array(
				'maxnumcomanda' => $this->getMaxNumEntity($year, BaseController::COMANDES) + 1 
		);
		
		$ipar = 0;
		
		$parteid = 0;
		$partes = array();
		
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		
		try {
			/***********************************************************************************/
			/****************************   PARTES i DUPLICATS  ********************************/
			/***********************************************************************************/
				
			while ($parte = $stmt->fetch()) {
				
				 if (substr($parte['dataentradadel'], 0, 4) > $year) {
					 $year = substr($parte['dataentradadel'], 0, 4);
					 $maxnums['maxnumcomanda'] = $this->getMaxNumEntity($year, BaseController::COMANDES) + 1;
					 
					 echo '***************************************************************************'.'<br/>';
					 echo '===============================> ANY '.$year.'  <=========================='.'<br/>';
					 echo '***************************************************************************'.'<br/>';
				 }
			
				 if ($parteid == 0) $parteid = $parte['id'];
						
				 if ($parteid != $parte['id']) {
				 	// Agrupar partes, poden venir vàries línies seguides segons categoria 'A', 'T' ...
				 	$this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
				 	
				 	$parteid = $parte['id'];
				 	$partes = array();
				 }
				 
				 $partes[] = $parte;
				 $ipar++;
				 error_log('fi PARTE '.$parte['id']);
				 $em->clear();
			}
			// El darrer parte del dia
			if ($parteid > 0) $this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
				
			$em->getConnection()->commit();
			
		} catch (Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
		
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
			
		return new Response("");
	}
	
	
	public function migracomandesAction(Request $request) {
		// http://www.fecdasnou.dev/migracomandes
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$batchSize = 20;
	
		$strQuery = "SELECT p.id, p.importparte, p.dataentradadel, p.databaixadel,";
		$strQuery .= " p.clubdel, t.descripcio as tdesc, c.categoria as ccat,";
		$strQuery .= " c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament,";
		$strQuery .= " p.dadespagament, p.importpagament,	p.comentari, p.datafacturacio, p.numfactura, ";
		$strQuery .= " e.preu, e.iva ";
		$strQuery .= " COUNT(l.id) as total FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte ";
		$strQuery .= " INNER JOIN m_tipusparte t ON p.tipus = t.id ";
		$strQuery .= " INNER JOIN m_categories c ON c.tipusparte = t.id ";
		
		$strQuery .= " INNER JOIN m_productes o ON c.producte = o.id ";
		$strQuery .= " INNER JOIN m_preus e ON e.producte = o.id ";
		$strQuery .= " WHERE e.anypreu = 2015 ";
		
		$strQuery .= " AND p.dataalta >= '2015-01-01 00:00:00' ";
		$strQuery .= " GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ";
		$strQuery .= " ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, ";
		$strQuery .= " p.comentari, p.datafacturacio, p.numfactura, e.preu, e.iva";
		$strQuery .= " ORDER BY p.id, csim ";
	
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$partes2015 = $stmt->fetchAll();
	
		$strQuery = "SELECT d.*,  p.id as pid, p.descripcio as pdescripcio, e.preu as epreu, e.iva as eiva ";
		$strQuery .= " FROM m_duplicats d  INNER JOIN m_carnets c ON d.carnet = c.id ";
		$strQuery .= " INNER JOIN m_productes p ON c.producte = p.id ";
		$strQuery .= " INNER JOIN m_preus e ON e.producte = p.id ";
		$strQuery .= " WHERE e.anypreu = 2015 ";
		$strQuery .= " ORDER BY d.datapeticio ";
	
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$duplicats2015 = $stmt->fetchAll();
		
		
		$strQuery = "SELECT * FROM m_clubs c ORDER BY c.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$clubs = array();
		while (isset($aux[$i])) {
			$clubs[ $aux[$i]['compte'] ] = $aux[$i];
			$i++;
		}
		
		echo "Total partes: " . count($partes2015) . PHP_EOL;
		echo "Total dupli: " . count($duplicats2015) . PHP_EOL;
	
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		// Tractar duplicats primer
		$current = date('Y');
		$maxnums = array(
				'maxnumcomanda' => $this->getMaxNumEntity($current, BaseController::COMANDES) + 1
		);
	
		$idup = 0;
		$ipar = 0;
	
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		
		$dataCurrent = \DateTime::createFromFormat('Y-m-d', "2015-01-01");
		
		try {
			 /***********************************************************************************/
			 /****************************   PARTES i DUPLICATS  ********************************/
			 /***********************************************************************************/
			
			error_log('Primer DUPLI '.$duplicats2015[0]['datapeticio']);
			error_log('Primer PARTE '.$partes2015[0]['dataentradadel']);
			echo 'Primer DUPLI '.$duplicats2015[0]['datapeticio'].'<br/>';
			echo 'Primer PARTE '.$partes2015[0]['dataentradadel'].'<br/>';
			
			$year = 2014;
			
			while ($dataCurrent->format('Y-m-d') < '2016-01-01') {
				error_log('***************************************************************************');
				error_log('============> DIA '.$dataCurrent->format('Y-m-d').'  <=====================');
				error_log('***************************************************************************');
				echo '***************************************************************************'.'<br/>';
				echo '============> DIA '.$dataCurrent->format('Y-m-d').'  <====================='.'<br/>';
				echo '***************************************************************************'.'<br/>';
				
				if ($dataCurrent->format('Y') > $year) {
					$year = $dataCurrent->format('Y');
					$maxnums['maxnumcomanda'] = 1;
				}
				
				
				$parteid = 0;
				$partes = array();
				
				//echo '!!!!!!!!!!!!!!!!! '.substr($duplicats2015[$idup]['datapeticio'],0,10).'-'.$dataCurrent->format('Y-m-d').'!!!!!!!!!<br/>';
				while (isset($duplicats2015[$idup]) && substr($duplicats2015[$idup]['datapeticio'],0,10) <= $dataCurrent->format('Y-m-d')) {
					$duplicat = $duplicats2015[$idup];
					$this->insertComandaDuplicat($clubs, $duplicat, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
					$idup++;
				}
				
				//echo '!!!!!!!!!!!!!!!!! '.substr($partes2015[$ipar]['dataentradadel'],0,10).'-'.$dataCurrent->format('Y-m-d').'!!!!!!!!!<br/>';
				while (isset($partes2015[$ipar]) && substr($partes2015[$ipar]['dataentradadel'],0,10) <= $dataCurrent->format('Y-m-d')) {
					$parte = $partes2015[$ipar];
					if ($parteid == 0) $parteid = $parte['id'];
					
					if ($parteid != $parte['id']) {
						// Agrupar partes, poden venir vàries línies seguides segons categoria 'A', 'T' ...
						$this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
							
						$parteid = $parte['id'];
						$partes = array();
					}
					$partes[] = $parte;
					$ipar++;
				}
				// El darrer parte del dia
				if ($parteid > 0) $this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
				
				// Següent dia
				$dataCurrent->add(new \DateInterval('P1D'));
			}
			
			$em->getConnection()->commit();
		} catch (Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("");
	}
	
	public function migraaltresAction(Request $request) {
		// http://www.fecdasnou.dev/migraaltres
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$batchSize = 20;
	
		$strQuery = "SELECT p.*, e.preu, e.iva FROM m_productes p LEFT JOIN m_preus e ON e.producte = p.id WHERE e.anypreu = 2015 ORDER BY p.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$productes = array();
		while (isset($aux[$i])) {
			if ($aux[$i]['preu'] == null) $aux[$i]['preu'] = 0;
			if ($aux[$i]['iva'] == null) $aux[$i]['iva'] = 0;
				
			$productes[ $aux[$i]['codi'] ] = $aux[$i];
			$i++;
		}
	
		$strQuery = "SELECT * FROM m_clubs c ORDER BY c.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$clubs = array();
		while (isset($aux[$i])) {
			$clubs[ $aux[$i]['compte'] ] = $aux[$i];
			$i++;
		}
	
	
		// Clubs: 4310000 - 4310999
		// Productes: 6230300  - 7590006  (Llicències 7010000 - 7010025) (Duplicats 7090000, 7590000, 7590002, 7590004)
	
		// SELECT num, COUNT(dh) FROM `apunts_2015` WHERE dh = 'D' GROUP BY num HAVING COUNT(dh) > 1	=> 3,5,11,13,16,18
	
		// SELECT num FROM apunts_2015 WHERE (compte >= 4310000 AND compte <= 4310999) OR
		//			(compte >= 6230300 AND compte < 7010000 AND compte > 7010025 AND compte <= 7590006 AND compte NOT IN (7090000, 7590000, 7590002, 7590004) )
	
	
		$strQuery = "SELECT * FROM apunts_2015 a ";
		//$strQuery .= " ORDER BY a.data, a.num, a.dh ";
		$strQuery .= " ORDER BY a.id ";
	
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$altress2015 = $stmt->fetchAll();
	
		echo "Total apunts: " . count($altress2015) . PHP_EOL;
	
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		// Tractar duplicats primer
		$current = date('Y');
		$maxnums = array(
				'maxnumcomanda' => $this->getMaxNumEntity($current, BaseController::COMANDES) + 1
		);
	
		$iapu = 0;
		$i = 0;
	
		$em->getConnection()->beginTransaction(); // suspend auto-commit
	
		try {
			/***********************************************************************************/
			/****************************   PARTES i DUPLICATS  ********************************/
			/***********************************************************************************/
				
			error_log('Primer ALTRE '.$altress2015[0]['data']);
			echo 'Primer ALTRE '.$altress2015[0]['data'].'<br/>';
				
			$altrenum = 0;
			$altres = array();
	
			while (isset($altress2015[$iapu])) {
	
				$altre = $altress2015[$iapu];
					
				if ($altrenum == 0) $altrenum = $altre['num'];
					
				if ($altrenum != $altre['num']) {
					// Agrupar apunts
					$this->insertComandaAltre($clubs, $productes, $altres, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
						
					$altrenum = $altre['num'];
					$altres = array();
				}
					
				if ($altre['dh'] == 'D') $altres['D'][] = $altre;
				else $altres['H'][] = $altre;
				$iapu++;

			}
			// La darrera comanda altre del dia
			if ($altrenum > 0) $this->insertComandaAltre($clubs, $productes, $altres, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
			$em->getConnection()->commit();
		} catch (Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("");
	}
	
	private function insertComandaAltre($clubs, $productes, $altres, &$maxnums, $flush = false) {
		
		// Poden haber vàris 'H' => un club parte llicències Tècnic + Aficionat per exemple
		// SELECT * FROM apunts_2015 a inner join m_productes p On a.compte = p.codi WHERE 1 ORDER BY data, num, dh
		//
		// SELECT * FROM apunts_2015 WHERE num IN ( 119, 510 ) ORDER BY num, dh
		$em = $this->getDoctrine()->getManager();
		
		$rebutId = 0;
		
		if (count($altres['D']) == 1) {
			// Un debe. Situació normal un club deutor
			$compteD = $altres['D'][0]['compte']; 
			$id = $altres['D'][0]['id'];
			$num = $altres['D'][0]['num'];
			$data = $altres['D'][0]['data'];
			
			if (count($altres['H']) == 0) error_log("ERROR 1 CAP 'H' = >".$id." ".$num);
			
			if ($compteD >= 5720000 && $compteD <= 5720005) {
				// Rebut ingrés, següent compte club
					
				// Camp concepte està la factura:  	FW:00063/2015 o Factura: 00076/2015
				// Camp document està el rebut: 	00010/15
					
				if (count($altres['H']) != 1) error_log("ERROR 2 => ".$num);
				
				$compteH = $altres['H'][0]['compte'];
				
				if ($altres['D'][0]['concepte'] != $altres['H'][0]['concepte'] ||
					$altres['D'][0]['document'] != $altres['H'][0]['document'] ||
					$altres['D'][0]['importapunt'] != $altres['H'][0]['importapunt']) {
						error_log("ERROR 3 CAP 'H' = >".$id." ".$num);
						echo "ERROR 3 CAP 'H' = >".$id." ".$num."<br/>";
						return;
						
				}
				
				$facturaAux = $altres['D'][0]['concepte'];	// FW:00063/2015 o Factura: 00076/2015 o Fra. 2541/2015
				$pos = strpos($facturaAux, 'Factura');
				if ($pos === false) {
					$pos = strpos($facturaAux, 'Fra');
					if ($pos === false) $numFactura = substr($facturaAux, 3, 5);
					else $numFactura = substr($facturaAux, 5, 4);
				}
				else $numFactura = substr($facturaAux, 9, 5);
				
				if (!is_numeric($numFactura)) {
					error_log("ERROR 5 Número de factura no numèrica=> ".$id." ".$num." ".$compteD." => ".$facturaAux);
					echo "ERROR 5 Número de factura no numèrica=> ".$id." ".$num." ".$compteD." => ".$facturaAux."<br/>";
					return;
				}
				
				$rebutAux = $altres['D'][0]['document'];		// 00010/15
				$numRebut = substr($rebutAux, 0, 5);
				
				$numRebut = $numRebut * 1;  // Number
				
				$import = $altres['D'][0]['importapunt'];
				
				if (!isset( $clubs[$compteH])) {
					error_log("ERROR 7 Club pagador no existeix=> ".$num." ".$compteH);
					echo "ERROR 7 Club pagador no existeix=> ".$num." ".$compteH."<br/>";
					return;
				}
			
				$statement = $em->getConnection()->executeQuery("SELECT * FROM m_factures WHERE num = ".($numFactura*1));
				$factExistent = $statement->fetch();
				
				if ($factExistent == null) {
					error_log("ERROR 9 Factura no trobada => ".$id." ".$num." ".$compteD." ".($numFactura*1));
					echo "ERROR 9 Factura no trobada => ".$id." ".$num." ".$compteD." ".($numFactura*1)."<br/>";
					echo "ERROR 9 Factura ".($numFactura*1)." no trobada => id: ".$id.", anotació: ".$num.", de compte: ".$compteD." a compte: ".$compteH." (".$clubs[$compteH]['nom'] .")<br/>";
					return;
				}
				
				
				// Count no funciona bé
				$em->getConnection()->executeUpdate("UPDATE m_factures SET datapagament = '".$data."' WHERE id = ".$factExistent['id']);
				
				$statement = $em->getConnection()->executeQuery("SELECT * FROM m_comandes WHERE factura = ".$factExistent['id']);
				$comanda = $statement->fetch();
				
				if ($comanda == null) {
					error_log("ERROR 11 Comanda no trobada => ".$id." ".$num." ".$compteD);
					echo "ERROR 11 Comanda no trobada => ".$id." ".$num." ".$compteD."<br/>";
					return;
				}
				
				$statement = $em->getConnection()->executeQuery("SELECT * FROM m_partes WHERE numfactura = '".$numFactura."/2015'");
				$parte = $statement->fetch();
				
				$datapagament = $data;
				$dataentrada = $data; 
				$dadespagament = null;
				
				$tipuspagament = null;
				switch ($compteD) {
					case 5700000:  	// CAIXA FEDERACIÓ, PTES.
						$tipuspagament = BaseController::TIPUS_PAGAMENT_CASH;
						break;
					case 5720001: 	// "LA CAIXA"  LAIETANIA
						$tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA;
						break;
					case 5720003:	// LA CAIXA-SARDENYA
						$tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_SARDENYA;
						break;
					case 5720002:	// LA CAIXA-OF. MALLORCA
					case 5720004:	// CAIXA CATALUNYA
					case 5720005:	// POLISSA DE CREDIT
						error_log("ERROR 7 Tipus de pagament desconegut => ".$id." ".$num." ".$compteD);
						echo "ERROR 7 Tipus de pagament desconegut => ".$id." ".$num." ".$compteD."<br/>";
						return;
							
						break;
				}
				
				
				if ($parte != null && $tipuspagament == BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA) {
					
					if ($parte['estatpagament'] == 'TPV OK' || $parte['estatpagament'] == 'TPV CORRECCIO')  $tipuspagament = BaseController::TIPUS_PAGAMENT_TPV;
					
					/*
					if ($parte['estatpagament'] == 'TPV OK' || $parte['estatpagament'] == 'TPV CORRECCIO')  $tipuspagament = BaseController::TIPUS_PAGAMENT_TPV;
					if ($parte['estatpagament'] == 'METALLIC GES' || $parte['estatpagament'] == 'METALLIC WEB')  $tipuspagament = BaseController::TIPUS_PAGAMENT_CASH;
					if ($parte['estatpagament'] == 'TRANS WEB') $tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA;
					if ($parte['estatpagament'] == 'TRANS GES') $tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_SARDENYA;
					*/
					$dataentrada = $parte['dataentradadel'];
					if ($parte['datapagament'] != null && $parte['datapagament'] != '') {
						$datapagament = $parte['datapagament'];
						$dadespagament = $parte['dadespagament'];
					}
					
				}
				
				
				// Insertar/Actualitzar rebut
				
				$comentari = "Rebut comanda ".$comanda['num']." ".str_replace("'","''",$comanda['comentaris']);
				
				$query = "INSERT INTO m_rebuts (datapagament, num, import, dadespagament, tipuspagament, comentari, dataentrada, club) VALUES ";
				$query .= "('".$datapagament."',".$numRebut.",".$import;
				$query .= ", ".($dadespagament==null?"NULL":"'".$dadespagament."'").",".$tipuspagament.",'".$comentari."','".$dataentrada."'";
				$query .= ",'".$clubs[$compteH]['codi']."')";
				
				//$maxnums['maxnumrebut']++;
					
				$em->getConnection()->exec( $query );
				
				$rebutId = $em->getConnection()->lastInsertId();
				
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
				
			}
			
			if ($compteD >= 4310000 && $compteD <= 4310999) {
				// Deutor club, Factura 
				if (!isset( $clubs[$compteD])) {
					error_log("ERROR 15 Club deutor no existeix=> ".$id." ".$num." ".$compteD);
					echo "ERROR 15 Club deutor no existeix=> ".$id." ".$num." ".$compteD."<br/>";
					return;
				}
				
				$club = $clubs[$compteD];
				
				$facturaAux = $altres['D'][0]['concepte'];	// W-F0063_ 10636110MAS 1AFC_  o F0019_1AFC_
				$pos = strpos($facturaAux, 'W-F');
				if ($pos === false) $numFactura = substr($facturaAux, 1, 4);
				else $numFactura = substr($facturaAux, 3, 4);
				
				if (!is_numeric($numFactura)) {
					error_log("ERROR 16 Número de factura no numèrica=> ".$id." ".$num." ".$compteD);
					echo "ERROR 16 Número de factura no numèrica=> ".$id." ".$num." ".$compteD."<br/>";
					return;
				}
				
				$import = $altres['D'][0]['importapunt'];

				// Revisió	
				$importDetalls = 0;
				$tipusAnterior = 0;
				for ($i = 0; $i < count($altres['H']); $i++) {
				
					$compteH = $altres['H'][$i]['compte'];
					
					if (!isset( $productes[$compteH])) {
						error_log("ERROR 17 producte no existeix=> ".$id." ".$num." ".$compteH);
						echo "ERROR 17 producte no existeix=> ".$id." ".$num." ".$compteH."<br/>";
						return;
					}
						
					$producte = $productes[$compteH];
					if ($tipusAnterior == 0) $tipusAnterior = $producte['tipus'];

					if ($producte['tipus'] != $tipusAnterior) error_log("ERROR 18 tipus productes barrejats=> ".$id." ".$num." ".$compteH);
					
					/*if ($producte['preu'] != $altres['H'][0]['importapunt']) {
						error_log("ERROR 19 preu producte incorrecte => ".$id." ".$num." ".$compteH);
						echo "ERROR 19 preu producte incorrecte => ".$id." ".$num." ".$compteH."<br/>";
						return;
					}*/
					
					$importDetalls += $altres['H'][$i]['importapunt'];
				}
				
				if ($import != $importDetalls)  {
					
					error_log("ERROR 20 suma detalls incorrecte=> ".$id." ".$num." ".$compteH." ".$import." <> ".$importDetalls." => ".count($altres['H']));
					echo "ERROR 20 suma detalls incorrecte=> ".$id." ".$num." ".$compteH." ".$import." <> ".$importDetalls." => ".count($altres['H'])."<br/>";
					//return;
				}
			
				if ($tipusAnterior <= 0 || $tipusAnterior >= 6)  error_log("ERROR 22 tipus producte desconegut => ".$id." ".$num." ".$compteH);
				
				
				if ($tipusAnterior != BaseController::TIPUS_PRODUCTE_LLICENCIES &&
					$tipusAnterior != BaseController::TIPUS_PRODUCTE_DUPLICATS) {
					// Insertar factura
					$textComanda = 'Comanda '.BaseController::getTipusProducte($tipusAnterior);
					
					$query = "INSERT INTO m_factures (datafactura, num, import, concepte, dataentrada, datapagament) VALUES ";
					$query .= "('".$data."',".$numFactura.",".$import.",'"."Factura - ".$textComanda."'";
					$query .= ",'".$data."',NULL)";
					
					$em->getConnection()->exec( $query );
					
					$facturaId = $em->getConnection()->lastInsertId();
					$em->getConnection()->commit();
					$em->getConnection()->beginTransaction(); // suspend auto-commit

					//error_log("1=====================================> ".$maxnums['maxnumcomanda']." ".$id." ".$num." ".$compteH."<br/>");
					//echo "1=====================================> ".$maxnums['maxnumcomanda']." ".$id." ".$num." ".$compteH."<br/>";
					
					// Insertar comanda
					$query = "INSERT INTO m_comandes (comptabilitat, comentaris, dataentrada, databaixa, club, num, rebut,factura, tipus) VALUES ";
					$query .= "(1, '".$textComanda."', '".$data."'";
					$query .= ",NULL,'".$club['codi']."',".$maxnums['maxnumcomanda'];
					$query .= ", ".($rebutId==0?"NULL":$rebutId).", ".($facturaId==0?"NULL":$facturaId).",'A')";
					
					$maxnums['maxnumcomanda']++;
					
					$em->getConnection()->exec( $query );
					
					$comandaId = $em->getConnection()->lastInsertId();
					
					for ($i = 0; $i < count($altres['H']); $i++) {
						// Insertar detall
						$compteH = $altres['H'][$i]['compte'];
						$producte = $productes[$compteH];
						$import = $altres['H'][$i]['importapunt'];
						$total = $producte['preu']==0 ? 1 : round($altres['H'][$i]['importapunt']/$producte['preu']);
						$anota = $total.'x'.str_replace("'","''",$producte['descripcio']);
					
						$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, preuunitat, ivaunitat, descomptedetall, anotacions, dataentrada, databaixa) VALUES ";
						$query .= "(".$comandaId.",".$producte['id'].",".$total.",".round($import/$total,2).",".($producte['iva']!=null?$producte['iva']:"NULL").", 0, '".$anota."',";
						$query .= "'".$data."',NULL)"; 
					
						$em->getConnection()->exec( $query );
					}
					
					
					//error_log("2=====================================> ".$maxnums['maxnumcomanda']." ".$id." ".$num." ".$compteH."<br/>");
					//echo "2=====================================> ".$maxnums['maxnumcomanda']." ".$id." ".$num." ".$compteH."<br/>";
					if ($flush) {
						$em->getConnection()->commit();
						$em->getConnection()->beginTransaction(); // suspend auto-commit
						$em->clear();
					}
				}
			}
		} else {
			// Varis o ningún 'D'
			/*if (count($altres['D']) == 0) error_log("CAP 'D' = >".$altres['H'][0]['num']." -".count($altres['D'])."-|-".count($altres['H'])."- " );
			else error_log("VARIS = >".$altres['D'][0]['num']." -".count($altres['D'])."-|-".count($altres['H'])."- " );*/
		}
		
	}
	
	private function insertComandaDuplicat($clubs, $duplicat, &$maxnums, $flush = false) {
	
		$em = $this->getDoctrine()->getManager();
	
		$preu = $duplicat['epreu'];
		$iva = $duplicat['eiva'];
		
		$desc = str_replace("'","''",$duplicat['pdescripcio']);
		$observa = str_replace("'","''",$duplicat['observacions']);
	
		$query = "INSERT INTO m_comandes (id, comptabilitat, comentaris, dataentrada, databaixa, club, num, tipus) VALUES ";
		$query .= "(".$duplicat['id'].", 1, '".$desc."','".$duplicat['datapeticio']."'";
		$query .= ",".($duplicat['databaixadel']==null?"NULL":"'".$duplicat['databaixadel']."'").",'".$duplicat['clubdel']."',".$maxnums['maxnumcomanda'].",'D')";
	
		$em->getConnection()->exec( $query );
	
		$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, preuunitat, ivaunitat, descomptedetall, anotacions, dataentrada, databaixa) VALUES ";
		$query .= "(".$duplicat['id'].",".$duplicat['pid'].", 1,".$preu.",".($iva!=null?$iva:"NULL").", 0, ".($duplicat['observacions']==null?"NULL":"'".$observa."'").",";
		$query .= "'".$duplicat['datapeticio']."',".($duplicat['databaixadel']==null?"NULL":"'".$duplicat['databaixadel']."'").")";
	
		$maxnums['maxnumcomanda']++;

		$em->getConnection()->exec( $query );
	
		if ($flush) {
			$em->getConnection()->commit();
			$em->getConnection()->beginTransaction(); // suspend auto-commit
		}
	}
	
	private function insertComandaParte($clubs, $partes, &$maxnums, $flush = false) {
		$em = $this->getDoctrine()->getManager();
	
		if (isset($partes[0])) {
			$parte = $partes[0];
			$desc = str_replace("'","''",$parte['tdesc']);
			$rebutId = 0;
			$facturaId = 0;
				
			if ($parte['datapagament'] != null) {
				// Rebut ==> Insert des de apunts
				/*$comentari = str_replace("'","''",$parte['comentari']);
	
				$tipuspagament = null;
				if ($parte['estatpagament'] == 'TPV OK' || $parte['estatpagament'] == 'TPV CORRECCIO')  $tipuspagament = BaseController::TIPUS_PAGAMENT_TPV;
				if ($parte['estatpagament'] == 'METALLIC GES' || $parte['estatpagament'] == 'METALLIC WEB')  $tipuspagament = BaseController::TIPUS_PAGAMENT_CASH;
				if ($parte['estatpagament'] == 'TRANS WEB') $tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA;
				if ($parte['estatpagament'] == 'TRANS GES') $tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_SARDENYA;
	
				$query = "INSERT INTO m_rebuts (datapagament, num, import, dadespagament, tipuspagament, comentari, dataentrada) VALUES ";
				$query .= "('".$parte['datapagament']."',".$maxnums['maxnumrebut'].",".$parte['importpagament'];
				$query .= ", '".$parte['dadespagament']."',".$tipuspagament.",'".$comentari."','".$parte['dataentradadel']."')";
	
				$maxnums['maxnumrebut']++;
					
				$em->getConnection()->exec( $query );
	
				$rebutId = $em->getConnection()->lastInsertId();
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit*/
			}
				
			if ($parte['numfactura'] != null) {
				// Factura
				$textLlista = 'Llista '.BaseController::PREFIX_ALBARA_LLICENCIES.str_pad($parte['id'],6,'0',STR_PAD_LEFT).". ".$desc;

				$codi = $parte['clubdel'];
				if (isset( $clubs[ $codi ] ) && isset ( $clubs[ $codi ]['nom'] ) ) $textLlista .= " ".$clubs[ $codi ]['nom'];
				
				$factArray = explode("/",$parte['numfactura']);
	
				$query = "INSERT INTO m_factures (datafactura, num, import, concepte, dataentrada, datapagament) VALUES ";
				$query .= "('".$parte['datafacturacio']."',".$factArray[0].",".$parte['importparte'].",'".$textLlista."'";
				$query .= ",'".$parte['dataentradadel']."',".($parte['datapagament']==null?"NULL":"'".$parte['datapagament']."'").")";
	
				//error_log($query);
	
				$em->getConnection()->exec( $query );
	
				$facturaId = $em->getConnection()->lastInsertId();
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
			}
				
			$query = "INSERT INTO m_comandes (id, comptabilitat, comentaris, dataentrada, databaixa, club, num, rebut,factura, tipus) VALUES ";
			$query .= "(".$parte['id'].", 1, '".$desc."', '".$parte['dataentradadel']."'";
			$query .= ",".($parte['databaixadel']==null?"NULL":"'".$parte['databaixadel']."'").",'".$parte['clubdel']."',".$maxnums['maxnumcomanda'];
			$query .= ", ".($rebutId==0?"NULL":$rebutId).", ".($facturaId==0?"NULL":$facturaId).",'P')";
	
			$em->getConnection()->exec( $query );
			
			foreach ($partes as $parte) {
				$total 	= $parte['total'];
				$anota 	= $total.'x'.$parte['ccat'];
				$preu 	= (isset($parte['preu'])?$parte['preu']:0);
				$iva	= (isset($parte['iva'])?$parte['iva']:0);
				
				$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, preuunitat, ivaunitat, descomptedetall, anotacions, dataentrada, databaixa) VALUES ";
				$query .= "(".$parte['id'].",".$parte['cpro'].",".$total.", ".$preu.", ".($iva > 0?$iva:"NULL") .", 0, '".$anota."',";
				$query .= "'".$parte['dataentradadel']."',".($parte['databaixadel']==null?"NULL":"'".$parte['databaixadel']."'").")";
			
				$em->getConnection()->exec( $query );
			}
			
			$maxnums['maxnumcomanda']++;
			
			if ($flush) {
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
			}
		}
	
		
	}
	
	private function crearComandaDuplicat($duplicat, $maxNumComanda, $flush = false) {
		$em = $this->getDoctrine()->getManager();
	
		$producte = $duplicat->getCarnet()->getProducte();
		
	
		//echo "duplicat " .$duplicat->getId().' '.$producte->getDescripcio().' '.$duplicat->getClub()->getNom(). PHP_EOL."<br/>". PHP_EOL."<br/>";
	
		$comanda = new EntityComanda($maxNumComanda, $duplicat->getClub(), $producte->getDescripcio(), null, $duplicat);
		$em->persist($comanda);
			
		if ($duplicat->esBaixa()) $comanda->setDatabaixa($duplicat->getDatabaixa());
	
		$detall = new EntityComandaDetall($comanda, $producte, 1, 0, $duplicat->getObservacions());
		$em->persist($detall);
	
		$comanda->addDetall($detall);
	
		if ($flush)	{
			$em->flush();
			$em->clear();
		}
	}
	
	private function crearComandaParte($parte, $maxNumComanda, $flush = false) {
		$em = $this->getDoctrine()->getManager();
	
		$comanda = new EntityComanda($maxNumComanda, $parte->getClub(), $parte->getTipus()->getDescripcio(), $parte, null);
		$em->persist($comanda);
			
		if ($parte->esBaixa()) $comanda->setDatabaixa($parte->getDatabaixa());
	
		//echo "parte " .$parte->getId(). PHP_EOL."<br/>". PHP_EOL."<br/>";
		foreach ($parte->getTipus()->getCategories() as $categoria) {
				
			switch ($categoria->getSimbol()) {
				case 'A':
					$total = $parte->getNumAficionats();
					break;
				case 'I':
					$total = $parte->getNumInfantils();
					break;
				case 'T':
					$total = $parte->getNumTecnics();
					break;
				default:
					return -1;
			}
			if ($total > 0) {
				//echo " ==> ".$total.'x'.$categoria->getCategoria()."(".$categoria->getProducte()->getDescripcio().")". PHP_EOL."<br/>". PHP_EOL."<br/>";
				$detall = new EntityComandaDetall($comanda, $categoria->getProducte(), $total, 0, $total.'x'.$categoria->getCategoria());
				$em->persist($detall);
					
				$comanda->addDetall($detall);
			}
		}
			
		if ($flush)	{
			$em->flush();
			$em->clear();
		}
	}
	
	
}
