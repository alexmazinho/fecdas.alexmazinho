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
use FecdasBundle\Entity\EntityDuplicat;
use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Controller\BaseController;


class FacturacioController extends BaseController {
	
	public function comandesAction(Request $request) {
		// Llistat de comandes
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
		
		$this->logEntryAuth('VIEW COMANDES', $this->get('session')->get('username'));
		
		$codi = $request->query->get('cerca', '');
		$tipus = $request->query->get('tipus', 0); // Llicències, Duplicats, Altres
		$baixes = $request->query->get('baixes', 0);
		$baixes = ($baixes == 1?true:false);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.dataentrada');
		$direction = $request->query->get('direction', 'desc');
		
		$query = $this->consultaComandes($codi, $tipus, $baixes, $sort, $direction);
			
		$paginator  = $this->get('knp_paginator');
			
		$comandes = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
			
		$formBuilder = $this->createFormBuilder()
			->add('cerca', 'hidden', array(
				'data' => $codi
		));
		
		$formBuilder
			->add('tipus', 'choice', array(
				'choices' => BaseController::getTipusDeComanda(),
				'required'  => false,
				'empty_value' => 'Qualsevol...',
				'data' => $tipus,
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
	
		$this->logEntryAuth('COMANDA NOVA',	'');

		$em = $this->getDoctrine()->getManager();
		
		$num = $this->getMaxNumEntity(date('Y'), BaseController::FACTURES);
		$factura = new EntityFactura(new \DateTime(), $num + 1);
		
		$em->persist($factura);
		
		$num = $this->getMaxNumEntity(date('Y'), BaseController::COMANDES);
		$comanda = new EntityComanda($num + 1, $factura);
		
		$em->persist($comanda);
		
		$form = $this->createForm(new FormComanda(), $comanda);
		 
		return $this->render('FecdasBundle:Facturacio:comanda.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'comanda' => $comanda)));
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
		
		if ($id == 0 && $cerca == "") {
			$response->headers->set('Content-Type', 'application/json');
			$response->setContent(json_encode(array()));
			return $response;
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
	
	protected function consultaComandes($codi, $tipus, $baixes, $strOrderBY = 'c.id', $direction = 'desc' ) {
		$em = $this->getDoctrine()->getManager();
	
		$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityComanda c ";
		$strQuery .= "WHERE 1 = 1 ";
		if ($codi != '') $strQuery .= " AND c.club = :codi ";
		if (! $baixes) $strQuery .= " AND c.databaixa IS NULL ";
		if ($tipus == BaseController::TIPUS_COMANDA_LLICENCIES) $strQuery .= " AND c.parte IS NOT NULL ";
		if ($tipus == BaseController::TIPUS_COMANDA_DUPLICATS) $strQuery .= " AND c.duplicat IS NOT NULL ";
		if ($tipus == BaseController::TIPUS_COMANDA_ALTRES) $strQuery .= " AND c.duplicat IS NULL AND c.parte IS NULL ";
		
		
		$strQuery .= " ORDER BY " .$strOrderBY ." ".$direction;
		$query = $em->createQuery($strQuery);
	
		if ($codi != '') $query->setParameter('codi', $codi);
		
		return $query;
	}
	
	
	public function getMaxNumEntity($year, $tipus) {
		$em = $this->getDoctrine()->getManager();
	
		$inici = $year."-01-01";
		$final = $year."-12-31";
	
		$strQuery = '';
		switch ($tipus) {
			case BaseController::REBUTS:
				$strQuery = "SELECT MAX(r.num) FROM FecdasBundle\Entity\EntityRebut r ";
				$strQuery .= " WHERE r.datapagament >= '".$inici."' AND r.datapagament <= '".$final."'";
				break;
			case BaseController::FACTURES:
				$strQuery = " SELECT MAX(f.num) FROM FecdasBundle\Entity\EntityFactura f ";
				$strQuery .= " WHERE f.datafactura >= '".$inici."' AND f.datafactura <= '".$final."'";
				break;
			case BaseController::COMANDES:
				$strQuery = " SELECT MAX(c.num) FROM FecdasBundle\Entity\EntityComanda c ";
				$strQuery .= " WHERE c.dataentrada >= '".$inici."' AND c.dataentrada <= '".$final."'";
				break;
			default:
				return -1;
		}
		
		$query = $em->createQuery($strQuery);
		$result = $query->getSingleScalarResult();
	
		if ($result == null) return 1; // Primer de l'any
			
		return $result;
	}
	
	/********************************************************************************************************************/
	/************************************ INICI SCRIPTS CARREGA *********************************************************/
	/********************************************************************************************************************/
	
	public function resetcomandesAction(Request $request) {
		// http://www.fecdasnou.dev/resetcomandes
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$comandes = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->findAll();
	
		foreach ($comandes as $c) {
			foreach ($c->getDetalls() as $d) $em->remove($d);
			$em->remove($c);
		}
	
		$em->flush();
	
		$factures = $this->getDoctrine()->getRepository('FecdasBundle:EntityFactura')->findAll();
	
		foreach ($factures as $f) $em->remove($f);
	
		$em->flush();
	
		$rebuts = $this->getDoctrine()->getRepository('FecdasBundle:EntityRebut')->findAll();
	
		foreach ($rebuts as $r) $em->remove($r);
	
		$em->flush();
	
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
		return new Response("");
	}
	
	public function migracomandesAction(Request $request) {
		// http://www.fecdasnou.dev/migracomandes
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$batchSize = 100;
	
		$strQuery = "SELECT p.id, p.importparte, p.dataentradadel, p.databaixadel,";
		$strQuery .= " p.clubdel, t.descripcio as tdesc, c.categoria as ccat,";
		$strQuery .= " c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament,";
		$strQuery .= " p.dadespagament, p.importpagament,	p.comentari, p.datafacturacio, p.numfactura, ";
		$strQuery .= " COUNT(p.id) as total FROM m_partes p ";
		$strQuery .= " INNER JOIN m_tipusparte t ON p.tipus = t.id ";
		$strQuery .= " INNER JOIN m_categories c ON c.tipusparte = t.id ";
		$strQuery .= " WHERE p.dataalta >= '2015-01-01 00:00:00' ";
		$strQuery .= " GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ";
		$strQuery .= " ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, ";
		$strQuery .= " p.comentari, p.datafacturacio, p.numfactura";
		$strQuery .= " ORDER BY p.id, csim ";
	
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$partes2015 = $stmt->fetchAll();
	
		$strQuery = "SELECT d.*,  p.id as pid, p.descripcio as pdescripcio, e.preu as epreu, e.iva as eiva ";
		$strQuery .= " FROM m_duplicats d  INNER JOIN m_carnets c ON d.carnet = c.id ";
		$strQuery .= " INNER JOIN m_productes p ON c.producte = p.id ";
		$strQuery .= " INNER JOIN m_preus e ON e.producte = p.id ";
		$strQuery .= " WHERE e.anypreu = 2015 ";
		$strQuery .= " ORDER BY d.datapeticio ";
	
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$duplicats2015 = $stmt->fetchAll();
	
		echo "Total partes: " . count($partes2015) . PHP_EOL;
		echo "Total dupli: " . count($duplicats2015) . PHP_EOL;
	
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		// Tractar duplicats primer
		$current = date('Y');
		$maxnums = array(
				'maxnumcomanda' => $this->getMaxNumEntity($current, BaseController::COMANDES),
				'maxnumrebut' => $this->getMaxNumEntity($current, BaseController::REBUTS)
		);
	
		$idup = 0;
		$ipar = 0;
	
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		try {
			while (isset($duplicats2015[$idup]) && $duplicats2015[$idup]['datapeticio'] < '2015-01-01') {
				$duplicat = $duplicats2015[$idup];
				$this->insertComandaDuplicat($duplicat, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
				$idup++;
			}
				
			$parteid = 0;
			$partes = array();
			while (isset($partes2015[$ipar])) {
				$parte = $partes2015[$ipar];
	
				while (isset($duplicats2015[$idup]) && $duplicats2015[$idup]['datapeticio'] < $parte['dataentradadel']) {
					$duplicat = $duplicats2015[$idup];
					$this->insertComandaDuplicat($duplicat, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
					$idup++;
				}
	
				if ($parteid == 0) $parteid = $parte['id'];
					
				if ($parteid != $parte['id']) {
					// Agrupar partes, poden venir vàries línies seguides segons categoria 'A', 'T' ...
					$this->insertComandaParte($partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
	
					$parteid = $parte['id'];
					$partes = array();
				}
				$partes[] = $parte;
				$ipar++;
			}
				
			// El darrer parte
			$this->insertComandaParte($partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
			//$maxNumComanda++;
		} catch (Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("");
	}
	
	private function insertComandaDuplicat($duplicat, &$maxnums, $flush = false) {
		$current = date('Y');
	
		$em = $this->getDoctrine()->getManager();
	
		$preu = $duplicat['epreu'];
		$iva = $duplicat['eiva'];
		$total = ($preu * (1 + $iva));
		$desc = str_replace("'","''",$duplicat['pdescripcio']);
		$observa = str_replace("'","''",$duplicat['observacions']);
	
		$query = "INSERT INTO m_comandes (id, comptabilitat, comentaris, total, dataentrada, databaixa, club, num, tipus) VALUES ";
		$query .= "(".$duplicat['id'].", 1, '".$desc."', ".$total.",'".$duplicat['datapeticio']."'";
		$query .= ",".($duplicat['databaixadel']==null?"NULL":"'".$duplicat['databaixadel']."'").",'".$duplicat['clubdel']."',".$maxnums['maxnumcomanda'].",'D')";
	
		$maxnums['maxnumcomanda']++;
		//error_log($query);
	
		$em->getConnection()->exec( $query );
	
		$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, descomptedetall, anotacions, dataentrada, databaixa) VALUES ";
		$query .= "(".$duplicat['id'].",".$duplicat['pid'].", 1, 0, ".($duplicat['observacions']==null?"NULL":"'".$observa."'").",";
		$query .= "'".$duplicat['datapeticio']."',".($duplicat['databaixadel']==null?"NULL":"'".$duplicat['databaixadel']."'").")";
	
		//error_log($query);
	
		$em->getConnection()->exec( $query );
	
		if ($flush) {
			$em->getConnection()->commit();
			$em->getConnection()->beginTransaction(); // suspend auto-commit
		}
	}
	
	private function insertComandaParte($partes, &$maxnums, $flush = false) {
		$em = $this->getDoctrine()->getManager();
	
		if (isset($partes[0])) {
			$parte = $partes[0];
			$desc = str_replace("'","''",$parte['tdesc']);
			$rebutId = 0;
			$facturaId = 0;
				
			if ($parte['datapagament'] != null) {
				// Rebut
				$comentari = str_replace("'","''",$parte['comentari']);
	
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
				$em->getConnection()->beginTransaction(); // suspend auto-commit
			}
				
			if ($parte['numfactura'] != null) {
				// Factura
				$textLlista = 'Llista '.EntityParte::PREFIX_ALBARA_LLICENCIES.str_pad($parte['id'],6,'0',STR_PAD_LEFT).". ".$desc;
	
				$factArray = explode("/",$parte['numfactura']);
	
				$query = "INSERT INTO m_factures (datafactura, num, import, concepte, dataentrada, datapagament) VALUES ";
				$query .= "('".$parte['datafacturacio']."',".$factArray[0].",".$parte['importparte'].",'".$textLlista."'";
				$query .= ",'".$parte['dataentradadel']."',".($parte['datapagament']==null?"NULL":"'".$parte['datapagament']."'").")";
	
				//error_log($query);
	
				$em->getConnection()->exec( $query );
	
				$facturaId = $em->getConnection()->lastInsertId();
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
			}
				
			$query = "INSERT INTO m_comandes (id, comptabilitat, comentaris, total, dataentrada, databaixa, club, num, rebut,factura, tipus) VALUES ";
			$query .= "(".$parte['id'].", 1, '".$desc."', ".$parte['importparte'].",'".$parte['dataentradadel']."'";
			$query .= ",".($parte['databaixadel']==null?"NULL":"'".$parte['databaixadel']."'").",'".$parte['clubdel']."',".$maxnums['maxnumcomanda'];
			$query .= ", ".($rebutId==0?"NULL":$rebutId).", ".($facturaId==0?"NULL":$facturaId).",'P')";
	
			$maxnums['maxnumcomanda']++;
				
			$em->getConnection()->exec( $query );
		}
	
		foreach ($partes as $parte) {
			$total = $parte['total'];
			$anota = $total.'x'.$parte['ccat'];
				
			$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, descomptedetall, anotacions, dataentrada, databaixa) VALUES ";
			$query .= "(".$parte['id'].",".$parte['cpro'].",".$total.", 0, '".$anota."',";
			$query .= "'".$parte['dataentradadel']."',".($parte['databaixadel']==null?"NULL":"'".$parte['databaixadel']."'").")";
				
			$em->getConnection()->exec( $query );
		}
	
		if ($flush) {
			$em->getConnection()->commit();
			$em->getConnection()->beginTransaction(); // suspend auto-commit
		}
	}
	
	private function crearComandaDuplicat($duplicat, $maxNumComanda, $flush = false) {
		$current = date('Y');
	
		$em = $this->getDoctrine()->getManager();
	
		$producte = $duplicat->getCarnet()->getProducte();
		$preu = $producte->getPreuAny($current);
		$iva = $producte->getIvaAny($current);
	
		//echo "duplicat " .$duplicat->getId().' '.$producte->getDescripcio().' '.$duplicat->getClub()->getNom(). PHP_EOL."<br/>". PHP_EOL."<br/>";
	
		$comanda = new EntityComanda($maxNumComanda, $duplicat->getClub(), $preu * (1 + $iva), $producte->getDescripcio(), null, $duplicat);
		$em->persist($comanda);
			
		if ($duplicat->esBaixa()) $comanda->setDatabaixa($duplicat->getDatabaixa());
	
		$detall = new EntityComandaDetall($comanda, $producte, 1, 0, $duplicat->getObservacions());
		$em->persist($detall);
	
		$comanda->addDetall($detall);
	
		if ($flush)	$em->flush();
	}
	
	private function crearComandaParte($parte, $maxNumComanda, $flush = false) {
		$em = $this->getDoctrine()->getManager();
	
		$comanda = new EntityComanda($maxNumComanda, $parte->getClub(), $parte->getImportParte(), $parte->getTipus()->getDescripcio(), $parte, null);
		$em->persist($comanda);
			
		if ($parte->esBaixa()) $comanda->setDatabaixa($parte->getDatabaixa());
	
		//echo "parte " .$parte->getId(). PHP_EOL."<br/>". PHP_EOL."<br/>";
		foreach ($parte->getTipus()->getCategories() as $categoria) {
				
			switch ($categoria->getSimbol()) {
				case 'A':
					$total = $parte->getNumAficionats();
					break;
				case 'I':
					$total = $parte->getNumInfantils();
					break;
				case 'T':
					$total = $parte->getNumTecnics();
					break;
				default:
					return -1;
			}
			if ($total > 0) {
				//echo " ==> ".$total.'x'.$categoria->getCategoria()."(".$categoria->getProducte()->getDescripcio().")". PHP_EOL."<br/>". PHP_EOL."<br/>";
				$detall = new EntityComandaDetall($comanda, $categoria->getProducte(), $total, 0, $total.'x'.$categoria->getCategoria());
				$em->persist($detall);
					
				$comanda->addDetall($detall);
			}
		}
			
		if ($flush)	$em->flush();
	}
	
	
}
