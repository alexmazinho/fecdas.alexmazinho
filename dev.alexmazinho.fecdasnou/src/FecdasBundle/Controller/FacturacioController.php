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
	
		if ($result == null) return 0; // Primer de l'any
			
		return $result;
	}
	
	/********************************************************************************************************************/
	/************************************ INICI SCRIPTS CARREGA *********************************************************/
	/********************************************************************************************************************/
	
	public function resetcomandesAction(Request $request) {
		// http://www.fecdasnou.dev/resetcomandes?id=106313&detall=149012&factura=8380
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$id = $request->query->get('id', 106313);	// id històric
		$detall = $request->query->get('detall', 149012);  // detall històric
		$factura = $request->query->get('factura', 8380);  // factures històric
		
		/*$comandes = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->findAll();
	
		foreach ($comandes as $c) {
			foreach ($c->getDetalls() as $d) $em->remove($d);
			$em->remove($c);
		}
		$em->flush();
		*/
		
	
		$sql = "DELETE FROM m_comandadetalls WHERE id > ".$detall;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		/*$sql = "UPDATE m_comandes SET factura = NULL, rebut = NULL WHERE id > ".$id;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();*/
		
		$sql = "DELETE FROM m_factures WHERE id > ".$factura;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_comandes WHERE id > ".$id;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_rebuts WHERE id > 0";
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
		return new Response("");
	}
	
	public function migrahistoricAction(Request $request) {
		// http://www.fecdasnou.dev/migrahistoric?year=20XX
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$year = $request->query->get('year', 0);
		
		$batchSize = 100;
		
		$strQuery = "SELECT p.id, p.importparte, p.dataentradadel, p.databaixadel,";
		$strQuery .= " p.clubdel, t.descripcio as tdesc, c.categoria as ccat,";
		$strQuery .= " c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament,";
		$strQuery .= " p.dadespagament, p.importpagament,	p.comentari, p.datafacturacio, p.numfactura, ";
		$strQuery .= " COUNT(p.id) as total FROM m_partes p ";
		$strQuery .= " INNER JOIN m_tipusparte t ON p.tipus = t.id ";
		$strQuery .= " INNER JOIN m_categories c ON c.tipusparte = t.id ";
		$strQuery .= " WHERE p.dataalta < '".($year+1)."-01-01 00:00:00' ";
		if ($year > 2002) $strQuery .= " AND p.dataalta >= '".$year."-01-01 00:00:00' ";
		$strQuery .= " GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ";
		$strQuery .= " ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, ";
		$strQuery .= " p.comentari, p.datafacturacio, p.numfactura";
		//$strQuery .= " ORDER BY p.id, csim ";
		$strQuery .= " ORDER BY p.dataentradadel, p.id ";
		
		
		
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$partesAbans2015 = $stmt->fetchAll();
		
		echo "Total partes: " . count($partesAbans2015) . PHP_EOL;
			
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
		
		// Tractar duplicats primer
		$maxnums = array(
				'maxnumcomanda' => $this->getMaxNumEntity($year, BaseController::COMANDES) + 1 
		);
		
		$ipar = 0;
		$i = 0;
		
		$parteid = 0;
		$partes = array();
		
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		
		try {
			/***********************************************************************************/
			/****************************   PARTES i DUPLICATS  ********************************/
			/***********************************************************************************/
				
			error_log('Primer PARTE '.$partesAbans2015[0]['dataentradadel']);
			echo 'Primer PARTE '.$partesAbans2015[0]['dataentradadel'].'<br/>';
				
			
			
			while (isset($partesAbans2015[$ipar])) {
			
				 if (substr($partesAbans2015[$ipar]['dataentradadel'], 0, 4) > $year) {
					 $year = substr($partesAbans2015[$ipar]['dataentradadel'], 0, 4);
					 $maxnums['maxnumcomanda'] = $this->getMaxNumEntity($year, BaseController::COMANDES) + 1;
					 
					 echo '***************************************************************************'.'<br/>';
					 echo '===============================> ANY '.$year.'  <=========================='.'<br/>';
					 echo '***************************************************************************'.'<br/>';
				 }
			
				 $parte = $partesAbans2015[$ipar];
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
			// El darrer parte del dia
			if ($parteid > 0) $this->insertComandaParte($partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
				
		} catch (Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
				
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
				'maxnumcomanda' => $this->getMaxNumEntity($current, BaseController::COMANDES) + 1
		);
	
		$idup = 0;
		$ipar = 0;
		$i = 0;
	
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		
		$dataCurrent = \DateTime::createFromFormat('Y-m-d', "2015-01-01");
		
		try {
			 /***********************************************************************************/
			 /****************************   PARTES i DUPLICATS  ********************************/
			 /***********************************************************************************/
			
			error_log('Primer DUPLI '.$duplicats2015[0]['datapeticio']);
			error_log('Primer PARTE '.$partes2015[0]['dataentradadel']);
			echo 'Primer DUPLI '.$duplicats2015[0]['datapeticio'].'<br/>';
			echo 'Primer PARTE '.$partes2015[0]['dataentradadel'].'<br/>';
			
			$year = 2014;
			
			while ($dataCurrent->format('Y-m-d') < '2016-01-01') {
				error_log('***************************************************************************');
				error_log('============> DIA '.$dataCurrent->format('Y-m-d').'  <=====================');
				error_log('***************************************************************************');
				echo '***************************************************************************'.'<br/>';
				echo '============> DIA '.$dataCurrent->format('Y-m-d').'  <====================='.'<br/>';
				echo '***************************************************************************'.'<br/>';
				
				if ($dataCurrent->format('Y') > $year) {
					$year = $dataCurrent->format('Y');
					$maxnums['maxnumcomanda'] = 1;
				}
				
				
				$parteid = 0;
				$partes = array();
				
				//echo '!!!!!!!!!!!!!!!!! '.substr($duplicats2015[$idup]['datapeticio'],0,10).'-'.$dataCurrent->format('Y-m-d').'!!!!!!!!!<br/>';
				while (isset($duplicats2015[$idup]) && substr($duplicats2015[$idup]['datapeticio'],0,10) <= $dataCurrent->format('Y-m-d')) {
					$duplicat = $duplicats2015[$idup];
					$this->insertComandaDuplicat($duplicat, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
					$idup++;
				}
				
				//echo '!!!!!!!!!!!!!!!!! '.substr($partes2015[$ipar]['dataentradadel'],0,10).'-'.$dataCurrent->format('Y-m-d').'!!!!!!!!!<br/>';
				while (isset($partes2015[$ipar]) && substr($partes2015[$ipar]['dataentradadel'],0,10) <= $dataCurrent->format('Y-m-d')) {
					$parte = $partes2015[$ipar];
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
				// El darrer parte del dia
				if ($parteid > 0) $this->insertComandaParte($partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
				
				// Següent dia
				$dataCurrent->add(new \DateInterval('P1D'));
			}
			
				
		} catch (Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("");
	}
	
	public function migraaltresAction(Request $request) {
		// http://www.fecdasnou.dev/migraaltres
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$batchSize = 100;
	
		$strQuery = "SELECT p.*, e.preu, e.iva FROM m_productes p LEFT JOIN m_preus e ON e.producte = p.id WHERE e.anypreu = 2015 ORDER BY p.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$productes = array();
		while (isset($aux[$i])) {
			if ($aux[$i]['preu'] == null) $aux[$i]['preu'] = 0;
			if ($aux[$i]['iva'] == null) $aux[$i]['iva'] = 0;
				
			$productes[ $aux[$i]['codi'] ] = $aux[$i];
			$i++;
		}
	
		$strQuery = "SELECT * FROM m_clubs c ORDER BY c.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$clubs = array();
		while (isset($aux[$i])) {
			$clubs[ $aux[$i]['compte'] ] = $aux[$i];
			$i++;
		}
	
	
		// Clubs: 4310000 - 4310999
		// Productes: 6230300  - 7590006  (Llicències 7010000 - 7010025) (Duplicats 7090000, 7590000, 7590002, 7590004)
	
		// SELECT num, COUNT(dh) FROM `apunts_2015` WHERE dh = 'D' GROUP BY num HAVING COUNT(dh) > 1	=> 3,5,11,13,16,18
	
		// SELECT num FROM apunts_2015 WHERE (compte >= 4310000 AND compte <= 4310999) OR
		//			(compte >= 6230300 AND compte < 7010000 AND compte > 7010025 AND compte <= 7590006 AND compte NOT IN (7090000, 7590000, 7590002, 7590004) )
	
	
		$strQuery = "SELECT * FROM apunts_2015 a ";
		//$strQuery .= " ORDER BY a.data, a.num, a.dh ";
		$strQuery .= " ORDER BY a.id ";
	
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$altress2015 = $stmt->fetchAll();
	
		echo "Total apunts: " . count($altress2015) . PHP_EOL;
	
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		// Tractar duplicats primer
		$current = date('Y');
		$maxnums = array(
				'maxnumcomanda' => $this->getMaxNumEntity($current, BaseController::COMANDES) + 1
		);
	
		$iapu = 0;
		$i = 0;
	
		$em->getConnection()->beginTransaction(); // suspend auto-commit
	
		try {
			/***********************************************************************************/
			/****************************   PARTES i DUPLICATS  ********************************/
			/***********************************************************************************/
				
			error_log('Primer ALTRE '.$altress2015[0]['data']);
			echo 'Primer ALTRE '.$altress2015[0]['data'].'<br/>';
				
			$year = 2014;
			
			$altrenum = 0;
			$altres = array();
	
			while (isset($altress2015[$iapu])) {
	
				$altre = $altress2015[$iapu];
					
				if ($altrenum == 0) $altrenum = $altre['num'];
					
				if ($altrenum != $altre['num']) {
					// Agrupar apunts
					$this->insertComandaAltre($clubs, $productes, $altres, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
						
					$altrenum = $altre['num'];
					$altres = array();
				}
					
				if ($altre['dh'] == 'D') $altres['D'][] = $altre;
				else $altres['H'][] = $altre;
				$iapu++;

			}
			// La darrera comanda altre del dia
			if ($altrenum > 0) $this->insertComandaAltre($clubs, $productes, $altres, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
	
		} catch (Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("");
	}
	
	private function insertComandaAltre($clubs, $productes, $altres, &$maxnums, $flush = false) {
		
		// Poden haber vàris 'H' => un club parte llicències Tècnic + Aficionat per exemple
		// SELECT * FROM apunts_2015 a inner join m_productes p On a.compte = p.codi WHERE 1 ORDER BY data, num, dh
		//
		// SELECT * FROM apunts_2015 WHERE num IN ( 119, 510 ) ORDER BY num, dh
		$em = $this->getDoctrine()->getManager();
		
		if (count($altres['D']) == 1) {
			// Un debe. Situació normal un club deutor
			$compteD = $altres['D'][0]['compte']; 
			$id = $altres['D'][0]['id'];
			$num = $altres['D'][0]['num'];
			$data = $altres['D'][0]['data'];
			
			if (count($altres['H']) == 0) error_log("ERROR 1 CAP 'H' = >".$id." ".$num);
			
			if ($compteD >= 5720000 && $compteD <= 5720005) {
				// Rebut ingrés, següent compte club
					
				// Camp concepte està la factura:  	FW:00063/2015 o Factura: 00076/2015
				// Camp document està el rebut: 	00010/15
					
				if (count($altres['H']) != 1) error_log("ERROR 2 => ".$num);
				
				$compteH = $altres['H'][0]['compte'];
				
				if ($altres['D'][0]['concepte'] != $altres['H'][0]['concepte'] ||
					$altres['D'][0]['document'] != $altres['H'][0]['document'] ||
					$altres['D'][0]['importapunt'] != $altres['H'][0]['importapunt']) {
						error_log("ERROR 3 CAP 'H' = >".$id." ".$num);
						echo "ERROR 3 CAP 'H' = >".$id." ".$num."<br/>";
						return;
						
				}
				
				$facturaAux = $altres['D'][0]['concepte'];	// FW:00063/2015 o Factura: 00076/2015 o Fra. 2541/2015
				$pos = strpos($facturaAux, 'Factura');
				if ($pos === false) {
					$pos = strpos($facturaAux, 'Fra');
					if ($pos === false) $numFactura = substr($facturaAux, 3, 5);
					else $numFactura = substr($facturaAux, 5, 4);
				}
				else $numFactura = substr($facturaAux, 9, 5);
				
				if (!is_numeric($numFactura)) {
					error_log("ERROR 5 Número de factura no numèrica=> ".$id." ".$num." ".$compteD." => ".$facturaAux);
					echo "ERROR 5 Número de factura no numèrica=> ".$id." ".$num." ".$compteD." => ".$facturaAux."<br/>";
					return;
				}
				
				$rebutAux = $altres['D'][0]['document'];		// 00010/15
				$numRebut = substr($rebutAux, 0, 5);
				
				$numRebut = $numRebut * 1;  // Number
				
				$import = $altres['D'][0]['importapunt'];
				
				if (!isset( $clubs[$compteH])) {
					error_log("ERROR 7 Club pagador no existeix=> ".$num." ".$compteH);
					echo "ERROR 7 Club pagador no existeix=> ".$num." ".$compteH."<br/>";
					return;
				}
			
				$statement = $em->getConnection()->executeQuery("SELECT * FROM m_factures WHERE num = ".($numFactura*1));
				$factExistent = $statement->fetch();
				
				if ($factExistent == null) {
					error_log("ERROR 9 Factura no trobada => ".$id." ".$num." ".$compteD." ".($numFactura*1));
					echo "ERROR 9 Factura no trobada => ".$id." ".$num." ".$compteD." ".($numFactura*1)."<br/>";
					echo "ERROR 9 Factura ".($numFactura*1)." no trobada => id: ".$id.", anotació: ".$num.", de compte: ".$compteD." a compte: ".$compteH." (".$clubs[$compteH]['nom'] .")<br/>";
					return;
				}
				
				
				// Count no funciona bé
				$count = $em->getConnection()->executeUpdate("UPDATE m_factures SET datapagament = '".$data."' WHERE id = ".$factExistent['id']);

				
				$statement = $em->getConnection()->executeQuery("SELECT * FROM m_comandes WHERE factura = ".$factExistent['id']);
				$comanda = $statement->fetch();
				
				if ($comanda == null) {
					error_log("ERROR 11 Comanda no trobada => ".$id." ".$num." ".$compteD);
					echo "ERROR 11 Comanda no trobada => ".$id." ".$num." ".$compteD."<br/>";
					return;
				}
				
				$statement = $em->getConnection()->executeQuery("SELECT * FROM m_partes WHERE numfactura = '".$numFactura."/2015'");
				$parte = $statement->fetch();
				
				$datapagament = $data;
				$dataentrada = $data; 
				$dadespagament = null;
				
				$tipuspagament = null;
				if ($parte != null) {
					
					if ($parte['estatpagament'] == 'TPV OK' || $parte['estatpagament'] == 'TPV CORRECCIO')  $tipuspagament = BaseController::TIPUS_PAGAMENT_TPV;
					if ($parte['estatpagament'] == 'METALLIC GES' || $parte['estatpagament'] == 'METALLIC WEB')  $tipuspagament = BaseController::TIPUS_PAGAMENT_CASH;
					if ($parte['estatpagament'] == 'TRANS WEB') $tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA;
					if ($parte['estatpagament'] == 'TRANS GES') $tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_SARDENYA;
					
					$dataentrada = $parte['dataentradadel'];
					if ($parte['datapagament'] != null && $parte['datapagament'] != '') {
						$datapagament = $parte['datapagament'];
						$dadespagament = $parte['dadespagament'];
					}
					
				}
				
				if ($tipuspagament == null) {
				
					switch ($compteD) {
						case 5700000:  	// CAIXA FEDERACIÓ, PTES.
							$tipuspagament = BaseController::TIPUS_PAGAMENT_CASH;
							break;
						case 5720001: 	// "LA CAIXA"  LAIETANIA
							$tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA;
							break;
						case 5720003:	// LA CAIXA-SARDENYA
							$tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_SARDENYA;
							break;
						case 5720002:	// LA CAIXA-OF. MALLORCA
						case 5720004:	// CAIXA CATALUNYA
						case 5720005:	// POLISSA DE CREDIT
							error_log("ERROR 7 Tipus de pagament desconegut => ".$id." ".$num." ".$compteD);
							echo "ERROR 7 Tipus de pagament desconegut => ".$id." ".$num." ".$compteD."<br/>";
							return;
							
							break;
					}				
				}			
				// Insertar/Actualitzar rebut
				
				$comentari = "Rebut comanda ".$comanda['num']." ".str_replace("'","''",$comanda['comentaris']);
				
				$query = "INSERT INTO m_rebuts (datapagament, num, import, dadespagament, tipuspagament, comentari, dataentrada) VALUES ";
				$query .= "('".$datapagament."',".$numRebut.",".$import;
				$query .= ", ".($dadespagament==null?"NULL":"'".$dadespagament."'").",".$tipuspagament.",'".$comentari."','".$dataentrada."')";
				
				//$maxnums['maxnumrebut']++;
					
				$em->getConnection()->exec( $query );
				
				$rebutId = $em->getConnection()->lastInsertId();
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
				
			}
			
			if ($compteD >= 4310000 && $compteD <= 4310999) {
				// Deutor club, Factura 
				if (!isset( $clubs[$compteD])) {
					error_log("ERROR 15 Club deutor no existeix=> ".$id." ".$num." ".$compteD);
					echo "ERROR 15 Club deutor no existeix=> ".$id." ".$num." ".$compteD."<br/>";
					return;
				}
				
				$club = $clubs[$compteD];
				
				$facturaAux = $altres['D'][0]['concepte'];	// W-F0063_ 10636110MAS 1AFC_  o F0019_1AFC_
				$pos = strpos($facturaAux, 'W-F');
				if ($pos === false) $numFactura = substr($facturaAux, 1, 4);
				else $numFactura = substr($facturaAux, 3, 4);
				
				if (!is_numeric($numFactura)) {
					error_log("ERROR 16 Número de factura no numèrica=> ".$id." ".$num." ".$compteD);
					echo "ERROR 16 Número de factura no numèrica=> ".$id." ".$num." ".$compteD."<br/>";
					return;
				}
				
				$rebut = null;		
				$import = $altres['D'][0]['importapunt'];

				// Revisió	
				$importDetalls = 0;
				$tipusAnterior = 0;
				for ($i = 0; $i < count($altres['H']); $i++) {
				
					$compteH = $altres['H'][$i]['compte'];
					
					if (!isset( $productes[$compteH])) {
						error_log("ERROR 17 producte no existeix=> ".$id." ".$num." ".$compteH);
						echo "ERROR 17 producte no existeix=> ".$id." ".$num." ".$compteH."<br/>";
						return;
					}
						
					$producte = $productes[$compteH];
					if ($tipusAnterior == 0) $tipusAnterior = $producte['tipus'];

					if ($producte['tipus'] != $tipusAnterior) error_log("ERROR 18 tipus productes barrejats=> ".$id." ".$num." ".$compteH);
					
					/*if ($producte['preu'] != $altres['H'][0]['importapunt']) {
						error_log("ERROR 19 preu producte incorrecte => ".$id." ".$num." ".$compteH);
						echo "ERROR 19 preu producte incorrecte => ".$id." ".$num." ".$compteH."<br/>";
						return;
					}*/
					
					$importDetalls += $altres['H'][$i]['importapunt'];
				}
				
				if ($import != $importDetalls)  {
					
					error_log("ERROR 20 suma detalls incorrecte=> ".$id." ".$num." ".$compteH." ".$import." <> ".$importDetalls." => ".count($altres['H']));
					echo "ERROR 20 suma detalls incorrecte=> ".$id." ".$num." ".$compteH." ".$import." <> ".$importDetalls." => ".count($altres['H'])."<br/>";
					//return;
				}
			
				if ($tipusAnterior <= 0 || $tipusAnterior >= 6)  error_log("ERROR 22 tipus producte desconegut => ".$id." ".$num." ".$compteH);
				
				
				if ($tipusAnterior != BaseController::TIPUS_PRODUCTE_LLICENCIES &&
					$tipusAnterior != BaseController::TIPUS_PRODUCTE_DUPLICATS) {
					// Insertar factura
					$textComanda = 'Comanda '.BaseController::getTipusProducte($tipusAnterior);
					
					$query = "INSERT INTO m_factures (datafactura, num, import, concepte, dataentrada, datapagament) VALUES ";
					$query .= "('".$data."',".$numFactura.",".$import.",'"."Factura - ".$textComanda."'";
					$query .= ",'".$data."',NULL)";
					
					$em->getConnection()->exec( $query );
					
					$facturaId = $em->getConnection()->lastInsertId();
					$em->getConnection()->commit();
					$em->getConnection()->beginTransaction(); // suspend auto-commit

					$rebutId = 0;
						
					//error_log("1=====================================> ".$maxnums['maxnumcomanda']." ".$id." ".$num." ".$compteH."<br/>");
					//echo "1=====================================> ".$maxnums['maxnumcomanda']." ".$id." ".$num." ".$compteH."<br/>";
					
					// Insertar comanda
					$query = "INSERT INTO m_comandes (id, comptabilitat, comentaris, total, dataentrada, databaixa, club, num, rebut,factura, tipus) VALUES ";
					$query .= "(".$maxnums['maxnumcomanda'].", 1, '".$textComanda."', ".$import.",'".$data."'";
					$query .= ",NULL,'".$club['codi']."',".$maxnums['maxnumcomanda'];
					$query .= ", ".($rebutId==0?"NULL":$rebutId).", ".($facturaId==0?"NULL":$facturaId).",'A')";
					
					$em->getConnection()->exec( $query );
					
					for ($i = 0; $i < count($altres['H']); $i++) {
						// Insertar detall
						$compteH = $altres['H'][$i]['compte'];
						$producte = $productes[$compteH];
						$total = $producte['preu']==0 ? 1 : round($altres['H'][$i]['importapunt']/$producte['preu']);
						$anota = $total.'x'.str_replace("'","''",$producte['descripcio']);
					
						$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, descomptedetall, anotacions, dataentrada, databaixa) VALUES ";
						$query .= "(".$maxnums['maxnumcomanda'].",".$producte['id'].",".$total.", 0, '".$anota."',";
						$query .= "'".$data."',NULL)"; 
					
						$em->getConnection()->exec( $query );
					}
					
					$maxnums['maxnumcomanda']++;
					//error_log("2=====================================> ".$maxnums['maxnumcomanda']." ".$id." ".$num." ".$compteH."<br/>");
					//echo "2=====================================> ".$maxnums['maxnumcomanda']." ".$id." ".$num." ".$compteH."<br/>";
					if ($flush || true) {
						$em->getConnection()->commit();
						$em->getConnection()->beginTransaction(); // suspend auto-commit
					}
				}
			}
		} else {
			// Varis o ningún 'D'
			/*if (count($altres['D']) == 0) error_log("CAP 'D' = >".$altres['H'][0]['num']." -".count($altres['D'])."-|-".count($altres['H'])."- " );
			else error_log("VARIS = >".$altres['D'][0]['num']." -".count($altres['D'])."-|-".count($altres['H'])."- " );*/
		}
		
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
	
		$em->getConnection()->exec( $query );
	
		$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, descomptedetall, anotacions, dataentrada, databaixa) VALUES ";
		$query .= "(".$duplicat['id'].",".$duplicat['pid'].", 1, 0, ".($duplicat['observacions']==null?"NULL":"'".$observa."'").",";
		$query .= "'".$duplicat['datapeticio']."',".($duplicat['databaixadel']==null?"NULL":"'".$duplicat['databaixadel']."'").")";
	
		$maxnums['maxnumcomanda']++;

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
				// Rebut ==> Insert des de apunts
				/*$comentari = str_replace("'","''",$parte['comentari']);
	
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
				$em->getConnection()->beginTransaction(); // suspend auto-commit*/
			}
				
			if ($parte['numfactura'] != null) {
				// Factura
				$textLlista = 'Llista '.BaseController::PREFIX_ALBARA_LLICENCIES.str_pad($parte['id'],6,'0',STR_PAD_LEFT).". ".$desc;
	
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
	
			$em->getConnection()->exec( $query );
			
			foreach ($partes as $parte) {
				$total = $parte['total'];
				$anota = $total.'x'.$parte['ccat'];
			
				$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, descomptedetall, anotacions, dataentrada, databaixa) VALUES ";
				$query .= "(".$parte['id'].",".$parte['cpro'].",".$total.", 0, '".$anota."',";
				$query .= "'".$parte['dataentradadel']."',".($parte['databaixadel']==null?"NULL":"'".$parte['databaixadel']."'").")";
			
				$em->getConnection()->exec( $query );
			}
			
			$maxnums['maxnumcomanda']++;
			
			if ($flush) {
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
			}
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
