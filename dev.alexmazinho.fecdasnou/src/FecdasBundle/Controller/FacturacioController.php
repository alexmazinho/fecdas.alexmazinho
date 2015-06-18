<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FecdasBundle\Form\FormProducte;
use FecdasBundle\Entity\EntityProducte;
use FecdasBundle\Form\FormFactura;
use FecdasBundle\Entity\EntityFactura;
use FecdasBundle\Form\FormRebut;
use FecdasBundle\Entity\EntityRebut;
use FecdasBundle\Form\FormComanda;
use FecdasBundle\Entity\EntityComanda;
use FecdasBundle\Form\FormComandaDetall;
use FecdasBundle\Entity\EntityComandaDetall;


class FacturacioController extends BaseController {

	public function productesAction(Request $request) {
		// Llista de productes i edició massiva

		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		if (!$this->isCurrentAdmin()) 
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$em = $this->getDoctrine()->getManager();
		$producte = null;		
		$idproducte = $request->query->get('cerca', 0);

		$this->logEntryAuth('VIEW PRODUCTES', $this->get('session')->get('username'));
		

		$tipus = $request->query->get('tipus', 0);
		$baixes = $request->query->get('baixes', 0);
		$baixes = ($baixes == 0?true:false);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'p.descripcio');
		$direction = $request->query->get('direction', 'asc');
		
		if ($idproducte > 0) {
			$tipus = 0;
			$baixes = true;
			$page = 1;
		}	

		$query = $this->consultaProductes($idproducte, $tipus, $baixes, $sort);
			
		$paginator  = $this->get('knp_paginator');
			
		$productes = $paginator->paginate(
			$query,
			$page,
			10/*limit per page*/
		);
			
		$formBuilder = $this->createFormBuilder()
			->add('cerca', 'hidden', array(
				'data' => $idproducte
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
		$this->get('session')->getFlashBag()->clear();
    	
    	if ($this->isAuthenticated() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_login'));
    
    	/* De moment administradors */
    	if ($this->isCurrentAdmin() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_home'));
    	
    	$producte = null;
    	if ($request->getMethod() != 'POST') {
    		$id = $request->query->get('id', 0);
    		
    		$producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->find($id);
    		 
    		if ($producte == null) {
    			// No trobat
    			$this->logEntryAuth('PRODUCTE EDIT KO',	'producte : ' . $request->query->get('id', 0));
    			$this->get('session')->getFlashBag()->add('error-notice', 'Producte no trobat ');
    			return $this->redirect($this->generateUrl('FecdasBundle_productes'));
    		}
    		$this->logEntryAuth('PRODUCTE EDIT',	'producte : ' . $producte->getId().' '.$producte->getDescripcio());
    	} else {
   			/* Alta o modificació de clubs */
    		$data = $request->request->get('producte');
    		$id = (isset($data['id'])?$data['id']:0);
    		
    		if ($id > 0) $producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->find($id);
    		
    		if ($producte == null) $producte = new EntityProducte();
    	}	

    	$form = $this->createForm(new FormProducte(), $producte);
    	
    	if ($request->getMethod() == 'POST') {
    		try {
    			$form->handleRequest($request);
    			 
    			$anypreus 	= $form->get('anypreus')->getData();
    			$preu 		= $form->get('preu')->getData();
    			$iva 		= $form->get('iva')->getData();
    			
    			 
    			
    			if (!$form->isValid()) {
    				
    				$producte->setAbreviatura(strtoupper($producte->getAbreviatura));
    				
    				//..... validacions
    				
    				throw new \Exception('Dades incorrectes, cal revisar les dades del producte ' ); //$form->getErrorsAsString()
    			}

    			$em->flush();
    			 
    			$this->get('session')->getFlashBag()->add('notice',	'El producte s\'ha desat correctament');
    			
    			$this->logEntryAuth('PRODUCTE SUBMIT',	'producte : ' . $producte->getId().' '.$producte->getDescripcio());
    			
    			// Ok, retorn form sms ok
    		} catch (\Exception $e) {
    			// Ko, mostra form amb errors
    			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
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
    	
    	$producte = new EntityProducte();
    	
    	$form = $this->createForm(new FormProducte(), $producte);
    	
    	return $this->render('FecdasBundle:Facturacio:producte.html.twig',
    			$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'producte' => $producte)));
	}
	
	public function baixaproducteAction(Request $request) {
		// Crida per donar de baixa un producte
		return new Response("");  
	}
	
	public function jsonproductesAction(Request $request) {
		//foment.dev/jsonperson?id=32
		$response = new Response();
	
		$cerca = $request->get('cerca', '');
		$id = $request->get('id', 0);
		
		$em = $this->getDoctrine()->getManager();
		
		if ($id > 0) {
			error_log("id ==========> ".$id);
			$producte = $em->getRepository('FecdasBundle:EntityProducte')->find($id);
			
			if ($producte != null) {
				$response->headers->set('Content-Type', 'application/json');
				$response->setContent(json_encode(array("id" => $producte->getId(), "text" => $producte->getDescripcio()) ));
				return $response;
			}
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
	
	protected function consultaProductes($idproducte, $tipus, $baixes, $strOrderBY = '') {
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
		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  
		$query = $em->createQuery($strQuery);
		
		if ($tipus > 0) $query->setParameter('tipus', $tipus);
		return $query;
	}
	
}
