<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
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


class FacturacioController extends BaseController {
	
	public function comandesAction(Request $request) {
		// Llistat de comandes
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
		
		$club = null;
		
		$idclub = $request->query->get('cerca', 0);
		
		$this->logEntryAuth('VIEW COMANDES', $this->get('session')->get('username'));
		
		$codi = $request->query->get('codi', '');
		$baixes = $request->query->get('baixes', 0);
		$baixes = ($baixes == 1?true:false);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.id');
		$direction = $request->query->get('direction', 'desc');
		
		$query = $this->consultaComandes($codi, $baixes, $sort, $direction);
			
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
		->add('baixes', 'checkbox', array(
				'required' => false,
				'data' => $baixes
		
		));
			
		return $this->render('FecdasBundle:Facturacio:comandes.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'comandes' => $comandes,  'sortparams' => array('sort' => $sort,'direction' => $direction))
				));
		
		return new Response("");
	}
	
	public function novacomandaAction(Request $request) {
		// Creació d'una nova comanda
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
		$producte = null;
		return new Response("");
	}
	
	
	public function editarcomandaAction(Request $request) {
		// Edició d'una nova comanda existent
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
		$producte = null;
		return new Response("");
	}
	
	
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
    				else  $producte->setDataentrada(new \DateTime());
    				
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
		//foment.dev/ajaxpreus?id=32&anypreu=2015
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
		$strQuery .= " ORDER BY " .$strOrderBY ." ".$direction;  
		$query = $em->createQuery($strQuery);
		
		if ($tipus > 0) $query->setParameter('tipus', $tipus);
		return $query;
	}
	
	protected function consultaComandes($codi, $baixes, $strOrderBY = 'c.id', $direction = 'desc' ) {
		$em = $this->getDoctrine()->getManager();
	
		$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityComanda c ";
		$strQuery .= "WHERE 1 = 1 ";
		if ($codi != '') $strQuery .= " AND c.club = :codi ";
		if (! $baixes) $strQuery .= " AND c.databaixa IS NULL ";
		$strQuery .= " ORDER BY " .$strOrderBY ." ".$direction;
		$query = $em->createQuery($strQuery);
	
		if ($codi != '') $query->setParameter('codi', $codi);
		
		return $query;
	}
	
	
}
