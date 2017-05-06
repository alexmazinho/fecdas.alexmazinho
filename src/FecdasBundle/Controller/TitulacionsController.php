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
use FecdasBundle\Form\FormStock;
use FecdasBundle\Entity\EntityStock;
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


class TitulacionsController extends BaseController {
	
	
	public function dadespersonalsAction(Request $request) {
		// Llista de membres del club amb les dades personals

		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'e.cognoms');
		$direction = $request->query->get('direction', 'asc');
		$format = $request->query->get('format', '');
		$currentDNI = $request->query->get('dni', '');
		$currentNom = $request->query->get('nom', '');
		$currentCognoms = $request->query->get('cognoms', '');
		$currentClub = $request->query->get('clubs', '');
		$currentTitol = $request->query->get('titols', '');
		$currentTitolExtern = $request->query->get('titolsexterns', '');
		$titol = null;
		if ($currentTitol != '') $titol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($currentTitol);
		$titolExtern = null;
		if ($currentTitolExtern != '') $titolExtern = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($currentTitolExtern);
		
		$desde = $request->query->get('desde', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->query->get('desde')):null;
		$fins = $request->query->get('fins', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->query->get('fins')):null;
		
		$currentDNI = trim($currentDNI);
		$currentNom = trim($currentNom);
		$currentCognoms = trim($currentCognoms);
		
		$currentVigent = false;
		if ($request->query->has('vigent') && $request->query->get('vigent') == 1) $currentVigent = true;
		    
		$club = $this->getCurrentClub(); // Admins poden cerca tots els clubs
		if ($this->isCurrentAdmin()) {
			if ($currentClub != '') $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($currentClub);
			else $club = null;
		}
				
		$this->logEntryAuth('VIEW PERSONES CLUB', ($format != ''?$format:'')."club: " . $currentClub." ".$currentNom.", ".$currentCognoms . "(".$currentDNI. ") ".
													"des de ".($desde != null?$desde->format('Y-m-d'):'--')." fins ".($fins != null?$fins->format('Y-m-d'):'--').
													" titol ".$currentTitol." altres ". $currentTitolExtern);
			
		$query = $this->consultaDadespersonals($currentDNI, $currentNom, $currentCognoms, $club, $desde, $fins, $currentVigent, $titol, $titolExtern, $sort.' '.$direction);
		
		if ($format == 'csv') {
			// Generar CSV
			return $this->exportAssegurats($request, $query->getResult(), $desde, $fins);
		}
		
		$paginator  = $this->get('knp_paginator');
		$persones = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		); 
		/* Paràmetres URL sort i pagination */
		$persones->setParam('desde',$desde!=null?$desde->format('d/m/Y'):'');
		$persones->setParam('fins',$fins!=null?$fins->format('d/m/Y'):'');
		if ($currentDNI != '') $persones->setParam('dni',$currentDNI);
		if ($currentNom != '') $persones->setParam('nom',$currentNom);
		if ($currentCognoms != '') $persones->setParam('cognoms',$currentCognoms);
		if ($currentVigent == true) $persones->setParam('vigent',true);
		if ($currentClub != '') $persones->setParam('club',$club);
		
		$formBuilder = $this->createFormBuilder()->add('dni', 'search', array('required'  => false, 'data' => $currentDNI)); 
		$formBuilder->add('nom', 'search', array('required'  => false, 'data' => $currentNom));
		$formBuilder->add('cognoms', 'search', array('required'  => false, 'data' => $currentCognoms));
		$formBuilder->add('vigent', 'checkbox', array('required'  => false, 'data' => $currentVigent));
		$formBuilder->add('desde', 'text', array('read_only' => false, 'required'  => false, 'data' => ($desde != null?$desde->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--')));
		$formBuilder->add('fins', 'text', array('read_only'  => false, 'required'  => false, 'data' => ($fins != null?$fins->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--')));
	
		$this->addClubsActiusForm($formBuilder, $club);
		
		$this->addTitolsForm($formBuilder, $titol, true, 'titols');
		
		$this->addTitolsForm($formBuilder, $titolExtern, false, 'titolsexterns');
		
		$form = $formBuilder->getForm(); 
	
		return $this->render('FecdasBundle:Titulacions:dadespersonals.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'persones' => $persones, 
						'sortparams' => array('sort' => $sort,'direction' => $direction)) 
						));

	}
	
	public function historialllicenciesAction(Request $request) {
		
		if ($this->isAuthenticated() != true) return new Response("");

		if (!$request->query->has('id')) return new Response("");
		
		$em = $this->getDoctrine()->getManager();
				
		$id = $request->query->get('id');
			
		if ($this->isCurrentAdmin()) {
			/* !!!!!!!!!!!! Administradors historia de tots els clubs per DNI !!!!!!!!!!!!!!!!!!!! */
			/*$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
			$strQuery .= " WHERE p.dni = :dni ";
			$strQuery .= " AND p.databaixa IS NULL ";
				
			$query = $em->createQuery($strQuery)->setParameter('dni', $persona->getDni()); 
			$persones = $query->getResult();

			$llicencies = array();
			foreach ($persones as $persona_iter) {
				$llicencies = array_merge($llicencies, $persona_iter->getLlicenciesSortedByDate(true));  // Incloure baixes
			}
			*/
			/* Ordenades de última a primera 
			 * SELECT e.dni, COUNT(DISTINCT p.club) FROM m_partes p 
			 * INNER JOIN m_llicencies l ON p.id = l.parte 
			 * INNER JOIN m_persones e ON l.persona = e.id 
			 * GROUP BY e.dni HAVING COUNT(DISTINCT p.club) > 1
			 * */
			 
			 $persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->find($id);
		} else {
			 $persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($id);
		}
		if (!$persona) return new Response("");
						
		$llicencies = $persona->getLlicenciesSortedByDate(true); // Incloure baixes
		
		// Ordre
		usort($llicencies, function($a, $b) {
			if ($a === $b) {
				return 0;
			}
			return ($a->getParte()->getDatacaducitat("getLlicenciesSortedByDate") > $b->getParte()->getDatacaducitat("getLlicenciesSortedByDate"))? -1:1;;
		});

		return $this->render('FecdasBundle:Titulacions:llicencieshistorial.html.twig', array('llicencies' => $llicencies, 'admin' => $this->isCurrentAdmin()));
		
	}

	public function historialtitulacionsAction(Request $request) {
		
		if ($this->isAuthenticated() != true) return new Response("");

		if (!$request->query->has('id')) return new Response("");
		
		$em = $this->getDoctrine()->getManager();
				
		$id = $request->query->get('id');
		
		if ($this->isCurrentAdmin()) {
			 $persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->find($id);
		} else {
			 $persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($id);
		}
			
		if (!$persona) return new Response("");
		
		$titulacions = $persona->getTitulacionsSortedByDate( $this->isCurrentAdmin() );  // Administradors veuen baixes
		
		$altrestitulacions = $persona->getAltrestitulacions();
		
		return $this->render('FecdasBundle:Titulacions:titulacionshistorial.html.twig', 
				array('titulacions' => $titulacions, 'altrestitulacions' => $altrestitulacions, 
					   'club' => $this->getCurrentClub(), 'admin' => $this->isCurrentAdmin()));
		
	}

	public function cursosAction(Request $request) {
		// Llista de cursos
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.datadesde');
		$direction = $request->query->get('direction', 'desc');
		
		$currentClub = $request->query->get('clubs', ''); // Admin filtra club
		
		$club = $this->getCurrentClub(); // Admins poden cerca tots els clubs

		if ($this->isCurrentAdmin()) {
			if ($currentClub != '') $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($currentClub);
			else $club = null;
		}
		
		$desde = $request->query->get('desde', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->query->get('desde')):null;
		$fins = $request->query->get('fins', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->query->get('fins')):null;

		$currentTitol = $request->query->get('titols', '');
		$titol = null;
		if ($currentTitol != '') $titol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($currentTitol);
		
		$currentPerValidar = false;
		if ($request->query->has('pervalidar') && $request->query->get('pervalidar') == 1) $currentPerValidar = true;
		
		$this->logEntryAuth('VIEW CURSOS', "club: " . $currentClub." "." titol ".$currentTitol.
											"des de ".($desde != null?$desde->format('Y-m-d'):'--')." fins ".($fins != null?$fins->format('Y-m-d'):'--'));
		
		$query = $this->consultaCursos($club, $titol, $desde, $fins, $currentPerValidar, $sort.' '.$direction);
		
		$paginator  = $this->get('knp_paginator');
		$cursos = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		); 

		$formBuilder = $this->createFormBuilder()->add('pervalidar', 'checkbox', array('required'  => false, 'data' => $currentPerValidar));
		$formBuilder->add('desde', 'text', array('read_only' => false, 'required'  => false, 'data' => ($desde != null?$desde->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--')));
		$formBuilder->add('fins', 'text', array('read_only'  => false, 'required'  => false, 'data' => ($fins != null?$fins->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--')));
	
		$this->addClubsActiusForm($formBuilder, $club);
		
		$this->addTitolsForm($formBuilder, $titol, true, 'titols');
		
		$form = $formBuilder->getForm(); 
		
		return $this->render('FecdasBundle:Titulacions:cursos.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'cursos' => $cursos, 'sortparams' => array('sort' => $sort,'direction' => $direction))
				));
	}

	public function cursAction(Request $request) {
		// Consulta/edicio curs 

		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		return $this->render('FecdasBundle:Titulacions:curs.html.twig',
				$this->getCommonRenderArrayOptions(array()));
	}

	private function consultaDadespersonals($dni, $nom, $cognoms, $club = null, $desde = null, $fins = null, $vigent = true, $titol = null, $titolExtern = null, $strOrderBY = '') { 
		$em = $this->getDoctrine()->getManager();
	
		$current = $this->getCurrentDate();
		
		if ($club != null) $strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e ";
		else $strQuery = "SELECT m FROM FecdasBundle\Entity\EntityMetaPersona m JOIN m.persones e ";
		
		if ($titol != null) $strQuery .= " JOIN e.titulacions t JOIN t.curs c ";
		if ($titolExtern != null) $strQuery .= " JOIN e.altrestitulacions at ";
		
		if ($vigent == true) {
			$strQuery .= " JOIN e.llicencies l JOIN l.parte p ";
			$strQuery .= " WHERE e.databaixa IS NULL AND l.databaixa IS NULL AND p.databaixa IS NULL ";
			$strQuery .= " AND p.pendent = 0 ";
			$strQuery .= " AND p.dataalta <= :currenttime ";
			$strQuery .= " AND l.datacaducitat >= :currentdate ";
		} else {
		    if ($desde != null || $fins != null) { 
    			$strQuery .= " JOIN e.llicencies l JOIN l.parte p ";
    			$strQuery .= " WHERE e.databaixa IS NULL AND p.databaixa IS NULL ";
    			$strQuery .= " AND p.pendent = 0 ";
    			if ($desde != null) $strQuery .= " AND p.dataalta >= :desde ";
    			if ($fins != null) $strQuery .= " AND p.dataalta <= :fins ";
            } else {
                $strQuery .= " WHERE e.databaixa IS NULL ";
            }
		}
		
		
		if ($titol != null) $strQuery .= " AND t.databaixa IS NULL AND t.datasuperacio IS NOT NULL AND c.titol = :titol ";
		if ($titolExtern != null) $strQuery .= " AND at = :titolextern ";
		
		if ($club != null) $strQuery .= " AND e.club = :club ";
		if ($dni != "") $strQuery .= " AND e.dni LIKE :dni ";
		if ($nom != "") $strQuery .= " AND e.nom LIKE :nom ";
		if ($cognoms != "") $strQuery .= " AND e.cognoms LIKE :cognoms ";

		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  // Només per PDF el paginator ho fa sol mentre el mètode de crida sigui POST
		
		$query = $em->createQuery($strQuery);
				
		// Algun filtre
		$query = $em->createQuery($strQuery);
		if ($club != null) $query->setParameter('club', $club->getCodi());
		if ($titol != null) $query->setParameter('titol', $titol->getId());
		if ($titolExtern != null) $query->setParameter('titolextern', $titolExtern->getId());
		if ($dni != "") $query->setParameter('dni', "%" . $dni . "%");
		if ($nom != "") $query->setParameter('nom', "%" . $nom . "%");
		if ($cognoms != "") $query->setParameter('cognoms', "%" . $cognoms . "%");
		if ($vigent == true) {
			$query->setParameter('currenttime', $current->format('Y-m-d').' 00:00:00');
			$query->setParameter('currentdate', $current->format('Y-m-d'));
		} else {
			if ($desde != null) $query->setParameter('desde', $desde->format('Y-m-d').' 00:00:00');
			if ($fins != null) $query->setParameter('fins', $fins->format('Y-m-d').' 23:59:59');
		}
	
		return $query;
	}


	private function consultaCursos($club = null, $titol = null, $desde = null, $fins = null, $pervalidar = false, $strOrderBY = '') {
		
		$em = $this->getDoctrine()->getManager();
	
		$current = $this->getCurrentDate();
		
		$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityCurs c JOIN c.titol t WHERE 1 = 1 ";
		
		if ($this->isCurrentAdmin()) {
			$strQuery .= " AND c.databaixa IS NULL ";
		}
		
		if ($pervalidar == true) {
			$strQuery .= " AND c.validat = 0 ";
		}
		
		if ($desde != null) $strQuery .= " AND c.datafins >= :desde ";
		if ($fins != null) $strQuery .= " AND c.datadesde <= :fins ";
		
		if ($titol != null) $strQuery .= " AND t.id = :titol ";
		
		if ($club != null) $strQuery .= " AND c.club = :club ";

		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  
		
		$query = $em->createQuery($strQuery);
				
		// Algun filtre
		$query = $em->createQuery($strQuery);
		if ($club != null) $query->setParameter('club', $club->getCodi());
		if ($titol != null) $query->setParameter('titol', $titol->getId());
		if ($desde != null) $query->setParameter('desde', $desde->format('Y-m-d'));
		if ($fins != null) $query->setParameter('fins', $fins->format('Y-m-d'));
	
		return $query;
	}
}
