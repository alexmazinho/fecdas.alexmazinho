<?php 
namespace FecdasBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Entity\EntityClub;


class AdminController extends BaseController {
	
	public function changeroleAction(Request $request) {
		if (!$this->isCurrentAdmin()) return new Response(""); 
			
		// Canviar Club Administrador	
		if ($request->query->has('roleclub')) $this->get('session')->set('roleclub', $request->query->get('roleclub'));
		
		return new Response("");
	}
	
	public function recentsAction(Request $request) {
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		// Només jo
		/*if ($this->get('session')->get('username') != 'alexmazinho@gmail.com')
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));*/
		
		$em = $this->getDoctrine()->getManager();
	
		$states = explode(";", self::CLUBS_STATES);
		$defaultEstat = self::TOTS_CLUBS_DEFAULT_STATE; // Tots normal
		if ($this->get('session')->get('username', '') == self::MAIL_FACTURACIO)  $defaultEstat = self::CLUBS_DEFAULT_STATE; // Diferits Remei

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
		$currentClub = $em->getRepository('FecdasBundle:EntityClub')->find($request->query->get('clubs', ''));
		
		$currentEstat = $request->query->get('estat', $defaultEstat);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'p.dataentrada');
		$direction = $request->query->get('direction', 'asc');
		
		if ($request->getMethod() == 'POST') {
			// Criteris de cerca.Desactivat JQuery  
			/*if ($request->request->has('form')) { 
				
				$formdata = $request->request->get('form');
				
				$page = 1; // Submit sempre comença per 1
				$sort = $formdata['sort'];
				$direction = $formdata['direction'];
				
				if (isset($formdata['clubs'])) $currentClub = $em->getRepository('FecdasBundle:EntityClub')->find($formdata['clubs']);
				if (isset($formdata['estat'])) $currentEstat = $formdata['estat'];
				if (isset($formdata['nopagat'])) $currentNoPagat = true; // Tots
				else $currentNoPagat = false;
				if (isset($formdata['baixa'])) $currentBaixa = true;
				else $currentBaixa = false;
				if (isset($formdata['nosincro'])) $currentNoSincro = true;
				else $currentNoSincro = false;

				
				
			}*/
			$this->logEntryAuth('ADMIN PARTES POST', "club: " . ($currentClub==null)?"":$currentClub->getNom() . " filtre estat: " . $states[$currentEstat] .
					" factura " .$currentNumfactura . " rebut " .$currentNumrebut . " pagament: " . $currentNoPagat . " baixa: " . $currentBaixa );
		} else {
			$this->logEntryAuth('ADMIN PARTES');
		}
		
		$formBuilder = $this->createFormBuilder();
		
		$clubsSelectOptions = array('class' => 'FecdasBundle:EntityClub',
				'choice_label' => 'nom',
				'label' => 'Filtre per club: ',
				'required'  => false );
			
		if ($currentClub != null) $clubsSelectOptions['data'] = $currentClub;
		
		$formBuilder->add('clubs', 'genemu_jqueryselect2_entity', $clubsSelectOptions);
		
		$formBuilder->add('estat', 'choice', array(
				'choices'   => $states,
				'preferred_choices' => array($defaultEstat),  // Estat per defecte sempre 
				'data' => $currentEstat
		));
		
		$current = date('Y');
		$formBuilder->add('numfactura', 'text', array(
					'required'  => false,
					'data' => $currentNumfactura,
				));
		$formBuilder->add('anyfactura', 'choice', array(
				'choices'   => array($current => $current, $current-1 => $current-1),
				'preferred_choices' => array($current),  // Any actual i anterior 
				'data' => $currentAnyfactura
		));
		
		$formBuilder->add('numrebut', 'text', array(
					'required'  => false,
					'data' => $currentNumrebut,
				));
		$formBuilder->add('anyrebut', 'choice', array(
				'choices'   => array($current => $current, $current-1 => $current-1),
				'preferred_choices' => array($current),  // Any actual i anterior 
				'data' => $currentAnyrebut
		));
		
		$formBuilder->add('nopagat', 'checkbox', array(
					'required'  => false,
					'data' => $currentNoPagat,
				));
		$formBuilder->add('noimpres', 'checkbox', array(
					'required'  => false,
					'data' => $currentNoImpres,
				));
		$formBuilder->add('baixa', 'checkbox', array(
    				'required'  => false,
					'data' => $currentBaixa,
				));
		$formBuilder->add('compta', 'checkbox', array(
    				'required'  => false,
					'data' => $currentCompta,
				));
		$form = $formBuilder->getForm();
		
		
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p JOIN p.tipus t JOIN p.club c JOIN c.estat e ";
		$strQuery .= " LEFT JOIN p.rebut r LEFT JOIN p.factura f WHERE ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365))";

		
		if ($currentNumrebut == '' && $currentNumfactura == '') {
			// Dates normals
			$inianual = $this->getSQLIniciAnual();
			$ini365 = $this->getSQLInici365();
		} else {
			// dates dels anys escollits a les factures / rebuts
			$inianual = min($currentAnyrebut, $currentAnyfactura).'-01-01 00:00:00';
			$ini365 = $inianual;
		
			if ($currentNumrebut != '') $strQuery .= " AND r.num = :numrebut ";
			if ($currentNumfactura != '') $strQuery .= " AND f.num = :numfactura ";
		}
		
		if ($currentClub != null) $strQuery .= " AND p.club = '" .$currentClub->getCodi() . "' "	;
		if ($currentEstat != self::TOTS_CLUBS_DEFAULT_STATE) $strQuery .= " AND e.descripcio = :filtreestat ";
		
		
		if ($currentBaixa == false) $strQuery .= " AND p.databaixa IS NULL ";
		if ($currentNoPagat == true) $strQuery .= " AND p.rebut IS NULL ";
		if ($currentNoImpres == true) $strQuery .= " AND (p.impres IS NULL OR p.impres = 0) AND p.pendent = 0 ";
		if ($currentCompta == true) $strQuery .= " AND f.comptabilitat <> 1 ";
		/* Quan es sincronitza es posa la data modificació a NULL de partes i llicències (No de persones que funcionen amb el check validat). 
		 * Els canvis des del gestor també deixen la data a NULL per detectar canvis del web que calgui sincronitzar */ 
		//if ($currentNoSincro == true) $strQuery .= " AND (p.idparte_access IS NULL OR (p.idparte_access IS NOT NULL AND p.datamodificacio IS NOT NULL) ) ";

		$strQuery .= " ORDER BY ".$sort; 

		$query = $em->createQuery($strQuery)
			->setParameter('ininormal', $inianual)
			->setParameter('ini365', $ini365);
		

		if ($currentNumrebut == '' && $currentNumfactura == '') {
			// Dates normals
		} else {
			// dates dels anys escollits a les factures / rebuts
			/*if ($currentNumrebut == true) $partesrecents->setParam('numrebut',$currentNumrebut);
			if ($currentNumfactura == true) $partesrecents->setParam('numfactura',$currentNumfactura);*/
			if ($currentNumrebut == true) $query->setParameter('numrebut',$currentNumrebut);
			if ($currentNumfactura == true) $query->setParameter('numfactura',$currentNumfactura);
		}

		/*if ($currentEstat != self::TOTS_CLUBS_DEFAULT_STATE) $query->setParameter('filtreestat', $states[$currentEstat]);
	
		// Paràmetres URL sort i pagination 
		if ($currentClub != null) $partesrecents->setParam('clubs',$currentClub->getCodi());
		if ($currentEstat != self::TOTS_CLUBS_DEFAULT_STATE) $partesrecents->setParam('estat',$currentEstat);
		
		if ($currentBaixa == true) $partesrecents->setParam('baixa',true);
		//if ($currentNoSincro == false) $partesrecents->setParam('nosincro',false);
		if ($currentNoPagat == true) $partesrecents->setParam('nopagat',true);
		if ($currentNoImpres == true) $partesrecents->setParam('noimpres',true);*/
				
		$sortparams = array('sort' => $sort,'direction' => $direction, 
							/*'numrebut' => $currentNumrebut, 'anyrebut' => $currentAnyrebut,
							'numfactura' => $currentNumfactura, 'anyfactura' => $currentAnyfactura,
							'estat' => $currentEstat, 'baixa' => true, 'nopagat' => true, 'noimpres' => 'true' */);

		$paginator  = $this->get('knp_paginator');
		$partesrecents = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		);
		
		$partesrecents->setParam('sortparams',$sortparams);
		
		return $this->render('FecdasBundle:Admin:recents.html.twig', 
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'partes' => $partesrecents,
						'sortparams' => $sortparams
				)));
	}
	
	public function consultaadminAction(Request $request) {
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$em = $this->getDoctrine()->getManager();
	
	
		// GET OPCIONS DE FILTRE
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'p.dataentrada');
		$direction = $request->query->get('direction', 'asc');

		$clubs = $request->query->get('clubs', array()); // Per defecte sense filtre de clubs

		$tipusparte = $request->query->get('tipusparte', array()); // Per defecte sense filtre de tipus

		$strDatainici = $request->query->get('datainici', '');
		$datainici = null;
		if ($strDatainici != '') $datainici = \DateTime::createFromFormat('d/m/y', $strDatainici); 
		
		$strDatafinal = $request->query->get('datafinal', '');
		if ($strDatafinal == '') $datafinal = $this->getCurrentDate();
		else $datafinal = \DateTime::createFromFormat('d/m/y', $strDatafinal); 
		
		$intervals = false;// Per defecte consulta periode
		if ($request->query->has('intervals') && $request->query->get('intervals') == 1) $intervals = true;
		
		$intervaldata = $request->query->get('intervaldata', 'M');
		
		$nombretotals = false;
		if ($request->query->has('nombretotals') && $request->query->get('nombretotals') == 1) $nombretotals = true;

		$sumatotals = false;
		if ($request->query->has('sumatotals') && $request->query->get('sumatotals') == 1) $sumatotals = true;

		$baixes = false;
		if ($request->query->has('baixes') && $request->query->get('baixes') == 1) $baixes = true;

		$noves = false;
		if ($request->query->has('noves') && $request->query->get('noves') == 1) $noves = true;

		
		if ($request->getMethod() == 'POST') {
		} else {
		}
		
		
		// CREAR FORMULARI
		$formBuilder = $this->createFormBuilder();
		
		
		// Selector múltiple de clubs
		$formBuilder->add('clubs', 'entity', array(
				'class' 		=> 'FecdasBundle:EntityClub',
		 		'choice_label' 	=> 'llistaText',
				'required'  	=> false,
				'data'			=>  $clubs,
				'multiple'		=> true));
		
		
		// Selector tipus de llicència
		$formBuilder->add('tipusparte', 'entity', array('class' => 'FecdasBundle:EntityParteType', 
				'choice_label' 	=> 'descripcio', 
				'multiple' 		=> true, 
				'required' 		=> false,
				'data'			=> array($tipusparte),
				'query_builder' => function($repository) {
					return $repository->createQueryBuilder('e')->where('e.actiu = true')->orderBy('e.id', 'ASC');
				})
		);
		
		// Selectors de dates: rang entre dates i per intervals mesos / anys
		$formBuilder->add('datainici', 'datetime', array(
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
				'data' 			=> $datainici
		));

		$formBuilder->add('datafinal', 'datetime', array(
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
				'data' 			=> $datafinal
		));
		
		$formBuilder->add('intervals', 'checkbox', array(
    			'required'  => false,
				'data' => $intervals,
		));
		
		
		$formBuilder->add('intervaldata', 'choice', array(
				'choices'   	=> array('M' => 'Mensual', 'A' => 'Anual'),
				'required' 		=> false,
				'expanded'		=> true,
				'multiple'		=> false,
				'empty_value' 	=> false,
				'disabled'		=> $intervals == false,
				'data' 			=> $intervaldata
		));
		
		// Acumular  totals
		$formBuilder->add('nombretotals', 'checkbox', array(
    			'required'  => false,
				'data' => $nombretotals,
		));
		
		$formBuilder->add('sumatotals', 'checkbox', array(
    			'required'  => false,
				'data' => $sumatotals,
		));

		// Selectors edats: rang entre edats i per intervals: 5, 10, 20
		
		// Per categories: infantil, tècnic, aficionat 
		
		// Per sexe: Home / Dona
		
		// Baixes
		$formBuilder->add('baixes', 'checkbox', array(
    			'required'  => false,
				'data' => $baixes,
		));
		
		// Noves, federats primera vegada
		$formBuilder->add('noves', 'checkbox', array(
    			'required'  => false,
				'data' => $noves,
		));
		
		
		// Temps des de la darrera llicència
		$form = $formBuilder->getForm();
		
		
		// PREPARAR CONSULTA
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT l FROM FecdasBundle\Entity\EntityLlicencia l JOIN l.parte p JOIN p.tipus t JOIN p.club c ";
		/*$strQuery .= " LEFT JOIN p.rebut r LEFT JOIN p.factura f WHERE ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365))";*/
		
		$strQuery .= " ORDER BY ".$sort; 

		$query = $em->createQuery($strQuery);
			/*->setParameter('ininormal', $inianual)
			->setParameter('ini365', $ini365);*/
		
		$sortparams = array('sort' => $sort,'direction' => $direction);

		$paginator  = $this->get('knp_paginator');
		$resultat = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		);
		
		$resultat->setParam('sortparams',$sortparams);
		
		return $this->render('FecdasBundle:Admin:consultaadmin.html.twig', 
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'resultat' => $resultat,
						'sortparams' => $sortparams
				)));
	}
	
	
	
	public function sincroaccessAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$em = $this->getDoctrine()->getManager();

		
		$parteid = $request->query->get("id");
		
		$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
		
		if ($parte != null) {
			$interval = \DateInterval::createfromdatestring('+15 minute');
			$current = $this->getCurrentDate();
			$current->add($interval);
			
			$parte->setDatamodificacio($current);
			
			foreach ($parte->getLlicencies() as $llicencia_iter) {
				if ($llicencia_iter->getDatabaixa() == null) {
					$llicencia_iter->setDatamodificacio($current);
					$llicencia_iter->getPersona()->setValidat(false);
				}
			}
			
			$em->flush();

			$this->get('session')->getFlashBag()->add('error-notice', 'Llista '.$parteid.' preparada per tornar a sincronitzar');
			
			$this->logEntryAuth('SINCRO ACCESS', $parteid);
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', 'Error en el procés de sincronització');
			
			$this->logEntryAuth('SINCRO ACCESS ERROR', $parteid);
		}
		
		$response = $this->forward('FecdasBundle:Admin:recents');
		return $response;
	}
	
	public function canviestatclubAction (Request $request) {
		if ($this->isCurrentAdmin() != true) return new Response("no admin");
		
		$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($request->query->get('codiclub'));
		$estat = $request->query->get('action');
		$limitcredit = $request->query->get('limitcredit');
		if ($limitcredit == "") $limitcredit = null;
		
		if ($club == null) {
			$this->logEntryAuth('CLUB STATE ERROR', $request->query->get('codiclub'));
			return new Response("ko");
		}
		
		$em = $this->getDoctrine()->getManager();
		
		switch ($estat) {
			case BaseController::CLUB_PAGAMENT_DIFERIT:  // Pagament diferit

				if ($request->query->get('imprimir') == 'true') $club->setImpressio(true);
				else $club->setImpressio(false);

				break;
			case BaseController::CLUB_PAGAMENT_IMMEDIAT:  // Pagament immediat
				
				break;
			case BaseController::CLUB_SENSE_TRAMITACIO:  // Sense tramitació
				
				// Enviar notificació mail
				$subject = "Notificació. Federació Catalana d'Activitats Subaquàtiques";
				if ($club->getMail() == null) $subject = "Notificació. Cal avisar aquest club no té adreça de mail al sistema";
				
				$bccmails = $this->getFacturacioMails();
				$tomails = array($club->getMail());
				$body = "<p>Benvolgut club ".$club->getNom()."</p>";
				$body .= "<p>Us fem saber que, a partir de la recepció d’aquest correu, 
						per a la realització de tràmits en el sistema de gestió de 
						llicències federatives i assegurances de la FECDAS us caldrà 
						contactar prèviament amb la federació</p>";
				
				$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			
				break;
		}

		$estatAnterior = $club->getEstat();
		$estat = $this->getDoctrine()->getRepository('FecdasBundle:EntityClubEstat')->find($estat);
		$club->setEstat($estat);
		$club->setLimitcredit($limitcredit);
		$em->flush();
		
		$this->logEntryAuth('CLUB STATE OK', $club->getNom()." ".$estatAnterior->getCodi()." -> ".$estat->getCodi());
		
		return new Response("ok");
	}
	
	public function clubsAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		// Només jo
		/*if ($this->get('session')->get('username') != 'alexmazinho@gmail.com')
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));*/
	
		$em = $this->getDoctrine()->getManager();
	
		$states = explode(";", self::CLUBS_STATES);
		$currentEstat = self::CLUBS_DEFAULT_STATE;
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.nom');
		$direction = $request->query->get('direction', 'asc');
		$currentEstat = $request->query->get('estat', $currentEstat);
		
		if ($request->getMethod() == 'POST') {
		// Criteris de cerca.Desactivat JQuery 
			$this->logEntryAuth('SALDO CLUBS POST', "Filtre estat: " . $states[$currentEstat]);
			
		} else {
			$this->logEntryAuth('SALDO CLUBS');
		}
			
		$formBuilder = $this->createFormBuilder()->add('estat', 'choice', array(
				'choices'   => $states,
				'preferred_choices' => array(self::CLUBS_DEFAULT_STATE),  // Estat per defecte sempre
				'data' => $currentEstat
		));
		
		$form = $formBuilder->getForm();
	
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityClub c JOIN c.estat e ";
		$strQuery .= " WHERE c.activat = true AND c.codi <> 'CAT000' ";
		if ($currentEstat != 0) $strQuery .= " AND e.descripcio = :filtreestat ";
		$strQuery .= " ORDER BY ". $sort;
		$query = $em->createQuery($strQuery);
		if ($currentEstat != 0) $query->setParameter('filtreestat', $states[$currentEstat]);
		
		$paginator  = $this->get('knp_paginator');
		$clubs = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		);
		$clubs->setParam('estat', $currentEstat);
		
		//$form->get('estat')->setData($currentEstat);  // Mantenir estat darrera consulta

		return $this->render('FecdasBundle:Admin:clubs.html.twig',  
			$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'clubs' => $clubs,
					'sortparams' => array('sort' => $sort,'direction' => $direction)
			))); 
	}
	
	public function anularpeticioAction(Request $request) {
		/* Anular petició duplicat */
				
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$em = $this->getDoctrine()->getManager();
		
		$duplicatid = $request->query->get("id");
		
		$duplicat = $this->getDoctrine()->getRepository('FecdasBundle:EntityDuplicat')->find($duplicatid);
		
		if ($duplicat != null && $duplicat->getCarnet() != null && $producte = $duplicat->getCarnet()->getProducte() != null) {
			$producte = $duplicat->getCarnet()->getProducte();

			$data = $this->getCurrentDate();
			$maxNumFactura = $this->getMaxNumEntity($data->format('Y'), BaseController::FACTURES) + 1;
			$maxNumRebut = $this->getMaxNumEntity($data->format('Y'), BaseController::REBUTS) + 1;

			$detall = $this->removeComandaDetall($duplicat, $producte, 1, $maxNumFactura, $maxNumRebut);	
			
			$em->flush();
		
			$this->get('session')->getFlashBag()->add('sms-notice', 'Petició de duplicat anul·lada correctament');
			
			$this->logEntryAuth('ANULA DUPLI OK', 'duplicat ' . $duplicatid);
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', 'Error anulant la petició');

			$this->logEntryAuth('ANULA DUPLI ERROR', 'duplicat ' . $duplicatid);
		}
		
		return $this->forward('FecdasBundle:Page:duplicats');
		//return $this->redirect($this->generateUrl('FecdasBundle_duplicats'));
	}
	
	public function imprespeticioAction(Request $request) {
		/* Marca petició duplicat com impressa i enviar un correu */
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();

		$duplicatid = $request->query->get("id");
	
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'd.datapeticio');
		$direction = $request->query->get('direction', 'desc');
	
		$duplicat = $this->getDoctrine()->getRepository('FecdasBundle:EntityDuplicat')->find($duplicatid);
	
		if ($duplicat != null) {
			$duplicat->setDataimpressio($this->getCurrentDate());
	
			$em->flush();
	
			// Enviar notificació mail
			$fedeMail = array();
			if ($duplicat->getCarnet()->esLlicencia() == true) $fedeMail = $this->getLlicenciesMails(); // Llicències Remei
			else $fedeMail = $this->getCarnetsMails(); // Carnets Albert
			
			if ($duplicat->getClub()->getMail() != null) {
				$subject = "Petició de duplicat. " . $duplicat->getCarnet()->getTipus();
				$tomails = array($duplicat->getClub()->getMail());
				$bccmails = $fedeMail;
			} else {
				$subject = "Petició de duplicat. " . $duplicat->getCarnet()->getTipus() . " CLUB SENSE CORREU!! ";
				$tomails = $fedeMail;
				$bccmails = array();
			}
			
			$body = "<p>Benvolgut club ".$duplicat->getClub()->getNom()."</p>";
			$body .= "<p>Us fem saber que la petició de duplicat per ";
			$body .= "<strong>".$duplicat->getPersona()->getNom() . " " . $duplicat->getPersona()->getCognoms() . "</strong> (<i>".$duplicat->getTextCarnet()."</i>),";
			$body .= " ha estat impresa i es pot passar a recollir per la Federació.</p>";
			
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			
			$this->get('session')->getFlashBag()->add('sms-notice', 'S\'ha enviat un mail al club');
				
			$this->logEntryAuth('PRINT DUPLI OK', 'duplicat ' . $duplicatid);
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', 'Error indicant impressió de la petició');
	
			$this->logEntryAuth('PRINT DUPLI ERROR', 'duplicat ' . $duplicatid);
		}
	
		return $this->redirect($this->generateUrl('FecdasBundle_duplicats', array('sort' => $sort,'direction' => $direction, 'page' => $page)));
	}
	
	public function ajaxclubsnomsAction(Request $request) {
		$search = $this->consultaAjaxClubs($request->get('term'));
		$response = new Response();
		$response->setContent(json_encode($search));
	
		return $response;
	}
	
}
