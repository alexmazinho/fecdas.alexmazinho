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

			/*
			 * 
			 * Validacions de dates, que no solapin
			 * 
			 * $error = "..."
			 * 
			 * */
			
			$form = $this->createForm(new FormEnquesta(), $enquesta);
			
			$form->bindRequest($request);
			
			$actiontext = (is_null($enquesta->getId()))?'NEW ENQUESTA OK':'UPD ENQUESTA OK';
			
			if ($form->isValid() && $error == "") {
				// Esborrar totes les preguntes i tornar-les a afegir
				
				if ($enquesta->getId() == null) $em->persist($enquesta);
				
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
		
		if ($this->isAuthenticated() != true or $this->get('session')->has('enquestapendent') != true) return "error";
		
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
				echo $preguntaId;
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
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
		
		return $this->render('FecdasPartesBundle:Enquestes:estadistiques.html.twig',
			array('admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
					'busseig' => $this->isCurrentBusseig()));
	}
	
	public function estadistiquesTab1Action() {
		/* AJAX. Evolució de la mitjana de totes les respostes */
		//return new Response("hola tab1");
		
		$enunciats = array();
		$dades = array();

		if ($this->isCurrentAdmin() != true) return new Response("La sessió ha expirat");
		
		/* Cada serie dades una pregunta */
		/* Serie 1 */
		$dadespregunta = array();
		$avgpreguntaenquesta = array(); 
		$avgpreguntaenquesta[] = "Enquesta 1";
		$avgpreguntaenquesta[] = 2.5;
		$dadespregunta[] =$avgpreguntaenquesta;
		
		$avgpreguntaenquesta = array();
		$avgpreguntaenquesta[] = "Enquesta 2";
		$avgpreguntaenquesta[] = 2.7;
		$dadespregunta[] =$avgpreguntaenquesta;
		
		$avgpreguntaenquesta = array();
		$avgpreguntaenquesta[] = "Enquesta 3";
		$avgpreguntaenquesta[] = 3;
		$dadespregunta[] =$avgpreguntaenquesta;
		
		$avgpreguntaenquesta = array();
		$avgpreguntaenquesta[] = "Enquesta 4";
		$avgpreguntaenquesta[] = 3.2;
		$dadespregunta[] =$avgpreguntaenquesta;
		
		$enunciats[] = array("label" => "enunciat 1 enunciat 1 enunciat 1 enunciat 1");
		//$enunciats .= "[{label: 'enunciat 1 enunciat 1 enunciat 1 enunciat 1'}";
		$dades[] = $dadespregunta;
		
		
		/* Serie 2 */
		$dadespregunta = array();
		$avgpreguntaenquesta = array();
		$avgpreguntaenquesta[] = "Enquesta 1";
		$avgpreguntaenquesta[] = 1.5;
		$dadespregunta[] =$avgpreguntaenquesta;
		
		$avgpreguntaenquesta = array();
		$avgpreguntaenquesta[] = "Enquesta 2";
		$avgpreguntaenquesta[] = 1.7;
		$dadespregunta[] =$avgpreguntaenquesta;
		
		$avgpreguntaenquesta = array();
		$avgpreguntaenquesta[] = "Enquesta 3";
		$avgpreguntaenquesta[] = 2.1;
		$dadespregunta[] =$avgpreguntaenquesta;
		
		$avgpreguntaenquesta = array();
		$avgpreguntaenquesta[] = "Enquesta 4";
		$avgpreguntaenquesta[] = 2.7;
		$dadespregunta[] =$avgpreguntaenquesta;
		
		$enunciats[] = array("label" => "enunciat 2 enunciat 2 enunciat 2 enunciat 2");
		//$enunciats .= ",{label: 'enunciat 2 enunciat 2 enunciat 2 enunciat 2'}]";
		$dades[] = $dadespregunta;
		
		
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
		$strQuery .= " ORDER BY p.id";
			
		$query = $em->createQuery($strQuery);
		
		$preguntes = $query->getResult();
		
		//if ($request->query->has('datainici'))
		//if ($request->query->has('datafi')) 
		
		/* Seleccionar enquestes entre dues dates */		
		$strQuery = "SELECT e FROM Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta e";
		$strQuery .= " ORDER BY e.datainici";
		$query = $em->createQuery($strQuery);
			
		$enquestes = $query->getResult();		
		
		/* Pregunta per mostrar resultats */
		$preguntaid = 1;
		if ($request->query->has('preguntaid')) $preguntaid = $request->query->get('preguntaid');
		
		
		
		
		$valors = array();  /* Cada serie */
		$dades = array();
		$mesures = array("Enquesta 1","Enquesta 2","Enquesta 3","Enquesta 4"); /* Eix x*/
		
		
		/* Cada serie acumulat d'un valor del rang d'una pregunta */
		/* Serie valor 1 */
		$dadespregunta = array();
		$dadespregunta[] = 15;
		$dadespregunta[] = 12;
		$dadespregunta[] = 13;
		$dadespregunta[] = 7;
		
		/*
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 15;
		$sumpreguntaenquesta[] = "Enquesta 1";
		$dadespregunta[] =$sumpreguntaenquesta;
		
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 12;
		$sumpreguntaenquesta[] = "Enquesta 2";
		$dadespregunta[] =$sumpreguntaenquesta;
		
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 13;
		$sumpreguntaenquesta[] = "Enquesta 3";
		$dadespregunta[] =$sumpreguntaenquesta;
		
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 7;
		$sumpreguntaenquesta[] = "Enquesta 4";
		$dadespregunta[] =$sumpreguntaenquesta;
		*/
		
		$valors[] = array("label" => "gens");
		$dades[] = $dadespregunta;
		
		/* Serie valor 2 */
		$dadespregunta = array();
		$dadespregunta[] = 10;
		$dadespregunta[] = 20;
		$dadespregunta[] = 23;
		$dadespregunta[] = 26;
		/*
		$dadespregunta = array();
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 10;
		$sumpreguntaenquesta[] = "Enquesta 1";
		$dadespregunta[] =$sumpreguntaenquesta;
		
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 20;
		$sumpreguntaenquesta[] = "Enquesta 2";
		$dadespregunta[] =$sumpreguntaenquesta;
		
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 23;
		$sumpreguntaenquesta[] = "Enquesta 3";
		$dadespregunta[] =$sumpreguntaenquesta;
		
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 26;
		$sumpreguntaenquesta[] = "Enquesta 4";
		$dadespregunta[] =$sumpreguntaenquesta;
		*/
		$valors[] = array("label" => "poc");
		$dades[] = $dadespregunta;
		
		/* Serie valor 3 */
		$dadespregunta = array();
		$dadespregunta[] = 12;
		$dadespregunta[] = 17;
		$dadespregunta[] = 25;
		$dadespregunta[] = 18;
		/*
		$dadespregunta = array();
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 12;
		$sumpreguntaenquesta[] = "Enquesta 1";
		$dadespregunta[] =$sumpreguntaenquesta;
		
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 17;
		$sumpreguntaenquesta[] = "Enquesta 2";
		$dadespregunta[] =$sumpreguntaenquesta;
		
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 25;
		$sumpreguntaenquesta[] = "Enquesta 3";
		$dadespregunta[] =$sumpreguntaenquesta;
		
		$sumpreguntaenquesta = array();
		$sumpreguntaenquesta[] = 18;
		$sumpreguntaenquesta[] = "Enquesta 4";
		$dadespregunta[] =$sumpreguntaenquesta;*/
		
		$valors[] = array("label" => "suficient");
		$dades[] = $dadespregunta;
		
		return $this->render('FecdasPartesBundle:Enquestes:estadistiquesTab2.html.twig',
				array('preguntes' => $preguntes, 'valors' => json_encode($valors), 'dades' => json_encode($dades), 'mesures' => json_encode($mesures)));
	}
}
