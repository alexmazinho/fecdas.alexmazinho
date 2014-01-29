<?php 
namespace Fecdas\PartesBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fecdas\PartesBundle\Classes\CSV_Reader;

use Fecdas\PartesBundle\Form\FormContact;
use Fecdas\PartesBundle\Form\FormPayment;
use Fecdas\PartesBundle\Form\FormParte;
use Fecdas\PartesBundle\Form\FormPersona;
use Fecdas\PartesBundle\Form\FormLlicencia;
use Fecdas\PartesBundle\Form\FormParteRenew;
use Fecdas\PartesBundle\Entity\EntityParteType;
use Fecdas\PartesBundle\Entity\EntityContact;
use Fecdas\PartesBundle\Entity\EntityParte;
use Fecdas\PartesBundle\Entity\EntityPersona;
use Fecdas\PartesBundle\Entity\EntityLlicencia;
use Fecdas\PartesBundle\Entity\EntityPayment;
use Fecdas\PartesBundle\Entity\EntityUser;
use Fecdas\PartesBundle\Entity\EntityClub;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class AdminController extends BaseController {
	
	public function changeroleAction() {
		if (!$this->isCurrentAdmin()) return new Response(""); 
			
		$request = $this->getRequest();

		// Canviar Club Administrador	
		if ($request->query->has('roleclub')) $this->get('session')->set('roleclub', $request->query->get('roleclub'));
		
		return new Response("");
	}
	
	public function recentsAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		// Només jo
		/*if ($this->get('session')->get('username') != 'alexmazinho@gmail.com')
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));*/
		
		$em = $this->getDoctrine()->getManager();
	
		$states = explode(";", self::CLUBS_STATES);
		
		$currentBaixa = 0;
		$currentSincro = 0;
		$currentPagament = "100";
		$currentClub = null;
		
		$defaultEstat = self::RECENTS_CLUBS_DEFAULT_STATE; // Tots
		if ($this->get('session')->has('username') and
		$this->get('session')->get('username') == self::MAIL_FACTURACIO) { 
			$defaultEstat = self::CLUBS_DEFAULT_STATE; // Diferits
			$currentPagament = "n";
		}
		$currentEstat = $defaultEstat;
		
		
		
		if ($request->getMethod() == 'POST') {
			// Criteris de cerca 
			if ($request->request->has('form')) { 
				$formdata = $request->request->get('form');
				
				if (isset($formdata['clubs'])) {
					$currentClub = $em->getRepository('FecdasPartesBundle:EntityClub')->find($formdata['clubs']);
				}
				if (isset($formdata['estat'])) $currentEstat = $formdata['estat'];
				if (isset($formdata['pagament'])) $currentPagament = $formdata['pagament'];
				if (isset($formdata['baixa'])) $currentBaixa = 1;
				if (isset($formdata['sincro'])) $currentSincro = 1;

				$this->logEntry($this->get('session')->get('username'), 'ADMIN PARTES SEARCH',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), 
						"club: " . ($currentClub==null)?"":$currentClub->getNom() . " filtre estat: " . $states[$currentEstat] . 
						" pagament: " . $currentPagament . " baixa: " . $currentBaixa .
						$currentSincro . " sync: " . $currentSincro );
			}
		} else {
			$this->logEntry($this->get('session')->get('username'), 'ADMIN PARTES',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'));
		}
		
		$formBuilder = $this->createFormBuilder();
		
		$clubsSelectOptions = array('class' => 'FecdasPartesBundle:EntityClub',
				'property' => 'nom',
				'label' => 'Filtre per club: ',
				'attr' => (array('onchange' => 'this.form.submit()')),
				'required'  => false );
			
		if ($currentClub != null) $clubsSelectOptions['data'] = $currentClub;
		
		$formBuilder->add('clubs', 'genemu_jqueryselect2_entity', $clubsSelectOptions);
		
		$formBuilder->add('estat', 'choice', array(
				'choices'   => $states,
				'preferred_choices' => array($defaultEstat),  // Estat per defecte sempre 
				'attr' => (array('onchange' => 'this.form.submit()'))
		));
		
		$formBuilder->add('pagament', 'choice', array(
    				'choices'   => array('100' => 'Darrers 100', 't' => 'Tots', 'n' => 'No pagats', 'p' => 'Pagats'), 
					'preferred_choices' => array('100'),  // Estat per defecte sempre 
					'attr' => (array('onchange' => 'this.form.submit()'))
				));
		$formBuilder->add('baixa', 'checkbox', array(
    				'required'  => false,
					'attr' => (array('onchange' => 'this.form.submit()'))
				));
		$formBuilder->add('sincro', 'checkbox', array(
    				'required'  => false,
					'attr' => (array('onchange' => 'this.form.submit()'))
				));
		$form = $formBuilder->getForm();
		
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityParte p JOIN p.tipus t JOIN p.club c JOIN c.estat e ";
		$strQuery .= "WHERE ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365))";
		
		if ($currentBaixa == 0) $strQuery .= " AND p.databaixa IS NULL ";
		else $strQuery .= " AND p.databaixa IS NOT NULL ";
		
		if ($currentEstat != 0) $strQuery .= " AND e.descripcio = :filtreestat ";
		
		if ($currentClub != null) $strQuery .= " AND p.club = '" .$currentClub->getCodi() . "' "	;
		
		/* Quan es sincronitza es posa la data modificació a NULL de partes i llicències (No de persones que funcionen amb el check validat). 
		 * Els canvis des del gestor també deixen la data a NULL per detectar canvis del web que calgui sincronitzar */ 
		if ($currentSincro != 0) $strQuery .= " AND (p.idparte_access IS NULL OR (p.idparte_access IS NOT NULL AND p.datamodificacio IS NOT NULL) ) ";
		//if ($currentPagament != "t" && $currentPagament != "100") {
			if ($currentPagament == "n") $strQuery .= " AND p.datapagament IS NULL ";
			if ($currentPagament == "p") $strQuery .= " AND p.datapagament IS NOT NULL ";
		//}
		$strQuery .= " ORDER BY p.id DESC"; 

		$query = $em->createQuery($strQuery)
			->setParameter('ininormal', $this->getSQLIniciAnual())
			->setParameter('ini365', $this->getSQLInici365());
		
		if ($currentEstat != 0) $query->setParameter('filtreestat', $states[$currentEstat]);
		
		if ($currentPagament == "100") $query->setMaxResults(100);
		
		
		$partesrecents = $query->getResult();
	
		/* Mantenir estat darrera consulta */
		if ($currentBaixa == 1) $form->get('baixa')->setData(true);
		if ($currentSincro == 1) $form->get('sincro')->setData(true);
		$form->get('pagament')->setData($currentPagament);
		$form->get('estat')->setData($currentEstat);  
		
		return $this->render('FecdasPartesBundle:Admin:recents.html.twig', 
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'partes' => $partesrecents)));
						
	}
	
	public function confirmapagamentAction() {
		$request = $this->getRequest();
		
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
		
		$em = $this->getDoctrine()->getManager();
		
		$parteid = $request->query->get('id');
		$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteid);
		
		if ($parte != null) {
			// Actualitzar data pagament
			$datapagat = \DateTime::createFromFormat('d/m/Y', $request->query->get('datapagat'));
			$parte->setDatapagament($datapagat);
			$parte->setEstatpagament($request->query->get('estatpagat'));
			if ($request->query->get('dadespagat') != '') $parte->setDadespagament($request->query->get('dadespagat'));
			if ($request->query->get('comentaripagat') != '') $parte->setComentari($request->query->get('comentaripagat'));
			$parte->setPendent(false);
			//$parte->setImportpagament($parte->getPreuTotalIVA());
			if ($parte->getIdparteAccess() == null) $parte->setImportpagament($parte->getPreuTotalIVA());  // Pagament sense sincronitzar si actualitza import pagament
			$parte->setDatamodificacio($this->getCurrentDate());
			
			$em->flush();
			
			$this->logEntry($this->get('session')->get('username'), 'CONFIRMAR PAGAMENT OK',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parteid);

			return new Response("ok");
		}
		
		$this->logEntry($this->get('session')->get('username'), 'CONFIRMAR PAGAMENT KO',
				$this->get('session')->get('remote_addr'),
				$this->getRequest()->server->get('HTTP_USER_AGENT'), $parteid);

		return new Response("ko");
	}
	
	public function sincroaccessAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		$em = $this->getDoctrine()->getManager();

		$parteid = $request->query->get("id");
		
		$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteid);
		
		if ($parte != null) {
			$parte->setDatamodificacio($this->getCurrentDate());
			
			foreach ($parte->getLlicencies() as $llicencia_iter) {
				if ($llicencia_iter->getDatabaixa() == null) {
					$llicencia_iter->setDatamodificacio($this->getCurrentDate());
					$llicencia_iter->getPersona()->setValidat(false);
				}
			}
			
			$em->flush();

			$this->get('session')->getFlashBag()->add('error-notice', 'Llista preparada per tornar a sincronitzar');
			
			$this->logEntry($this->get('session')->get('username'), 'SINCRO ACCESS',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parteid);
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', 'Error en el procés de sincronització');
			
			$this->logEntry($this->get('session')->get('username'), 'SINCRO ACCESS ERROR',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parteid);
		}
		
		$response = $this->forward('FecdasPartesBundle:Admin:recents');
		return $response;
	}
	
	public function canviestatclubAction () {
		$request = $this->getRequest();
		
		if ($this->isCurrentAdmin() != true) return new Response("no admin");
		
		$club = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')->find($request->query->get('codiclub'));
		$estat = $request->query->get('action');
		$limitcredit = $request->query->get('limitcredit');
		if ($limitcredit == "") $limitcredit = null;
		
		if ($club == null) {
			$this->logEntry($this->get('session')->get('username'), 'CLUB STATE ERROR',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $request->query->get('codiclub'));
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
		$estat = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClubEstat')->find($estat);
		$club->setEstat($estat);
		$club->setLimitcredit($limitcredit);
		$em->flush();
		
		$this->logEntry($this->get('session')->get('username'), 'CLUB STATE OK',
				$this->get('session')->get('remote_addr'),
				$this->getRequest()->server->get('HTTP_USER_AGENT'), $club->getNom()." ".$estatAnterior->getCodi()." -> ".$estat->getCodi());
		
		return new Response("ok");
	}
	
	public function clubsAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	
		// Només jo
		/*if ($this->get('session')->get('username') != 'alexmazinho@gmail.com')
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));*/
	
		$em = $this->getDoctrine()->getManager();
	
		$states = explode(";", self::CLUBS_STATES);
		$currentEstat = self::CLUBS_DEFAULT_STATE;
		
		if ($request->getMethod() == 'POST') {
		// Criteris de cerca
			if ($request->request->has('form')) {
			$formdata = $request->request->get('form');
	
			if (isset($formdata['estat'])) $currentEstat = $formdata['estat'];
	
				$this->logEntry($this->get('session')->get('username'), 'SALDO CLUBS FILTER',
							$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), "Filtre estat: " . $states[$currentEstat] );
			}
		} else {
			$this->logEntry($this->get('session')->get('username'), 'SALDO CLUBS',
			$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'));
			}
			
			$formBuilder = $this->createFormBuilder()->add('estat', 'choice', array(
					'choices'   => $states,
					'preferred_choices' => array(self::CLUBS_DEFAULT_STATE),  // Estat per defecte sempre
					'attr' => (array('onchange' => 'this.form.submit()'))
			));
			$form = $formBuilder->getForm();
	
			// Crear índex taula partes per data entrada
			$strQuery = "SELECT c FROM Fecdas\PartesBundle\Entity\EntityClub c JOIN c.estat e ";
			$strQuery .= " WHERE c.activat = true ";
			if ($currentEstat != 0) $strQuery .= " AND e.descripcio = :filtreestat ";
			$strQuery .= " ORDER BY c.nom";
			$query = $em->createQuery($strQuery);
			if ($currentEstat != 0) $query->setParameter('filtreestat', $states[$currentEstat]);
			$clubs = $query->getResult();
			
			$form->get('estat')->setData($currentEstat);  // Mantenir estat darrera consulta
	
			return $this->render('FecdasPartesBundle:Admin:clubs.html.twig',  
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'clubs' => $clubs)));
	}
	
	public function anularpeticioAction() {
		/* Anular petició duplicat */
		$request = $this->getRequest();
		
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
		
		$em = $this->getDoctrine()->getManager();
		
		$duplicatid = $request->query->get("id");
		
		$duplicat = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityDuplicat')->find($duplicatid);
		
		if ($duplicat != null) {
			$duplicat->setDatabaixa($this->getCurrentDate());
				
			$em->flush();
		
			$this->get('session')->getFlashBag()->add('error-notice', 'Petició de duplicat anulada correctament');
			
			$this->logEntryAuth('ANULA DUPLI OK', 'duplicat ' . $duplicatid);
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', 'Error anulant la petició');

			$this->logEntryAuth('ANULA DUPLI ERROR', 'duplicat ' . $duplicatid);
		}
		
		return $this->redirect($this->generateUrl('FecdasPartesBundle_duplicats'));
	}
	
	public function imprespeticioAction() {
		/* Marca petició duplicat com impressa i enviar un correu */
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$duplicatid = $request->query->get("id");
	
		$duplicat = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityDuplicat')->find($duplicatid);
	
		if ($duplicat != null) {
			$duplicat->setDataimpressio($this->getCurrentDate());
	
			$em->flush();
	
			// Enviar notificació mail
			if ($duplicat->getClub()->getMail() != null) {
				$subject = "Petició de duplicat. " . $duplicat->getCarnet()->getTipus();
				$tomails = array($duplicat->getClub()->getMail());
				$bccmails = $this->getLlicenciesMails();
			} else {
				$subject = "Petició de duplicat. " . $duplicat->getCarnet()->getTipus() . " CLUB SENSE CORREU!! ";
				$tomails = $this->getLlicenciesMails();
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
	
		return $this->redirect($this->generateUrl('FecdasPartesBundle_duplicats'));
	}
	
	public function pagamentpeticioconfirmAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		$em = $this->getDoctrine()->getManager();
	
		$duplicatid = $request->query->get('id');
		$duplicat = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($duplicatid);
	
		if ($duplicat != null) {
			// Crear pagament
			$data = \DateTime::createFromFormat('d/m/Y', $request->query->get('datapagat'));
			$estat = $request->query->get('estatpagat');
			$dades = $request->query->get('dadespagat');
			$comentari = $request->query->get('comentaripagat');
			$import = $duplicat->getCarnet()->getPreu();
			$pagament = $this->crearPagament($data, $import, $estat, $dades, $comentari);
			// Actualitzar pagament
			$duplicat->setPagament($pagament);
				
			$em->flush();
			
			$this->logEntryAuth('CONF. PAGAMENT DUPLI', 'duplicat ' . $duplicatid);
			
			return new Response("ok");
		}
		$this->logEntryAuth('CONF. PAGAMENT DUPLI KO', 'duplicat ' . $duplicatid);
	
		return new Response("ko");
	}
	
	public function ajaxclubsnomsAction(Request $request) {
		$search = $this->consultaAjaxClubs($request->get('term'));
		$response = new Response();
		$response->setContent(json_encode($search));
	
		return $response;
	}
	
}
