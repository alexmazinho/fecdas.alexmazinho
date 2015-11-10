<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use FecdasBundle\Form\FormProducte;
use FecdasBundle\Entity\EntityProducte;
use FecdasBundle\Form\FormRebut;
use FecdasBundle\Entity\EntityRebut;
use FecdasBundle\Form\FormComanda;
use FecdasBundle\Entity\EntityDuplicat;
use FecdasBundle\Entity\EntityComanda;
use FecdasBundle\Form\FormPayment;
use FecdasBundle\Entity\EntityPayment;
use FecdasBundle\Entity\EntityPreu;
use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityComptabilitat;


class FacturacioController extends BaseController {
	
	public function traspascomptabilitatAction(Request $request) {
		// http://www.fecdasnou.dev/traspascomptabilitat
		if (!$this->isAuthenticated())
			throw new \Exception("Usuari no autenticat");
	
		if (!$this->isCurrentAdmin())
			throw new \Exception("Usuari sense privilegis");
	
		$page = $request->query->get('page', 1);
			
		$em = $this->getDoctrine()->getManager();
		
		// Obtenir pendents
		// Factures excepte no consolidades (en tramitació)
		/*$datamax = $this->getCurrentDate('now');
		$datamax->sub($this->getIntervalConsolidacio()); // Substract 20 minutes
		
		$strQuery = "SELECT COUNT(f.id) FROM FecdasBundle\Entity\EntityFactura f ";
		$strQuery .= " WHERE f.comptabilitat IS NULL ";
		$strQuery .= " AND f.import != 0 ";
		$strQuery .= " AND f.dataentrada <= :max ";
		$query = $em->createQuery($strQuery);
		$query->setParameter('max', $datamax->format('Y-m-d H:i:s'));
		$factures = $query->getSingleScalarResult();			*/

		$facturesArray = $this->consultaFacturesConsolidades(null, $this->getCurrentDate('now'));
		$factures = count($facturesArray);  

		// Rebuts
		$rebutsArray = $this->consultaRebutsConsolidats(null, $this->getCurrentDate('now'));
		$rebuts = count($rebutsArray);
		/*		
		$strQuery = "SELECT COUNT(r.id) FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= " WHERE r.comptabilitat IS NULL ";
		$strQuery .= " AND r.import != 0 ";
		$query = $em->createQuery($strQuery);				
		$rebuts = $query->getSingleScalarResult();*/				
		
		// Històric de traspassos		
		$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityComptabilitat c ";
		$strQuery .= " WHERE c.fitxer <> '' ";
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
				'required'		=>	false,
				//'empty_value' 	=> false,
				'format' 		=> 'dd/MM/yyyy'))
			->add('datafins', 'date', array(
				'disabled' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'data'			=> $this->getCurrentDate('now'),
				'format' 		=> 'dd/MM/yyyy',
			));
		
		return $this->render('FecdasBundle:Facturacio:traspas.html.twig',
				$this->getCommonRenderArrayOptions(
						array('form' 		=> $formBuilder->getForm()->createView(),
							  'enviaments' 	=> $enviaments, 'factures' => $factures, 'rebuts' => $rebuts
						)
				));
			
	}
	
	public function anulartraspasAction(Request $request) {
		// http://www.fecdasnou.dev/traspascomptabilitat
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$id = $request->query->get('id', 0);
		$rebutid = $request->query->get('rebut', 0);
		
		$em = $this->getDoctrine()->getManager();
		
		if ($rebutid > 0) { // Anular un únic rebut
			$rebut = $this->getDoctrine()->getRepository('FecdasBundle:EntityRebut')->find($rebutid);
			
			if ($rebut == null) {
				$this->logEntryAuth('TRASPAS BAIXA KO', ' Rebut '.$rebutid);
				throw new \Exception("NO s'ha pogut anul·lar la comptabilitat ");
			}
			
			$rebut->setComptabilitat(null);
			$em->flush();
			$this->logEntryAuth('TRASPAS BAIXA OK', ' Rebut '.$rebut->getId());
			
			return new Response("Comptabilitat anul·lada correctament");
			// No seguim
		}
		
		$enviament = $this->getDoctrine()->getRepository('FecdasBundle:EntityComptabilitat')->find($id);
		
		if ($enviament != null) {
			// Factures
			$strQuery = "SELECT f FROM FecdasBundle\Entity\EntityFactura f ";
			$strQuery .= " WHERE f.comptabilitat = :id ";
						
			$query = $em->createQuery($strQuery)->setParameter('id', $enviament->getId());
					
			$factures = $query->getResult();
			
			foreach ($factures as $factura) $factura->setComptabilitat(null);

			// Rebuts
			$strQuery = "SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
			$strQuery .= " WHERE r.comptabilitat = :id ";
						
			$query = $em->createQuery($strQuery)->setParameter('id', $enviament->getId());
					
			$rebuts = $query->getResult();
			
			foreach ($rebuts as $rebut) $rebut->setComptabilitat(null);
			
			$enviament->setDatabaixa($this->getCurrentDate());
			
			$em->flush();
			
			$this->logEntryAuth('TRASPAS BAIXA OK', ' Enviament '.$enviament->getId());

			$this->get('session')->getFlashBag()->add('sms-notice', 
				'Enviament en data '.$enviament->getDataenviament()->format('Y-m-d H:m:s').' anul·lat correctament ('.
				$enviament->getFactures().' factures i '.$enviament->getRebuts().' rebuts)');
		} else {
			$this->logEntryAuth('TRASPAS BAIXA KO', ' Enviament '.$id);
			
			$this->get('session')->getFlashBag()->add('error-notice', 'No s\'ha pogut anul·lar l\'enviament');
		}  
			
		return $this->redirect($this->generateUrl('FecdasBundle_traspascomptabilitat'));
	}	
	
	public function fitxercomptabilitatAction(Request $request) {
		// http://www.fecdasnou.dev/fitxercomptabilitat?inici=2015-01-01&final=2015-06-22
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$em = $this->getDoctrine()->getManager();
		
		$datamin = $this->getCurrentDate('now');
		$datamin->sub(new \DateInterval('P60D')); // Substract 2 mesos		
		
		$datamax = $this->getCurrentDate('now');
		$datamax->sub($this->getIntervalConsolidacio()); // Substract 20 minutes

		// Data d'alta màxima 20 minuts endarrera (Partes a mitges) 
		$inici = $request->query->get('inici', '');
		$final = $request->query->get('final', date('d/m/Y'));
		
		if ($inici == '') $datainici = $datamin; 
		else $datainici = \DateTime::createFromFormat('d/m/Y H:i:s', $inici." 00:00:00");
		
		$datafinal = \DateTime::createFromFormat('d/m/Y H:i:s', $final." 23:59:59");
		if ($datafinal->format('Y-m-d H:i:s') > $datamax->format('Y-m-d H:i:s')) $datafinal = $datamax;
		 
		$filename = BaseController::PREFIX_ASSENTAMENTS.'_'.$datafinal->format("Ymd_His").".txt";
	
		$enviament = null;
		$fs = new Filesystem();
		try {
			if (!$fs->exists(__DIR__.BaseController::PATH_TO_COMPTA_FILES)) {
				throw new \Exception("No existeix el directori " .__DIR__.BaseController::PATH_TO_COMPTA_FILES);
			} else {
				$enviament = new EntityComptabilitat($filename, $datainici, $datafinal);
				$em->persist($enviament);
				$assentaments = $this->generarFitxerAssentaments($enviament); // Array
				if ($enviament->getApunts() == 0) throw new \Exception("No hi ha assentaments per aquests criteris de cerca ");
				
				$fs->dumpFile(__DIR__.BaseController::PATH_TO_COMPTA_FILES.$filename, implode("\r\n",$assentaments));
				
				$em->flush();
			}
		} catch (\Exception $e) {
			if ($enviament != null) $em->detach($enviament);
			//$this->logEntryAuth('FITXER COMPTA KO',	'Dates : ' . $inici ." - ".$final);
			
			$response = new Response();
			//$response->setCharset('ISO-8859-1');
			$response->setStatusCode(500, mb_convert_encoding($e->getMessage(), 'ISO-8859-1',  'auto'));
			return $response;
		}
		
		$this->get('session')->getFlashBag()->add('sms-notice', 
				'Nou enviament en data '.$enviament->getDataenviament()->format('Y-m-d H:m:s').' creat correctament ('.
				$enviament->getFactures().' factures i '.$enviament->getRebuts().' rebuts)');
		
		$this->logEntryAuth('FITXER COMPTA OK',	'Dates : ' . $inici ." - ".$final);
		
		//$response = new Response(  $this->generateUrl('FecdasBundle_downloadassentaments', array('filename' => $filename), true));  
		//$response = $this->downloadFile(__DIR__.BaseController::PATH_TO_COMPTA_FILES.$filename, $filename, 'Fitxer traspàs assentaments dia '.date("Y-m-d H:i"));
		//$response->prepare($request);
		$response = new Response('Ok');
		/*$response->headers->set('Content-Type', 'application/json');
		
		if ($enviament != null) $data = array(
				'id'=> $enviament->getId(), 
				'filename' => $enviament->getFitxer(),
				'text' => $enviament->getTextComptabilitat(),
		);
		
		$response->setContent(json_encode($data));*/ 
		
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
		//$apuntsComandesAltes = $this->generarAssentamentsComandes($enviament, false);
		//$apuntsComandesBaixes = $this->generarAssentamentsComandes($enviament, true);
		//$apuntsRebutsAltes	= $this->generarAssentamentsRebuts($enviament, false);
		//$apuntsRebutsBaixes	= $this->generarAssentamentsRebuts($enviament, true);
		//$assentaments = array_merge($apuntsComandesAltes, $apuntsComandesBaixes, $apuntsRebutsAltes, $apuntsRebutsBaixes);
		$num = 0;
		$apuntsFactures = $this->generarAssentamentsFactures($enviament, $num);
		$apuntsRebuts = $this->generarAssentamentsRebuts($enviament, $num);
		$assentaments = array_merge($apuntsFactures, $apuntsRebuts);
		return $assentaments; 
	}


	private function consultaFacturesConsolidades($desde = null, $fins = null) {
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT f FROM FecdasBundle\Entity\EntityFactura f ";
		
		$strQuery .= " WHERE f.import != 0 ";
		if ($desde != null) $strQuery .= " AND f.dataentrada >= :ini ";
		if ($fins != null) $strQuery .= " AND f.dataentrada <= :final ";
		$strQuery .= " AND (f.comptabilitat IS NULL) ";   // Pendent d'enviar encara 
		$strQuery .= " ORDER BY f.dataentrada";
		
		$query = $em->createQuery($strQuery);
		if ($desde != null) $query->setParameter('ini', $desde->format('Y-m-d H:i:s'));
		if ($fins != null) $query->setParameter('final', $fins->format('Y-m-d H:i:s'));
		
		$totesFactures = $query->getResult();

		$factures = array();		
		foreach ($totesFactures as $factura) {
			if ($factura->esAnulacio()) $comanda = $factura->getComandaAnulacio();
			else $comanda = $factura->getComanda();

			if ($comanda != null && $comanda->comandaConsolidada()) $factures[] = $factura;
		}
		return $factures;
	}

	private function consultaRebutsConsolidats($desde = null, $fins = null) {
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= " WHERE r.import != 0 ";
		if ($desde != null)  $strQuery .= " AND r.dataentrada >= :ini ";
		if ($fins != null)  $strQuery .= " AND r.dataentrada <= :final ";
		$strQuery .= " AND (r.comptabilitat IS NULL) ";	// Pendent d'enviar encara 
		$strQuery .= " ORDER BY r.dataentrada";
		
		$query = $em->createQuery($strQuery);
		if ($desde != null) $query->setParameter('ini', $desde->format('Y-m-d H:i:s'));
		if ($fins != null)  $query->setParameter('final', $fins->format('Y-m-d H:i:s'));

		$rebuts = $query->getResult();
		
		return $rebuts;
	}
	
	/**
	 * Factures	  => Club apunt D + Producte corresponent apunt H	
	 * Anular factura? => Club apunt D + Producte corresponent apunt H  però els dos amb import negatiu
	 * 
	 * @param unknown $datainici
	 * @param unknown $datafinal
	 * @param string $baixes
	 * @return multitype:string
	 */
	private function generarAssentamentsFactures($enviament, &$num) {
		$em = $this->getDoctrine()->getManager();
		
		$factures = $this->consultaFacturesConsolidades($enviament->getDatadesde(), $enviament->getDatafins());
		
		$totalFactures = 0;	
		$totalAnulacions = 0;
		$assentaments = array();
		foreach ($factures as $factura) {
			if ($factura->esAnulacio()) {
				$comanda = $factura->getComandaAnulacio();
				$totalAnulacions++;
				$totalFactures++;	
			} else {
				$comanda = $factura->getComanda();
				$totalFactures++;
			}	
			$assentament = $this->assentamentFactura($num, $factura, $comanda);
			$assentaments = array_merge($assentaments, $assentament);
			
			$factura->setComptabilitat($enviament);
			$num++;
		}
		$enviament->setFactures($totalFactures);
		//$enviament->setAnulacions($totalAnulacions);
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
	private function generarAssentamentsRebuts($enviament, &$num) {
		/*$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= " WHERE r.import != 0 ";
		if ($enviament->getDatadesde() != null)  $strQuery .= " AND r.dataentrada >= :ini ";
		if ($enviament->getDatafins() != null)  $strQuery .= " AND r.dataentrada <= :final ";
		$strQuery .= " AND (r.comptabilitat IS NULL) ";	// Pendent d'enviar encara 
		$strQuery .= " ORDER BY r.dataentrada";
		
		$query = $em->createQuery($strQuery);
		if ($enviament->getDatadesde() != null) $query->setParameter('ini', $enviament->getDatadesde()->format('Y-m-d H:i:s'));
		if ($enviament->getDatafins() != null)  $query->setParameter('final', $enviament->getDatafins()->format('Y-m-d H:i:s'));

		$rebuts = $query->getResult();*/

		$rebuts = $this->consultaRebutsConsolidats($enviament->getDatadesde(), $enviament->getDatafins());

		$totalRebuts = 0;
		$assentaments = array();
		$comandes = array();
		foreach ($rebuts as $rebut) {
			$assentament = $this->assentamentRebut($num, $rebut);
			$assentaments = array_merge($assentaments, $assentament);
			$rebut->setComptabilitat($enviament);
			$totalRebuts++;
			$num++;
		}
		$enviament->setRebuts($totalRebuts);

		return $assentaments;
	} 		
				
	private function crearLiniaAssentament($data, $numAssenta, $linia, $compte, $desc, $conc, $doc, $import, $tipus) {
		$signe = ($import >= 0?'0':'-');
			
		$apunt = "0".$data->format('Ymd');
		$apunt .= substr(str_pad($numAssenta."", 6, "0", STR_PAD_LEFT), 0, 6); 
		$apunt .= substr(str_pad($linia."", 4, "0", STR_PAD_LEFT), 0, 4); 	
		$apunt .= substr(str_pad($compte."", 9, " ", STR_PAD_RIGHT), 0, 9);		
		$apunt .= substr(str_pad($desc, 100, " ", STR_PAD_RIGHT), 0, 100);	
		$apunt .= substr(str_pad($conc, 40, " ", STR_PAD_RIGHT), 0, 40);;
		$apunt .= substr(str_pad($doc, "0", STR_PAD_LEFT), 0, 8);
		$apunt .= str_repeat(" ",4).str_repeat(" ",4);
		$apunt .= $signe.substr(str_pad((number_format(abs($import), 2, '.', '').''), 12, "0", STR_PAD_LEFT), 0, 12);
		$apunt .= $tipus.str_repeat("0",15);
		
		return $apunt;
	}
	
	private function assentamentRebut($num, $rebut) {
		$assentament = array();
		$linia = 1;
			
		$data = $rebut->getDatapagament();
		//$numAssenta = str_pad($rebut->getId(), 6, "0", STR_PAD_LEFT);/*str_repeat("0",6);*/
		//$numAssenta = str_repeat("0",6);
			
		// APUNT CLUB
		$club = $rebut->getClub();	
		$compte = $club->getCompte();
		if ($compte == null || $compte == '') throw new \Exception("El club  ".$club->getNom()." no té indicat compte comptable");
		//$desc = $this->netejarNom($rebut->getConcepteRebutLlarg(), false);
		$desc = $this->netejarNom($club->getNom(), false);
		$conc = mb_convert_encoding($this->netejarNom($rebut->getConcepteRebutCurt(), false), 'UTF-8',  'auto');
		$doc = $rebut->getNumRebutCurt();
		$import = $rebut->getImport();
		$assentament[] = $this->crearLiniaAssentament($data, $num, $linia, $compte, $desc, $conc, $doc, $import, BaseController::HABER);
			
		$linia++;
		// APUNT CAIXA
		$compte = BaseController::getComptePagament($rebut->getTipuspagament());
		$desc = BaseController::getTextComptePagament($rebut->getTipuspagament());
		
		$assentament[] = $this->crearLiniaAssentament($data, $num, $linia, $compte, $desc, $conc, $doc, $import, BaseController::DEBE);
		
		return $assentament;
	}
	 
	private function assentamentFactura($num, $factura, $comanda) {
		$assentament = array();
		$linia = 1;
		
		$data = $factura->getDatafactura();
		//$numAssenta = str_pad($factura->getId(), 6, "0", STR_PAD_LEFT);//str_repeat("0",6);
		//$numAssenta = str_repeat("0",6);
		
		// APUNT CLUB
		$club = $comanda->getClub();
		$compte = $club->getCompte();
		if ($compte == null || $compte == '') throw new \Exception("El club  ".$club->getNom()." no té compte comptable assignat");
		
		//$desc = 'PEDIDO '.$comanda->getNumComanda().' '.$this->netejarNom($club->getNom(), false);
		$desc = $this->netejarNom($club->getNom(), false);
		//$conc = mb_convert_encoding($factura->getNumFactura()." (".$this->netejarNom($factura->getConcepte(), false).")", 'UTF-8',  'auto');
		$conc = $factura->getNumFactura()." (".utf8_decode($this->netejarNom($factura->getConcepte(), false)).")";
		$doc = $factura->getNumFacturaCurt();
		$import = $factura->getImport();
					
		$assentament[] = $this->crearLiniaAssentament($data, $num, $linia, $compte, $desc, $conc, $doc, $import, BaseController::DEBE);
					
		$linia++;
		$importAcumula = 0;
		$index = 1;
		$numApunts = count(json_decode($factura->getDetalls(), true)); // Compta en format array
		//$detallsArray = json_decode($factura->getDetalls(), false, 512, JSON_UNESCAPED_UNICODE);
		$detallsArray = json_decode($factura->getDetalls(), false, 512);
		foreach ($detallsArray as $compte => $d) {
		// APUNT/S PRODUCTE/S
			$desc = $this->netejarNom($d->producte, false); 								// Descripció del compte KIT ESCAFADRISTA B2E/SVB
			//$conc = $doc."(".$index."-".$numApunts.") ".$d->total." ".mb_convert_encoding($d->producte, 'UTF-8',  'auto');						
			$conc = $doc."(".$index."-".$numApunts.") ".$d->total." ".utf8_decode($d->producte);
			$importDetall = $d->import;	
			$importAcumula += $importDetall;	

			$assentament[] = $this->crearLiniaAssentament($data, $num, $linia, $compte, $desc, $conc, $doc, $importDetall, BaseController::HABER);
						
			$linia++;
			$index++;
		}

		// Validar que quadren imports		
		if (abs($import - $importAcumula) > 0.01) throw new \Exception("Imports detall de la factura ".$doc." no quadren");
			
		return $assentament;
	} 


	 
	/*private function generarAssentamentsComandes($enviament, $baixes = false) {
	
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityComanda c ";
		
		//if ($baixes == false) {
			$strQuery .= " WHERE (c.dataentrada >= :ini AND c.dataentrada <= :final) ";
			$strQuery .= " AND (c.comptabilitat IS NULL) ";   // Pendent d'enviar encara 
		//} else {
		//	$strQuery .= " WHERE (c.databaixa >= :ini AND c.databaixa <= :final) ";
		//	$strQuery .= " AND (c.comptabilitat IS NOT NULL) ";  // Enviat a compta
		//}
		$strQuery .= " ORDER BY c.dataentrada";
		
		$query = $em->createQuery($strQuery);
		$query->setParameter('ini', $enviament->getDatadesde()->format('Y-m-d H:i:s'));
		$query->setParameter('final', $enviament->getDatafins()->format('Y-m-d H:i:s'));
		
		$result = $query->getResult();

		$comandes = $enviament->getComandes();
		$assentaments = array();
		foreach ($result as $comanda) {
			$assentament = $this->assentamentComanda($comanda);
			$assentaments = array_merge($assentaments, $assentament);
		}
		//$enviament->setComandes($comandes);

		return $assentaments;
	}	

	private function assentamentComanda($comanda) {
		$assentament = array();
		
		$importTotal = $comanda->getTotalDetalls();
			
		if ($importTotal > 0) {	
			$comandes++;
			$linia = 1;
			//$signe = ($baixes == false?'0':'-');
			$signe = '0';
				
			$data = $comanda->getDataentrada()->format('Ymd');
				
			$numAssenta = str_pad($comanda->getId(), 6, "0", STR_PAD_LEFT);//str_repeat("0",6);
				
			$desc = $this->netejarNom($comanda->getConcepteComanda(), false);
			$conc = 'FRA '.$comanda->getFactura()->getNumFactura();
			$doc = $comanda->getNumAssentament();
			$import = $signe.str_pad((number_format($importTotal, 2, '.', '').''), 12, "0", STR_PAD_LEFT);
			// Apunt club
			$club = $comanda->getClub();
			$compte = $club->getCompte();
				
			$apuntclub = "0".$data.$numAssenta.str_pad($linia."", 4, "0", STR_PAD_LEFT).str_pad($compte."", 9, " ", STR_PAD_RIGHT);
			$apuntclub .= str_pad($desc, 100, " ", STR_PAD_RIGHT).str_pad($conc, 40, " ", STR_PAD_RIGHT).$doc.str_repeat(" ",4).str_repeat(" ",4);
			$apuntclub .= $import.BaseController::HABER.str_repeat("0",15);
					
			// Validar que quadren imports
			$assentament[] = $apuntclub;
					
			$linia++;
				
			$detalls = $comanda->getDetallsAcumulats();
				
			foreach ($detalls as $compte => $d) {
			// Apunt/s producte/s
					
				$desc = $d['total']." x ".$this->netejarNom($d['producte'], false);
						
				$import = $signe.str_pad((number_format($d['import'], 2, '.', '').''), 12, "0", STR_PAD_LEFT);
					
				$apunt = "0".$data.$numAssenta.str_pad($linia."", 4, "0", STR_PAD_LEFT).str_pad($compte."", 9, " ", STR_PAD_RIGHT);
				$apunt .= str_pad($desc, 100, " ", STR_PAD_RIGHT).str_pad($conc, 40, " ", STR_PAD_RIGHT).$doc.str_repeat(" ",4).str_repeat(" ",4);
				$apunt .= $import.BaseController::DEBE.str_repeat("0",15);
				
				$assentament[] = $apunt;
						
				$linia++;
			}
				
			$comanda->getFactura()->setComptabilitat($enviament);
		} else {
			$comanda->addComentari("Comanda amb import 0 no s'envia a comptabilitat");
		}
		return $assentament;
	}
	*/
	
	
	//private function downloadFile($fitxer, $path, $desc) {
	public function downloadassentamentsAction(Request $request, $filename) {
			
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
	
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'r.datapagament');
		$direction = $request->query->get('direction', 'desc');
	
		$query = $this->consultaIngresos($codi, $numrebut, $anyrebut, $sort, $direction);
					
		$paginator  = $this->get('knp_paginator');
					
		$ingresos = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
		
		$formBuilder = $this->createFormBuilder()
			->add('cerca', 'entity', array(
					'class' 		=> 'FecdasBundle:EntityClub',
					'query_builder' => function($repository) {
							return $repository->createQueryBuilder('c')
								->orderBy('c.nom', 'ASC')
								->where('c.activat = 1');
							}, 
					'choice_label' 	=> 'nom',
					'empty_value' 	=> 'Seleccionar Club',
					'required'  	=> false,
					'data'			=> $codi
			))
			->add('numrebut', 'text', array(
				'required' => false,
				'data' => $nr
		));
												
		return $this->render('FecdasBundle:Facturacio:ingresos.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'ingresos' => $ingresos,  'sortparams' => array('sort' => $sort,'direction' => $direction))
		));
	}
	
	public function comandesAction(Request $request) {
		// Llistat de comandes
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin()) {  // Users normals només consulten comandes pròpies 
			$club = $this->getCurrentClub();
			$codi = $club->getCodi(); 
		}	
		else $codi = $request->query->get('cerca', ''); // Admin filtra club
	
		$this->logEntryAuth('VIEW COMANDES', $this->get('session')->get('username'));
		
		
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

		$pendents = $request->query->get('pendents', 0);
		$pendents = ($pendents == 1?true:false);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.dataentrada');
		$direction = $request->query->get('direction', 'desc');

		$query = $this->consultaComandes($codi, $numfactura, $anyfactura, $numrebut, $anyrebut, $baixes, $pendents, $sort, $direction);
		
		$paginator  = $this->get('knp_paginator');
			
		$comandes = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
		
		$formBuilder = $this->createFormBuilder()
			 ->add('cerca', 'entity', array(
						'class' 		=> 'FecdasBundle:EntityClub',
						'query_builder' => function($repository) {
								return $repository->createQueryBuilder('c')
									->orderBy('c.nom', 'ASC')
									->where('c.activat = 1');
								}, 
						'choice_label' 	=> 'nom',
						'empty_value' 	=> 'Seleccionar Club',
						'required'  	=> false,
				))
			->add('numfactura', 'text', array(
				'data' 	=> $nf ))
			->add('numrebut', 'text', array(
				'data' => $nr	))
			->add('baixes', 'checkbox', array(
				'required' => false,
				'data' => $baixes))
			->add('pendents', 'checkbox', array(
				'required' => false,
				'data' => $pendents
			
		));
		
			
		return $this->render('FecdasBundle:Facturacio:comandes.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'comandes' => $comandes,  'sortparams' => array('sort' => $sort,'direction' => $direction))
				));
	}

	public function graellaproductesAction(Request $request) {
		// Graella de productes per afegir a la cistella

		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		$this->logEntryAuth('VIEW GRAELLA PRODUCTES', $this->get('session')->get('username'));
		
		$tipus = $request->query->get('tipus', 0);
		$compte = $request->query->get('compte', '');
		$sort = $request->query->get('sort', 'p.descripcio');
		$direction = $request->query->get('direction', 'asc');
		
		$productes = array();
		
		if ($tipus > 0) {
			$query = $this->consultaProductes(0, $compte, $tipus, false, $sort, $direction);
		
			$productes = $query->getResult();
		}
		
		foreach ($productes as $k => $producte) {
			// No mostrar preus a 0
			if ($producte->getCurrentPreu() <= 0) unset($productes[$k]);  	
		}
			
		$formBuilder = $this->createFormBuilder()
			->add('cerca', 'search', array( ) )
			->add('tipus', 'hidden', array(
				'data' => $tipus));
		
		$cart = $this->getSessionCart();
		$formtransport = $this->formulariTransport($cart);		
			
		return $this->render('FecdasBundle:Facturacio:graellaproductes.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(), 
						'title' => BaseController::getTipusProducte($tipus),
						'productes' => $productes,  
						'tipus' => $tipus,
						'compte' => $compte,
						'sortparams' => array('sort' => $sort,'direction' => $direction),
						'formtransport' => $formtransport
						)));
	}
	
	public function tramitarcistellaAction(Request $request) {
		// Recupera la cookie amb els productes del carrito
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$action = $request->query->get('action', '');
		$tipus = $request->query->get('tipus', 0);
		$transport = $request->query->get('transport', 1);
		$transport = ($transport == 1?true:false);
		$comentaris = $request->query->get('comentaris', '');
		
		$this->logEntryAuth('CISTELLA TRAMITAR', 'action => '.$action);

		try {
			$em = $this->getDoctrine()->getManager();
	
			if ($action == 'desar' || $action == 'pagar') {
				// Comanda nova. Crear factura
				$current = $this->getCurrentDate();
				
				$comanda = $this->crearComanda($current, $comentaris);
				
				$cart = $this->getSessionCart();
				
				foreach ($cart['productes'] as $id => $info) {
					$producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->find($id);
					
					$anotacions = $info['unitats'].'x'.$info['descripcio'];
		
					$detall = $this->addComandaDetall($comanda, $producte, $info['unitats'], 0, $anotacions);
				}
				
				if ($transport == true) {
					$pesComanda = $this->getPesComandaCart($cart);
					$tarifa = BaseController::getTarifaTransport($pesComanda);
					
					$producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->findOneByCodi(BaseController::PRODUCTE_CORREUS);
					$anotacions = $producte->getDescripcio().' '.$pesComanda.'g';	
					$detall = $this->addComandaDetall($comanda, $producte, 1, 0, $anotacions);		
				}
				
				if (count($cart['productes']) == 0) $detall = $this->addComandaDetall($comanda); // Sempre afegir un detall si comanda nova
			
				// Validacions comuns i anotacions stock
				$this->tramitarComanda($comanda);

				$factura = $this->crearFactura($current, $comanda);
			
				$em ->flush();
				
			}
			
			if ($action == 'anular' || $action == 'desar' || $action == 'pagar') { // Totes borren sessió
				// Esborrar comanda de la sessió
				$session = $this->get('session');
				
				$session->remove('cart');
			}	
			
			switch ($action) {
			    case 'pagar':
			        // Obrir TPV
			        if ($this->isCurrentAdmin() == true) {
						$response = $this->redirect($this->generateUrl('FecdasBundle_confirmapagament', array(
					        'id'  => $comanda->getId(),
					        'tipuspagament' => $request->query->get('tipuspagament', BaseController::TIPUS_PAGAMENT_CASH),
					        'datapagament' => $request->query->get('datapagament', ''),
					        'dadespagament' => $request->query->get('dadespagament', ''),
					        'comentaripagament' => $request->query->get('comentaripagament', ''),
					    ))); 
						
			        	return $response;
					}
					
			        return $this->redirect($this->generateUrl('FecdasBundle_pagamentcomanda', array( 'id' => $comanda->getId())));
			        
			        break;
			    case 'desar':
			        // Recordatori pagament
					$this->get('session')->getFlashBag()->add('sms-notice',	'La comanda s\'ha desat correctament'); 
					
			        break;
			    case 'anular':
					// Missatge 
					$this->get('session')->getFlashBag()->add('sms-notice',	'Comanda anul·lada');	
			        break;
			}
			
			return $this->redirect($this->generateUrl('FecdasBundle_comandes'));
			
		} catch (\Exception $e) {
				// Ko, mostra form amb errors
			$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
		}
		
		return $this->redirect($this->generateUrl('FecdasBundle_graellaproductes', array( 'tipus' => $tipus)));
	}

	public function afegircistellaAction(Request $request) {
		// Afegir producte a la cistella (desada temporalment en cookie)
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$producte = null;
		$idProducte = $request->query->get('id', 0);
		$unitats = $request->query->get('unitats', 1);
		$tipus = $request->query->get('tipus', 0);
		
		try {
			$producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->find($idProducte);
			
			if ($producte != null && $unitats > 0) {
				// Recollir cistella de la sessió
				$cart = $this->getSessionCart();				
				
				$import = $producte->getPreuAny(date('Y'));
				
				if ( !isset( $cart['productes'][$idProducte] ) ) {
					$cart['productes'][$idProducte] = array(
							'abreviatura' 	=> $producte->getAbreviatura(),
							'descripcio' 	=> $producte->getDescripcio(),
							'transport'		=> $producte->getTransport(),
							'pes'			=> 0,
							'unitats' 		=> $unitats,
							'import' 		=> $import
					);
				} else {
					$cart['productes'][$idProducte]['unitats'] += $unitats;
				}
				//$cart['total'] += $import;
	
				$unitats = $cart['productes'][$idProducte]['unitats'];
				
				if ($producte->getTransport() == true) $cart['productes'][$idProducte]['pes'] = $unitats * $producte->getPes(); 
				
				/*
				if ($producte->getTransport() == true) {
					if ($producte->getCanvitarifa() != null && is_numeric($producte->getCanvitarifa()) && $producte->getCanvitarifa() <= $unitats ) {
						$cart['productes'][$idProducte]['tarifa'] = BaseController::TARIFA_TRANSPORT2;
					} else {
						$cart['productes'][$idProducte]['tarifa'] = BaseController::TARIFA_TRANSPORT1;
					}
				}*/
							
				if ($cart['productes'][$idProducte]['unitats'] <= 0) {
					// Afegir unitats < 0
					unset( $cart['productes'][$idProducte] );	
					if (count($cart['productes'] <= 0)) $this->get('session')->remove('cart');
				}
				
				$form = $this->formulariTransport($cart);		
				
				$session = $this->get('session');
				$session->set('cart', $cart);
			}
		} catch (\Exception $e) {
			// Ko, mostra form amb errors
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
			//$response->headers->set('Content-Type', 'application/json');
			//$response->setContent(json_encode( array('result' => 'KO', 'sms' => $e->getMessage()) ));
			return $response;
		}
		return $this->render('FecdasBundle:Facturacio:graellaproductescistella.html.twig', 
							array('formtransport' => $form, 'tipus' => $tipus, 'admin' => $this->isCurrentAdmin())); 
		
	}

	public function treurecistellaAction(Request $request) {
		// Afegir producte a la cistella (desada temporalment en cookie)
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$producte = null;
		$idProducte = $request->query->get('id', 0);
		$tipus = $request->query->get('tipus', 0);
		
		$producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->find($idProducte);
		
		if ($producte != null) {
			$cart = $this->getSessionCart();	
			
			/*if ( isset( $cart['productes'][$idProducte] ) ) {
				$cart['total'] -= $cart['productes'][$idProducte]['unitats'] * $cart['productes'][$idProducte]['import'];
			}*/
			unset( $cart['productes'][$idProducte] );

			if (count($cart['productes'] <= 0)) $this->get('session')->remove('cart');  			
			else $session->set('cart', $cart);
		}

		$form = $this->formulariTransport($cart);
				
		return $this->render('FecdasBundle:Facturacio:graellaproductescistella.html.twig', 
						array('formtransport' => $form, 'tipus' => $tipus, 'admin' => $this->isCurrentAdmin()));
		
	}

	private function formulariTransport(&$cart) {
		// Revisar si cal transport
		$pesComanda = $this->getPesComandaCart($cart);
		$total = $this->getTotalComandaCart($cart);
		$tarifa = BaseController::getTarifaTransport($pesComanda);

		$cart['tarifatransport'] = $tarifa;
				
		$formBuilder = $this->createFormBuilder()
			->add('tarifatransport', 'hidden', array(
				'data' => $tarifa
		));
		$formBuilder->add('importcomanda', 'hidden', array(
				'data' => $total
		));
		$formBuilder->add('transport', 'choice', array(
				'choices'   => array(0 => 'Incloure enviament', 1 => 'Recollir a la federació'),
				'multiple'  => false,
				'expanded'  => true,
				'data' 		=> 0 
		));
		
		return $formBuilder->getForm()->createView();
	}

	public function editarcomandaAction(Request $request) {
		// Edició d'una nova comanda existent
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
		$comanda = null;
		$comandaOriginalBaixa = false;
		$originalDetalls = new \Doctrine\Common\Collections\ArrayCollection();
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
		
			if ($comanda == null) {
				
				// Comanda nova. Crear factura
				$current = $this->getCurrentDate();
				
				$comanda = $this->crearComanda($current);
				
				$detall = $this->addComandaDetall($comanda); // Sempre afegir un detall si comanda nova
			
				$factura = $this->crearFactura($current, $comanda);

			} else {
				// Create an ArrayCollection of the current detalls
				foreach ($comanda->getDetalls() as $detall) {
					$originalDetalls->add(clone $detall);
				}
			}
			$comandaOriginalBaixa = $comanda->esBaixa();
		}
		
		$comandaPagadaOriginal = $comanda->comandaPagada(); // Per detectar nous pagaments 
		
		$form = $this->createForm(new FormComanda(), $comanda);
		if ($request->getMethod() == 'POST') {
			try {
				$form->handleRequest($request);
				if ($form->isValid()) {
					$strDatapagament = (isset($data['datapagament']) && $data['datapagament'] != ''?$data['datapagament']:'');
					$tipusPagament = (isset($data['tipuspagament']) && $data['tipuspagament'] != ''?$data['tipuspagament']:'');
					$informarPagament = false;
					if (!$comandaPagadaOriginal && $strDatapagament != '') $informarPagament = true;  
					
					// Validacions comuns i anotacions stock
					$this->tramitarComanda($comanda, $originalDetalls, $informarPagament, $form);

					// Si nou pagament, crear rebut
					$strDatapagament = (isset($data['datapagament']) && $data['datapagament'] != ''?$data['datapagament']:'');
					$tipusPagament = (isset($data['tipuspagament']) && $data['tipuspagament'] != ''?$data['tipuspagament']:'');
					if (!$comandaPagadaOriginal && $strDatapagament != '') {
							
						$datapagament = \DateTime::createFromFormat('d/m/Y H:i:s', $strDatapagament." 00:00:00");
						$this->crearRebut($datapagament, $tipusPagament, $comanda);
					} 

					if ($comanda->esNova()) $comanda->setNum($maxNumComanda); // Per si canvia
		
				} else {
					throw new \Exception('Dades incorrectes, cal revisar les dades de la comanda ' ); //$form->getErrorsAsString()
				}
		
				$em->flush();
		
				$this->get('session')->getFlashBag()->add('sms-notice',	'La comanda s\'ha desat correctament');
				 
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
			}
			return $this->redirect($this->generateUrl('FecdasBundle_editarcomanda',
						array( 'id' => $comanda->getId() )));
		}
		
		return $this->render('FecdasBundle:Facturacio:comanda.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'comanda' => $comanda)));
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
		
		$data = $this->getCurrentDate();
		$maxNumFactura = $this->getMaxNumEntity($data->format('Y'), BaseController::FACTURES) + 1;
		$maxNumRebut = $this->getMaxNumEntity($data->format('Y'), BaseController::REBUTS) + 1;
		
		if ($comanda->esParte()) {
			$parte = $comanda;
			
			foreach ($parte->getLlicencies() as $llicencia) {
				if (!$llicencia->esBaixa()) {
					$detallBaixa = $this->removeParteDetall($parte, $llicencia, $maxNumFactura, $maxNumRebut);
				}
			}
		} else {
		//foreach ($detalls as $detall) {
			foreach ($comanda->getDetalls() as $detall) {
				if (!$detall->esBaixa()) {
						
					$detallBaixa = $this->removeComandaDetall($comanda, $detall->getProducte(), $detall->getUnitats());

					$this->crearFacturaRebutAnulacio($this->getCurrentDate(), $comanda, $detallBaixa, $maxNumFactura, $maxNumRebut);
					
				}
			}
		}

		$comanda->setDatamodificacio(new \DateTime());
		$comanda->setDatabaixa(new \DateTime());
	
	
		$em->flush();
	
		$this->logEntryAuth('BAIXA COMANDA OK', 'Comanda: '.$comanda->getId());
		$this->get('session')->getFlashBag()->add('sms-notice', 'Comanda '.$comanda->getInfoComanda().' donada de baixa ');
		return $this->redirect($this->generateUrl('FecdasBundle_comandes', array('baixes' => true)));
	}
	
	public function pagamentcomandaAction(Request $request) {
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$comandaid = 0;
		if ($request->query->has('id')) {
			$comentaris = $request->query->get('comentaris', '');
			$comandaid = $request->query->get('id');
			$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($comandaid);
		
			if ($comanda != null  && $comanda->comandaPagada() != true) {
				
				if ($comentaris != '') $comanda->setComentaris($comentaris);
				$club = $comanda->getClub();
				$origen = $comanda->getOrigenPagament();
				$desc = $comanda->getDescripcioPagament();
				
				// Get factura detall
				$detallfactura = $comanda->getDetallsAcumulats(); 
				
				$payment = new EntityPayment($comandaid, $this->get('kernel')->getEnvironment(),
							$comanda->getTotalDetalls(), $desc, $club->getNom(), $origen);
				$formpayment = $this->createForm(new FormPayment(), $payment);
				
				$this->logEntryAuth('PAGAMENT VIEW', $comandaid);
				
				return $this->render('FecdasBundle:Facturacio:pagament.html.twig',
						$this->getCommonRenderArrayOptions(array('formpayment' => $formpayment->createView(),
								'comanda' => $comanda, 'payment' => $payment, 
								'detall' => $detallfactura,
								'backurl' => $this->generateUrl($comanda->getBackURLPagament())
						)));
			}
		}
		
		/* Error */
		$this->logEntryAuth('PAGAMENT KO', $parteid);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut accedir al pagament, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
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
		$compte = $request->query->get('compte', '');
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

		$query = $this->consultaProductes($idproducte, $compte, $tipus, $baixes, $sort, $direction);
			
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
			->add('compte', 'hidden', array(
					'data' => $compte,
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
   			/* Alta o modificació de preus */
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
    				} else {
    					$producte->setLimitnotifica(null);
						$producte->setStock(null);
    				}
    				
					if ($producte->getTransport() == true) {
						if ($producte->getPes() == null || $producte->getPes() < 0) {
    						$form->get('pes')->addError(new FormError('Valor incorrecte'));
    						throw new \Exception('Cal indicar el pes del producte per calcular la tarifa de transport ' );
    					}
					} else {
						$producte->setPes(0);
					}
    				
    				$producte->setAbreviatura(strtoupper($producte->getAbreviatura()));
    				
    				if ($iva == 0) $iva = null;
    				
    				$preu = $producte->getPreu($anypreu);
    				if ($preu == null) {
    					// Crear nou
    					$preu = new EntityPreu($anypreu, $importpreu, $iva, $producte);
    					$em->persist($preu);
						$producte->addPreus($preu);
    				} else {
    					$preu->setPreu($importpreu);
    					$preu->setIva($iva);
    				}
    				
    				
    			} else {
    				throw new \Exception('Dades incorrectes, cal revisar les dades del producte ' ); //$form->getErrorsAsString()
    			}

    			$em->flush();
    			 
    			$this->get('session')->getFlashBag()->add('sms-notice',	'El producte s\'ha desat correctament');
    			
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
		$this->get('session')->getFlashBag()->add('sms-notice', 'Producte '.$producte->getDescripcio().' donat de baixa ');
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
		
		
		/*$this->get('session')->getFlashBag()->add('sms-notice', 'Preu esborrat correctament ');
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
		$tipus = $request->get('tipus', 'compte');
		$id = $request->get('id', 0);
		$em = $this->getDoctrine()->getManager();
		
		if ($id > 0) {
			$producte = $em->getRepository('FecdasBundle:EntityProducte')->find($id);
			if ($producte != null) {
				$response->headers->set('Content-Type', 'application/json');
				
				if  ($tipus != 'compte') $response->setContent(json_encode(array("id" => $producte->getId(), "text" => $producte->getDescripcio()) ) );
				else $response->setContent(json_encode(array("id" => $producte->getId(), "text" => $producte->getCodi()."") ) );
				return $response;
			}
		}
		
		if ($id == 0 && $cerca == "") {
			// Res a cercar
			$response->headers->set('Content-Type', 'application/json');
			$response->setContent(json_encode(array()));
			return $response;
		}
		$strQuery = " SELECT p FROM FecdasBundle\Entity\EntityProducte p ";
		$strQuery .= " WHERE p.databaixa IS NULL ";
		if  ($tipus != 'compte') $strQuery .= " AND p.descripcio LIKE :cerca";
		else $strQuery .= " AND p.codi >= :min AND p.codi <= :max ";
		$strQuery .= " ORDER BY p.descripcio";  
	
		$query = $em->createQuery($strQuery);
		if  ($tipus != 'compte') $query->setParameter('cerca', '%'.$cerca.'%');
		else {
			$max = substr( str_pad( $cerca."", 7, "9", STR_PAD_RIGHT), 0, 7);
			$min = substr( str_pad( $cerca."", 7, "0", STR_PAD_RIGHT), 0, 7);
			$query->setParameter('max', $max);
			$query->setParameter('min', $min);
		} 
		
		$search = array();
		if ($query != null) {
			$result = $query->getResult();
			foreach ($result as $p) {
				if  ($tipus != 'compte') $search[] = array("id" => $p->getId(), "text" => $p->getDescripcio());
				else $search[] = array("id" => $p->getId(), "text" => $p->getCodi()."");
			}
		}
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($search));
		
		return $response;
	}
	
	protected function consultaProductes($idproducte, $compte, $tipus, $baixes = false, $strOrderBY = 'p.description' , $direction = 'asc' ) {
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
		if ($compte != '') $strQuery .= " AND p.codi = :compte";
		
		$strQuery .= " ORDER BY " .implode(" ".$direction.", ",explode(",",$strOrderBY)). " ".$direction;
		
		$query = $em->createQuery($strQuery);
		
		if ($tipus > 0) $query->setParameter('tipus', $tipus);
		if ($compte != '') $query->setParameter('compte', $compte);
		return $query;
	}
	
	protected function consultaIngresos($codi, $nr, $ar, $strOrderBY = 'r.datapagament', $direction = 'desc' ) {
		$em = $this->getDoctrine()->getManager();
	
		//$strQuery = "SELECT r, c FROM FecdasBundle\Entity\EntityRebut r LEFT JOIN r.comanda c ";
		$strQuery = "SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= "WHERE 1 = 1 ";
		//$strQuery .= "WHERE c IS NULL "; 								// Ingrés no associat a cap comanda
		if ($codi != '') $strQuery .= " AND r.club = :codi ";
	
		if (is_numeric($nr) && $nr > 0) $strQuery .= " AND r.num = :numrebut ";
	
		if (is_numeric($ar) && $ar > 0) {
			$datainicirebut = \DateTime::createFromFormat('Y-m-d H:i:s', $ar."-01-01 00:00:00");
			$datafinalrebut = \DateTime::createFromFormat('Y-m-d H:i:s', $ar."-12-31 23:59:59");
			$strQuery .= " AND r.datapagament >= :rini AND r.datapagament <= :rfi ";
		}
	
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
	
	public function nouingresAction(Request $request) {
		// Introduir un ingrés d'un club
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$this->get('session')->getFlashBag()->clear();
		
		$this->logEntryAuth('INGRES NOU',$request->getMethod());
		
		$em = $this->getDoctrine()->getManager();

		// Comandes pendents de pagament. Inicialment al club connectat
		if ($request->getMethod() != 'POST') $codi = $request->query->get('codi', '');
		else {
			$formdata = $request->request->get('rebut');
			$codi = $formdata['club'];
		}
			
		$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		if ($club == null) $club = $this->getCurrentClub();
		 
		$query = $this->consultaComandes($club->getCodi(), 0, 0, 0, 0, false, true);
		
		$comandes = $query->getResult(); // Comandes pendents rebut del club
		
		// Nou rebut
		$tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA;
		
		$rebut = $this->crearIngres($this->getCurrentDate(), $tipuspagament, $club);
		
		$em->persist($rebut);
		
		$form = $this->createForm(new FormRebut(), $rebut);
		
		if ($request->getMethod() == 'POST') {
			try {
				$form->handleRequest($request);

				if ($form->isValid()) {
					
					$maxNumRebut = $this->getMaxNumEntity($rebut->getDataentrada()->format('Y'), BaseController::REBUTS) + 1;
					$rebut->setNum($maxNumRebut);

					$comandesIds = json_decode($request->request->get('comandesSelected', ''));

					foreach ($comandesIds as $comandaId) {
						$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($comandaId);
					
						// Comanda de la selecció no trobada
						if ($comanda == null) throw new \Exception('La comanda '.$comanda->getNumComanda().' no està disponible' );
					
						if ($comanda->comandaPagada() == true) throw new \Exception('La comanda '.$comanda->getNumComanda().' ja está pagada' );
					
						$rebut->addComanda($comanda);
						$comanda->setDatamodificacio(new \DateTime());
						$comanda->setRebut($rebut); 
						if ($comanda->esParte()) $comanda->setPendent(false);
					}

					$this->validIngresosRebuts($form, $rebut);
					
					$club->setTotalpagaments($club->getTotalpagaments() + $rebut->getImport()); 
					
					$em->flush();
					
				}
			} catch (\Exception $e) {
					
				$response = new Response($e->getMessage());
				$response->setStatusCode(500);
				return $response;
				//$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
				
			}
		}				
		
		return $this->render('FecdasBundle:Facturacio:ingres.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'rebut' => $rebut, 'comandes' =>$comandes)));
	}
	 
	
	public function editarrebutAction(Request $request) {
		// Formulari d'edició d'un rebut
		//$this->get('session')->getFlashBag()->clear();
		 
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		/* De moment administradors */
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_home'));
				 
		$em = $this->getDoctrine()->getManager();
				 
		$rebut = null;
		
		if ($request->getMethod() != 'POST') {
			$id = $request->query->get('id', 0);
	
			$rebut = $this->getDoctrine()->getRepository('FecdasBundle:EntityRebut')->find($id);
					 
			if ($rebut == null) {
				// No trobat
				$this->logEntryAuth('REBUT EDIT KO',	'rebut : ' . $request->query->get('id', 0));
				$this->get('session')->getFlashBag()->add('error-notice', 'Rebut no trobat ');
				return $this->redirect($this->generateUrl('FecdasBundle_ingresos'));
			}
	
			$this->logEntryAuth('REBUT EDIT',	'rebut : ' . $rebut->getId().' '.$rebut->getComentari());
			
		} else {
			/* Alta o modificació de clubs */
			$data = $request->request->get('rebut');
			$id = (isset($data['id'])?$data['id']:0);
	
			if ($id > 0) $rebut = $this->getDoctrine()->getRepository('FecdasBundle:EntityRebut')->find($id);
			if ($rebut == null) {
				$tipuspagament = BaseController::TIPUS_PAGAMENT_CASH;
				$rebut = $this->crearRebut($this->getCurrentDate(), $tipuspagament);
			}
		}
		$form = $this->createForm(new FormRebut(), $rebut);
				 
		if ($request->getMethod() == 'POST') {
			try {
				$form->handleRequest($request);
		
				if ($form->isValid()) {
	
					$rebut->setDatamodificacio(new \DateTime());
					if ($rebut->getId() == 0)  {
						$maxNumRebut = $this->getMaxNumEntity(date('Y'), BaseController::REBUTS) + 1;
						$rebut->setNum($maxNumRebut);
						if ($rebut->getComanda() != null) $rebut->getComanda()->setRebut($rebut);
					}
	
					$this->validIngresosRebuts($form, $rebut);
		
				} else {
					throw new \Exception('Dades incorrectes, cal revisar les dades del rebut ' ); //$form->getErrorsAsString()
				}
		
				$em->flush();
		
				$this->get('session')->getFlashBag()->add('sms-notice','El rebut s\'ha desat correctament');
							 
				$this->logEntryAuth('REBUT SUBMIT',	'rebut : ' . $rebut->getId().' '.$rebut->getComentari());
				// Ok, retorn form sms ok
				return $this->redirect($this->generateUrl('FecdasBundle_editarrebut',
									array( 'id' => $rebut->getId() )));
							 
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
			}
		}
		return $this->render('FecdasBundle:Facturacio:rebut.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'rebut' => $rebut)));
	}
	
	private function validIngresosRebuts($form, $rebut) {
		
		if ($rebut->getImport() <= 0) {
			$form->get('import')->addError(new FormError('Valor incorrecte'));
				throw new \Exception('Cal indicar un import superior a 0' );
		}
		if ($rebut->esAnulacio()) {
			if ($rebut->getDatapagament()->format('Y-m-d') < $rebut->getComandaAnulacio()->getDataentrada()->format('Y-m-d')) {
				$form->get('datapagament')->addError(new FormError('Data incorrecte'));
					throw new \Exception('La data d\'anul·lació ha de ser igual o posterior a la data de la comanda' );
			}
			
		} else {
			$total = 0; 
			
			foreach ($rebut->getComandes() as $comanda) {
				
				if ($rebut->getDatapagament()->format('Y-m-d') < $comanda->getDataentrada()->format('Y-m-d')) {
				$form->get('datapagament')->addError(new FormError('Data incorrecte'));
					throw new \Exception('La data de pagament ha de ser igual o posterior a la data de la comanda' );
				}
				
				$total += $comanda->getTotalDetalls();		
			}
			
			//if (abs($rebut->getImport() - $rebut->getComanda()->getTotalDetalls()) > 0.01) {
			if ($total > $rebut->getImport()) {	
								
				$form->get('import')->addError(new FormError('Valor incorrecte'));
					throw new \Exception('El total de les comandes supera l\'import de l\'ingrés');
			}
				
		}
		
	} 
	
	
	protected function consultaComandes($codi, $nf, $af, $nr, $ar, $baixes, $pendents, $strOrderBY = 'c.dataentrada', $direction = 'desc' ) {
		
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
		
		if ($pendents) $strQuery .= " AND c.rebut IS NULL ";
		
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

	public function notificacioOkAction(Request $request) {
		// Resposta TPV on-line, genera resposta usuaris correcte
		return $this->notificacioOnLine($request);
	}
	
	public function notificacioKoAction(Request $request) {
		// Resposta TPV on-line, genera resposta usuaris incorrecte		
		return $this->notificacioOnLine($request);
	}
	
	public function notificacioOnLine(Request $request) {
	
		$tpvresponse = $this->tpvResponse($request->query);

		$result = 'KO';
		$url = $this->generateUrl('FecdasBundle_homepage');
		if ($tpvresponse['itemId'] > 0) {
			if ($tpvresponse['pendent'] == true) $result = 'PEND';
			else $result = 'OK';
			
			if ($tpvresponse['source'] = BaseController::PAGAMENT_LLICENCIES)
				$url = $this->generateUrl('FecdasBundle_parte', array('id'=> $tpvresponse['itemId']));
			if ($tpvresponse['source'] = BaseController::PAGAMENT_DUPLICAT)
				$url = $this->generateUrl('FecdasBundle_duplicats');
			if ($tpvresponse['source'] = BaseController::PAGAMENT_ALTRES)
				$url = $this->generateUrl('FecdasBundle_comandes');
		}
		$this->logEntryAuth('TPV NOTIFICA '. $result, $tpvresponse['logEntry']);
		
		return $this->render('FecdasBundle:Facturacio:notificacio.html.twig',
				array('result' => $result, 'itemId' => $tpvresponse['itemId'], 'url' => $url) );
	}
	
	public function notificacioAction(Request $request) {
		// Crida asincrona des de TPV. Actualització dades pagament del parte

		try {
			$tpvresponse = $this->tpvResponse($request->request);
			
			if ($request->getMethod() != 'POST')
				throw new \Exception('Error configuració TPV, la URL de notificación ha de ser: http://www.fecdasgestio.cat/notificacio');
			
			if (!isset($tpvresponse['Ds_Response']))
				throw new \Exception('Error resposta TPV. Manca Ds_Response');

			if (!isset($tpvresponse['Ds_Order']))
				throw new \Exception('Error resposta TPV. Manca Ds_Order');

			if ($tpvresponse['Ds_Response'] == 0) {
				// Ok
				$id = !isset($tpvresponse['itemId'])?0:$tpvresponse['itemId'];
				$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($id);
					
				if ($comanda == null) 
						throw new \Exception('Error actualitzar comanda TPV. id: '.$id);
				
				// Afegir rebut
				$this->crearRebut($this->getCurrentDate(), BaseController::TIPUS_PAGAMENT_TPV, $comanda, $tpvresponse['Ds_Order']);
					
				$em = $this->getDoctrine()->getManager();
				$em->flush();
				
				$this->logEntryAuth('TPV OK', $tpvresponse['logEntry']);
				
				return new Response('');
			} 
			
			if ($tpvresponse['pendent'] == true) {
				// Pendent, enviar mail 
				$subject = ":: TPV. Pagament pendent de confirmació ::";
				$bccmails = array();
				$tomails = array(self::MAIL_ADMINTEST);
						
				$body = "<h1>Parte pendent</h1>";
				$body .= "<p>". $tpvresponse['logEntry']. "</p>"; 
				$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
					
				$this->logEntryAuth('TPV PEND', $tpvresponse['logEntry']);
					
				return new Response('');
			}
			
			throw new \Exception('Error desconegut TPV');
			
		} catch (\Exception $e) {
			
			$this->logEntryAuth('TPV ERROR', $e->getMessage().'('.$tpvresponse['logEntry'].')');
				
			$subject = ':: Incidència TPV ::';
			$bccmails = array();
			$tomails = array(self::MAIL_ADMINTEST);
				
			$body = '<h1>Error TPV</h1>';
			$body .= '<h2>Missatge: '.$e->getMessage().'</h2>';
			$body .= '<p>Dades: '.$tpvresponse['logEntry'].'</p>';
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
		}
		
		return new Response("");
	}

	public function confirmapagamentAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$em = $this->getDoctrine()->getManager();
		
		$comandaId = $request->query->get('id',0);
		
		$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($comandaId);
		if ($comanda != null) {

			$tipusPagament = $request->query->get('tipuspagament', BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA);
			
			$dataAux = $request->query->get('datapagament', '');
			$dataPagament = ($dataAux!='')? \DateTime::createFromFormat('d/m/Y',$dataAux): $this->getCurrentDate();
			$dadesPagament = $request->query->get('dadespagament', '');
			$comentariPagament = $request->query->get('pagatcomentari', 'Confirmació del pagament manual');
			$this->crearRebut($dataPagament, $tipusPagament, $comanda, $dadesPagament, $comentariPagament);
			$em->flush();

			$this->logEntryAuth('CONF. PAGAMENT OK', $comandaId);
				
			return new Response("ok");
		}
		
		$this->logEntryAuth('CONF. PAGAMENT KO', $comandaId);

		return new Response("ko");
	}

	private function tpvResponse($tpvdata) {
	
		$tpvresponse = array('itemId' => 0, 'environment' => '', 'source' => '',
				'Ds_Response' => '', 'Ds_Order' => 0, 'Ds_Date' => '', 'Ds_Hour' => '',
				'Ds_PayMethod' => '', 'logEntry' => '', 'pendent' => false);
		if ($tpvdata->has('Ds_MerchantData') and $tpvdata->get('Ds_MerchantData') != '') {
			$dades = $tpvdata->get('Ds_MerchantData');
			$dades_array = explode(";", $dades);
				
			$tpvresponse['itemId'] = $dades_array[0];
			$tpvresponse['source'] = $dades_array[1];  /* Origen del pagament. Partes, duplicats */
			$tpvresponse['environment'] = $dades_array[2];
		}
	
		if ($tpvdata->has('Ds_Response')) $tpvresponse['Ds_Response'] = $tpvdata->get('Ds_Response');
		if ($tpvdata->has('Ds_Order')) $tpvresponse['Ds_Order'] = $tpvdata->get('Ds_Order');
		if ($tpvdata->has('Ds_Date')) $tpvresponse['Ds_Date'] = $tpvdata->get('Ds_Date');
		if ($tpvdata->has('Ds_Hour')) $tpvresponse['Ds_Hour'] = $tpvdata->get('Ds_Hour');
		if ($tpvdata->has('Ds_PayMethod')) $tpvresponse['Ds_PayMethod'] = $tpvdata->get('Ds_PayMethod');
	
		if (($tpvresponse['Ds_Response'] == '0930' or $tpvresponse['Ds_Response'] == '9930') and $tpvdata->get('Ds_PayMethod') == 'R') {
			$tpvresponse['pendent'] = true;
		}
		
		$tpvresponse['logEntry'] = $tpvresponse['itemId'] . "-" . $tpvresponse['source'] . "-" . 
				$tpvresponse['Ds_Response'] . "-" . $tpvresponse['environment'] . "-" . $tpvresponse['Ds_Date'] . "-" .
				$tpvresponse['Ds_Hour'] . "-" . $tpvresponse['Ds_Order'] . "-" . $tpvresponse['Ds_PayMethod'];
	
		return $tpvresponse;
	}
	
	public function notificacioTestAction(Request $request) {
		// http://www.fecdas.dev/notificaciotest
		$formBuilder = $this->createFormBuilder()->add('Ds_Response', 'text');
		$formBuilder->add('Ds_MerchantData', 'text', array('required' => false));
		$formBuilder->add('Ds_Date', 'text');
		$formBuilder->add('Ds_Hour', 'text');
		$formBuilder->add('Ds_Order', 'text');
		$formBuilder->add('Ds_PayMethod', 'text', array('required' => false));
		$formBuilder->add('accio', 'choice', array(
				'choices'   => array($this->generateUrl('FecdasBundle_notificacio') => 'FecdasBundle_notificacio',
						$this->generateUrl('FecdasBundle_notificacioOk') => 'FecdasBundle_notificacioOk',
						$this->generateUrl('FecdasBundle_notificacioKo') => 'FecdasBundle_notificacioKo'),
				'required'  => true,
		));
	
		$form = $formBuilder->getForm();
		$form->get('Ds_Response')->setData(0);
		$form->get('Ds_MerchantData')->setData('4;'.self::PAGAMENT_DUPLICAT.';dev');
		$form->get('Ds_Date')->setData(date('d/m/Y'));
		$form->get('Ds_Hour')->setData(date('h:i'));
		$form->get('Ds_Order')->setData(date('Ymdhi'));
		$form->get('Ds_PayMethod')->setData('');
	
		return $this->render('FecdasBundle:Facturacio:notificacioTest.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView())));
	}
	
		
	/********************************************************************************************************************/
	/************************************ INICI SCRIPTS CARREGA *********************************************************/
	/********************************************************************************************************************/
	
	public function resetcomandesAction(Request $request) {
		// http://www.fecdas.dev/resetcomandes?id=106313&detall=149012&factura=8380
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
		
	
		$sql = "DELETE FROM m_comandadetalls WHERE id >= ".$detall;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "UPDATE m_comandes SET factura = NULL, rebut = NULL WHERE id >= ".$id;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_factures WHERE id >= ".$factura;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_comandes WHERE id >= ".$id;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_rebuts WHERE id > 0";
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
		return new Response("");
	}
	
	public function migrahistoricAction(Request $request) {
		// http://www.fecdas.dev/migrahistoric?desde=20XX&fins=2014
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
		
		
		$strQuery = "SELECT p.id, p.importparte, p.dataalta, p.dataentradadel, p.databaixadel,";
		$strQuery .= " p.clubdel, t.descripcio as tdesc, c.categoria as ccat,";
		$strQuery .= " c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament,";
		$strQuery .= " p.dadespagament, p.importpagament,	p.comentari, p.datafacturacio, p.numfactura, ";
		$strQuery .= " COUNT(l.id) as total FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte ";
		$strQuery .= " INNER JOIN m_categories c ON c.id = l.categoria ";
		$strQuery .= " INNER JOIN m_tipusparte t ON c.tipusparte = t.id ";
		$strQuery .= " WHERE p.dataalta < '".($yearfins+1)."-01-01 00:00:00' ";
		$strQuery .= " AND p.dataalta >= '".$yeardesde."-01-01 00:00:00' ";
		$strQuery .= " GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ";
		$strQuery .= " ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, ";
		$strQuery .= " p.comentari, p.datafacturacio, p.numfactura";
		//$strQuery .= " ORDER BY p.id, csim ";
		$strQuery .= " ORDER BY p.dataentradadel, p.id ";

		/*
		 SELECT p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, t.descripcio as tdesc, c.categoria as ccat, 
		 c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, p.comentari, 
		 p.datafacturacio, p.numfactura, COUNT(l.id) as total 
		 FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte INNER JOIN m_categories c ON c.id = l.categoria 
		 INNER JOIN m_tipusparte t ON c.tipusparte = t.id WHERE p.dataalta < '2015-01-01 00:00:00' 
		 AND p.dataalta >= '2013-01-01 00:00:00' GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, 
		 p.clubdel, tdesc, ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, p.comentari, 
		 p.datafacturacio, p.numfactura ORDER BY p.dataentradadel, p.id 
		 */ 
		
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		//$partesAbans2015 = $stmt->fetchAll();
		
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
				 	echo "insert comanda parte => ". $parteid;
				 	
				 	$this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
				 	
				 	$parteid = $parte['id'];
				 	$partes = array();
				 }
				 
				 $partes[] = $parte;
				 $ipar++;

				 $em->clear();
			}
			// El darrer parte del dia
			if ($parteid > 0) $this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
			
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
		
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
			
		return new Response("");
	}

	public function migracomandesAction(Request $request) {
		// http://www.fecdas.dev/migracomandes?comanda=xx&duplicat=ss
		// http://www.fecdas.dev/migracomandes?id=xx&&current=2014
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$mincomanda = $request->query->get('comanda', 0); // min comanda
		$minduplicat = $request->query->get('duplicat', 0); // min duplicat
		$idcomanda = $request->query->get('id', 0); // id única comanda
		$current = $request->query->get('current', date('Y')); // any
	
		$batchSize = 20;
	
		$strQuery = "SELECT p.id, p.importparte, p.dataalta, p.dataentradadel, p.databaixadel,";
		$strQuery .= " p.clubdel, t.descripcio as tdesc, c.categoria as ccat,";
		$strQuery .= " c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament,";
		$strQuery .= " p.dadespagament, p.importpagament,	p.comentari, p.datafacturacio, p.numfactura, ";
		$strQuery .= " e.preu, e.iva, ";
		$strQuery .= " COUNT(l.id) as total FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte ";
		$strQuery .= " INNER JOIN m_categories c ON c.id = l.categoria ";
		$strQuery .= " INNER JOIN m_tipusparte t ON c.tipusparte = t.id ";
		$strQuery .= " INNER JOIN m_productes o ON c.producte = o.id ";
		$strQuery .= " INNER JOIN m_preus e ON e.producte = o.id ";
		$strQuery .= " WHERE e.anypreu = ".$current." " ;
		
		if ($idcomanda > 0) {
			$strQuery .= " AND p.id = ".$idcomanda." ";
		} else {
			if ($mincomanda > 0) $strQuery .= " AND p.id >= ".$mincomanda." ";
			$strQuery .= " AND p.dataalta >= '".$current."-01-01 00:00:00' ";
		}
		$strQuery .= " GROUP BY p.id, p.importparte, p.dataalta, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ";
		$strQuery .= " ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, ";
		$strQuery .= " p.comentari, p.datafacturacio, p.numfactura, e.preu, e.iva";
		$strQuery .= " ORDER BY p.id, csim ";
	
	
		/*
		"SELECT p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, t.descripcio as tdesc, 
		c.categoria as ccat, c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament, p.dadespagament, 
		p.importpagament, p.comentari, p.datafacturacio, p.numfactura, e.preu, e.iva, COUNT(l.id) as total 
		FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte INNER JOIN m_categories c ON c.id = l.categoria 
		INNER JOIN m_tipusparte t ON c.tipusparte = t.id 
		INNER JOIN m_productes o ON c.producte = o.id INNER JOIN m_preus e ON e.producte = o.id    
		
		WHERE e.anypreu = 2015 AND p.dataalta >= '2015-01-01 00:00:00' 
		GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ccat, cpro, csim, p.datapagament, 
		p.estatpagament, p.dadespagament, p.importpagament, p.comentari, p.datafacturacio, p.numfactura, e.preu, e.iva 
		ORDER BY p.id, csim "
		*/
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$partes2015 = $stmt->fetchAll();
	
		
		if ($idcomanda > 0) {
			if (count($partes2015) == 0) return new Response("CAP");	
			
			$duplicats2015 = array();
		} else {
			$strQuery = "SELECT d.*,  p.id as pid, p.descripcio as pdescripcio, e.preu as epreu, e.iva as eiva ";
			$strQuery .= " FROM m_duplicats d  INNER JOIN m_carnets c ON d.carnet = c.id ";
			$strQuery .= " INNER JOIN m_productes p ON c.producte = p.id ";
			$strQuery .= " INNER JOIN m_preus e ON e.producte = p.id ";
			$strQuery .= " WHERE e.anypreu = 2015 ";
			if ($minduplicat > 0) $strQuery .= " AND d.id >= ".$minduplicat." ";
			$strQuery .= " ORDER BY d.datapeticio ";
		
			$stmt = $em->getConnection()->prepare($strQuery);
			$stmt->execute();
			$duplicats2015 = $stmt->fetchAll();
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
			
			if (count($duplicats2015) > 0) error_log('Primer DUPLI '.$duplicats2015[0]['datapeticio']);
			error_log('Primer PARTE '.$partes2015[0]['dataentradadel']);
			if (count($duplicats2015) > 0) 'Primer DUPLI '.$duplicats2015[0]['datapeticio'].'<br/>';
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
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("FINAL");
	}
	
	
	public function updatemigrafacturesAction(Request $request) {
		// http://www.fecdas.dev/updatemigrafactures?id=
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		error_log('Inicia updatemigrafacturesAction');
		$batchSize = 20;
		$id = $request->query->get('id', 0); // min id
		try {
			$em = $this->getDoctrine()->getManager();
			
			//$em->getConnection()->beginTransaction(); // suspend auto-commit
			
			$repository = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda');
			
			$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityComanda c ";
			$strQuery .= " WHERE c.id >= :id AND c.factura IS NOT NULL ";
			$strQuery .= " ORDER BY c.id";
	
			$query = $em->createQuery($strQuery);
			$query->setParameter('id', $id);
	
			$comandes = $query->getResult();
			error_log(count($comandes). ' comandes');
			$total = 0;
			foreach ($comandes as $comanda) {
				error_log('comanda => '.$comanda->getId());
				$factura = $comanda->getFactura();
				if ($factura != null && ($factura->getDetalls() == 'null' 	|| 
										$factura->getDetalls() == '' 		||
										$factura->getDetalls() == null)) {
					
					$detalls = $comanda->getDetallsAcumulats();
					//$detalls = json_encode($detalls, JSON_UNESCAPED_UNICODE); // Desar estat detalls a la factura
					$detalls = json_encode($detalls); // Desar estat detalls a la factura
					$factura->setDetalls($detalls);
				}
				$total++;
				if ( ($total % $batchSize) == 0 ) {
					//$em->getConnection()->commit();
					$em->flush();
				}
									
			}
			$em->flush();
			error_log('Acaba updatemigrafacturesAction');
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
		return new Response("");
	}

	public function omplirdetallsfacturesAction(Request $request) {
		// http://www.fecdas.dev/omplirdetallsfactures?current=2015  => Torna a calcular detalls per factures del 2015 amb detalls ''
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		echo 'Inicia omplirdetallsfacturesAction';
		$batchSize = 20;
		$current = $request->query->get('current', date('Y')); 
		try {
			$em = $this->getDoctrine()->getManager();
			
			$desde = date('Y').'-01-01 00:00:00';
			$fins = date('Y').'-12-31 23:59:59';
			
			$em->getConnection()->beginTransaction(); // suspend auto-commit
			
			$strQuery = " SELECT f FROM FecdasBundle\Entity\EntityFactura f ";
			$strQuery .= " WHERE f.datafactura >= :desde AND f.datafactura <= :fins AND f.detalls = '' ";
			$strQuery .= " ORDER BY f.id";
	
			$query = $em->createQuery($strQuery);
			$query->setParameter('desde', $desde);
			$query->setParameter('fins', $fins);
	
			$factures = $query->getResult();
			echo count($factures). ' factures';
			$total = 0;
			foreach ($factures as $factura) {
				
				if ($factura->esAnulacio()) $comanda = $factura->getComandaanulacio();
				else $comanda = $factura->getComanda();
				
				if ($comanda == null) {
					echo "factura sense comanda " . $factura->getId(). ' ' .$factura->getNum();
					
				} else {
					echo "factura actualitzada: ".$factura->getNumFactura();
					$detalls = $comanda->getDetallsAcumulats();
					//$detalls = json_encode($detalls, JSON_UNESCAPED_UNICODE); // Desar estat detalls a la factura
					$detalls = json_encode($detalls); // Desar estat detalls a la factura
					$factura->setDetalls($detalls);
					$em->flush();
				}
				$total++;
									
			}
			$em->flush();
			echo 'Acaba omplirdetallsfacturesAction';
			$em->getConnection()->commit();
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
		return new Response("");
	}
	
	public function migraanulacomandesAction(Request $request) {
		// http://www.fecdas.dev/migraanulacomandes
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$strQuery = "SELECT a.id, a.dni, a.dataanulacio, a.codiclub,  ";
		$strQuery .= " a.dataparte, a.categoria, a.tipusparte, a.factura, a.relacio, e.preu ";
		$strQuery .= " FROM IMPORT_ANULACIONS a LEFT JOIN m_persones p ON a.dni = p.dni AND a.codiclub = p.club ";
		$strQuery .= " INNER JOIN m_categories c ON c.tipusparte = a.tipusparte AND c.simbol = a.categoria  ";
		$strQuery .= " INNER JOIN m_productes o ON c.producte = o.id ";
		$strQuery .= " INNER JOIN m_preus e ON e.producte = o.id ";
		$strQuery .= " WHERE e.anypreu = 2015 ";
		
		$strQuery .= " ORDER BY a.factura, a.relacio ";
	
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$anulacions2015 = $stmt->fetchAll();
	
		echo "Total anul·la: " . count($anulacions2015) . PHP_EOL;
	
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		$facturanum = '';
		$relacio = 0;
		$comanda = null;
		$detalls = '';
		$dataanulacio = null;
		$import = 0;
		$aficionats = 0;
		$tecnics = 0;
		$infantils = 0;
		$current = date('Y');
		
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		
		try {
			foreach ($anulacions2015 as $anulacio) {
				
				if ($facturanum == '' ) {
					$facturanum = $anulacio['factura'];
					$dataanulacio = \DateTime::createFromFormat('d/m/Y', $anulacio['dataanulacio']);
					$import = $anulacio['preu'];
					
					$aficionats = ($anulacio['categoria']=='A'?1:0);
					$tecnics = ($anulacio['categoria']=='T'?1:0);
					$infantils = ($anulacio['categoria']=='I'?1:0);
					
					$relacio = $anulacio['relacio'];
					
					$query = $em->createQuery("SELECT p FROM FecdasBundle:EntityParte p 
											WHERE p.numrelacio = :rel AND 
											p.dataalta >= '2015-01-01 00:00:00' AND
											p.club = :club AND
											p.databaixa IS NULL	")->setParameter('rel', $relacio)
											->setParameter('club', $anulacio['codiclub']);
					
					$parte = $query->setMaxResults(1)->getOneOrNullResult();
					
					if ($parte == null) {
						echo "ERROR ANULA 1 comanda no trobada => relacio  ".$relacio."<br/>";
						return new Response("");
					}
				}

				if ($facturanum != $anulacio['factura']) {
					// Consolidar factura
					$concepte = 'Anul·lació. ';
					if ($aficionats > 0) $concepte .= $aficionats.'x Aficionats ';
					if ($tecnics > 0) $concepte .= $tecnics.'x Tècnics ';
					if ($infantils > 0) $concepte .= $infantils.'x Infantils ';
					
					echo "***********************<br/>";
					echo "factura  ".$facturanum."<br/>";
					echo "data  ".$dataanulacio->format('Y-m-d')."<br/>";
					echo "import  ".$import."<br/>";
					echo "concepte  ".$concepte."<br/>";
					echo "detalls  ".$detalls."<br/>";
					echo "***********************<br/>";
					echo "<br/>";
					
					
					if ($current < date('Y')) $facturaId = $this->inserirFactura('2014-12-31', str_replace('/2014', '', $facturanum), (-1)*$import, $concepte); // 2014
					else $facturaId = $this->inserirFactura($dataanulacio->format('Y-m-d'), str_replace('/2015', '', $facturanum), (-1)*$import, $concepte);
					
					$factura = $this->getDoctrine()->getRepository('FecdasBundle:EntityFactura')->findOneById($facturaId);
					
					$parte->addFacturaanulacio($factura);
					$factura->setComandaanulacio($parte);
					$factura->setComanda(null);
					//$factura->setDetalls(json_encode($detalls));
					$factura->setDetalls($detalls);
					
					// Actualitzar dades
					$facturanum = $anulacio['factura'];
					$dataanulacio = \DateTime::createFromFormat('d/m/Y', $anulacio['dataanulacio']);
					$import = $anulacio['preu'];
					$aficionats = ($anulacio['categoria']=='A'?1:0);
					$tecnics = ($anulacio['categoria']=='T'?1:0);
					$infantils = ($anulacio['categoria']=='I'?1:0);
				;
				} else {
					$import += $anulacio['preu'];
					$aficionats += ($anulacio['categoria']=='A'?1:0);
					$tecnics += ($anulacio['categoria']=='T'?1:0);
					$infantils += ($anulacio['categoria']=='I'?1:0);
					
					
				}
								
				if ($relacio != $anulacio['relacio'] ) {
					$relacio = $anulacio['relacio'];
					$detalls = '';
					$current = date('Y');
					$query = $em->createQuery("SELECT p FROM FecdasBundle:EntityParte p 
											WHERE p.numrelacio = :rel AND 
											p.dataalta >= '2015-01-01 00:00:00' AND
											p.club = :club AND
											p.databaixa IS NULL	")->setParameter('rel', $relacio)
											->setParameter('club', $anulacio['codiclub']);
					
					$parte = $query->setMaxResults(1)->getOneOrNullResult();
					if ($parte == null) {
						// Provar 2014 
						$query = $em->createQuery("SELECT p FROM FecdasBundle:EntityParte p 
											WHERE p.numrelacio = :rel AND 
											p.dataalta >= '2014-01-01 00:00:00' AND
											p.club = :club AND
											p.databaixa IS NULL	")->setParameter('rel', $relacio)
											->setParameter('club', $anulacio['codiclub']);
					
						$parte = $query->setMaxResults(1)->getOneOrNullResult();
						
						if ($parte == null) {
							echo "ERROR ANULA 3 comanda no trobada => relacio  ".$relacio."-".$anulacio['codiclub']."<br/>";
							//return new Response("");
							continue;
						} 
						$current = date('Y')-1;
					}	
				
				}
				$llicencia = null;
				//echo '?'. $anulacio['dni']."<br/>";
				foreach ($parte->getLlicencies() as $llic) {
					//if (!$llic->esBaixa()) {
						//echo '=>'. $llic->getPersona()->getDni()."<br/>";
						if ($llic->getPersona()->getDni() == $anulacio['dni']) $llicencia = $llic;
					//}
				}
				if ($llicencia == null) {
					echo "ERROR ANULA 4 llicencia no existeix per  ".$anulacio['dni']." relacio ".$relacio."<br/>";
					return new Response("");
				}
				//$detalls = $parte->getDetallsAcumulats(); // sense baixes
				
				if ($detalls == '') $detalls = 'baixes: '.$llicencia->getPersona()->getNomCognoms();
				else $detalls .= ', '.$llicencia->getPersona()->getNomCognoms();
				
			}
			$em->flush();
			
			$em->getConnection()->commit();
			
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("FINAL");
	}
	
	
	public function migraaltresAction(Request $request) {
		// http://www.fecdasnou.dev/migraaltres?num=0
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$num = $request->query->get('num', 0); // min num
		$codi = $request->query->get('codi', 0); // Club
		
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
	
		$strQuery = " SELECT * FROM m_clubs c ";
		if ($codi != '') $strQuery .= " WHERE c.codi = '".$codi."'";
		else $strQuery .= " ORDER BY c.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$compteClub = '';
		$clubs = array();
		while (isset($aux[$i])) {
			$clubs[ $aux[$i]['compte'] ] = $aux[$i];
			if ($codi != '' && $aux[$i]['codi'] == $codi) $compteClub = $aux[$i]['compte'];  
			$i++;
		}
	
		echo '-'.$compteClub.'-';
		 
		
		
		// Clubs: 4310000 - 4310999
		// Productes: 6230300  - 7590006  (Llicències 7010000 - 7010025) (Duplicats 7090000, 7590000, 7590002, 7590004)
	
		// SELECT num, COUNT(dh) FROM `apunts_2015` WHERE dh = 'D' GROUP BY num HAVING COUNT(dh) > 1	=> 3,5,11,13,16,18
	
		// SELECT num FROM apunts_2015 WHERE (compte >= 4310000 AND compte <= 4310999) OR
		//			(compte >= 6230300 AND compte < 7010000 AND compte > 7010025 AND compte <= 7590006 AND compte NOT IN (7090000, 7590000, 7590002, 7590004) )
	
		if ($compteClub != '') {
			$strQuery = "SELECT DISTINCT num FROM apunts_2015 a WHERE compte = ".$compteClub;
			if ($num > 0) $strQuery .= " AND num = ".$num;
			$strQuery .= " ORDER BY a.num ";
			$stmt = $em->getConnection()->prepare($strQuery);
			$stmt->execute();
			$numapuntsClub = $stmt->fetchAll();
			
			if (count($numapuntsClub) == 0) {
				echo "sense apunts pel compte ".$compteClub;
				exit;
			}
			
			$apuntsClub = array(); 
			foreach ($numapuntsClub as $key => $value) {
				//echo json_encode($value);
				$apuntsClub[] = $value['num'];
			}
				
			$strQuery = "SELECT * FROM apunts_2015 a WHERE num IN (".implode(", ", $apuntsClub).") ORDER BY a.num";
			$stmt = $em->getConnection()->prepare($strQuery);
			$stmt->execute();
			$altress2015 = $stmt->fetchAll();
			
		} else {
			$strQuery = "SELECT * FROM apunts_2015 a ";
			//$strQuery .= " ORDER BY a.data, a.num, a.dh ";
			if ($num > 0) $strQuery .= " WHERE num >= ".$num;
			$strQuery .= " ORDER BY a.num ";
		
			$stmt = $em->getConnection()->prepare($strQuery);
			$stmt->execute();
			$altress2015 = $stmt->fetchAll();
	
		}
	
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
			$sortir = false;
			$persist = true;
			$facturesRebutsPendents = array(); 
			while (isset($altress2015[$iapu]) && $sortir == false) {
				
				$altre = $altress2015[$iapu];
				echo '=>'. $altre['num'].'-'.$iapu.'<br/>';	
				if ($altrenum == 0) $altrenum = $altre['num'];
				if ($altrenum != $altre['num']) {
					// Agrupar apunts
					$this->insertComandaAltre($clubs, $productes, $altres, $maxnums, $facturesRebutsPendents, $persist, true);
						
					$altrenum = $altre['num'];
					$altres = array();
				}
					
				if ($altre['dh'] == 'D') $altres['D'][] = $altre;
				else $altres['H'][] = $altre;
				$iapu++;
				if ($iapu > 5000 && $altre['dh'] == 'D') $sortir = true;

			}
			// La darrera comanda altre del dia
			if ($altrenum > 0) $this->insertComandaAltre($clubs, $productes, $altres, $maxnums, $facturesRebutsPendents, $persist, true);
			
			echo "Apunt FINAL ".$iapu."<br/>";
			
			// Mirar factures pendents
			error_log(str_repeat("=", 40))." ".count($facturesRebutsPendents);
			echo str_repeat("=", 40)." ".count($facturesRebutsPendents)."<br/>";
			echo json_encode($facturesRebutsPendents)."<br/>";
			/*foreach ($facturesRebutsPendents as $k =>  $factura) {
				error_log("ERROR REBUTS/INGRESOS 9 Factura -".($k)."- no trobada, concepte =>".$factura['concepte'].
						"<= => id: ".$factura['id'].", anotació: ".$factura['num'].", de compte: ".$factura['compte']);
				echo "ERROR REBUTS/INGRESOS 9 Factura -".($k)."- no trobada, concepte =>".$factura['concepte'].
						"<= => id: ".$factura['id'].", anotació: ".$factura['num'].", de compte: ".$factura['compte'].")<br/>";
			}*/
			$em->getConnection()->commit();
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("FINAL");
	}
	
		
	
	
	
	private function insertComandaAltre($clubs, $productes, $altres, &$maxnums, &$facturesRebutsPendents, $persist = false, $warning = true) {
		
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
			$anyFactura = substr($data, 0, 4);  // 2015-10-02
			if (count($altres['H']) == 0) {
				error_log("ERROR 1 CAP 'H' = >".$id." ".$num);
				return;
			}
			
			
			//Mirar Apunts debe entre comptes  4310001 i 4310999 excepte (4310149)
			
			if ($compteD >= 4310001 && $compteD <= 4310999 && $compteD != 4310149) {
				// Deutor club, Factura 
				if ( !isset( $clubs[$compteD])) {
					error_log($id." ".$num."WARNING - COMANDES 15 Club deutor no existeix=> ".$compteD);
					if  ($warning == true) echo $id." ".$num."WARNING - COMANDES 15 Club deutor no existeix=> ".$compteD."<br/>";
					return;
				}
				
				$club = $clubs[$compteD];
				$concepteAux = $altres['D'][0]['concepte'];
				$numFactura = $this->extraerFactures(strtolower($concepteAux), 'F');
				
				if (!is_numeric($numFactura)) {
					error_log($id." ".$num."ERROR COMANDA 16 Número de factura no numèrica=> ".$numFactura."(".$compteD.")");
					echo $id." ".$num."ERROR COMANDA 16 Número de factura no numèrica=> ".$numFactura."(".$compteD.")<br/>";
					return;
				}
				
				$import = $altres['D'][0]['importapunt'];

				// Revisió	
				$importDetalls = 0;
				$tipusAnterior = 0;
				$actualCompteDuplicat = '';
				$compteH = '';
				for ($i = 0; $i < count($altres['H']); $i++) {
				
					$compteH = $altres['H'][$i]['compte'];
					
					if (!isset( $productes[$compteH])) {
						error_log($id." ".$num."ERROR COMANDA 17 producte no existeix=> ".$compteH);
						echo $id." ".$num."ERROR COMANDA 17 producte no existeix=> ".$compteH."<br/>";
						return;
					}
						
					$producte = $productes[$compteH];
					//if ($tipusAnterior == 0 && $producte['tipus'] != 6290900) $tipusAnterior = $producte['tipus'];  // Correus no compta
					if ($tipusAnterior == 0 && $compteH != 6290900) $tipusAnterior = $producte['tipus'];  // Correus no compta
					

					//if ($producte['tipus'] != $tipusAnterior && $producte['tipus'] != 6290900) {
					if ($producte['tipus'] != $tipusAnterior && $compteH != 6290900) {
						if ($warning == true) {
							error_log($id." ".$num."WARNING COMANDA 18 tipus productes barrejats:".$tipusAnterior."=> ".$compteH);
							echo $id." ".$num."WARNING COMANDA 18 tipus productes barrejats:".$tipusAnterior."=> ".$compteH."<br/>";
						}
						$tipusAnterior = BaseController::TIPUS_PRODUCTE_ALTRES;
					}
						
					$importDetalls += $altres['H'][$i]['importapunt'];
						
					if ($tipusAnterior == BaseController::TIPUS_PRODUCTE_DUPLICATS) $actualCompteDuplicat = $compteH;
				}
				
				if (abs($import - $importDetalls) > 0.01)  {
					error_log($id." ".$num."ERROR COMANDA 20 suma detalls incorrecte=> D ".$altres['D'][0]['num']." - ".$altres['D'][0]['concepte']." ==> H ".json_encode($altres['H']));
					echo $id." ".$num."ERROR COMANDA 20 suma detalls incorrecte=> D ".$altres['D'][0]['num']." - ".$altres['D'][0]['concepte']."(".$import.")".
							" ==> H ".json_encode($altres['H'])."(".$importDetalls.")"."<br/>";
					return;
				}

				if ($tipusAnterior == 0 && $compteH == '6290900') $tipusAnterior = BaseController::TIPUS_PRODUCTE_ALTRES; // Assentaments només correu
			
				if ($tipusAnterior <= 0 || $tipusAnterior > 6)  {
					error_log($id." ".$num."ERROR 22 tipus producte desconegut:".$tipusAnterior." => ".$compteH);
					echo $id." ".$num."ERROR 22 tipus producte desconegut".$tipusAnterior." => ".$compteH."<br/>";
					$tipusAnterior = BaseController::TIPUS_PRODUCTE_LLICENCIES;
					return;
				}
				
				$textComanda = 'COMANDA '.BaseController::getTipusProducte($tipusAnterior).' '.$concepteAux;
				
				$facturaId = 0;
				$comandaId = 0;	
				
				switch ($tipusAnterior) {
				case BaseController::TIPUS_PRODUCTE_LLICENCIES:
					// Validar que existeix el parte. Si no troba la factura alta parte amb WARNING
					$pos = strpos($concepteAux, '/2014');
					if ($pos !== false) $anyFactura = 2014; 
					$factExistent = $this->consultarFactura($numFactura, $anyFactura);
					if ($factExistent == null) {
						if ($import > 0) {
							error_log($id." ".$num."WARNING COMANDA 9 Factura no trobada. Crear factura i comanda nova llicències per factura ".$numFactura." => ".$compteD);
							if  ($warning == true) echo $id." ".$num."WARNING COMANDA 9 Factura no trobada. Crear factura i comanda nova llicències per factura ".$numFactura." => ".$compteD."<br/>";
	if ($persist == false) return;
							$facturaId = $this->inserirFactura($data, $numFactura, $import, "Factura - ".$textComanda);
							$comandaId = $this->inserirComandaAmbDetalls($data, $club['codi'], $maxnums, $textComanda, 0, $facturaId, $altres['H'], $productes);
						} else {
							error_log($id." ".$num."REVISAR 15 Factura anul·lació llicencies. Crear factura i afegir a anul·lació ".$numFactura." => ".$compteD);
							echo $id." ".$num."REVISAR 15 Factura anul·lació llicències. Crear factura i afegir a anul·lació ".$numFactura." => ".$compteD."<br/>";
						return;
						}
						
					} else {
						// Validar factures import diferent
						if ($factExistent['import'] != $import) {
							error_log($id." ".$num."WARNING COMANDA 10 Factura imports incorrectes. Actualitzar import factura ".$numFactura." => ".$compteD);
							if  ($warning == true) echo $id." ".$num."WARNING COMANDA 10 Factura imports incorrectes. Actualitzar import factura ".$numFactura." => ".$compteD."<br/>";
	if ($persist == false) return;							
							$query = "UPDATE m_factures SET import = ".$import." WHERE id = ". $facturaId;
							$em->getConnection()->exec( $query );					
						}
					}
						
					break;
				case BaseController::TIPUS_PRODUCTE_DUPLICATS:  // Duplicats 7590002 7590000 7590004 7090000
				case BaseController::TIPUS_PRODUCTE_KITS:
				case BaseController::TIPUS_PRODUCTE_MERCHA:
				case BaseController::TIPUS_PRODUCTE_CURSOS:
				case BaseController::TIPUS_PRODUCTE_ALTRES:
					// Validar que existeix el parte. Cal inserir la Factura
					// Insertar factura
					if ($import > 0) {
	if ($persist == false) return;
						$facturaId = $this->inserirFactura($data, $numFactura, $import, "Factura - ".$textComanda);
						if ($tipusAnterior != BaseController::TIPUS_PRODUCTE_DUPLICATS) {
							// Insertar comanda
							$comandaId = $this->inserirComandaAmbDetalls($data, $club['codi'], $maxnums, $textComanda, 0, $facturaId, $altres['H'], $productes);
						} else {
							//Buscar la comanda del club encara sense factura
							$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityDuplicat c INNER JOIN c.detalls d INNER JOIN d.producte p";
							$strQuery .= " WHERE c.club = :codi ";
							$strQuery .= " AND c.datapeticio <= :data ";
							$strQuery .= " AND c.databaixa IS NULL ";
							$strQuery .= " AND c.factura IS NULL ";
							$strQuery .= " AND d.unitats = 1 ";
							$strQuery .= " AND p.codi = :compte ";
							$strQuery .= " ORDER BY c.datapeticio DESC ";
						
							$query = $em->createQuery($strQuery);
							$query->setParameter('codi', $club['codi']);
							$query->setParameter('data', $data);
							$query->setParameter('compte', $actualCompteDuplicat);  
						
							$result = $query->getResult();
							
							if (count($result) >= 1) $comandaId = $result[0]->getId(); 
							else {
								$comandaId = $this->inserirComandaAmbDetalls($data, $club['codi'], $maxnums, $textComanda, 0, $facturaId, $altres['H'], $productes);
								
								error_log($id." ".$num."WARNING REBUTS/INGRESOS 12 Comanda duplicat ".$actualCompteDuplicat." no trobada => ".$compteD." ".$club['codi']." ".$numFactura);
								if ($warning == true) echo $id." ".$num."WARNING REBUTS/INGRESOS 12 Comanda duplicat ".$actualCompteDuplicat." no trobada => ".$compteD." ".$club['codi']." ".$numFactura."<br/>";
								
								return;
							}
						}
					} else {
						error_log($id." ".$num."REVISAR 17 Factura anul·lació altres. Crear factura i afegir a anul·lació ".$numFactura." => ".$compteD);
						echo $id." ".$num."REVISAR 17 Factura anul·lació altres.  Crear factura i afegir a anul·lació ".$numFactura." => ".$compteD."<br/>";
						return;
					}

					break;
				}
		
				if ($facturaId != 0 && $comandaId != 0) {
					$this->updateComandaNumFactura($comandaId, $facturaId);	
				} else {
					if ($tipusAnterior != BaseController::TIPUS_PRODUCTE_LLICENCIES) {
						error_log($id." ".$num."ERROR REBUTS/INGRESOS 13  insercions facturaId: ".$facturaId." comandaId: ".$comandaId." REVISAR ".$tipusAnterior."=> ".$compteD. " ".$numFactura);
						echo $id." ".$num."ERROR REBUTS/INGRESOS 13  insercions facturaId: ".$facturaId." comandaId: ".$comandaId." REVISAR ".$tipusAnterior."=> ".$compteD. " ".$numFactura."<br/>";
						return;
					}
				}			
			}
		
		
			if ($compteD >= 5720000 && $compteD <= 5720005) {
				// Rebut ingrés, següent compte club
					
				// Camp concepte està la factura:  	FW:00063/2015 o Factura: 00076/2015
				// Camp document està el rebut: 	00010/15
					
				if (count($altres['H']) != 1) {
					error_log("ERROR 2 => ".$num);
					return;
				}
				
				$compteH = $altres['H'][0]['compte'];
				
				//Mirar Apunts haber a comptes  4310001 i 4310999 excepte (4310149)  => Clubs
				if ($compteH >= 4310001 && $compteH <= 4310999 && $compteH != 4310149) {
					
					if ( !isset( $clubs[$compteH])) {
						error_log($id." ".$num."WARNING REBUTS/INGRESOS - 7 Club pagador no existeix=> ".$compteH);	
						if  ($warning == true) echo $id." ".$num."WARNING REBUTS/INGRESOS 7 Club pagador no existeix=> ".$compteH."<br/>";
						return;
					}
					
					if ($altres['D'][0]['concepte'] != $altres['H'][0]['concepte'] ||
						$altres['D'][0]['document'] != $altres['H'][0]['document'] ||
						$altres['D'][0]['importapunt'] != $altres['H'][0]['importapunt']) {
							error_log($id." ".$num."ERROR 3 CAP 'H'");
							echo $id." ".$num."ERROR 3 CAP 'H'<br/>";
							return;
					}
					
					$concepteAux = $altres['D'][0]['concepte'];	// FW:00063/2015 o Factura: 00076/2015 o Fra. 2541/2015 o FW:00510/2015 o F. 1456/2014
					$ingres = true; 
					$numFactura = 'NA';
	
					$datapagament = $data;
					$dataentrada = $data; 
					$dadespagament = null;
					$comentari = str_replace("'","''", $altres['D'][0]['concepte']);
	
					$rebutAux = $altres['D'][0]['document'];		// 00010/15
					$numRebut = substr($rebutAux, 0, 5);
							
					$numRebut = $numRebut * 1;  // Number
					$import = $altres['D'][0]['importapunt'];
	
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
							error_log($id." ".$num."ERROR REBUTS/INGRESOS 8 Tipus de pagament desconegut => ".$id." ".$num." ".$compteD);
							echo $id." ".$num."ERROR REBUTS/INGRESOS 8 Tipus de pagament desconegut => ".$compteD."<br/>";
							return;
											
							break;
					}
					
					$numFactures = $this->extraerFactures(strtolower($concepteAux), 'R');
					
					if ($numFactures != null) {
						// Ok, trobadaes 
					
						$comandesIdPerActualitzar = array();
											
						foreach ($numFactures as  $numFactura) {
		
							$pos = strpos(strtolower($concepteAux), '/2014');
							if ($pos !== false) $anyFactura = 2014; 
							
							$factExistent = $this->consultarFactura($numFactura, $anyFactura);
								
							if ($factExistent == null) {
								//error_log("ERROR REBUTS/INGRESOS 9 Factura -".($numFactura)."- no trobada, concepte =>".$concepteAux."<= => id: ".$id.", anotació: ".$num.", de compte: ".$compteD." a compte: ".$compteH." (".$clubs[$compteH]['nom']);
								//echo "ERROR REBUTS/INGRESOS 9 Factura -".($numFactura)."- no trobada, concepte =>".$concepteAux."<= => id: ".$id.", anotació: ".$num.", de compte: ".$compteD." a compte: ".$compteH." (".$clubs[$compteH]['nom'] .")<br/>";
								
								// S'ha fet l'ingrés la comanda encara no ha arribat, desar a un array
								$facturesRebutsPendents[$numFactura] =  array('id' => $id, 'num' => $num, 'compte' => $compteD, 'factura' => $numFactura,
																			'data' => $data, 'import' => $import, 'concepte' => $concepteAux, 
																			'rebut' => $numRebut, 'datapagament' => $datapagament, 'tipuspagament' => $tipuspagament,
																			'club' => $clubs[$compteH]['codi'], 'comandes' => $comandesIdPerActualitzar, 
																			'comentari' => $comentari, 'dadespagament' => $dadespagament );
															 	
								return;
							} else {
								// Count no funciona bé
								//$em->getConnection()->executeUpdate("UPDATE m_factures SET datapagament = '".$data."' WHERE id = ".$factExistent['id']);
											
								$statement = $em->getConnection()->executeQuery("SELECT * FROM m_comandes WHERE factura = ".$factExistent['id']);
								$comanda = $statement->fetch();
									
								if ($comanda == null) {
									echo json_encode($factExistent);
									error_log($id." ".$num."ERROR REBUTS/INGRESOS 11 Comanda no trobada => ".$compteD. " ".$numFactura);
									echo $id." ".$num."ERROR REBUTS/INGRESOS 11 Comanda no trobada => ".$compteD. " ".$numFactura."<br/>";
									return;
								}
									
							}
							
							$comandesIdPerActualitzar[] = $comanda['id'];
						}
		if ($persist == false) return;
						$rebutId = $this->inserirRebut($datapagament, $numRebut, $import, $tipuspagament, $clubs[$compteH]['codi'], $dataentrada, $comentari, $dadespagament);
						
						foreach ($comandesIdPerActualitzar as  $comandaId) {	
								
							$query = "UPDATE m_comandes SET rebut = ".$rebutId." WHERE id = ". $comandaId;
							$em->getConnection()->exec( $query );
						}
						
					} else {
						// cercar ingrés
						
						$concepteAux = strtolower( $altres['D'][0]['concepte'] );	// 	Ingres a compte saldo Club
						$pos1 = strpos($concepteAux, 'ingres');
						$pos2 = strpos($concepteAux, 'ingrés');
						$pos3 = strpos($concepteAux, 'compte');
						$pos4 = strpos($concepteAux, 'liquida');
						
						if ($pos1 === false &&
							$pos2 === false &&
							$pos3 === false &&
							$pos4 === false) {
								error_log($id." ".$num."WARNING REBUTS/INGRESOS 5 no detectat ni factura ni ingrés => ".$compteD." => ".$concepteAux);
								if ($warning == true) echo $id." ".$num."WARNING REBUTS/INGRESOS 5 no detectat ni factura ni ingrés => ".$compteD." => ".$concepteAux."<br/>";
						}
		if ($persist == false) return;
						$rebutId = $this->inserirRebut($datapagament, $numRebut, $import, $tipuspagament, $clubs[$compteH]['codi'], $dataentrada, $comentari, $dadespagament);
						
					}
					$em->getConnection()->commit();
					$em->getConnection()->beginTransaction(); // suspend auto-commit
						
				}
		
			}	
		} else {
			// Varis o ningún 'D'
			/*if (count($altres['D']) == 0) error_log("CAP 'D' = >".$altres['H'][0]['num']." -".count($altres['D'])."-|-".count($altres['H'])."- " );
			else error_log("VARIS = >".$altres['D'][0]['num']." -".count($altres['D'])."-|-".count($altres['H'])."- " );*/
		}
	}
	
	private function consultarFactura($numFactura, $anyFactura) {
		//error_log("****** consultar factura .".$numFactura."/".$anyFactura);
		$em = $this->getDoctrine()->getManager();
		$statement = $em->getConnection()->executeQuery("SELECT * FROM m_factures WHERE num = ".($numFactura*1)." AND YEAR(datafactura) = ".$anyFactura. " ORDER BY id DESC");
		$factExistent = $statement->fetch();
		return $factExistent;
	}
	
	private function inserirFactura($data, $numFactura, $import, $concepte = '') {
		$em = $this->getDoctrine()->getManager();
		
		$query = "INSERT INTO m_factures (datafactura, num, import, concepte, dataentrada, comptabilitat) VALUES ";
		$query .= "('".$data."',".$numFactura.",".$import.",'".$concepte."'";
		$query .= ",'".$data."', 1)";
						
		$em->getConnection()->exec( $query );
						
		$facturaId = $em->getConnection()->lastInsertId();
		
		$em->getConnection()->commit();
		$em->getConnection()->beginTransaction(); // suspend auto-commit

		//error_log("****** inserir factura .".$numFactura." => ".$facturaId);		
		return $facturaId;
		
	}
	
	private function inserirRebut($datapagament, $numRebut, $import, $tipuspagament, $codiClub, $dataentrada, $comentari = '', $dadespagament = null) {
		$em = $this->getDoctrine()->getManager();
		
		$query = "INSERT INTO m_rebuts (datapagament, num, import, dadespagament, tipuspagament, comentari, dataentrada, club, comptabilitat) VALUES ";
		$query .= "('".$datapagament."',".$numRebut.",".$import;
		$query .= ", ".($dadespagament==null?"NULL":"'".$dadespagament."'").",".$tipuspagament.",'".$comentari."','".$dataentrada."'";
		$query .= ",'".$codiClub."',1)";
		
		$em->getConnection()->exec( $query );
		$rebutId = $em->getConnection()->lastInsertId();
		
		//error_log("****** inserir rebut .".$numRebut." => ".$rebutId);
		return $rebutId; 
	}
	
	private function updateComandaNumFactura($comandaId, $facturaId) {
		$em = $this->getDoctrine()->getManager();
		$comanda = $em->getRepository('FecdasBundle:EntityComanda')->find($comandaId);
		$detalls = $comanda->getDetallsAcumulats();
		//$detalls = json_encode($detalls, JSON_UNESCAPED_UNICODE); // Desar estat detalls a la factura
		$detalls = json_encode($detalls); // Desar estat detalls a la factura
		
		$factura = $em->getRepository('FecdasBundle:EntityFactura')->find($facturaId);
		$factura->setDetalls($detalls);
		//error_log("****** update comanda .".$comandaId."-".$facturaId);
		$em->flush();
	}
	
	private function inserirComandaAmbDetalls($data, $codiClub, $maxnums, $descripcio = '', $rebutId = 0, $facturaId = 0, $detalls = array(), $productes) {
		// Insertar comanda
		$em = $this->getDoctrine()->getManager();
		
		$query = "INSERT INTO m_comandes (comentaris, dataentrada, databaixa, club, num, rebut,factura, tipuscomanda) VALUES ";
		$query .= "('".$descripcio."', '".$data."'";
		$query .= ",NULL,'".$codiClub."', 0 ";
		$query .= ", ".($rebutId==0?"NULL":$rebutId).", ".($facturaId==0?"NULL":$facturaId).",'A')";
				
		$maxnums['maxnumcomanda']++;
					
		$em->getConnection()->exec( $query );
						
		$comandaId = $em->getConnection()->lastInsertId();
						
		for ($i = 0; $i < count($detalls); $i++) {
			// Insertar detall
			$compteH = $detalls[$i]['compte'];
			$producte = $productes[$compteH];
			$import = $detalls[$i]['importapunt'];
			
			$total = $producte['preu']==0 ? 1 : round($detalls[$i]['importapunt']/$producte['preu']);
			$anota = $total.'x'.str_replace("'","''",$producte['descripcio']);
							
			$preuunitat = ($total == 0?$import:round($import/$total,2));
						
			$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, preuunitat, ivaunitat, descomptedetall, anotacions, dataentrada) VALUES ";
			$query .= "(".$comandaId.",".$producte['id'].",".$total.",".$preuunitat.",".($producte['iva']!=null?$producte['iva']:"NULL").", 0, '".$anota."',";
			$query .= "'".$data."')"; 
						
			$em->getConnection()->exec( $query );
		}
		
		$query = "UPDATE m_comandes SET num = ".$comandaId." WHERE id = ". $comandaId;		
		
		$em->getConnection()->exec( $query );
		
		$em->getConnection()->commit();
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		//error_log("****** inserir comanda amb detalls .".$comandaId." => ".count($detalls));
		return $comandaId;
	}				
	
	private function extraerFactures($concepte, $tipus) {
		$numFactura = 'NA';
		if ($tipus == 'R') { // Rebut				
			$pos = strpos($concepte, 'factura');
			if ($pos === false) {
				$pos = strpos($concepte, 'fra');
				if ($pos === false) {
					$pos = strpos($concepte, 'fw');
					if ($pos === false) {
						$pos = strpos($concepte, 'Factura: 1117-1115/2015');
						if ($pos === false) {
							$numFactura = substr($concepte, 3, 4);  // F. 1234/2014
						} else {
							 return array(1117, 1115);
						}
					}
					else $numFactura = substr($concepte, 3, 5);
				}
				else $numFactura = substr($concepte, 5, 4);
			}
			else $numFactura = substr($concepte, 9, 5);
			
			if (is_numeric($numFactura)) return array( $numFactura );  // Trobada
				
			// factura: 1234-1388-1448/2015
			$concepte = str_replace('factura:', '', $concepte);
			$concepte = str_replace('factura', '', $concepte);
			$concepte = str_replace('fra.', '', $concepte);
			$concepte = str_replace('fra', '', $concepte);
			$concepte = str_replace('/2015', '', $concepte);
			$concepte = str_replace('/2014', '', $concepte);
			$concepte = str_replace('/201', '', $concepte);
			$concepte = str_replace('/', '', $concepte);
			$concepte = str_replace('- 150´-', '', $concepte);
			
			$testArrayFactures = explode("-", trim($concepte));
			
			foreach ($testArrayFactures as $key => $numFactura) {
				if (!is_numeric( trim($numFactura) )) return null;
			}
			return $testArrayFactures;
				
		}
			
		// SELECT * FROM `apunts_2015` WHERE `compte` BETWEEN 4310000 AND 4310999 AND dh = 'D'
		// W-F0063_ 10636110MAS 1AFC_  o F0019_1AFC_
		if ($tipus == 'F') { // Factura
			$pos = strpos($concepte, 'w-f');
			if ($pos === false) {
				$pos = strpos($concepte, 'fra');
				if ($pos === false) { 
					$pos = strpos($concepte, 'pago s/fra. ');
					if ($pos === false) { 
						$pos = strpos($concepte, 'retroces pago f.');
						if ($pos === false) {
							$pos = strpos($concepte, 'F1217_1K3E');
							if ($pos === false) { 
								$numFactura = substr($concepte, 1, 4);
							} else {
								return 1217;
							} 
						}
						else {
							$numFactura = substr($concepte, $pos, 4);
						}
					}
					else {
						$numFactura = substr($concepte, $pos, 2);
					}
				}	
				else $numFactura = substr($concepte, 5, 4);
			}
			else $numFactura = substr($concepte, 3, 4);
		}				
		if (!is_numeric($numFactura)) return $concepte."-".$numFactura."-";
		return trim($numFactura);
	}
				
	
	
	private function insertComandaDuplicat($clubs, $duplicat, &$maxnums, $flush = false) {
	
		$em = $this->getDoctrine()->getManager();
	
		$preu = $duplicat['epreu'];
		$iva = $duplicat['eiva'];
		
		$desc = str_replace("'","''",$duplicat['pdescripcio']);
		$observa = str_replace("'","''",$duplicat['observacions']);
	
		$query = "INSERT INTO m_comandes (id, comentaris, dataentrada, databaixa, club, num, tipuscomanda) VALUES ";
		$query .= "(".$duplicat['id'].", '".$desc."','".$duplicat['datapeticio']."'";
		$query .= ",".($duplicat['databaixadel']==null?"NULL":"'".$duplicat['databaixadel']."'").",'".$duplicat['clubdel']."',".$duplicat['id'].",'D')";
	
		$em->getConnection()->exec( $query );
	
		$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, preuunitat, ivaunitat, descomptedetall, anotacions, dataentrada) VALUES ";
		$query .= "(".$duplicat['id'].",".$duplicat['pid'].", 1,".$preu.",".($iva!=null?$iva:"NULL").", 0, ".($duplicat['observacions']==null?"NULL":"'".$observa."'").",";
		$query .= "'".$duplicat['datapeticio']."')";

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
			$desc = $parte['total'].'x'.str_replace("'","''",$parte['tdesc']);
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
	
	
				$datafactura = ($parte['datafacturacio'] == '' || 
								$parte['datafacturacio'] == null ||
								substr($parte['datafacturacio'], 0, 4) != substr($parte['dataalta'], 0, 4)?
								$parte['dataalta']:$parte['datafacturacio']); // canvi d'any agafar dataalta
				
	
				$query = "INSERT INTO m_factures (datafactura, num, import, concepte, dataentrada, comptabilitat) VALUES ";
				$query .= "('".$datafactura."',".$factArray[0].",".$parte['importparte'].",'".$textLlista."'";
				$query .= ",'".$parte['dataentradadel']."', 1)";
	
				$em->getConnection()->exec( $query );
	
				$facturaId = $em->getConnection()->lastInsertId();
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
			}
				
			$query = "INSERT INTO m_comandes (id, comentaris, dataentrada, databaixa, club, num, rebut,factura, tipuscomanda) VALUES ";
			$query .= "(".$parte['id'].", '".$desc."', '".$parte['dataentradadel']."'";
			//$query .= ",".($parte['databaixadel']==null?"NULL":"'".$parte['databaixadel']."'").",'".$parte['clubdel']."',".$maxnums['maxnumcomanda'];
			$query .= ",".($parte['databaixadel']==null?"NULL":"'".$parte['databaixadel']."'").",'".$parte['clubdel']."',".$parte['id'];
			$query .= ", ".($rebutId==0?"NULL":$rebutId).", ".($facturaId==0?"NULL":$facturaId).",'P')";
	
			$em->getConnection()->exec( $query );
			
			$totalComanda = 0;
			
			foreach ($partes as $parte) {
				$total 	= $parte['total'];
				$anota 	= $total.'x'.$parte['ccat'];
				$preu 	= (isset($parte['preu'])?$parte['preu']:0);
				$iva	= (isset($parte['iva'])?$parte['iva']:0);

				$totalComanda += $total * $preu * (1 + $iva);
				
				$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, preuunitat, ivaunitat, descomptedetall, anotacions, dataentrada) VALUES ";
				/*$query .= "(".$parte['id'].",".$parte['cpro'].",".$total.", ".$preu.", ".($iva > 0?$iva:"NULL") .", 0, '".$anota."',";*/
				$query .= "(".$parte['id'].",".$parte['cpro'].",".$total.", ".$preu.", NULL, 0, '".$anota."',";
				$query .= "'".$parte['dataentradadel']."')";
			
			
				$em->getConnection()->exec( $query );
			}
			
			if ($parte['importparte'] == 0) {
				// Els partes des de gestor tenen a 0 aquest import
				$query = "UPDATE m_factures SET import = ".$totalComanda." ";
				$query .= " WHERE id = ".$facturaId.";";
				$em->getConnection()->exec( $query );
			}
			
			$maxnums['maxnumcomanda']++;
			 
			if ($flush) {
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
			}
		}
	
		
	}
}
