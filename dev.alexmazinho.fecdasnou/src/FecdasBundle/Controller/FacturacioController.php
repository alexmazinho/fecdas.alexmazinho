<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Form\FormLlicenciaRenovar;

class FacturacioController extends BaseController {

	public function productesAction() {
		// Llista de productes i edició massiva
		$request = $this->getRequest();

		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		if (!$this->isCurrentAdmin()) 
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$tipus = $request->query->get('tipus', 0);
		$cerca = $request->query->get('cerca', '');
		$baixes = $request->query->get('baixes', false);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'p.descripcio');
		$direction = $request->query->get('direction', 'asc');
		
		//$club = $this->getCurrentClub();
		
		/*$desdeDefault = "01/01/".(date("Y") - 1);
		$desde = \DateTime::createFromFormat('d/m/Y', $request->query->get('desde', $desdeDefault));
		
		$finsDefault = "31/12/".(date("Y"));
		if (date("m") == self::INICI_TRAMITACIO_ANUAL_MES and date("d") >= self::INICI_TRAMITACIO_ANUAL_DIA) $finsDefault = "31/12/".(date("Y")+1);		
		$fins = \DateTime::createFromFormat('d/m/Y', $request->query->get('fins', $finsDefault));
		
		
		error_log($tipus);
		
		*/
		
		/*if ($request->getMethod() == 'POST') {

			return $this->redirect($this->generateUrl('FecdasBundle_parte'));
			
		} else {
			if ($request->query->has('desde') || $request->query->has('fins') || $request->query->has('tipus')) {
				$this->logEntryAuth('VIEW PARTES SEARCH', $club->getCodi()." ".$tipus.":".
									$desde->format('Y-m-d')."->".$fins->format('Y-m-d'));
			}
			else $this->logEntryAuth('VIEW PARTES', $club->getCodi());
		}*/
		
		$this->logEntryAuth('VIEW PRODUCTES', $this->get('session')->get('username'));
		
		
		/*if (date("m") == self::INICI_TRAMITACIO_ANUAL_MES and date("d") >= self::INICI_TRAMITACIO_ANUAL_DIA) {
			// A partir 10/12 poden fer llicències any següent
			$request->getSession()->getFlashBag()->add('error-notice', 'Ja es poden començar a tramitar les llicències del ' . (date("Y")+1));
		}*/
				
		$formBuilder = $this->createFormBuilder()
			->add('cerca', 'text', array(
				'read_only' => false,
				'required'  => false,
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
			
		$query = $this->consultaProductes($cerca, $tipus, $baixes, $sort);
		
		$paginator  = $this->get('knp_paginator');
		
		$productes = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
		
		return $this->render('FecdasBundle:Facturacio:productes.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(), 
						'productes' => $productes,  'sortparams' => array('sort' => $sort,'direction' => $direction))
						));
	}
	
	public function editarproducteAction() {
		// Formulari d'edició d'un producte
		return new Response("");  
	}
	
	public function baixaproducteAction() {
		// Crida per donar de baixa un producte
		return new Response("");  
	}
	
	
	protected function consultaProductes($cerca, $tipus, $baixes, $strOrderBY = '') {
		$em = $this->getDoctrine()->getManager();
	
		// Consultar no només les vigents sinó totes
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityProducte p ";
		$strQuery .= "WHERE 1 = 1 ";
		if (! $baixes) $strQuery .= " AND p.databaixa IS NULL ";
		if ($tipus > 0) $strQuery .= " AND t.id == :tipus";
		if ($cerca != '') $strQuery .= " AND p.descripcio LIKE :cerca";
		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  
	
		$query = $em->createQuery($strQuery);
		
		if ($tipus > 0) $query->setParameter('tipus', $tipus);
		if ($cerca != '') $query->setParameter('cerca', '%'.$cerca.'%');
			
		return $query;
	}
	
}
