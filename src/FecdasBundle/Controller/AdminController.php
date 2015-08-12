<?php 
namespace FecdasBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use FecdasBundle\Form\FormContact;
use FecdasBundle\Form\FormPayment;
use FecdasBundle\Form\FormParte;
use FecdasBundle\Form\FormPersona;
use FecdasBundle\Form\FormLlicencia;
use FecdasBundle\Form\FormParteRenew;
use FecdasBundle\Entity\EntityParteType;
use FecdasBundle\Entity\EntityContact;
use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Entity\EntityPayment;
use FecdasBundle\Entity\EntityUser;
use FecdasBundle\Entity\EntityClub;
use Symfony\Component\HttpFoundation\File\UploadedFile;


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
		$currentNoPagat = true;// No pagats
		if ($request->query->has('nopagat') && $request->query->get('nopagat') == 0) $currentNoPagat = false;
		$currentNoImpres = false;// No impres
		if ($request->query->has('noimpres') && $request->query->get('noimpres') == 1) $currentNoImpres = true;

		
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
					" pagament: " . $currentNoPagat . " baixa: " . $currentBaixa );
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
		
		$formBuilder->add('nopagat', 'checkbox', array(
					'required'  => false,
					'data' => $currentNoPagat,
				));
		$formBuilder->add('noimpres', 'checkbox', array(
					'required'  => false,
					'data' => $currentNoImpres,
				));
		error_log("==========================> ".$currentNoImpres."-".$currentNoPagat);		
		$formBuilder->add('baixa', 'checkbox', array(
    				'required'  => false,
					'data' => $currentBaixa,
				));
		$form = $formBuilder->getForm();
		
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p JOIN p.tipus t JOIN p.club c JOIN c.estat e LEFT JOIN p.rebut r ";
		$strQuery .= "WHERE ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365))";

		if ($currentClub != null) $strQuery .= " AND p.club = '" .$currentClub->getCodi() . "' "	;
		if ($currentEstat != self::TOTS_CLUBS_DEFAULT_STATE) $strQuery .= " AND e.descripcio = :filtreestat ";
				
		if ($currentBaixa == false) $strQuery .= " AND p.databaixa IS NULL ";
		if ($currentNoPagat == true) $strQuery .= " AND (p.rebut IS NULL OR (p.rebut IS NOT NULL AND r.dataanulacio IS NOT NULL)) ";
		if ($currentNoImpres == true) $strQuery .= " AND (p.impres IS NULL OR p.impres = 0) ";
		/* Quan es sincronitza es posa la data modificació a NULL de partes i llicències (No de persones que funcionen amb el check validat). 
		 * Els canvis des del gestor també deixen la data a NULL per detectar canvis del web que calgui sincronitzar */ 
		//if ($currentNoSincro == true) $strQuery .= " AND (p.idparte_access IS NULL OR (p.idparte_access IS NOT NULL AND p.datamodificacio IS NOT NULL) ) ";

		$strQuery .= " ORDER BY ".$sort; 

		$query = $em->createQuery($strQuery)
			->setParameter('ininormal', $this->getSQLIniciAnual())
			->setParameter('ini365', $this->getSQLInici365());
		
		if ($currentEstat != self::TOTS_CLUBS_DEFAULT_STATE) $query->setParameter('filtreestat', $states[$currentEstat]);
		
		$paginator  = $this->get('knp_paginator');
		$partesrecents = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		);
	
		/* Paràmetres URL sort i pagination */
		if ($currentClub != null) $partesrecents->setParam('clubs',$currentClub->getCodi());
		if ($currentEstat != self::TOTS_CLUBS_DEFAULT_STATE) $partesrecents->setParam('estat',$currentEstat);
		if ($currentBaixa == true) $partesrecents->setParam('baixa',true);
		//if ($currentNoSincro == false) $partesrecents->setParam('nosincro',false);
		if ($currentNoPagat == false) $partesrecents->setParam('nopagat',false);
		if ($currentNoImpres == true) $partesrecents->setParam('noimpres',true);
				
		return $this->render('FecdasBundle:Admin:recents.html.twig', 
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'partes' => $partesrecents,
						'sortparams' => array('sort' => $sort,'direction' => $direction)
				)));
	}
	
	public function confirmapagamentAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$em = $this->getDoctrine()->getManager();
		
		$parteid = $request->query->get('id');
		$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
		
		if ($parte != null) {
			$tipusPagament = $request->query->get('tipuspagament', BaseController::TIPUS_PAGAMENT_CASH);
			$dataAux = $request->query->get('datapagament', '');
			$dataPagament = ($dataAux!='')? \DateTime::createFromFormat('d/m/Y',$dataAux): $this->getCurrentDate();
			$dadesPagament = $request->query->get('dadespagament', '');
			$comentariPagament = $request->query->get('comentaripagament', 'Confirmació manual');
				
			$this->crearRebut($dataPagament, $tipusPagament, $parte, $dadesPagament, $comentariPagament);
			
			$em->flush();

			$this->logEntryAuth('CONFIRMAR PAGAMENT OK', $parteid);
				
			return new Response("ok");
		}
		
		$this->logEntryAuth('CONFIRMAR PAGAMENT KO', $parteid);

		return new Response("ko");
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
			case 'DIFE':  // Pagament diferit

				if ($request->query->get('imprimir') == 'true') $club->setImpressio(true);
				else $club->setImpressio(false);

				break;
			case 'IMME':  // Pagament immediat
				
				break;
			case 'NOTR':  // Sense tramitació
				
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
		
		if ($duplicat != null) {
			$duplicat->setDatabaixa($this->getCurrentDate());
			
			
			$em->flush();
		
			$this->get('session')->getFlashBag()->add('error-notice', 'Petició de duplicat anulada correctament');
			
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
			
			$this->get('session')->getFlashBag()->add('error-notice', 'S\'ha enviat un mail al club');
				
			$this->logEntryAuth('PRINT DUPLI OK', 'duplicat ' . $duplicatid);
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', 'Error indicant impressió de la petició');
	
			$this->logEntryAuth('PRINT DUPLI ERROR', 'duplicat ' . $duplicatid);
		}
	
		return $this->redirect($this->generateUrl('FecdasBundle_duplicats'));
	}
	
	public function ajaxclubsnomsAction(Request $request) {
		$search = $this->consultaAjaxClubs($request->get('term'));
		$response = new Response();
		$response->setContent(json_encode($search));
	
		return $response;
	}
	
}
