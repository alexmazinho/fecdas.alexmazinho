<?php 
namespace Fecdas\PartesBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta;
use Fecdas\PartesBundle\Entity\Enquestes\EntityEnquestaPregunta;
use Fecdas\PartesBundle\Entity\Enquestes\EntityPregunta;
use Fecdas\PartesBundle\Entity\Enquestes\EntityRealitzacio;
use Fecdas\PartesBundle\Entity\Enquestes\EntityResposta;
use Fecdas\PartesBundle\Form\Enquestes\FormEnquesta;

class EnquestesController extends BaseController {
	public function enquestesAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	
		$this->get('session')->clearFlashes();
		
		$em = $this->getDoctrine()->getEntityManager();
	
		$this->logEntry($this->get('session')->get('username'), 'VIEW ENQUESTES',
				$this->get('session')->get('remote_addr'),
				$this->getRequest()->server->get('HTTP_USER_AGENT'));
	
		// Get all enquestes
		$strQuery = "SELECT e FROM Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta e";
		$strQuery .= " ORDER BY e.dataalta DESC";
		$query = $em->createQuery($strQuery);
			
		$enquestes = $query->getResult();
	
		// Get all users
		$strQuery = "SELECT COUNT(u.user) FROM Fecdas\PartesBundle\Entity\EntityUser u";
		$query = $em->createQuery($strQuery);
		
		$poblacio = $query->getSingleScalarResult();
		
		return $this->render('FecdasPartesBundle:Enquestes:enquestes.html.twig',
				array('enquestes' => $enquestes, 'poblacio' => $poblacio, 
						'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig()));
	}
	
	public function tancarenquestaAction() {
		$request = $this->getRequest();
		
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
		
		if ($request->query->has('id')) {
			$enquesta = $this->getDoctrine()->getRepository('FecdasPartesBundle:Enquestes\EntityEnquesta')->find($request->query->get('id'));
			
			$em = $this->getDoctrine()->getEntityManager();
			
			$enquesta->setDatafinal($this->getCurrentDate('now'));
			
			$em->flush();
			
			$this->logEntry($this->get('session')->get('username'), 'TANCAR ENQUESTA',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), "enquesta " . $enquesta->getId());
		} else {
			$this->logEntry($this->get('session')->get('username'), 'TANCAR ENQUESTA ERROR',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), "enquesta " . $enquesta->getId());
		}
		
		$response = $this->forward('FecdasPartesBundle:Enquestes:enquestes');
		return $response;
	}
	
	
	public function enquestaAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	
		$em = $this->getDoctrine()->getEntityManager();
		
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\Enquestes\EntityPregunta p";
		$strQuery .= " ORDER BY p.id";
			
		$query = $em->createQuery($strQuery);
		
		$preguntestotes = $query->getResult();
		$preguntesno = array();
		
		$enquesta = null;
		if ($request->getMethod() == 'POST') {
			//print_r($request->request);
			//return new Response();
			$error = "";
			if ($request->request->has('enquesta')) {
				$paramenquesta = $request->request->get('enquesta');
				if ($paramenquesta['id'] != "") {
					$enquesta = $this->getDoctrine()->getRepository('FecdasPartesBundle:Enquestes\EntityEnquesta')->find($paramenquesta['id']);
				} else {
					$enquesta = new EntityEnquesta($this->getCurrentDate('now'));
				}
			}

			$form = $this->createForm(new FormEnquesta(), $enquesta);
			
			$form->bindRequest($request);
			
			$actiontext = (is_null($enquesta->getId()))?'NEW ENQUESTA OK':'UPD ENQUESTA OK';

			
			/* Seleccionar enquestes entre dues dates */
			$strQuery = "SELECT e FROM Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta e";
			$strQuery .= " ORDER BY e.datainici";
			$query = $em->createQuery($strQuery);
				
			$enquestes = $query->getResult();
			
			$datainici = $enquesta->getDatainici()->format('Y-m-d');
			$datafinal = ""	;
			
			
			if ($enquesta->getDatafinal() != null) {
				$datafinal = $enquesta->getDatafinal()->format('Y-m-d');
				if ($datainici > $datafinal) $error = "La data d'inici no pot ser posterior a la data final";
			}
			
			if ($error == "") {
				foreach ($enquestes as $c => $enquesta_iter) {
					/* Validacions de dates, que no solapin */
					if ($enquesta_iter != $enquesta){
						if ($enquesta_iter->getDatafinal() == null) $error = "Encara hi ha una enquesta activa, cal tancar-la";
						else {
							if ($datafinal == null) {
							// (StartA <= EndB) and (inifinite >= StartB)
								if ($datainici <= $enquesta_iter->getDatafinal()->format('Y-m-d')) $error = "Aquesta enquesta comença abans que finalitzi una altra";
							} else {
							// (StartA <= EndB) and (EndA >= StartB)
								if ($datainici <= $enquesta_iter->getDatafinal()->format('Y-m-d') and
									$datafinal >= $enquesta_iter->getDatainici()->format('Y-m-d')) $error = "Existeix una altra enquesta activa per aquest periode";
							}
						}
					}
				}
			}
			
			if ($form->isValid() && $error == "") {
				if ($enquesta->getId() == null) $em->persist($enquesta);
				// Esborrar totes les preguntes i tornar-les a afegir
				if ($request->request->has('preguntes')) {
					parse_str($request->request->get('preguntes'));  // Parse array $pregunta
					// Esborrar primer totes les preguntes  array clear() no funciona
					foreach ($enquesta->getPreguntes() as $c => $preguntacheck) {
						//$enquesta->removeEntityEnquestaPregunta($preguntacheck);
						$em->remove($preguntacheck);
					}
					
					foreach ($preguntestotes as $c => $preguntacheck) {
						$pos = array_search($preguntacheck->getId(), $pregunta);
						
						if (!($pos === false)) { // Compte false avalua 0, no fer servir ==
							$enquestapregunta = new EntityEnquestaPregunta($enquesta, $preguntacheck, $pos);
							$enquesta->addEntityEnquestaPregunta($enquestapregunta);
							$em->persist($enquestapregunta);
						} 
					}
				}
				
				try {
					$em->flush();
				} catch (\Exception $e) {
					$error = "Error desant les dades: " .  $e->getMessage() . "\n";
				}
			} else {
				if ($error == "") $error = "Les dades són incorrectes";
			}
			
			if ($error != "") {
				$em->detach($enquesta);
				$this->logEntry($this->get('session')->get('username'), (is_null($enquesta->getId()))?'NEW ENQUESTA KO':'UPD ENQUESTA KO',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), $enquesta->getId() . "-". $error);
				$this->get('session')->setFlash('error-notice', $error );
				
			} else {
				$this->logEntry($this->get('session')->get('username'), $actiontext,
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), $enquesta->getId());
				$this->get('session')->setFlash('error-notice', "Enquesta actualitzada correctament" );
			}
		} else {
			// Get
			if ($request->query->has('id')) {
				$enquesta = $this->getDoctrine()->getRepository('FecdasPartesBundle:Enquestes\EntityEnquesta')->find($request->query->get('id'));

				if (count($enquesta->getRealitzacions()) > 0) {
					// No es pot modificar l'enquesta, s'ha començat a contestar 
					$response = $this->forward('FecdasPartesBundle:Enquestes:enquestes');
					return $response;
				} 
				
				$preguntesno = $preguntestotes;
				$preguntestotes = array();
				
				foreach ($enquesta->getPreguntesSortedByOrdre()  as $c => $epregunta) {
					$pregunta = $epregunta->getPregunta();
					
					$pos = array_search($pregunta,$preguntesno);
					
					if (!($pos === false))  {  // Compte false avalua 0, no fer servir ==
						$preguntestotes[] = $pregunta;
						unset($preguntesno[$pos]);
					}
				}
				
			} else {
				$enquesta = new EntityEnquesta($this->getCurrentDate('now'));
			}
			
			$form = $this->createForm(new FormEnquesta(), $enquesta);
			
			$this->logEntry($this->get('session')->get('username'), 'VIEW ENQUESTA',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $enquesta->getId());
		}
		
		return $this->render('FecdasPartesBundle:Enquestes:enquesta.html.twig',
				array('form' => $form->createView(), 'preguntessi' => $preguntestotes, 'preguntesno' => $preguntesno,
						'admin' => $this->isCurrentAdmin(),
						'authenticated' => $this->isAuthenticated(), 'busseig' => $this->isCurrentBusseig()));
	}
	
	public function enquestausuariAction() {
		$request = $this->getRequest();
		
		//if ($this->isAuthenticated() != true or $this->get('session')->has('enquestapendent') != true) return new Response("error");
		if ($this->isAuthenticated() != true) return new Response("error");
		
		$action = "";
		if ($request->getMethod() == 'POST') {
			$formenquesta = $request->request->get('form');
			$enquesta = $this->getDoctrine()->getRepository('FecdasPartesBundle:Enquestes\EntityEnquesta')->find($formenquesta['id']);

			$em = $this->getDoctrine()->getEntityManager();

			// Comprovar si s'ha començat l'enquesta
			$realitzacio = $enquesta->getRealitzada($this->get('session')->get('username'));
			if ($realitzacio == null) {
				$usuari = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityUser')->find($this->get('session')->get('username'));
				$realitzacio = new EntityRealitzacio($usuari, $enquesta);
				$enquesta->addEntityRealitzacio($realitzacio);
				$em->persist($realitzacio);
			}
			
			// No mirar id ni _token, només respostes
			unset($formenquesta['_token']);
			unset($formenquesta['id']);
			
			$a_keys = array_keys($formenquesta);
			foreach($a_keys as $preguntakey) {
				$dades_array = explode("_", $preguntakey);
				$preguntaId = $dades_array[1];
				$respostaValor =  $formenquesta[$preguntakey];
				
				$pregunta = $this->getDoctrine()->getRepository('FecdasPartesBundle:Enquestes\EntityPregunta')->find($preguntaId);
				// Preparem resposta
				$resposta = $realitzacio->getResposta($pregunta);
				if ($resposta == null) {
					$resposta = new EntityResposta($realitzacio, $pregunta);
					$em->persist($resposta);  // Sempre nova. Si existeix anterior s'esborra
					$realitzacio->addEntityResposta($resposta);
				}
				
				switch ($pregunta->getTipus()) {
					case "RANG":
						$resposta->setRespostarang($respostaValor);
						break;
					case "BOOL":
						if ($respostaValor == "KEY_YES") $resposta->setRespostabool(true);
						else $resposta->setRespostabool(false);
						break;
					case "OPEN":
						$resposta->setRespostatxt($respostaValor);
						break;
				}
			}

			$em->flush();
			
			$this->logEntry($this->get('session')->get('username'), 'SAVE ENQUESTA USUARI',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $enquesta->getId());
			
			return new Response("Enquesta desada correctament!");
		} else {
			//Get
			$id = $this->get('session')->get('enquestapendent');
			if ($request->query->has('id')) {
				$id = $request->query->get('id'); // Demana administrador per revisar
				$action = "preview";
			}
			$enquesta = $this->getDoctrine()->getRepository('FecdasPartesBundle:Enquestes\EntityEnquesta')->find($id);
			
			$this->logEntry($this->get('session')->get('username'), 'VIEW ENQUESTA USUARI',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $enquesta->getId());
		}
		
		$form = $this->createEnquestaForm($enquesta);
		
		return $this->render('FecdasPartesBundle:Enquestes:enquestausuari.html.twig',
				array('form' => $form->createView(), 'enquesta' => $enquesta, 'action' => $action,
						'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(), 'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}
	
	private function createEnquestaForm($enquesta) {
		$formBuilder = $this->createFormBuilder();
		$formBuilder->add('id', 'hidden',array('data' => $enquesta->getId()));
		
		$realitzacio = $enquesta->getRealitzada($this->get('session')->get('username'));
		$resposta = null;
		
		foreach ($enquesta->getPreguntesSortedByOrdre() as $c => $epregunta) {
			$pregunta = $epregunta->getPregunta();
			
			if ($realitzacio != null) $resposta = $realitzacio->getResposta($pregunta);
			
			$fieldname = 'pregunta_' . $pregunta->getId();
			
			$dades = '';
			switch ($pregunta->getTipus()) {
				case "RANG":
					if ($resposta != null) $dades =   $resposta->getRespostarang();
					$formBuilder->add($fieldname, 'choice', array(
							'choices'   => array('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'),
							'required'  => false,
							'multiple'  => false,
							'expanded'  => true,
							'data' 		=> $dades,
							'label' => $epregunta->getOrdre().". ".$pregunta->getEnunciat(),
					));
					break;
				case "BOOL":
					if ($resposta != null) {
						if ($resposta->getRespostabool() == true) $dades = 'KEY_YES';
						else $dades = 'KEY_NO';
					}
					$formBuilder->add($fieldname, 'choice', array(
							'choices'   => array('KEY_YES' => 'Si', 'KEY_NO' => 'No'),
							'required'  => false,
							'multiple'  => false,
							'expanded'  => true,
							'data' 		=> $dades,
							'label' => $epregunta->getOrdre().". ".$pregunta->getEnunciat(),
					));
					break;
				case "OPEN":
					if ($resposta != null) $dades =   $resposta->getRespostatxt();
					$formBuilder->add($fieldname, 'textarea', array('label' => $epregunta->getOrdre().". ".$pregunta->getEnunciat(),'required'  => false, 'data' 		=> $dades,));
					break;
			}
			
		}
		return $formBuilder->getForm();
	}
	
	
	public function enquestaresultatsAction() {
		/* Genera les dades de les respostes d'una enquesta */
		$request = $this->getRequest();
		
		if ($request->query->has('id')) {
			$id = $request->query->get('id'); // Demana administrador per revisar
			
			$enquesta = $this->getDoctrine()->getRepository('FecdasPartesBundle:Enquestes\EntityEnquesta')->find($id);
			
			$respostes = $enquesta->getResultats();
			
			$this->logEntry($this->get('session')->get('username'), 'VIEW RESULTATS ENQUESTA',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $enquesta->getId());
			
			return $this->render('FecdasPartesBundle:Enquestes:enquestaresultats.html.twig',
					array('respostes' => $respostes));
		}
		
		return new Response("No data");
	}

	public function estadistiquesAction() {
		$request = $this->getRequest();
		
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
		
		$this->logEntry($this->get('session')->get('username'), 'VIEW ESTADISTIQUES ENQUESTES',
				$this->get('session')->get('remote_addr'),
				$this->getRequest()->server->get('HTTP_USER_AGENT'));
		
		/* Tab / pregunta per mostrar */
		$tab = 0;
		$preguntaid = 1;
		if ($request->query->has('tab')) $tab = $request->query->get('tab');
		if ($request->query->has('preguntaid')) $preguntaid = $request->query->get('preguntaid');
		
		
		return $this->render('FecdasPartesBundle:Enquestes:estadistiques.html.twig',
			array('tab' => $tab,  'pselected' => $preguntaid, 'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
					'busseig' => $this->isCurrentBusseig()));
	}
	
	public function estadistiquesTab1Action() {
		/* AJAX. Evolució de la mitjana de totes les respostes */
		//return new Response("hola tab1");
		
		$enunciats = array();
		$dades = array();

		if ($this->isCurrentAdmin() != true) return new Response("La sessió ha expirat");
		
		$em = $this->getDoctrine()->getEntityManager();
		
		/* Obtenir totes les preguntes */
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\Enquestes\EntityPregunta p";
		$strQuery .= " WHERE p.tipus <> 'OPEN' ORDER BY p.id";
			
		$query = $em->createQuery($strQuery);
		
		$preguntes = $query->getResult();
		
		/* Obtenir totes les enquestes */
		$strQuery = "SELECT e FROM Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta e";
		$strQuery .= " ORDER BY e.datainici";
		$query = $em->createQuery($strQuery);
		
		$enquestes = $query->getResult();
		
		foreach ($preguntes as $c => $pregunta) {
			if ($pregunta->getTipus() == "RANG" or $pregunta->getTipus() == "BOOL") {
				/* Només preguntes resultat numèric */
				$dadespregunta = array();
				$enunciats[] = array("label" => $pregunta->getEnunciat());
				
				foreach ($enquestes as $c => $enquesta) {
					/* Només enquestes amb dades */
					if (count($enquesta->getRealitzacions()) > 0) {
						$avgpreguntaenquesta = array();
						$avgpreguntaenquesta[] = $enquesta->getDatainici()->format('d-m-Y');
						
						$avgpreguntaenquesta[] = $enquesta->getAvgPregunta($pregunta);
						$dadespregunta[] =$avgpreguntaenquesta;
					}
				}
				
				$dades[] = $dadespregunta;
			}
		}
		
		return $this->render('FecdasPartesBundle:Enquestes:estadistiquesTab1.html.twig',
				array('enunciats' => json_encode($enunciats), 'dades' => json_encode($dades)));
	}
	
	
	public function estadistiquesTab2Action() {  
		/* AJAX. Evolució d'una pregunta tipus RANG o BOOL al llarg de les diferents enquestes (temps) */
		
		$request = $this->getRequest();
		
		if ($this->isCurrentAdmin() != true) return new Response("La sessió ha expirat");
		
		$em = $this->getDoctrine()->getEntityManager();
		
		/* Obtenir totes les preguntes */
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\Enquestes\EntityPregunta p";
		$strQuery .= " WHERE p.tipus <> 'OPEN' ORDER BY p.id";
			
		$query = $em->createQuery($strQuery);
		
		$preguntes = $query->getResult();
		
		/* Seleccionar enquestes entre dues dates */		
		$strQuery = "SELECT e FROM Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta e";
		$strQuery .= " ORDER BY e.datainici";
		$query = $em->createQuery($strQuery);
			
		$enquestes = $query->getResult();		
		
		/* Pregunta per mostrar resultats */
		$preguntaid = 1;
		if ($request->query->has('preguntaid')) $preguntaid = $request->query->get('preguntaid');
		$pregunta = $this->getDoctrine()->getRepository('FecdasPartesBundle:Enquestes\EntityPregunta')->find($preguntaid);
		
		$valors = array();
		$dades = array();
		$mesures = array(); /* Eix x. Enquesta data */
		
		$dadespreguntagens = array();
		$dadespreguntapoc = array();
		$dadespreguntasuficient = array();
		$dadespreguntabastant = array();
		$dadespreguntamolt = array();
		$dadespreguntasi = array();
		$dadespreguntano = array();
		
		foreach ($enquestes as $c => $enquesta) {
			/* Només enquestes amb dades */
			if (count($enquesta->getRealitzacions()) > 0) {
				if ($pregunta->getTipus() == "RANG") {
					/* Totals per resposta de cada pregunta */
					$totals = $enquesta->getTotalPreguntaRang($pregunta);
					$totalRespostes = $totals[0]+$totals[1]+$totals[2]+$totals[3]+$totals[4];
					$dadespreguntagens[] = ($totalRespostes == 0)?0:$totals[0]/$totalRespostes*100;
					$dadespreguntapoc[] = ($totalRespostes == 0)?0:$totals[1]/$totalRespostes*100;
					$dadespreguntasuficient[] = ($totalRespostes == 0)?0:$totals[2]/$totalRespostes*100;
					$dadespreguntabastant[] = ($totalRespostes == 0)?0:$totals[3]/$totalRespostes*100;
					$dadespreguntamolt[] = ($totalRespostes == 0)?0:$totals[4]/$totalRespostes*100;
					/*$dadespreguntagens[] = $totals[0];
					$dadespreguntapoc[] = $totals[1];
					$dadespreguntasuficient[] = $totals[2];
					$dadespreguntabastant[] = $totals[3];
					$dadespreguntamolt[] = $totals[4];*/
				}
				if ($pregunta->getTipus() == "BOOL") {
					/* Totals per resposta de cada pregunta */
					$totals = $enquesta->getTotalPreguntaBool($pregunta);
					$dadespreguntasi[] = $totals[0];
					$dadespreguntano[] = $totals[1];
				}
				
				$mesures[] = $enquesta->getDatainici()->format('d-m-Y');
			}
		}
		
		if ($pregunta->getTipus() == "RANG") {
			$valors = array(array("label" => "gens"), array("label" => "poc"), array("label" => "suficient"),
					array("label" => "bastant"), array("label" => "molt"));  /* Cada serie */
					
			$dades[] = $dadespreguntagens;
			$dades[] = $dadespreguntapoc;
			$dades[] = $dadespreguntasuficient;
			$dades[] = $dadespreguntabastant;
			$dades[] = $dadespreguntamolt;
		}
		if ($pregunta->getTipus() == "BOOL") {
			$valors = array(array("label" => "Si"), array("label" => "No"));  /* Cada serie */
			$dades[] = $dadespreguntasi;
			$dades[] = $dadespreguntano;
		}
		
		return $this->render('FecdasPartesBundle:Enquestes:estadistiquesTab2.html.twig',
				array('preguntes' => $preguntes, 'pselected' => $preguntaid, 
						'valors' => json_encode($valors), 'dades' => json_encode($dades), 'mesures' => json_encode($mesures)));
	}
	
}
