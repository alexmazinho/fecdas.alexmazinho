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
use FecdasBundle\Entity\EntityComanda;
use FecdasBundle\Form\FormPayment;
use FecdasBundle\Entity\EntityPayment;
use FecdasBundle\Entity\EntityPreu;
use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityComptabilitat;
use FecdasBundle\Classes\RedsysAPI;
use FecdasBundle\Classes\Funcions;


class FacturacioController extends BaseController {
		
	public function registresaldosAction(Request $request) {
		// Llistat de comandes
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$club = null;
		$saldo = 0;
		$codi = $request->query->get('cerca', ''); // filtra club
		$grup = $request->query->get('grup', 'D'); // diari
		$strDesde = $request->query->get('desde', ''); 
		$strFins = $request->query->get('fins', '');
		$format = $request->query->get('format', '');
		$page = $request->query->get('page', 1);
		
		if ($strFins != '') $fins = \DateTime::createFromFormat('d/m/Y', $strFins);
		else $fins = $this->getCurrentDate('today');
		
		switch ($grup) {
		case 'D':
		    // Diari
		    if ($strDesde != '') $desde = \DateTime::createFromFormat('d/m/Y', $strDesde);
			else $desde = \DateTime::createFromFormat('d/m/Y', "01/01/".date('Y'));
			
			break;
			
		case 'M':
			// Mensual
			if ($strDesde != '') $desde = \DateTime::createFromFormat('d/m/Y', '01'.substr($strDesde,2, strlen($strDesde)));  // Dia 1 del mes
			else $desde = \DateTime::createFromFormat('d/m/Y', "01/01/".date('Y'));		
			
			$daysOfMonth = $fins->format('t');
			
			$fins = \DateTime::createFromFormat('d/m/Y', $daysOfMonth.'/'.$fins->format('m/Y'));	// Final de mes
			
			break;
			
		case 'A':
			// Anual
			if ($strDesde != '') $desde = \DateTime::createFromFormat('d/m/Y', '01/01/'.substr($strDesde,-4));
			else $desde = \DateTime::createFromFormat('d/m/Y', '01/01/'.date('Y'));
			
			$fins = \DateTime::createFromFormat('d/m/Y', '31/12/'.$fins->format('Y'));	// Final d'any
			
		    break;
		}					
		
		
		$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		
		if ($club == null) {   
			$club = $this->getCurrentClub();
			$codi = $club->getCodi(); 
		}	
	
		$this->logEntryAuth('REGISTRE SALDOS', $club->getCodi()." ".$desde->format('Y-m-d H:i:s')." - ".$fins->format('Y-m-d H:i:s'));
		
		
		// Obtenir saldo comptable des de l'inici de l'exercici fins al dia anterior a la data desde 
		
		$saldoComptableCurrent = $this->saldoComptableData($club, $desde); // $desde no inclosa
		
		$saldos = $this->saldosEntre($desde, $fins, $club);

		$saldosArray = array();
		
		$formatter = new \IntlDateFormatter('ca_ES.utf8', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
		$formatter->setPattern('yyyy MMMM');
		
		$keyAnterior = '';
		foreach ($saldos as $saldo) {
				if ($saldoComptableCurrent === null && $saldo->getDataregistre()->format('Y') >= $club->getExercici()) {
					$saldoComptableCurrent = $club->getRomanent();
				}
					
				if ($saldoComptableCurrent !== null) {
					$saldoComptableCurrent += $saldo->getEntrades();  // Acumular dia
					$saldoComptableCurrent -= $saldo->getSortides();	
				} 

				switch ($grup) {
			    case 'D':
			        // Diari
					
					$registreAnterior = null;
					if (isset($saldosArray[$keyAnterior])) $registreAnterior = $saldosArray[$keyAnterior]; 

					$canvi = $this->detectarCanvisRegistreSaldos($registreAnterior, $saldo, $saldoComptableCurrent);

					// Si és el primer registre o hi ha algun canvi en alguna dada el registre es mostra
					if ( $canvi ) {
					
						$keyActual = $saldo->getId().$saldo->getDataregistre()->format('Y-m-d');
					
						$saldosArray[$keyActual] = array(
							'id' 				=> $saldo->getId(),
							'dataregistre' 		=> $saldo->getDataregistre()->format('d/m/Y'),
							'romanent' 			=> $saldo->getRomanent(),
							'totalpagaments' 	=> $saldo->getTotalpagaments(),
							'totalllicencies' 	=> $saldo->getTotalllicencies(),
							'totalduplicats' 	=> $saldo->getTotalduplicats(),
							'totalaltres' 		=> $saldo->getTotalaltres(),
							'ajustsubvencions' 	=> $saldo->getAjustsubvencions(),
							'saldo' 			=> $saldo->getSaldo(),
							'entrades' 			=> $saldo->getEntrades(),
							'sortides' 			=> $saldo->getSortides(),
							'saldocompta'		=> $saldoComptableCurrent,
							'comentaris' 		=> $saldo->getComentaris()
						);
						
						$keyAnterior = $keyActual;
					
					}
					
			        break;
			    case 'M':
			    	// Mensual
					
					if (isset($saldosArray[$saldo->getDataregistre()->format('Y-m')])) {
							
						$saldosArray[$saldo->getDataregistre()->format('Y-m')]['entrades'] += $saldo->getEntrades();
						$saldosArray[$saldo->getDataregistre()->format('Y-m')]['sortides'] += $saldo->getSortides();
						
					} else {
						$saldosArray[$saldo->getDataregistre()->format('Y-m')] = array(
							'id' 				=> 0,
							'dataregistre' 		=> ucwords($formatter->format($saldo->getDataregistre())),
							'romanent'			=> 0,
							'totalpagaments'	=> 0,
							'totalllicencies' 	=> 0,
							'totalduplicats' 	=> 0,
							'totalaltres' 		=> 0,
							'ajustsubvencions' 	=> 0,
							'saldo' 			=> 0,
							'entrades' 			=> $saldo->getEntrades(),
							'sortides' 			=> $saldo->getSortides(),
							'saldocompta'		=> 0,
							'comentaris' 		=> ''
						);
					}
					
					// Ordenat per dataentrada, sempre posa la última
					$saldosArray[$saldo->getDataregistre()->format('Y-m')]['romanent'] = $saldo->getRomanent();
					$saldosArray[$saldo->getDataregistre()->format('Y-m')]['totalpagaments']	= $saldo->getTotalpagaments();
					$saldosArray[$saldo->getDataregistre()->format('Y-m')]['totalllicencies'] = $saldo->getTotalllicencies();
					$saldosArray[$saldo->getDataregistre()->format('Y-m')]['totalduplicats']	= $saldo->getTotalduplicats();
					$saldosArray[$saldo->getDataregistre()->format('Y-m')]['totalaltres'] = $saldo->getTotalaltres();
					$saldosArray[$saldo->getDataregistre()->format('Y-m')]['ajustsubvencions'] = $saldo->getAjustsubvencions();
					$saldosArray[$saldo->getDataregistre()->format('Y-m')]['saldo'] = $saldo->getSaldo();

					$saldosArray[$saldo->getDataregistre()->format('Y-m')]['saldocompta'] = $saldoComptableCurrent;
					$saldosArray[$saldo->getDataregistre()->format('Y-m')]['comentaris'] = '';
					
			        break;
			    case 'A':
					// Anula
					
					if (isset($saldosArray[$saldo->getDataregistre()->format('Y')])) {
							
						$saldosArray[$saldo->getDataregistre()->format('Y')]['entrades'] += $saldo->getEntrades();
						$saldosArray[$saldo->getDataregistre()->format('Y')]['sortides'] += $saldo->getSortides();
						
					} else {
						$saldosArray[$saldo->getDataregistre()->format('Y')] = array(
							'id' 				=> 0,
							'dataregistre' 		=> ucwords($formatter->format($saldo->getDataregistre())),
							'romanent'			=> 0,
							'totalpagaments'	=> 0,
							'totalllicencies' 	=> 0,
							'totalduplicats' 	=> 0,
							'totalaltres' 		=> 0,
							'ajustsubvencions' 	=> 0,
							'saldo' 			=> 0,
							'entrades' 			=> $saldo->getEntrades(),
							'sortides' 			=> $saldo->getSortides(),
							'saldocompta'		=> 0,
							'comentaris' 		=> ''
						);
					}
					
					// Ordenat per dataentrada, sempre posa la última
					$saldosArray[$saldo->getDataregistre()->format('Y')]['romanent'] = $saldo->getRomanent();
					$saldosArray[$saldo->getDataregistre()->format('Y')]['totalpagaments']	= $saldo->getTotalpagaments();
					$saldosArray[$saldo->getDataregistre()->format('Y')]['totalllicencies'] = $saldo->getTotalllicencies();
					$saldosArray[$saldo->getDataregistre()->format('Y')]['totalduplicats']	= $saldo->getTotalduplicats();
					$saldosArray[$saldo->getDataregistre()->format('Y')]['totalaltres'] = $saldo->getTotalaltres();
					$saldosArray[$saldo->getDataregistre()->format('Y')]['ajustsubvencions'] = $saldo->getAjustsubvencions();
					$saldosArray[$saldo->getDataregistre()->format('Y')]['saldo'] = $saldo->getSaldo();

					$saldosArray[$saldo->getDataregistre()->format('Y')]['saldocompta'] = $saldoComptableCurrent;
					$saldosArray[$saldo->getDataregistre()->format('Y')]['comentaris'] = '';
					
					
			        break;
				}
		}
		
		if ($format == 'csv') {
			
			$strGrup = '';
			if ($grup != 'D') $strGrup = 'diari';
			if ($grup != 'M') $strGrup = 'mensual';
			if ($grup != 'A') $strGrup = 'anual';
			
			$filename = "export_saldos_".$strGrup."_".Funcions::netejarPath($club->getNom())."_".$desde->format('Y-m-d')."_".$fins->format('Y-m-d')."_".date('Hms').".csv";
			
			$header = array('id', 'Data', 'Romanent', 'Pagaments', 'Llicències', 'Duplicats', 'Altres', 'Subvencions', 'Saldo', 'Entrades', 'Sortides', 'Saldo Comptable', 'Comentaris' ); 
			
			$data = array(); // Get only data matrix
			foreach ($saldosArray as &$row) {
				$row['romanent'] = number_format($row['romanent'], 2, ',', '');
				$row['totalpagaments'] = number_format($row['totalpagaments'], 2, ',', '');
				$row['totalllicencies'] = number_format($row['totalllicencies'], 2, ',', '');
				$row['totalduplicats'] = number_format($row['totalduplicats'], 2, ',', '');
				$row['totalaltres'] = number_format($row['totalaltres'], 2, ',', '');
				$row['ajustsubvencions'] = number_format($row['ajustsubvencions'], 2, ',', '');
				$row['saldo'] = number_format($row['saldo'], 2, ',', '');
				$row['entrades'] = number_format($row['entrades'], 2, ',', '');
				$row['sortides'] = number_format($row['sortides'], 2, ',', '');
				$row['saldocompta'] = ($row['saldocompta']!=null?number_format($row['saldocompta'], 2, ',', ''):'');
			}
				
			$response = $this->exportCSV($request, $header, $saldosArray, $filename);
			
			return $response;
		}
		
		// !!!!!!!!!!! CONSULTAR I MOSTRAR REBUTS ENTRATS AL 2016 AMB DATA PAGAMENT 2015 !!!!!! POSSIBLES FONTS D'ERRADES
		$rebutsControlar = $this->rebuts2016pagats2015($codi);
		
		
		$formBuilder = $this->createFormBuilder()
			->add('desde', 'date', array(
				'disabled' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'required'		=>	false,
				'data'			=> $desde,
				'format' 		=> 'dd/MM/yyyy'))
			->add('fins', 'date', array(
				'disabled' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'data'			=> $fins,
				'format' 		=> 'dd/MM/yyyy'))
			->add('grup', 'choice', array(
				'choices'   	=> array('D' => 'diari', 'M' => 'mensual', 'A' => 'Anual'),
				'multiple'  	=> false,
				'expanded' 		=> true,
				'data'			=> $grup
		));
		
		$this->addClubsActiusForm($formBuilder, $club);
		
		return $this->render('FecdasBundle:Facturacio:registresaldos.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'saldos' => $saldosArray, 'club' => $club, 'rebutscontrol' => $rebutsControlar)
				));
	}	
		
	private function detectarCanvisRegistreSaldos($anterior, $actual, $saldoComptableCurrent = null) {
		
		if ($anterior == null) return true; // Actual és el primer
		
		if ($anterior['romanent'] 		!= $actual->getRomanent() ||
			$anterior['totalpagaments'] 	!= $actual->getTotalpagaments() ||
			$anterior['totalllicencies']	!= $actual->getTotalllicencies() ||
			$anterior['totalduplicats'] 	!= $actual->getTotalduplicats() ||
			$anterior['totalaltres'] 		!= $actual->getTotalaltres() ||
			$anterior['ajustsubvencions'] 	!= $actual->getAjustsubvencions() ||
			$anterior['comentaris'] 		!= $actual->getComentaris() ) return true;
			
		if ($anterior['entrades'] 	!= $actual->getEntrades() ||
			$anterior['sortides'] 	!= $actual->getSortides() ) return true;	

		if ($saldoComptableCurrent != null && $anterior['saldocompta'] != $saldoComptableCurrent) return true;
		
		return false;
	}	
		
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
		
		/*$datamin = $this->getCurrentDate('now');
		$datamin->sub(new \DateInterval('P365D')); // Substract 365 dies*/	
		
		$datamax = $this->getCurrentDate('now');
		//$datamax->sub($this->getIntervalConsolidacio()); // Substract 20 minutes

		// Data d'alta màxima 20 minuts endarrera (Partes a mitges) 
		$inici = $request->query->get('inici', '');
		$final = $request->query->get('final', date('d/m/Y'));
		
		if ($inici == '') $datainici = null; 
		else $datainici = \DateTime::createFromFormat('d/m/Y H:i:s', $inici." 00:00:00");
		
		$datafinal = \DateTime::createFromFormat('d/m/Y H:i:s', $final." 23:59:59");
		if ($datafinal->format('Y-m-d H:i:s') > $datamax->format('Y-m-d H:i:s')) $datafinal = $datamax;
		 
		//$filename = BaseController::PREFIX_ASSENTAMENTS.'_'.$datafinal->format("Ymd_His").".txt";
		$filename = BaseController::PREFIX_ASSENTAMENTS.'_'.$datafinal->format("Ymd_His").".csv";
	
		$enviament = null;
		$fs = new Filesystem();
		try {
			if (!$fs->exists(__DIR__.BaseController::PATH_TO_COMPTA_FILES)) {
				throw new \Exception("No existeix el directori " .__DIR__.BaseController::PATH_TO_COMPTA_FILES);
			} else {
				$enviament = new EntityComptabilitat($filename, $datainici, $datafinal);
				$em->persist($enviament);
				//$assentaments = $this->generarFitxerAssentaments($enviament); // Array
				$assentaments = $this->generarFitxerAssentamentsContasol($enviament); // Array
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

	/**
	 * Get fitxer assentaments per transpassar al programa de comptabilitat
	 *
	 * @return array
	 */
	private function generarFitxerAssentamentsContasol($enviament) {

		$num = 0;
		$apuntsFactures = $this->generarAssentamentsFactures($enviament, $num);
		$apuntsRebuts = $this->generarAssentamentsRebuts($enviament, $num);
		$assentaments = array_merge($apuntsFactures, $apuntsRebuts);
		return $assentaments; 
	}


	private function consultaFacturesConsolidades($desde = null, $fins = null, $club = null, $pendents = true) {
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT f FROM FecdasBundle\Entity\EntityFactura f ";
		
		$strQuery .= " WHERE f.import != 0 ";
		if ($desde != null) $strQuery .= " AND f.datafactura >= :ini ";
		if ($fins != null) $strQuery .= " AND f.datafactura <= :final ";
		if ($pendents == true) $strQuery .= " AND (f.comptabilitat IS NULL) ";   // Pendent d'enviar encara 
		$strQuery .= " ORDER BY f.datafactura";

		$query = $em->createQuery($strQuery);
		if ($desde != null) $query->setParameter('ini', $desde->format('Y-m-d H:i:s'));
		if ($fins != null) $query->setParameter('final', $fins->format('Y-m-d H:i:s'));
		
		$totesFactures = $query->getResult();

		$factures = array();		
		foreach ($totesFactures as $factura) {
			if ($factura->esAnulacio()) $comanda = $factura->getComandaAnulacio();
			else $comanda = $factura->getComanda();

			if ($comanda != null && $comanda->comandaConsolidada()) {
				if ($club != null) {
					if ($comanda->getClub()->getCodi() == $club->getCodi()) $factures[] = $factura;
				} else {
					if ($comanda->getClub() != BaseController::CODI_CLUBTEST) $factures[] = $factura; // No s'envia res de la Federació o del club TEST a comptabilitat
				}
			} 
		}
		return $factures;
	}

	private function consultaRebutsConsolidats($desde = null, $fins = null, $club = null, $pendents = true) {
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= " WHERE r.import != 0 ";
		if ($desde != null)  $strQuery .= " AND r.datapagament >= :ini ";
		if ($fins != null)  $strQuery .= " AND r.datapagament <= :final ";
		if ($pendents == true) $strQuery .= " AND (r.comptabilitat IS NULL) ";	// Pendent d'enviar encara
		if ($club != null)  $strQuery .= " AND r.club = :club "; 		// Un club 
		else {
			$strQuery .= " AND (r.club != '".BaseController::CODI_CLUBTEST."') ";	// No s'envia res del club TEST
		}
		$strQuery .= " ORDER BY r.datapagament";
		$query = $em->createQuery($strQuery);
		if ($desde != null) $query->setParameter('ini', $desde->format('Y-m-d H:i:s'));
		if ($fins != null)  $query->setParameter('final', $fins->format('Y-m-d H:i:s'));
		if ($club != null)  $query->setParameter('club', $club);

		$rebuts = $query->getResult();
		return $rebuts;
	}
	
	/**
	 * Factures	  => Club apunt D + Producte corresponent apunt H	
	 * Anular factura? => Club apunt D + Producte corresponent apunt H  però els dos amb import negatiu
	 * 
	 */
	private function generarAssentamentsFactures($enviament, &$num) {
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

		$datafins = $this->getCurrentDate('now'); // No cal esperar 20 minuts pels rebuts
		if ($enviament->getDatafins()->format('Y-m-d') < $datafins->format('Y-m-d') ) $datafins = $enviament->getDatafins();
			
		$rebuts = $this->consultaRebutsConsolidats($enviament->getDatadesde(), $datafins);

		$totalRebuts = 0;
		$assentaments = array();
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
		$apunt .= substr(str_pad($conc, 40, " ", STR_PAD_RIGHT), 0, 40);
		if ($doc != '') $apunt .= substr(str_pad($doc, "0", STR_PAD_LEFT), 0, 8);
		else $apunt .= str_repeat(" ",8);
		$apunt .= str_repeat(" ",4).str_repeat(" ",4);
		$apunt .= $signe.substr(str_pad((number_format(abs($import), 2, '.', '').''), 12, "0", STR_PAD_LEFT), 0, 12);
		$apunt .= $tipus.str_repeat("0",15);
		
		return $apunt;
	}
	
	private function crearLiniaAssentamentContasol($data, $numAssenta, $linia, $compte, $conc, $dpt, $subdpt, $doc, $import, $tipus) {
		/**
		 * Columna	Descripció
		 * A 		Diario : 1   => General
		 * B		Fecha: DD/MM/AAAA
		 * C		Asiento: Si es 0 s'assigna automàtic. Agrupa assentaments per número de document
		 * D		Orden: Linia? 
		 * E		Cuenta: XXXXXXX
		 * F		Import pessetes 
		 * G		Concepte (Max 60)
		 * H		Document (Max 5)
		 * I		Import Deure eur  	=> Si haver > 0 aquest val 0,00
		 * J 		Import haver eur	=> Si deure > 0 aquest val 0,00
		 * K		Moneda P -contramoneda (Pesseta) o E - Empresa
		 * L		Punteo 1-Si 0-No
		 * M		Tipus de IVA: R- Repercutido S-Soportado	¿Repercutido?
		 * N		Codi IVA: numèric ?
		 * O 		Departament  veure taula constants
		 * P		Subdepartament  veure taula constants
		 * Q		Imatge
		 * 
		 */
		$separadorCSV = ';'; 
		$altreSeparadorCSV = ',';
		
		$import = number_format($import, 2, ',', '');
		$conc = $this->netejarNom($conc, false);
		$conc = str_replace($separadorCSV, ' ', $conc);  // separador csv
		$conc = str_replace($altreSeparadorCSV, ' ', $conc);  // separador csv
		
		$apunt = array();
		$apunt[] = BaseController::INDEX_DIARI_CONTASOL;  	// Columna A
		$apunt[] = $data->format('d/m/Y'); 					// Columna B
		$apunt[] = 0; 										// Columna C 
		$apunt[] = $linia; 									// Columna D
		$apunt[] = $compte; 								// Columna E
		$apunt[] = 0; 										// Columna F
		$apunt[] = substr(str_pad($conc, 60, " ", STR_PAD_RIGHT), 0, 60); 	// Columna G
		$apunt[] = substr(str_pad($doc, 5, "0", STR_PAD_LEFT), 0, 5);		// Columna H
		$apunt[] = ($tipus == BaseController::DEBE?$import:0); 	// Columna I
		$apunt[] = ($tipus == BaseController::HABER?$import:0);; 	// Columna J
		$apunt[] = 'E'; 									// Columna K
		$apunt[] = 0; 										// Columna L
		$apunt[] = ''; 										// Columna M
		$apunt[] = 0; 										// Columna N
		$apunt[] = $dpt; 									// Columna O
		$apunt[] = $subdpt; 								// Columna P
		$apunt[] = ''; 										// Columna Q
		
		return implode($separadorCSV,$apunt);
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
		//$desc = $this->netejarNom($club->getNom(), false);
		//$concRebut = mb_convert_encoding($this->netejarNom($rebut->getConcepteRebutCurt(), false), 'UTF-8',  'auto');
		$concRebut = utf8_decode($this->netejarNom($rebut->getConcepteRebutCurt(), false));
		$conc = substr($this->netejarNom($club->getNom(), false), 0, 20).'. '.$concRebut;
		//$doc = $rebut->getNumRebutCurt();
		$doc = str_pad($rebut->getNum(), 5,"0", STR_PAD_LEFT); // Sense any
		$import = $rebut->getImport();
		//$assentament[] = $this->crearLiniaAssentament($data, $num, $linia, $compte, $desc, $conc, $doc, $import, BaseController::HABER);
		$assentament[] = $this->crearLiniaAssentamentContasol($data, $num, $linia, $compte, 'R/ '.$conc, 0, 0, $doc, $import, BaseController::HABER);
			
		$linia++;
		// APUNT CAIXA
		$compte = BaseController::getComptePagament($rebut->getTipuspagament());		// 5700000 o 5720001;
		
		//$conc = BaseController::getTextComptePagament($rebut->getTipuspagament()).'. '.$concRebut;		// 'CAIXA FEDERACIO' o 'BANC \'LA CAIXA\'';
		$conc .= " ". BaseController::getTextComptePagament($rebut->getTipuspagament());
		
		//$assentament[] = $this->crearLiniaAssentament($data, $num, $linia, $compte, $desc, $conc, $doc, $import, BaseController::DEBE);
		$assentament[] = $this->crearLiniaAssentamentContasol($data, $num, $linia, $compte, 'R/ '.$conc, 0, 0, $doc, $import, BaseController::DEBE);
		
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
		//$desc = $this->netejarNom($club->getNom(), false);
		//$conc = mb_convert_encoding($factura->getNumFactura()." (".$this->netejarNom($factura->getConcepte(), false).")", 'UTF-8',  'auto');
		$conc = substr($this->netejarNom($club->getNom(), false), 0, 20).'. '.utf8_decode($this->netejarNom($factura->getConcepte(), false));
		//$docAny = $factura->getNumFacturaCurt();
		$doc = str_pad($factura->getNum(), 5,"0", STR_PAD_LEFT); // Sense any
		
		$import = $factura->getImport();
					
		//$assentament[] = $this->crearLiniaAssentament($data, $num, $linia, $compte, $desc, $conc, '', $import, BaseController::DEBE);
		$assentament[] = $this->crearLiniaAssentamentContasol($data, $num, $linia, $compte, 'FRA/ '.$conc, 0, 0, $doc, $import, BaseController::DEBE);
					
		$linia++;
		$importAcumula = 0;
		$index = 1;
		//$numApunts = count(json_decode($factura->getDetalls(), true)); // Compta en format array
		//$detallsArray = json_decode($factura->getDetalls(), false, 512, JSON_UNESCAPED_UNICODE);
		
		$detallsArray = json_decode($factura->getDetalls(), false, 512);
		foreach ($detallsArray as $id => $d) {
		// APUNT/S PRODUCTE/S
			//$desc = $this->netejarNom($d->producte, false); 								// Descripció del compte KIT ESCAFADRISTA B2E/SVB
			//$conc = $doc."(".$index."-".$numApunts.") ".$d->total." ".mb_convert_encoding($d->producte, 'UTF-8',  'auto');						
			$conc = $d->total.' '.utf8_decode($d->producte);
			$importDetall = $d->import;	
			$importAcumula += $importDetall;	
			//$producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->findOneByCodi($compte);
            $producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->findOneById($id);
         
            if ($producte == null) throw new \Exception("Factura ".$factura->getNumfactura()." incorrecte. Producte ".$d->codi." incorrecte (".$id.")");
            
			//$assentament[] = $this->crearLiniaAssentament($data, $num, $linia, $compte, $desc, $conc, '', $importDetall, BaseController::HABER);
			$assentament[] = $this->crearLiniaAssentamentContasol($data, $num, $linia, $producte->getCodi(), 'FRA/ '.$conc, $producte->getDepartament(), $producte->getSubdepartament(), $doc, $importDetall, BaseController::HABER);
						
			$linia++;
			$index++;
		}

		// Validar que quadren imports		
		if (abs($import - $importAcumula) > 0.01) throw new \Exception("Imports detall de la factura ".$doc." no quadren");
			
		return $assentament;
	} 

	
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
	
		$this->logEntryAuth('VIEW INGRESOS', $this->get('session')->get('username'));

		$club = null;
		if (!$this->isCurrentAdmin()) { // Users normals només consulten comandes pròpies 
			$club = $this->getCurrentClub();
			$codi = $club->getCodi(); 
		} else {
			$codi = $request->query->get('cerca', '');
			if ($codi != '') $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		}
		
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
		
		$formBuilder = $this->createFormBuilder();
		
		if ($this->isCurrentAdmin()) $this->addClubsActiusForm($formBuilder, $club);
		
		$formBuilder->add('numrebut', 'text', array(
				'required' => false,
				'data' => $nr
		));
			
		$currentMaxNumRebut = $this->getMaxNumEntity(date('Y'), BaseController::REBUTS);
												
		return $this->render('FecdasBundle:Facturacio:ingresos.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'ingresos' => $ingresos, 'maxnum' => $currentMaxNumRebut,  'sortparams' => array('sort' => $sort,'direction' => $direction))
		));
	}
	
	public function comandesAction(Request $request) {
		// Llistat de comandes
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$club = null;
		$saldo = 0;
		$codi = $request->query->get('cerca', ''); // Admin filtra club
		if (!$this->isCurrentAdmin()) {  // Users normals només consulten comandes pròpies 
			$club = $this->getCurrentClub();
			$codi = $club->getCodi(); 
			$saldo = $club->getSaldo();
		}	
		else {
			if ($codi != '') $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		}
	
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
		
		$nc = $request->query->get('numcomanda', '');
		
		$arrayCom = explode("/", $nc);
		$numcomanda = is_numeric($nc)?$nc:substr($nc, 1);
		$anycomanda = 0;
		if (count($arrayCom) > 1) {
			$numcomanda = is_numeric($arrayCom[0])?$arrayCom[0]:substr($arrayCom[0], 1);
			$numcomanda = is_numeric($numcomanda)?$numcomanda:0;
			
			$anycomanda = is_numeric($arrayCom[1])?$arrayCom[1]:0;
		}
		
		$baixes = $request->query->get('baixes', 0);
		$baixes = ($baixes == 1?true:false);

		$pendents = $request->query->get('pendents', 0);
		$pendents = ($pendents == 1?true:false);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.dataentrada');
		$direction = $request->query->get('direction', 'desc');

		// Recorrer totes les comandes de les pàgines anteriors a l'actual per calcular saldo pendent (només clubs amb deute)
		if ($page > 1 && !$this->isCurrentAdmin() && $saldo < 0) {
			$query = $this->consultaComandes($codi, $numcomanda, $anycomanda, $numfactura, $anyfactura, $numrebut, $anyrebut, $baixes, $pendents, $sort, $direction);
			$index = 0;
			$anteriors = ($page - 1) * 10;
			$comandesAnteriors = $query->getResult();
			foreach ($comandesAnteriors as $comanda) {
				if (!$comanda->comandaPagada() && $comanda->getTotalComanda() > 0 && $saldo < 0) {
					$saldo += $comanda->getTotalComanda();
				}
				$index++;
				if ($index >= $anteriors) break;
			}
		} 
		
		$query = $this->consultaComandes($codi, $numcomanda, $anycomanda, $numfactura, $anyfactura, $numrebut, $anyrebut, $baixes, $pendents, $sort, $direction);
		
		$paginator  = $this->get('knp_paginator');
			
		$comandes = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
		
		if ($this->isCurrentAdmin() && count($comandes) == 0 && $numrebut > 0) {
			// Consulta possible ingrés no associat a cap comanda
			$queryIngresos = $this->consultaIngresos($codi, $numrebut, $anyrebut, 'r.datapagament', $direction);
			
			$ingresos = $queryIngresos->getResult();

			if (count($ingresos) > 0) $this->get('session')->getFlashBag()->add('sms-notice',	'El rebut correspon a un ingrès no associat a cap comanda');	
		}
		
		$formBuilder = $this->createFormBuilder()
			->add('numcomanda', 'text', array(
				'data' 	=> $nc ))
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
		
		$this->addClubsActiusForm($formBuilder, $club);
		
		return $this->render('FecdasBundle:Facturacio:comandes.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'comandes' => $comandes, 'saldo' => $saldo, 'sortparams' => array('sort' => $sort,'direction' => $direction))
				));
	}


	public function facturesAction(Request $request) {
		// Llistat de factures
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$club = null;
		$codi = $request->query->get('cerca', ''); // Admin filtra club
		if (!$this->isCurrentAdmin()) {  // Users normals només consulten comandes pròpies 
			$club = $this->getCurrentClub();
			$codi = $club->getCodi(); 
		}	
		else {
			if ($codi != '') $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		}
	
		$this->logEntryAuth('VIEW COMANDES', $this->get('session')->get('username'));
		
		$nf = $request->query->get('numfactura', '');
		
		$arrayFact = explode("/", $nf);
		$numfactura = $nf;
		$anyfactura = 0;
		if (count($arrayFact) > 1) {
			$numfactura = is_numeric($arrayFact[0])?$arrayFact[0]:0;
			$anyfactura = is_numeric($arrayFact[1])?$arrayFact[1]:0;
		}
		
		$nc = $request->query->get('numcomanda', '');
		
		$arrayCom = explode("/", $nc);
		$numcomanda = is_numeric($nc)?$nc:substr($nc, 1);
		$anycomanda = 0;
		if (count($arrayCom) > 1) {
			$numcomanda = is_numeric($arrayCom[0])?$arrayCom[0]:substr($arrayCom[0], 1);
			$numcomanda = is_numeric($numcomanda)?$numcomanda:0;
			
			$anycomanda = is_numeric($arrayCom[1])?$arrayCom[1]:0;
		}
		
		$pendents = $request->query->get('pendents', 0);
		$pendents = ($pendents == 1?true:false);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.dataentrada');
		$direction = $request->query->get('direction', 'desc');

		$query = $this->consultaFactures($codi, $numcomanda, $anycomanda, $numfactura, $anyfactura, $pendents, $sort, $direction);
		
		$paginator  = $this->get('knp_paginator');
			
		$factures = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
		
		$formBuilder = $this->createFormBuilder()
			->add('numcomanda', 'text', array(
				'data' 	=> $nc ))
			->add('numfactura', 'text', array(
				'data' 	=> $nf ))
			->add('pendents', 'checkbox', array(
				'required' => false,
				'data' => $pendents
		));
		
		$this->addClubsActiusForm($formBuilder, $club);
			
		return $this->render('FecdasBundle:Facturacio:factures.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'factures' => $factures,  'sortparams' => array('sort' => $sort,'direction' => $direction))
				));
	}

	public function apuntsAction(Request $request) {
		// Llistat d'apunts d'un club ordenats per data descendent
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$club = null;
		if ($this->isCurrentAdmin()) {   
			$codi = $request->query->get('club', ''); // Admin filtra club
			$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		}
		if ($club == null) {  // Users normals només consulten comandes pròpies 
			$club = $this->getCurrentClub();
		}	
	
		$this->logEntryAuth('VIEW APUNTS', $this->get('session')->get('username'));

		// GET OPCIONS DE FILTRE
		$action = $request->query->get('action', '');

		$datadesde = null;
		$datafins = null;
		$strDatadesde = $request->query->get('datadesde', '');
		$strDatafins = $request->query->get('datafins', '');
		
		if ($strDatadesde != '') $datadesde = \DateTime::createFromFormat('d/m/Y H:i:s', $strDatadesde . ' 00:00:00'); 
		if ($datadesde == null) $datadesde = \DateTime::createFromFormat('d/m/Y H:i:s', '01/01/'.date('Y'). ' 00:00:00');
		
		if ($strDatafins != '') $datafins = \DateTime::createFromFormat('d/m/Y H:i:s', $strDatafins . ' 23:59:59'); 
		if ($datafins == null) $datafins = $this->getCurrentDate('now');

 		$apunts = $this->consultaApunts($club, $datadesde, $datafins);

		if ($action == 'csv') {
			$filename = "export_apunts_".Funcions::netejarPath($club->getNom())."_".$datadesde->format('Y-m-d')."_".$datafins->format('Y-m-d')."_".date('Hms').".csv";
			
			$header = array('Núm', 'Data', 'Deure', 'Haver', 'Comanda', 'Concepte', 'Saldo a '.$datafins->format('Y-m-d'), 'Entrada'); 
			
			$data = array(); // Get only data matrix
			foreach ($apunts as $row) {
				$deure = 0;
				$haver = 0;
				if ($row['tipus'] == 'F') $deure = number_format($row['import'], 2, ',', '.');
				if ($row['tipus'] == 'R') $haver = number_format($row['import'], 2, ',', '.');
				$strComanda = '';
				foreach ($row['comandes'] as $comanda) {
					$strComanda .= $comanda['num'].($comanda['import']!=0?' '.number_format($comanda['import'], 2, ',', '.'):'');
				} 
				
				$strConcepte = $row['concepte'];
				if ($row['extra'] != false) {
					$strExtra = $row['extra']['dades'];	
					if (strlen($strExtra) > 80) $strExtra = substr($strExtra, 0, 77).'...';
					
					$strConcepte .= PHP_EOL.$strExtra; 
				}
				$strNum = "";
				if ($row['anulacio'] == true) $strNum .= '(-)';
				$strNum .= $row['num']; 
				
				$rowdata = array( $strNum, $row['data']->format('Y-m-d'), $deure, $haver, $strComanda, $strConcepte, number_format($row['saldo'], 2, ',', '.'), $row['entrada']->format('Y-m-d H:i:s')); 
				
				$data[] = $rowdata;
			}
			
			$response = $this->exportCSV($request, $header, $data, $filename);
			
			return $response;
		}

		$page = $request->query->get('page', 1);
		$perpage = BaseController::PER_PAGE_DEFAULT; 
		$offset = ($page - 1) * $perpage;
		$total = count($apunts);		

		$pagination = array('page' => $page, 'perpage' => $perpage, 'total' => $total, 'pages' => ceil($total/$perpage), 'club' => $club->getCodi(), 'datadesde' => $datadesde->format('d/m/Y'), 'datafins' => $datafins->format('d/m/Y') );
		
		$apunts = array_slice($apunts, $offset, $perpage, true);
		
		if ($request->isXmlHttpRequest()) {
			// Crida ajax. Recarrega taula
			return $this->render('FecdasBundle:Facturacio:taulaapunts.html.twig',
				$this->getCommonRenderArrayOptions(array(
						'apunts' => $apunts, 'pagination' => $pagination, 'club' => $club)
				));
		} 
		
		$formBuilder = $this->createFormBuilder()
			->add('datadesde', 'date', array(
				'disabled' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'required'		=>	false,
				'empty_value' 	=> false,
				'data'			=> $datadesde,
				'format' 		=> 'dd/MM/yyyy'))
			->add('datafins', 'date', array(
				'disabled' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'data'			=> $datafins,
				'format' 		=> 'dd/MM/yyyy'
		));
		
		if ($this->isCurrentAdmin()) $this->addClubsActiusForm($formBuilder, $club); // Admin selecció de club
			
		return $this->render('FecdasBundle:Facturacio:apunts.html.twig',
				$this->getCommonRenderArrayOptions(array(
						'form' => $formBuilder->getForm()->createView(),
						'club' => $club,
						'apunts' => $apunts,
						'pagination' => $pagination)
				));
	}

	private function consultaApunts($club, $datadesde, $datafins) {
		// Consulta per $datafins null => calcular saldos des de saldo actual endarrera 
		$factures = $this->consultaFacturesConsolidades($datadesde, null, $club, false);
		$rebuts = $this->consultaRebutsConsolidats($datadesde, null, $club, false);
		$apunts = array();
		
		$saldo = $club->getSaldo();

		// rebuts i factures a format comú
		foreach ($factures as $factura) {
			if ($factura->esAnulacio()) $comanda = $factura->getComandaAnulacio();
			else $comanda = $factura->getComanda();
			
			$extra = array('dades' => $factura->getConcepteExtra(), 'more' => false);
			if ($factura->getNumDetallsExtra() > 2) $extra['more'] = true;
			
			$apunts[] = array(
							'tipus' 	=> 'F',
							'id' 		=> $factura->getId(), 
							'num' 		=> $factura->getNumfactura(),
							'anulacio'	=> $factura->esAnulacio(),
							'data' 		=> $factura->getDatafactura(),
							'entrada' 	=> $factura->getDataentrada(),
							'import' 	=> $factura->getImport(),
							'comandes'	=> array( $comanda->getNum() => array( 'num' => $comanda->getNumcomanda(), 'import' => $comanda->getTotalDetalls() ) ),
							'concepte'	=> $factura->getConcepte(),
							'extra'		=> $extra,
							'compta'	=> ($factura->getComptabilitat()!=null?$factura->getComptabilitat()->getDataenviament():''),
							'saldo'		=> 0
							
			); 
		}

		foreach ($rebuts as $rebut) {
			$apunts[] = array(
							'tipus' 	=> 'R',
							'id' 		=> $rebut->getId(), 
							'num' 		=> $rebut->getNumrebut(),  // No hi ha rebuts anul·lació
							'anulacio'	=> false,
							'data' 		=> $rebut->getDatapagament(),
							'entrada' 	=> $rebut->getDataentrada(),
							'import' 	=> $rebut->getImport(),
							'comandes'	=> $rebut->getArrayNumsComandes(),
							'concepte'	=> $rebut->getComentari(),
							'extra'		=> false,
							'compta'	=> ($rebut->getComptabilitat()!=null?$rebut->getComptabilitat()->getDataenviament():''),
							'saldo'		=> 0
							
			);
		}
		
		// Ordenar per data
		usort($apunts, function($a, $b) {
			if ($a === $b) {
				return 0;
			}
			if ($a['data']->format('Y-m-d H:i:s') == $b['data']->format('Y-m-d H:i:s')) return ($a['tipus'] > $b['tipus'])? -1:1;
			return ($a['data'] > $b['data'])? -1:1;
		});
		
		$current = $this->getCurrentDate(); // Moviments data futura es tenen en compte també
	
		// Calcular saldo i filtre datafins
		foreach ($apunts as $k => $apunt) {
			$apunts[$k]['saldo'] = $saldo;
			
			if ($apunt['tipus'] == 'R') $saldo -= $apunt['import'];
			else $saldo += $apunt['import'];
			
			if ($datafins != null && $datafins->format('Y-m-d') < $current->format('Y-m-d') && $apunt['data']->format('Y-m-d') > $datafins->format('Y-m-d')) {
				// Només treure si són consultes fins a data anterior a avui	
				unset($apunts[$k]);
			} 
		}
		return $apunts;
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
		$club = null;
		
		if ($this->isCurrentAdmin()) {  // Admins poden escollir el club 
			$codi = $request->query->get('club', ''); 
			if ($codi != '') $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
			if ($club != null) $this->get('session')->set('roleclub', $codi);
		}
		if ($club == null) $club = $this->getCurrentClub();
		
		$productes = array();
		
		if ($tipus > 0) {
			
			if ($tipus == BaseController::TIPUS_PRODUCTE_LLICENCIES ||
				$tipus == BaseController::TIPUS_PRODUCTE_DUPLICATS) $sort = 'p.id';
			
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
		
		$this->addClubsActiusForm($formBuilder, $club);
		
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
		$comptefactura = $request->query->get('comptefactura', $this->getIbanGeneral());
		
		$club = null;
		$datafacturacio = $this->getCurrentDate();
		$strDatafacturacio = $request->query->get('datafacturacio', '');
		$comandaKits = false;
		
		if ($strDatafacturacio != '') $datafacturacio = \DateTime::createFromFormat('d/m/Y', $strDatafacturacio); 
				
		if ($this->isCurrentAdmin()) {  // Admins poden escollir el club i data facturacio 
			$codi = $request->query->get('club', ''); 
			if ($codi != '') $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		}
		if ($club == null) $club = $this->getCurrentClub();
		
		$this->logEntryAuth('CISTELLA TRAMITAR', 'action => '.$action);

		try {
			$em = $this->getDoctrine()->getManager();
	
			if ($action == 'desar' || $action == 'pagar') {
				// Comanda nova. Crear factura
				$comanda = $this->crearComanda($this->getCurrentDate(), $comentaris);
				
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
					
					if ($producte == null) throw new \Exception("No es pot afegir el transport a la comanda, poseu-vos en contacte amb la Federació"); 
					
					$anotacions = $producte->getDescripcio().' '.$pesComanda.'g';	
					$detall = $this->addComandaDetall($comanda, $producte, 1, 0, $anotacions);		
				}
				
				if (count($cart['productes']) == 0) $detall = $this->addComandaDetall($comanda); // Sempre afegir un detall si comanda nova
			
				// Validacions comuns i anotacions stock
				$this->tramitarComanda($comanda);

				$factura = $this->crearFactura($datafacturacio, $comanda, $comptefactura);
			
				$em ->flush();
			
			
				// Enviar notificació mail Albert si és una comanda de Kits
				if ($comanda->comandaKits()) {
					$subject = ":: Comanda KITS ::";
					$tomails = $this->getCarnetsMails(); // Carnets Albert
					
					$body = "<h3>Nova comanda Kits del club ". $comanda->getClub()->getNom()."</h3>";
					$body .= "<p>Comanda: ". $comanda->getNumcomanda() ."</p>";
					$body .= "<p>". $comanda->getInfoLlistat( "<br/>" ) ."</p>";

					$this->buildAndSendMail($subject, $tomails, $body);
					
					$this->logEntryAuth('NOTI. KITS', 'club ' . $comanda->getClub()->getNom() . ' comanda ' . $comanda->getNumcomanda());
					
					$this->get('session')->getFlashBag()->add('sms-notice',"Notificació enviada correctament");
				}
				
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
					
					return $this->redirect($this->generateUrl('FecdasBundle_comandes'));
					
			        break;
			    case 'anular':
					// Missatge 
					$this->get('session')->getFlashBag()->add('sms-notice',	'Comanda anul·lada');	
					
			        break;
			}
			
		} catch (\Exception $e) {
				// Ko, mostra form amb errors
			$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
		}
		
		return $this->redirect($this->generateUrl('FecdasBundle_graellaproductes', array( 'tipus' => $tipus, 'club' => $club->getCodi())));
	}

	public function afegircistellaAction(Request $request) {
		// Afegir producte a la cistella (desada temporalment en cookie)
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$producte = null;
		$idProducte = $request->query->get('id', 0);
		$unitats = $request->query->get('unitats', 1);
		$tipus = $request->query->get('tipus', 0);
		
		// Recollir cistella de la sessió
		$cart = $this->getSessionCart();				
		$form = null;

		try {
			$producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->find($idProducte);
			
			//if ($producte != null && $unitats > 0) {
			if ($producte != null) {
				
				if ($unitats == 0) throw new \Exception("Cal indicar el nombre d'unitats del producte");
				
				if ($unitats < 0 && !$this->isCurrentAdmin()) throw new \Exception("El nombre d'unitats és incorrecte");
				
				// Comprovar que tots els detalls siguin d'abonament o normals
				if (count($cart['productes']) > 0) {
					$abonament = false;
					foreach ($cart['productes'] as $info) {
						if ($info['unitats'] < 0) $abonament = true;
					}
					
					if (($abonament == true && $unitats > 0) ||
						($abonament == false && $unitats < 0)) throw new \Exception("No es poden barrejar abonaments i comandes normals");
				}			
				
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
				
				if ($producte->getTransport() == true && $unitats > 0) $cart['productes'][$idProducte]['pes'] = $unitats * $producte->getPes(); 
				
				/*
				if ($producte->getTransport() == true) {
					if ($producte->getCanvitarifa() != null && is_numeric($producte->getCanvitarifa()) && $producte->getCanvitarifa() <= $unitats ) {
						$cart['productes'][$idProducte]['tarifa'] = BaseController::TARIFA_TRANSPORT2;
					} else {
						$cart['productes'][$idProducte]['tarifa'] = BaseController::TARIFA_TRANSPORT1;
					}
				}*/
							
				if ($cart['productes'][$idProducte]['unitats'] == 0 ||
					($cart['productes'][$idProducte]['unitats'] < 0  && !$this->isCurrentAdmin())) {
					// Afegir unitats < 0
					unset( $cart['productes'][$idProducte] );
				}
				
				if (count($cart['productes']) <= 0) {
					$this->get('session')->remove('cart');
				} else {
					$form = $this->formulariTransport($cart);		
					
					$session = $this->get('session');
					$session->set('cart', $cart);
				}
			}
		} catch (\Exception $e) {
			// Ko, mostra form amb errors
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
			//$response->headers->set('Content-Type', 'application/json');
			//$response->setContent(json_encode( array('result' => 'KO', 'sms' => $e->getMessage()) ));
			return $response;
		}
		
		if ($form == null) $form = $this->formulariTransport($cart);
		
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
			/* Alta o modificació de comandes */
			$data = $request->request->get('comanda');
			$id = (isset($data['id'])?$data['id']:0);
		
			if ($id > 0) $comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($id);
		
			if ($comanda == null) {
				
				// Comanda nova. Crear factura => Opció no disponible, només tramitar comandes noves a través de la cistella
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
		
		$strDatafacturacio = $request->query->get('datafacturacio', '');
		$dataFacturacio = $this->getCurrentDate();
		if ($strDatafacturacio != '') $dataFacturacio = \DateTime::createFromFormat('d/m/Y', $strDatafacturacio);
	
		$comanda = $em->getRepository('FecdasBundle:EntityComanda')->find($id);
	
		if ($comanda == null) {
			$this->logEntryAuth('BAIXA COMANDA KO', 'comanda : ' . $id);
			$this->get('session')->getFlashBag()->add('error-notice', 'Comanda no trobada ');
			return $this->redirect($this->generateUrl('FecdasBundle_comandes'));
		}
		
		$this->baixaComanda($comanda, $dataFacturacio);
	
		$em->flush();
	
		$this->logEntryAuth('BAIXA COMANDA OK', 'Comanda: '.$comanda->getId());
		$this->get('session')->getFlashBag()->add('sms-notice', 'Comanda '.$comanda->getInfoComanda().' donada de baixa ');
		
		$params = $request->query->all();
		if (isset($params['baixes'])) $params['baixes'] = true;
		
		return $this->redirect($this->generateUrl('FecdasBundle_comandes', $params));
		//return $this->redirect($this->generateUrl('FecdasBundle_comandes', array('baixes' => true)));
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
				
                $url = $this->getComercRedsysParam( 'COMERC_REDSYS_URL' ); // Real
                $urlmerchant = $this->getComercRedsysParam( 'COMERC_REDSYS_URLMER' );
                $key = $this->getComercRedsysParam( 'COMERC_REDSYS_SHA_256_KEY' );
                
                if ($this->get('kernel')->getEnvironment() == 'dev') {
                    $url = $this->getComercRedsysParam( 'COMERC_REDSYS_URL_TEST' ); // Test
                    $urlmerchant = $this->getComercRedsysParam( 'COMERC_REDSYS_URLMER_TEST' );
                    $key = $this->getComercRedsysParam( 'COMERC_REDSYS_SHA_256_KEY_TEST' );
                }
                
                $payment = new EntityPayment($comandaid, $this->get('kernel')->getEnvironment(),
							$comanda->getTotalComanda(), $desc, $club->getNom(), $origen, $url, $urlmerchant, 
							$this->getComercRedsysParam( 'COMERC_REDSYS_FUC' ), $this->getComercRedsysParam( 'COMERC_REDSYS_CURRENCY' ),
                            $this->getComercRedsysParam( 'COMERC_REDSYS_TRANS' ), $this->getComercRedsysParam( 'COMERC_REDSYS_TERMINAL' ),
                            $this->getComercRedsysParam( 'COMERC_REDSYS_MERCHANTNAME' ), $this->getComercRedsysParam( 'COMERC_REDSYS_LANG' ),
                            $this->getComercRedsysParam( 'COMERC_REDSYS_SHA_256_VERSION' ), $key);              

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
		$this->logEntryAuth('PAGAMENT KO', $comandaid);
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

		$query = $this->consultaProductes($idproducte, $compte, $tipus, true, $sort, $direction);
			
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
    				
    				/* NO ES VALIDA CODIS DIFERENTS, ara varis productes poden tenir mateix codi Agost 2016
                    $codiExistent = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->findOneBy(array('codi' => $producte->getCodi()));
    				
    				if ($codiExistent != null && $codiExistent != $producte) {
    					$form->get('codi')->addError(new FormError('Codi existent'));
    					throw new \Exception('El codi indicat ja existeix pel producte: ' .$codiExistent->getDescripcio() );
    				}*/
    				
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
    				throw new \Exception('Dades incorrectes, cal revisar les dades del producte ' .$form->getErrorsAsString()); 
    			}

				if ($producte->getTipus() == BaseController::TIPUS_PRODUCTE_LLICENCIES) {
					$activat = $form->get('activat')->getData();
					if ($producte->getCategoria() != null && 
						$producte->getCategoria()->getTipusparte() != null) {
							$producte->getCategoria()->getTipusparte()->setActiu($activat);
						}
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
		$strQuery = " SELECT p FROM FecdasBundle\Entity\EntityProducte p WHERE ";
		//$strQuery .= " WHERE p.databaixa IS NULL ";
		if  ($tipus != 'compte') $strQuery .= " p.descripcio LIKE :cerca";
		else $strQuery .= " p.codi >= :min AND p.codi <= :max ";
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
		if (! $this->isCurrentAdmin() ) $strQuery .= " AND p.visible = 1";

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
		
		$em = $this->getDoctrine()->getManager();
 
		// Comandes pendents de pagament. Inicialment al club connectat
		if ($request->getMethod() != 'POST') {
			$codi = $request->query->get('codi', '');
			if ($codi != '') $this->get('session')->set('roleclub', $codi);
		} else {
			$formdata = $request->request->get('rebut');
			$codi = $formdata['club'];
		}
			
		$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		if ($club == null) $club = $this->getCurrentClub();

		if ($request->getMethod() != 'POST')  $this->logEntryAuth('INGRES NOU',$request->getMethod().' '.$codi);
		 
		$query = $this->consultaComandes($club->getCodi(), 0, 0, 0, 0, 0, 0, false, true);
		
		$comandesPendents = $query->getResult(); // Comandes pendents rebut del club
		
		$comandes = array(); // Filter comandes amb anul·lacions 
		foreach ($comandesPendents as $comanda) {
			if ($comanda->getNumFactures(true) == 1 &&
				$comanda->getTotalComanda() > 0) $comandes[] = $comanda;
			
			
		}
		
		// Nou rebut
		$tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA;
		
		$current = $this->getCurrentDate('now');
		
		$rebut = $this->crearIngres($current, $tipuspagament, $club);
		
		$em->persist($rebut);
		
		$form = $this->createForm(new FormRebut(), $rebut);
		
		if ($request->getMethod() == 'POST') {
			try {
				$form->handleRequest($request);

				if ($form->isValid()) {
					
					$maxNumRebut = $this->getMaxNumEntity($rebut->getDatapagament()->format('Y'), BaseController::REBUTS) + 1;
					$rebut->setNum($maxNumRebut);

					if ($current->format('Y-m-d') == $rebut->getDatapagament()->format('Y-m-d')) {
						// Update de les hores, el form només gestiona dies i posa les hores a 00:00:00
						$rebut->setDatapagament($current);
					}

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
					
					$rebut->updateClubPagaments($rebut->getImport());
					
					$em->flush();
					$this->logEntryAuth('INGRES NOU',$request->getMethod().' '.$codi.' => '.$maxNumRebut.' '.$rebut->getImport());
					// Redirect json
					$response = new Response("OK");
					return $response;
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
	 
	public function esborrarultimrebutAction(Request $request) {
		// http://www.fecdasnou.dev/esborrarultimrebut?rebut=xxx
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$rebutid = $request->query->get('rebut', 0);

		$em = $this->getDoctrine()->getManager();
		
		try {
			if ($rebutid > 0) { // Esborrar
				$rebut = $this->getDoctrine()->getRepository('FecdasBundle:EntityRebut')->find($rebutid);
				
				if ($rebut == null) {
					$this->logEntryAuth('REBUT BAIXA KO 1', ' Rebut '.$rebutid);
					throw new \Exception("NO s'ha pogut esborrar el rebut ");
				}
				
				if ($rebut->estaComptabilitzat() == true) {
					$this->logEntryAuth('REBUT BAIXA KO 2', ' Rebut '.$rebutid);
					throw new \Exception("El rebut està enviat a comptabilitat i no es pot esborrar");
				}
				
				$currentMaxNumRebut = $this->getMaxNumEntity(date('Y'), BaseController::REBUTS);
				
				if ($currentMaxNumRebut != $rebut->getNum() || !$rebut->isCurrentYear())  {
					$this->logEntryAuth('REBUT BAIXA KO 3', ' Rebut id '.$rebutid.' num '.$rebut->getNum() .' max '.$currentMaxNumRebut);
					throw new \Exception("Només es pot esborrar el darrer rebut");
				}
				
				if ($rebut->getTipuspagament() == BaseController::TIPUS_PAGAMENT_TPV)   {
					$this->logEntryAuth('REBUT BAIXA KO 4', ' Rebut id '.$rebutid.' num '.$rebut->getNum() .' TPV');
					throw new \Exception("No es pot esborrar un rebut del TPV");
				}
				
				// Esborrar el rebut de totes les comandes
				foreach ($rebut->getComandes() as $comanda) $comanda->setRebut(null);	
	
				if ($rebut->getComandaanulacio() != null) $rebut->getComandaanulacio()->removeRebutsanulacions($rebut);
				 			
				
				// Actualitzar saldo club
				$rebut->updateClubPagaments(-1 * $rebut->getImport());
				
				$em->remove($rebut);
				$em->flush();
				$this->logEntryAuth('REBUT BAIXA OK', ' Rebut '.$rebutid);
				
				$this->get('session')->getFlashBag()->add('sms-notice', 'Rebut esborrat correctament ');
				
				// No seguim
			}
		} catch (\Exception $e) {
			// Ko, mostra form amb errors
			$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
			return $this->redirect($this->generateUrl('FecdasBundle_editarrebut',
									array( 'id' => $rebut->getId() )));
		}

		return $this->redirect($this->generateUrl('FecdasBundle_ingresos'));

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
		$rebutOriginal = null;
		
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
		$rebutOriginal = clone $rebut;
		
		$form = $this->createForm(new FormRebut(), $rebut);
		
		$currentMaxNumRebut = $this->getMaxNumEntity(date('Y'), BaseController::REBUTS);
		
		if ($request->getMethod() == 'POST') {
			try {
				$form->handleRequest($request);
		
				if ($form->isValid()) {
	
					$rebut->setDatamodificacio(new \DateTime());
					if ($rebut->getId() == 0)  {
						$currentMaxNumRebut++;
						$rebut->setNum($currentMaxNumRebut); 
						if ($rebut->getComanda() != null) $rebut->getComanda()->setRebut($rebut);
					}
	
					$this->validIngresosRebuts($form, $rebut, $rebutOriginal);
		
					// Actualitzar saldos
					$rebut->updateClubPagaments( $rebut->getImport() - $rebutOriginal->getImport() );
		
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
				
				return $this->redirect($this->generateUrl('FecdasBundle_editarrebut',
									array( 'id' => $rebut->getId() )));
			}
		}
		return $this->render('FecdasBundle:Facturacio:rebut.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'rebut' => $rebut, 'maxnum' => $currentMaxNumRebut)));
	}
	
	private function validIngresosRebuts($form, $rebut, $rebutOriginal = null) {
		
		if ($rebut->getImport() <= 0) {
			/*$form->get('import')->addError(new FormError('Valor incorrecte'));
			throw new \Exception('Cal indicar un import superior a 0' );*/
		}
		if ($rebut->getImport() == 0) throw new \Exception('El rebut no pot tenir un import 0' );
		
		if ($rebut->esAnulacio()) {
			if ($rebut->getDatapagament()->format('Y-m-d') < $rebut->getComandaAnulacio()->getDataentrada()->format('Y-m-d')) {
				$form->get('datapagament')->addError(new FormError('Data incorrecte'));
				throw new \Exception('La data d\'anul·lació ha de ser igual o posterior a la data de la comanda' );
			}
			
		} else {
			$total = 0; 
			
			if ($rebut->getImport() <= 0) {
				
				if (count($rebut->getComandes()) > 0) {
					throw new \Exception('Els abonaments no van a compte de cap comanda' );
				}
				
			} else {	
			
				foreach ($rebut->getComandes() as $comanda) {
					
					/* Treure validació 06/12/2016 */
					/*if ($rebut->getDatapagament()->format('Y-m-d') < $comanda->getDataentrada()->format('Y-m-d')) {
						$form->get('datapagament')->addError(new FormError('Data incorrecte'));
						throw new \Exception('La data de pagament ha de ser igual o posterior a la data de la comanda' );
					}*/
					
					$total += $comanda->getTotalComanda();		
				}
				
				//if (abs($rebut->getImport() - $rebut->getComanda()->getTotalComanda()) > 0.01) {
				if ($total > $rebut->getImport()) {	
									
					$form->get('import')->addError(new FormError('Valor incorrecte'));
					throw new \Exception('El total de les comandes supera l\'import de l\'ingrés');
				}
			}

			// Validacions edició
			if ($rebutOriginal != null) {
				$editable = $rebutOriginal->esIngres() || $rebutOriginal->getTipuspagament() != BaseController::TIPUS_PAGAMENT_TPV;
				
				if ($rebut->getClub()->getCodi() != $rebutOriginal->getClub()->getCodi()) {
					$form->get('club')->addError(new FormError('Valor incorrecte'));
					throw new \Exception('No es pot modificar el club associat al rebut');
				}
				
				if (!$editable && $rebut->getDadespagament() != $rebutOriginal->getDadespagament()) {
					$form->get('dadespagament')->addError(new FormError('Valor incorrecte'));
					throw new \Exception('No es poden modificar les dades dels rebuts del TPV');
				}
				
				if (!$editable && $rebut->getTipuspagament() != $rebutOriginal->getTipuspagament()) {
					$form->get('tipuspagament')->addError(new FormError('Valor incorrecte'));
					throw new \Exception('No es pot modificar el tipus de pagament dels rebuts del TPV');
				}
					
				if (!$editable && $rebut->getDatapagament()->format('Y-m-d') != $rebutOriginal->getDatapagament()->format('Y-m-d')) {
					$form->get('datapagament')->addError(new FormError('Valor incorrecte'));
					throw new \Exception('No es pot modificar la data de pagament dels rebuts del TPV');	
				}

				if (!$editable && $rebut->getImport() != $rebutOriginal->getImport()) {
					$form->get('datapagament')->addError(new FormError('Valor incorrecte'));
					throw new \Exception('No es pot modificar l\'import dels rebuts del TPV');
				}	
				
				// La resta de rebuts no poden modificar ni import ni data
				if ($rebutOriginal->estaComptabilitzat() && $rebut->getImport() != $rebutOriginal->getImport()) {
					$form->get('import')->addError(new FormError('Valor incorrecte'));
					throw new \Exception('No es pot modificar l\'import dels rebuts comptabilitzats, cal anul·lar l\'apunt');
				}
					
				if ($rebutOriginal->estaComptabilitzat() && $rebut->getDatapagament() != $rebutOriginal->getDatapagament()) {
					$form->get('datapagament')->addError(new FormError('Valor incorrecte'));
					throw new \Exception('No es pot modificar la data de pagamentdels rebuts comptabilitzats, cal anul·lar l\'apunt');
				}
			}

		}
		
	} 
	
	private function consultaComandes($codi, $nc, $ac, $nf, $af, $nr, $ar, $baixes, $pendents, $strOrderBY = 'c.dataentrada', $direction = 'desc' ) {
		
		$em = $this->getDoctrine()->getManager();
		
		$anulaIds = array();
		
		$strQuery = "SELECT c, f, r FROM FecdasBundle\Entity\EntityComanda c LEFT JOIN c.factura f LEFT JOIN c.rebut r ";
		$strQuery .= "WHERE 1 = 1 ";
		if ($codi != '') $strQuery .= " AND c.club = :codi ";
		
		if (is_numeric($nf) && $nf > 0) {
			$strQuery .= " AND ( (f.num = :numfactura ";

			if (is_numeric($af) && $af > 0) {
				$datainicifactura = \DateTime::createFromFormat('Y-m-d H:i:s', $af."-01-01 00:00:00");
				$datafinalfactura = \DateTime::createFromFormat('Y-m-d H:i:s', $af."-12-31 23:59:59");
				$strQuery .= " AND f.datafactura >= :fini AND f.datafactura <= :ffi ) ";
			} else {
				$strQuery .= " ) ";
			}

			// Obté anul·lacions amb aquest número			
			$anulacions = $this->consultaFacturesAnulacio($nf, $af);
			foreach ($anulacions as $factura) {
				$anulaIds[] = $factura->getComandaAnulacio()->getId();
			}
			
			if (count($anulaIds) > 0) $strQuery .= " OR c.id IN (:anulacions) ";
			
			$strQuery .= " ) ";
			
		}
		
		if (is_numeric($nr) && $nr > 0) {
			$strQuery .= " AND r.num = :numrebut ";

			if (is_numeric($ar) && $ar > 0) {
				$datainicirebut = \DateTime::createFromFormat('Y-m-d H:i:s', $ar."-01-01 00:00:00");
				$datafinalrebut = \DateTime::createFromFormat('Y-m-d H:i:s', $ar."-12-31 23:59:59");
				$strQuery .= " AND r.datapagament >= :rini AND r.datapagament <= :rfi ";
			}
		}
		
		if (is_numeric($nc) && $nc > 0) {
			$strQuery .= " AND c.num = :numcomanda ";

			if (is_numeric($ac) && $ac > 0) {
				$datainicicomanda = \DateTime::createFromFormat('Y-m-d H:i:s', $ac."-01-01 00:00:00");
				$datafinalcomanda = \DateTime::createFromFormat('Y-m-d H:i:s', $ac."-12-31 23:59:59");
				$strQuery .= " AND c.dataentrada >= :cini AND c.dataentrada <= :cfi ";
			}
		}
		
		if (!$baixes) $strQuery .= " AND c.databaixa IS NULL ";
		
		if ($pendents) $strQuery .= " AND c.rebut IS NULL ";
		
		$pos = strpos($strOrderBY, "r.num");
		if ($pos !== false) $strQuery .= " AND c.rebut IS NOT NULL ";
		
		$pos = strpos($strOrderBY, "f.num");
		if ($pos !== false) $strQuery .= " AND c.factura IS NOT NULL ";
		
		$strQuery .= " ORDER BY " .implode(" ".$direction.", ",explode(",",$strOrderBY)). " ".$direction;
		
		$query = $em->createQuery($strQuery);
	
		if ($codi != '') $query->setParameter('codi', $codi);
		if (is_numeric($nf) && $nf > 0) {
			$query->setParameter('numfactura', $nf);

			if (is_numeric($af) && $af > 0) {
				$query->setParameter('fini', $datainicifactura);
				$query->setParameter('ffi', $datafinalfactura);
			}
				
			if (count($anulaIds) > 0) {
				$query->setParameter('anulacions', $anulaIds);
			}
		}
		if (is_numeric($nr) && $nr > 0) {
			$query->setParameter('numrebut', $nr);

			if (is_numeric($ar) && $ar > 0) {
				$query->setParameter('rini', $datainicirebut);
				$query->setParameter('rfi', $datafinalrebut);
			}
		}
		if (is_numeric($nc) && $nc > 0) {
			$query->setParameter('numcomanda', $nc);

			if (is_numeric($ac) && $ac > 0) {
				$query->setParameter('cini', $datainicicomanda);
				$query->setParameter('cfi', $datafinalcomanda);
			}
		}
		
		return $query;
	}

	private function consultaFactures($codi, $nc, $ac, $nf, $af, $pendents, $strOrderBY = 'f.datafactura', $direction = 'desc' ) {
		
		$em = $this->getDoctrine()->getManager();
		
		$current = $this->getCurrentDate();
		$datainicifactura = $current;
		$datafinalfactura = $current;
		$datainicicomanda = $current; 
		$datafinalcomanda = $current;
		
		$strQuery = "SELECT f FROM FecdasBundle\Entity\EntityFactura f LEFT JOIN f.comanda c LEFT JOIN f.comandaanulacio a ";
		$strQuery .= "WHERE 1 = 1 ";
		if ($codi != '') $strQuery .= " AND (c.club = :codi OR a.club = :codi) ";
		
		if (is_numeric($nf) && $nf > 0) {
			$strQuery .= " AND f.num = :numfactura ";

			if (is_numeric($af) && $af > 0) {
				$datainicifactura = \DateTime::createFromFormat('Y-m-d H:i:s', $af."-01-01 00:00:00");
				$datafinalfactura = \DateTime::createFromFormat('Y-m-d H:i:s', $af."-12-31 23:59:59");
				$strQuery .= " AND f.datafactura >= :fini AND f.datafactura <= :ffi ";
			}
		}
		
		if (is_numeric($nc) && $nc > 0) {
			$strQuery .= " AND ( (c.num = :numcomanda ";

			if (is_numeric($ac) && $ac > 0) {
				$datainicicomanda = \DateTime::createFromFormat('Y-m-d H:i:s', $ac."-01-01 00:00:00");
				$datafinalcomanda = \DateTime::createFromFormat('Y-m-d H:i:s', $ac."-12-31 23:59:59");
				$strQuery .= " AND c.dataentrada >= :cini AND c.dataentrada <= :cfi) ";
			} else {
				$strQuery .= " ) ";
			}
			
			$strQuery .= " OR (a.num = :numcomanda ";

			if (is_numeric($ac) && $ac > 0) {
				$datainicicomanda = \DateTime::createFromFormat('Y-m-d H:i:s', $ac."-01-01 00:00:00");
				$datafinalcomanda = \DateTime::createFromFormat('Y-m-d H:i:s', $ac."-12-31 23:59:59");
				$strQuery .= " AND a.dataentrada >= :cini AND a.dataentrada <= :cfi) ";
			} else {
				$strQuery .= " ) ";
			}
			$strQuery .= " ) ";
		}
		
		if ($pendents) $strQuery .= " AND (c.rebut IS NULL AND a.rebut IS NULL) ";
		
		$strQuery .= " ORDER BY " .implode(" ".$direction.", ",explode(",",$strOrderBY)). " ".$direction;
		
		$query = $em->createQuery($strQuery);
	
		if ($codi != '') $query->setParameter('codi', $codi);
		if (is_numeric($nf) && $nf > 0) {
			$query->setParameter('numfactura', $nf);

			if (is_numeric($af) && $af > 0) {
				$query->setParameter('fini', $datainicifactura);
				$query->setParameter('ffi', $datafinalfactura);
			}
		}
		
		if (is_numeric($nc) && $nc > 0) {
			$query->setParameter('numcomanda', $nc);

			if (is_numeric($ac) && $ac > 0) {
				$query->setParameter('cini', $datainicicomanda);
				$query->setParameter('cfi', $datafinalcomanda);
			}
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
	
		$url = $this->generateUrl('FecdasBundle_homepage');
		$error = '';
		$tpvresponse = array();
		try {
			$redsysapi = $this->validaFirmaNotificacio($request->query->all());
			
			if ($redsysapi == null) throw new \Exception('Error resposta notificació. Signatura invàlida');
	
			$tpvresponse = $this->tpvResponse($redsysapi);

			if ($tpvresponse['Ds_Response'] >= '0100' && $tpvresponse['pendent'] != true)  throw new \Exception('Error TPV codi '.$tpvresponse['Ds_Response']);

			$result = 'OK';
			
			if ($tpvresponse['itemId'] > 0) {
				if ($tpvresponse['pendent'] == true) $result = 'PEND';
				
				if ($tpvresponse['source'] = BaseController::PAGAMENT_LLICENCIES)
					$url = $this->generateUrl('FecdasBundle_parte', array('id'=> $tpvresponse['itemId']));
				if ($tpvresponse['source'] = BaseController::PAGAMENT_DUPLICAT)
					$url = $this->generateUrl('FecdasBundle_duplicats');
				if ($tpvresponse['source'] = BaseController::PAGAMENT_ALTRES)
					$url = $this->generateUrl('FecdasBundle_comandes');
			}
		} catch (\Exception $e) {
			$result = 'KO';
			$error = $e->getMessage();
		}
		
		$this->logEntryAuth('TPV NOTIFICA '. $result, $error.' '.implode(' - ', $tpvresponse));
		
		return $this->render('FecdasBundle:Facturacio:notificacio.html.twig',
				array('result' => $result, 'error' => $error, 'url' => $url) );
	}
	
	public function notificacioAction(Request $request) {
		// Crida asincrona des de TPV. Actualització dades pagament del parte

		$tpvresponse = array();
		try {
			$redsysapi = $this->validaFirmaNotificacio($request->request->all());

			if ($redsysapi == null) throw new \Exception('Error resposta notificació. Signatura invàlida');

			$tpvresponse = $this->tpvResponse($redsysapi);
			
			if ($tpvresponse['Ds_Response'] < '0100') {
				// Ok
				$id = !isset($tpvresponse['itemId'])?0:$tpvresponse['itemId'];
				$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($id);
					
				if ($comanda == null) throw new \Exception('Error actualitzar comanda TPV. id: '.$id);
				
				// Afegir rebut
				$this->crearRebut($this->getCurrentDate(), BaseController::TIPUS_PAGAMENT_TPV, $comanda, $tpvresponse['Ds_Response'].':'.$tpvresponse['Ds_Order']);
					
				$em = $this->getDoctrine()->getManager();
				
				$em->flush();
				
				$this->logEntryAuth('TPV OK', implode(' - ', $tpvresponse));
				
				return new Response('');
			} 
			
			if ($tpvresponse['pendent'] == true) {
				// Pendent, enviar mail 
				$subject = ":: TPV. Pagament pendent de confirmació ::";
				$bccmails = array();
				$tomails = array($this->getParameter('MAIL_ADMINTEST'));
						
				$body = "<h1>Parte pendent</h1>";
				$body .= "<p>". $tpvresponse['logEntry']. "</p>"; 
				$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
					
				$this->logEntryAuth('TPV PEND', $tpvresponse['logEntry']);
					
				return new Response('');
			}
			
			throw new \Exception('Error TPV codi '.$tpvresponse['Ds_Response']);
			
		} catch (\Exception $e) {
			
			$strError = ' ('.implode(' - ', $tpvresponse).')';
			$this->logEntryAuth('TPV ERROR', $e->getMessage().$strError);
			
				
			$subject = ':: Incidència TPV ::';
			$bccmails = array();
			$tomails = array($this->getParameter('MAIL_ADMINTEST'));
				
			$body = '<h1>Error TPV</h1>';
			$body .= '<h2>Missatge: '.$e->getMessage().'</h2>';
			$body .= '<p>Dades: '.$strError.'</p>';
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			
			return new Response($e->getMessage().$strError);
		}
		
		return new Response('');
	}

	public function confirmapagamentAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$em = $this->getDoctrine()->getManager();
		
		$comandaId = $request->query->get('id',0);
		
		$comanda = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->find($comandaId);
		if ($comanda != null) {

			$factura = $comanda->getFactura();

			$tipusPagament = $request->query->get('tipuspagament', BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA);
			
			$tipuspagamentOk = $this->checkIbanTipusPagament($tipusPagament, $factura->getNumcompte());

			if (!$tipuspagamentOk) {
				$this->logEntryAuth('CONF. PAGAMENT TIPUS KO', $comandaId);
				
				$response = new Response('El pagament no es correspon amb el compte de la factura');
				$response->setStatusCode(500);
				return $response;
			}
			
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

	private function validaFirmaNotificacio($tpvdata) {

		$redsysapi = new RedsysAPI();		
		
		//$version = $tpvdata["Ds_SignatureVersion"];
		$params = $tpvdata["Ds_MerchantParameters"];
		$signaturaRebuda = $tpvdata["Ds_Signature"];

		//$decodec = $redsysapi->decodeMerchantParameters($params);	
		
		if ($this->get('kernel')->getEnvironment() == 'dev') $key = $this->getComercRedsysParam( 'COMERC_REDSYS_SHA_256_KEY_TEST' );
        else $key = $this->getComercRedsysParam( 'COMERC_REDSYS_SHA_256_KEY' );
		
		$signatura = $redsysapi->createMerchantSignatureNotif($key, $params);

		if ($signatura !== $signaturaRebuda) return null;

		return $redsysapi;
	}

	private function tpvResponse($redsysapi) {
		
		$tpvresponse = array('itemId' => 0, 'environment' => '', 'source' => '',
				'Ds_Response' => '', 'Ds_Order' => 0, 'Ds_Date' => '', 'Ds_Hour' => '',
				'pendent' => false);
		
		$dades = $redsysapi->getParameter("Ds_MerchantData");
		
		$dadesArray = explode(";", urldecode($dades));

		if (!is_array($dadesArray) || count($dadesArray) != 3) throw new \Exception('Error TPV dades retornades ');				
		
		//$environment = $dadesArray[2];
		
		$tpvresponse['itemId'] = $dadesArray[0];
		$tpvresponse['source'] = $dadesArray[1];  /* Origen del pagament. Partes, duplicats */
		$tpvresponse['environment'] = $dadesArray[2];
	
		$tpvresponse['Ds_Response'] = $redsysapi->getParameter("Ds_Response");	// entre 0000 i 0099 OK, > 100 ERROR
		$tpvresponse['Ds_Order'] = $redsysapi->getParameter('Ds_Order');
		$tpvresponse['Ds_Date'] = $redsysapi->getParameter('Ds_Date');	// dd/mm/yyyy
		$tpvresponse['Ds_Hour'] = $redsysapi->getParameter('Ds_Hour');	//HH:mm
		
		if ($tpvresponse['Ds_Response'] == '9930') {
			try {
				$tpvresponse['Ds_PayMethod'] = $redsysapi->getParameter('Ds_PayMethod');
			} catch (\Exception $e) {
				$tpvresponse['Ds_PayMethod'] = '';
			}
		}
	
		if (($tpvresponse['Ds_Response'] == '0930' || ($tpvresponse['Ds_Response'] == '9930') && $tpvresponse['Ds_PayMethod'] == 'R')) {
			$tpvresponse['pendent'] = true;
		}
		
		return $tpvresponse;
	}
	
	public function notificacioTestAction(Request $request) {
		// http://www.fecdas.dev/notificaciotest
		if ($request->isXmlHttpRequest()) {
			// Muntar form per enviar des de ajax amb les dades inicials 			
			try {
				
				if ($request->getMethod() != 'POST') throw new \Exception('Mètode no POST');
			
				$formdata = $request->request->get('form');
				
				$redsysapi = new RedsysAPI();
				$redsysapi->setParameter("Ds_Date",$formdata['Ds_Date']);
				$redsysapi->setParameter("Ds_Hour",$formdata['Ds_Hour']);
				$redsysapi->setParameter("Ds_Amount",0);
				$redsysapi->setParameter("Ds_Currency",$this->getComercRedsysParam( 'COMERC_REDSYS_CURRENCY' ));
				$redsysapi->setParameter("Ds_Order",$formdata['Ds_Order']);
				$redsysapi->setParameter("Ds_MerchantCode",$this->getComercRedsysParam( 'COMERC_REDSYS_FUC' ));
				$redsysapi->setParameter("Ds_Terminal",$this->getComercRedsysParam( 'COMERC_REDSYS_TERMINAL' ));
				$redsysapi->setParameter("Ds_Response",$formdata['Ds_Response']);
				$redsysapi->setParameter("Ds_MerchantData",$formdata['Ds_MerchantData']);
				$redsysapi->setParameter("Ds_SecurePayment",1);
				$redsysapi->setParameter("Ds_TransactionType",$this->getComercRedsysParam( 'COMERC_REDSYS_TRANS' ));
				$redsysapi->setParameter("Ds_ConsumerLanguage", $this->getComercRedsysParam( 'COMERC_REDSYS_LANG' ));		// Català - 3

				
				$strResponse = '<div>'.$formdata['accio'].'</div>';
				if ($formdata['accio'] == $this->generateUrl('FecdasBundle_notificacio')) $method = 'POST';
				else $method = 'GET';
				 
				$paramsResponse = $redsysapi->encodeBase64(json_encode($redsysapi->vars_pay)); 
				 
				$strResponse .= '<form id="responseform" action="'.$formdata['accio'].'" method="'.$method.'"  class="appform">';
		   		$strResponse .= '<p><textarea rows="5" name="Ds_MerchantParameters">'.$paramsResponse.'</textarea></p>';
		   		$strResponse .= '<p><input type="text" name="Ds_Signature" value="'.$redsysapi->createMerchantSignatureNotif($this->getComercRedsysParam( 'COMERC_REDSYS_SHA_256_KEY_TEST' ), $paramsResponse).'"></p>';	
		   		$strResponse .= '<p><input type="text" name="Ds_SignatureVersion" value="'.$this->getComercRedsysParam( 'COMERC_REDSYS_SHA_256_VERSION' ).'"></p>';
		   		$strResponse .= '<p><input type="submit" class="forminput-inside" value="Test" /></p>';
		   		$strResponse .= '</form>';
	
			
			} catch (\Exception $e) {
				$strResponse = 'Error creant form '.$e->getMessage().'<br/>'.json_encode($formdata);
			}
	
			return new Response($strResponse); // ajax
	
		} else {
			// Form inicial de dades
			//Escollir darrera comanda
			$strQuery = " SELECT MAX(c.id) FROM FecdasBundle\Entity\EntityComanda c ";
			$em = $this->getDoctrine()->getManager();
			$query = $em->createQuery($strQuery);
			$id = $query->getSingleScalarResult();
			if ($id == null) $id = 0;
			
			$formBuilder = $this->createFormBuilder()->add('Ds_Response', 'text', array('data' => 0));
			$formBuilder->add('Ds_MerchantData', 'text', array('required' => false, 'data' => $id.'%3B'.self::PAGAMENT_LLICENCIES.'%3Bdev'));
			$formBuilder->add('Ds_Date', 'text', array('data' => date('d/m/Y')));
			$formBuilder->add('Ds_Hour', 'text', array('data' => date('h:i')));
			$formBuilder->add('Ds_Order', 'text', array('data' => date('Ymdhi')));
			
			$formBuilder->add('accio', 'choice', array(
					'choices'   => array($this->generateUrl('FecdasBundle_notificacio') => 'FecdasBundle_notificacio',
							$this->generateUrl('FecdasBundle_notificacioOk') => 'FecdasBundle_notificacioOk',
							$this->generateUrl('FecdasBundle_notificacioKo') => 'FecdasBundle_notificacioKo'),
					'required'  => true,
			));
		}
		
		$form = $formBuilder->getForm();
	
		return $this->render('FecdasBundle:Facturacio:notificacioTest.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView())));
	}
	
}
