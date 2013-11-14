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
	
	public function recentsAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		// Només jo
		/*if ($this->get('session')->get('username') != 'alexmazinho@gmail.com')
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));*/
		
		$em = $this->getDoctrine()->getEntityManager();
	
		$currentWeb = 0;
		$currentSincro = 0;
		$currentEstat = "100";
		$currentClub = "";
		
		if ($request->getMethod() == 'POST') {
			// Criteris de cerca 
			if ($request->request->has('form')) { 
				$formdata = $request->request->get('form');
				
				if (isset($formdata['codi'])) $currentClub = $formdata['codi'];
				if (isset($formdata['estat'])) $currentEstat = $formdata['estat'];
				if (isset($formdata['web'])) $currentWeb = 1;
				if (isset($formdata['sincro'])) $currentSincro = 1;

				$this->logEntry($this->get('session')->get('username'), 'ADMIN PARTES SEARCH',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), 
						"club: " . $currentClub . " estat: " . 
						$currentEstat . " web: " . $currentWeb .
						$currentSincro . " sync: " . $currentSincro );
			}
		} else {
			$this->logEntry($this->get('session')->get('username'), 'ADMIN PARTES',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'));
		}
		
		$formBuilder = $this->createFormBuilder()->add('clubs', 'search', array('required' => false));
		
		$formBuilder->add('codi', 'hidden');
		
		$formBuilder->add('estat', 'choice', array(
    				'choices'   => array('100' => 'Darrers 100', 't' => 'Tots', 'n' => 'No pagats', 'p' => 'Pendents', 'f' => 'Pagats'), 
					'preferred_choices' => array('100'),
				));
		$formBuilder->add('web', 'checkbox', array(
    				'required'  => false,
				));
		$formBuilder->add('sincro', 'checkbox', array(
    				'required'  => false,
				));
		$form = $formBuilder->getForm();
		
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityParte p JOIN p.tipus t ";
		$strQuery .= "WHERE p.databaixa IS NULL ";
		$strQuery .= " AND ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365))";
		
		if ($currentClub != "") {
			$clubcercar = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')->find($currentClub);
			$form->get('clubs')->setData($clubcercar->getNom());
			$form->get('codi')->setData($clubcercar->getCodi());
			$strQuery .= " AND p.club = '" .$clubcercar  . "' "	;
		}
			
		if ($currentWeb != 0) $strQuery .= " AND p.web = 1 ";
		/* Quan es sincronitza es posa la data modificació a NULL de partes i llicències (No de persones que funcionen amb el check validat). 
		 * Els canvis des del gestor també deixen la data a NULL per detectar canvis del web que calgui sincronitzar */ 
		if ($currentSincro != 0) $strQuery .= " AND (p.idparte_access IS NULL OR (p.idparte_access IS NOT NULL AND p.datamodificacio IS NOT NULL) ) ";
		//if ($currentEstat != "t" && $currentEstat != "100") {
			if ($currentEstat == "n") $strQuery .= " AND p.datapagament IS NULL ";
			if ($currentEstat == "p") $strQuery .= " AND p.numfactura = -1 ";
			if ($currentEstat == "f") $strQuery .= " AND p.datapagament IS NOT NULL ";
		//}
		$strQuery .= " ORDER BY p.id DESC"; 

		$query = $em->createQuery($strQuery)
			->setParameter('ininormal', $this->getSQLIniciAnual())
			->setParameter('ini365', $this->getSQLInici365());
		
		if ($currentEstat == "100") $query->setMaxResults(100);
		
		$partesrecents = $query->getResult();
	
		if ($currentWeb == 1) $form->get('web')->setData(true);
		if ($currentSincro == 1) $form->get('sincro')->setData(true);
		$form->get('estat')->setData($currentEstat);		
		
		return $this->render('FecdasPartesBundle:Admin:recents.html.twig',
				array('form' => $form->createView(), 'partes' => $partesrecents,
						'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}
	
	public function sincroaccessAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		$em = $this->getDoctrine()->getEntityManager();

		$parteid = $request->query->get("id");
		
		$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteid);
		
		if ($parte != null) {
			$parte->setDatamodificacio($this->getCurrentDate());
			
			foreach ($parte->getLlicencies() as $llicencia_iter) {
				if ($llicencia_iter->getDatabaixa() == null) $llicencia_iter->setDatamodificacio($this->getCurrentDate());
			}
			
			$em->flush();

			$this->get('session')->setFlash('error-notice', 'Llista preparada per tornar a sincronitzar');
			
			$this->logEntry($this->get('session')->get('username'), 'SINCRO ACCESS',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parteid);
		} else {
			$this->get('session')->setFlash('error-notice', 'Error en el procés de sincronització');
			
			$this->logEntry($this->get('session')->get('username'), 'SINCRO ACCESS ERROR',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parteid);
		}
		
		$response = $this->forward('FecdasPartesBundle:Admin:recents');
		return $response;
	}
	
	public function clubsAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		// Només jo
		/*if ($this->get('session')->get('username') != 'alexmazinho@gmail.com')
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));*/
	
		$em = $this->getDoctrine()->getEntityManager();
	
		$currentEstat = "TOT";
	
		if ($request->getMethod() == 'POST') {
		// Criteris de cerca
			if ($request->request->has('form')) {
			$formdata = $request->request->get('form');
	
			if (isset($formdata['estat'])) $currentEstat = $formdata['estat'];
	
				$this->logEntry($this->get('session')->get('username'), 'SALDO CLUBS FILTER',
							$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), "Filtre estat: " . $currentEstat );
			}
		} else {
			$this->logEntry($this->get('session')->get('username'), 'SALDO CLUBS',
			$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'));
			}
	
			$formBuilder = $this->createFormBuilder()->add('estat', 'choice', array(
					'choices'   => array('TOT' => 'Tots', 'DIF' => 'Pagament diferit', 'IMM' => 'Pagament immediat', 'NOT' => 'Sense tramitació'),
					'preferred_choices' => array('TOT'),
			));
			$form = $formBuilder->getForm();
	
			// Crear índex taula partes per data entrada
			$strQuery = "SELECT c FROM Fecdas\PartesBundle\Entity\EntityClub c ";
			if ($currentEstat != "TOT") $strQuery .= "WHERE c.estat = :filtreestat ";
			$strQuery .= " ORDER BY c.nom";
			$query = $em->createQuery($strQuery);
			if ($currentEstat != "TOT") $query->setParameter('filtreestat', $currentEstat);
			$clubs = $query->getResult();
			$form->get('estat')->setData($currentEstat);
	
			return $this->render('FecdasPartesBundle:Admin:clubs.html.twig',
					array('form' => $form->createView(), 'clubs' => $clubs,
							'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
							'busseig' => $this->isCurrentBusseig(),
							'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}
	
	public function ajaxclubsnomsAction(Request $request) {
		$search = $this->consultaAjaxClubs($request->get('term'));
		$response = new Response();
		$response->setContent(json_encode($search));
	
		return $response;
	}
	
}
