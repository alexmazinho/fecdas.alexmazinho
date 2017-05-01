<?php 
namespace FecdasBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FecdasBundle\Entity\Enquestes\EntityEnquesta;
use FecdasBundle\Entity\Enquestes\EntityEnquestaPregunta;
use FecdasBundle\Entity\Enquestes\EntityPregunta;
use FecdasBundle\Entity\Enquestes\EntityRealitzacio;
use FecdasBundle\Entity\Enquestes\EntityResposta;
use FecdasBundle\Form\Enquestes\FormEnquesta;

class EnquestesController extends BaseController {
	public function enquestesAction(Request $request) {
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		//$this->get('session')->getFlashBag()->clear();
		
		$em = $this->getDoctrine()->getManager();
	
		$this->logEntryAuth('VIEW ENQUESTES');
	
		// Get all enquestes
		$strQuery = "SELECT e FROM FecdasBundle\Entity\Enquestes\EntityEnquesta e";
		$strQuery .= " ORDER BY e.dataalta DESC";
		$query = $em->createQuery($strQuery);
			
		$enquestes = $query->getResult();
	
		$strQuery = "SELECT COUNT(u.user) FROM FecdasBundle\Entity\EntityUser u JOIN u.club c ";
		$strQuery .= " WHERE u.databaixa IS NULL ";
		$strQuery .= " AND u.role = 'user' ";
		$strQuery .= " AND c.activat = TRUE ";
		$query = $em->createQuery($strQuery);
		
		$poblacio = $query->getSingleScalarResult();
		
		return $this->render('FecdasBundle:Enquestes:enquestes.html.twig',
				$this->getCommonRenderArrayOptions(array('enquestes' => $enquestes, 'poblacio' => $poblacio)));
	}
	
	public function tancarenquestaAction(Request $request) {
		
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		if ($request->query->has('id')) {
			$enquesta = $this->getDoctrine()->getRepository('FecdasBundle:Enquestes\EntityEnquesta')->find($request->query->get('id'));
			
			if ($enquesta == null or $enquesta->estaTancada()) {
				$this->get('session')->getFlashBag()->add('error-notice', "La enquesta ja està tancada" );
				
				$this->logEntryAuth('TANCAR ENQUESTA ERROR', "enquesta tancada " . $request->query->get('id'));
			} else { 
				$em = $this->getDoctrine()->getManager();
				
				$enquesta->setDatafinal($this->getCurrentDate('now'));

				$em->flush();
				
				$this->logEntryAuth('TANCAR ENQUESTA', "enquesta " . $enquesta->getId());
				
				$this->get('session')->getFlashBag()->add('error-notice', "La enquesta tancada correctament a data d'avui" );
			}
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', "La enquesta ja està tancada" );
			
			$this->logEntryAuth('TANCAR ENQUESTA ERROR', "enquesta " . $enquesta->getId());
		}
		
		$response = $this->forward('FecdasBundle:Enquestes:enquestes');
		return $response;
	}
	
	
	public function enquestaAction(Request $request) {
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = "SELECT p FROM FecdasBundle\Entity\Enquestes\EntityPregunta p";
		$strQuery .= " ORDER BY p.id";
			
		$query = $em->createQuery($strQuery);
		
		$preguntestotes = $query->getResult();
		$preguntesno = array();
		
		$enquesta = null;
		if ($request->getMethod() == 'POST') {
			$error = "";
			if ($request->request->has('enquesta')) {
				$paramenquesta = $request->request->get('enquesta');
				if ($paramenquesta['id'] != "") {
					$enquesta = $this->getDoctrine()->getRepository('FecdasBundle:Enquestes\EntityEnquesta')->find($paramenquesta['id']);
				} else {
					$enquesta = new EntityEnquesta($this->getCurrentDate('now'));
				}
			}
			
			$form = $this->createForm(new FormEnquesta(), $enquesta);
			
			$form->bind($request);

			$actiontext = (is_null($enquesta->getId()))?'NEW ENQUESTA OK':'UPD ENQUESTA OK';

			
			/* Seleccionar enquestes entre dues dates */
			$strQuery = "SELECT e FROM FecdasBundle\Entity\Enquestes\EntityEnquesta e";
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
			
			if ($error == "" and $enquesta->getId() != null and count($enquesta->getRealitzacions()) > 0) 
				$error = "No es pot modificar la enquesta, els usuaris ja han començat a respondre"; 
			
			if ($form->isValid() && $error == "") {
				if ($enquesta->getId() == null) $em->persist($enquesta);
				
				// Esborrar totes les preguntes i tornar-les a afegir
				if ($request->request->has('preguntes')) {
					parse_str($request->request->get('preguntes'));  // Parse array $pregunta
					// Esborrar primer totes les preguntes  array clear() no funciona
					
					foreach ($enquesta->getPreguntes() as $c => $preguntacheck) {
						//$enquesta->removeEntityEnquestaPregunta($preguntacheck);
						$em->remove($preguntacheck);  // e_enquestes_preguntes USER necessita delete 
					}
					$enquesta->clearPreguntes();
					
					foreach ($preguntestotes as $c => $preguntacheck) {
						$pos = array_search($preguntacheck->getId(), $pregunta);
						$pos++;  // Ordenades comencen per 1
						
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
				$this->logEntryAuth((is_null($enquesta->getId()))?'NEW ENQUESTA KO':'UPD ENQUESTA KO', $enquesta->getId() . "-". $error);
				
				$this->get('session')->getFlashBag()->add('error-notice', $error );
				
				return new Response("error", Response::HTTP_INTERNAL_SERVER_ERROR);
			} else {
				$this->logEntryAuth((is_null($enquesta->getId()))?'NEW ENQUESTA OK':'UPD ENQUESTA OK', $enquesta->getId() . "-". $error);
				
				$this->get('session')->getFlashBag()->add('error-notice', "Enquesta actualitzada correctament" );
				
				return new Response("ok");
			}
		} else {
			// Get
			if ($request->query->has('id')) {
				$enquesta = $this->getDoctrine()->getRepository('FecdasBundle:Enquestes\EntityEnquesta')->find($request->query->get('id'));

				if (count($enquesta->getRealitzacions()) > 0) {
					// No es pot modificar l'enquesta, s'ha començat a contestar 
					$this->get('session')->getFlashBag()->add('error-notice', "No es pot modificar la enquesta, els usuaris ja han començat a respondre");
					$response = $this->forward('FecdasBundle:Enquestes:enquestes');
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
				$enquesta->setDatainici($this->getCurrentDate('now'));
				$enquesta->setDatafinal($this->getCurrentDate('now')->add(new \DateInterval('P1M')));
				
				$desc = "Hola !\n";
				$desc .= "Un cop el sistema de gestió de federatives en línia ha superat el seu primer any de vida";
				$desc .= ", us demanem que dediqueu uns moments a respondre un qüestionari de qualitat que permeti millorar el servei que se us ofereix.\n";
				$desc .= "Gràcies per la vostra col·laboració !";
				
				$enquesta->setDescripcio($desc);
			}
			
			$form = $this->createForm(new FormEnquesta(), $enquesta);
			
			$this->logEntryAuth('VIEW ENQUESTA', $enquesta->getId());
		}
		
		return $this->render('FecdasBundle:Enquestes:enquesta.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'preguntessi' => $preguntestotes, 'preguntesno' => $preguntesno)));
	}
	
	public function enquestausuariAction(Request $request) {
		
		//if ($this->isAuthenticated() != true or $this->get('session')->has('enquestapendent') != true) return new Response("error");
		if ($this->isAuthenticated() != true) return new Response("error");
		
		$action = "";
		if ($request->getMethod() == 'POST') {
			$formenquesta = $request->request->get('form');
			
			$enquesta = $this->getDoctrine()->getRepository('FecdasBundle:Enquestes\EntityEnquesta')->find($formenquesta['id']);

			$em = $this->getDoctrine()->getManager();

			// Comprovar si s'ha començat l'enquesta
			$realitzacio = $enquesta->getRealitzada($this->get('session')->get('username'));
			if ($realitzacio == null) {
				$usuari = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($this->get('session')->get('username'));
				$realitzacio = new EntityRealitzacio($usuari, $enquesta);
				$enquesta->addEntityRealitzacio($realitzacio);
				$em->persist($realitzacio);
			}
			
			// No mirar id ni _token, només respostes
			unset($formenquesta['_token']);
			unset($formenquesta['id']);
			
			$a_keys = array_keys($formenquesta);
			$respSerial = $enquesta->getId() . "-" . $this->get('session')->get('username') . ":";
			foreach($a_keys as $preguntakey) {
				$dades_array = explode("_", $preguntakey);
				$preguntaId = $dades_array[1];
				
				$respostaValor =  $formenquesta[$preguntakey];
				
				$respSerial .= $preguntaId . "_" . $respostaValor . ", ";
				
				$pregunta = $this->getDoctrine()->getRepository('FecdasBundle:Enquestes\EntityPregunta')->find($preguntaId);
				
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
						if (trim($respostaValor) != "") $resposta->setRespostatxt($respostaValor);
						else $resposta->setRespostatxt(null);
						//else $realitzacio->removeEntityResposta($resposta); // No cal, sense permisos
						break;
				}
			}
			$realitzacio->setDatadarreraccess($this->getCurrentDate());
			if ($request->request->get('submitaction') == "final") {
				$respSerial = "(fin) " . $respSerial;
				$realitzacio->setDatafinal($this->getCurrentDate());
				$this->get('session')->remove('enquestapendent');
			}
			else $realitzacio->setDatafinal(null);
				
			$em->flush();
			
			$this->logEntryAuth('SAVE ENQUESTA USUARI', $respSerial);
				
			return new Response("Enquesta desada correctament!");
		} else {
			//Get
			$id = 0;
			if ($request->query->has('id')) {
				$id = $request->query->get('id'); // Demana administrador per revisar
				$action = "preview";
			} else {
				if ($this->get('session')->has('enquestapendent')) $id = $this->get('session')->get('enquestapendent'); 
				else {
					if ($this->get('session')->has('enquesta')) $id = $this->get('session')->get('enquesta');
				}
				
			}
			$enquesta = $this->getDoctrine()->getRepository('FecdasBundle:Enquestes\EntityEnquesta')->find($id);

			if ($enquesta == null) {
				$this->logEntryAuth('ERROR ENQUESTA USUARI', $id);
				return new Response("error");
			}
			$this->logEntryAuth('VIEW ENQUESTA USUARI', $id);
		}
		
		$form = $this->createEnquestaForm($enquesta);
		
		return $this->render('FecdasBundle:Enquestes:enquestausuari.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'enquesta' => $enquesta, 'action' => $action)));
	}
	
	private function createEnquestaForm($enquesta) {
		$formBuilder = $this->createFormBuilder();
		$formBuilder->add('id', 'hidden', array('data' => $enquesta->getId()));
		
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
							'placeholder' => false,
							'data' 		=> $dades,
							'label' => $epregunta->getOrdre().". ".$pregunta->getEnunciat(),
							'label_attr'   =>  array( 'class'   => 'enquesta-enunciat'),
							'attr'   =>  array(	'class'   => 'enquesta-resposta')
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
							'placeholder' => false,
							'data' 		=> $dades,
							'label' => $epregunta->getOrdre().". ".$pregunta->getEnunciat(),
							'label_attr'   =>  array( 'class'   => 'enquesta-enunciat'),
							'attr'   =>  array(	'class'   => 'enquesta-resposta')
					));
					break;
				case "OPEN":
					if ($resposta != null) $dades =   $resposta->getRespostatxt();
					$formBuilder->add($fieldname, 'textarea', array(
							'label' => $epregunta->getOrdre().". ".$pregunta->getEnunciat(),
							'required'  => false, 'data' 		=> $dades,
							'label_attr'   =>  array( 'class'   => 'enquesta-enunciat-textarea'),
							'attr'   =>  array(	'class'   => 'enquesta-resposta-textarea')
					));
					break;
			}
			
		}
		return $formBuilder->getForm();
	}
	
	
	public function enquestaresultatsAction(Request $request) {
		/* Genera les dades de les respostes d'una enquesta */
		
		if ($request->query->has('id')) {
			$id = $request->query->get('id'); // Demana administrador per revisar
			
			$enquesta = $this->getDoctrine()->getRepository('FecdasBundle:Enquestes\EntityEnquesta')->find($id);
			
			if ($enquesta == null) return new Response("No s'ha trobat dades per a l'enquesta");
			
			$respostes = $enquesta->getResultats();

			$this->logEntryAuth('VIEW RESULTATS ENQUESTA', $id);
				
			return $this->render('FecdasBundle:Enquestes:enquestaresultats.html.twig',
					array('respostes' => $respostes));
		}
		
		return new Response("No s'ha trobat dades");
	}

	public function estadistiquesAction(Request $request) {
		
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		
		$this->logEntryAuth('VIEW ESTATS. ENQUESTES');
		
		/* Tab / pregunta per mostrar */
		$tab = 0;
		$preguntaid = 1;
		if ($request->query->has('tab')) $tab = $request->query->get('tab');
		if ($request->query->has('preguntaid')) $preguntaid = $request->query->get('preguntaid');
		
		
		return $this->render('FecdasBundle:Enquestes:estadistiques.html.twig',
				$this->getCommonRenderArrayOptions(array('tab' => $tab,  'pselected' => $preguntaid)));
	}
	
	public function estadistiquesTab1Action(Request $request) {
		/* AJAX. Evolució de la mitjana de totes les respostes */
		//return new Response("hola tab1");
		
		$enunciats = array();
		$dades = array();

		if ($this->isCurrentAdmin() != true) return new Response("La sessió ha expirat");
		
		$em = $this->getDoctrine()->getManager();
		
		/* Obtenir totes les preguntes */
		$strQuery = "SELECT p FROM FecdasBundle\Entity\Enquestes\EntityPregunta p";
		$strQuery .= " WHERE p.tipus <> 'OPEN' ORDER BY p.id";
			
		$query = $em->createQuery($strQuery);
		
		$preguntes = $query->getResult();
		
		/* Obtenir totes les enquestes */
		$strQuery = "SELECT e FROM FecdasBundle\Entity\Enquestes\EntityEnquesta e";
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
		
		return $this->render('FecdasBundle:Enquestes:estadistiquesTab1.html.twig',
				array('enunciats' => json_encode($enunciats), 'dades' => json_encode($dades)));
	}
	
	
	public function estadistiquesTab2Action(Request $request) {  
		/* AJAX. Evolució d'una pregunta tipus RANG o BOOL al llarg de les diferents enquestes (temps) */
		
		if ($this->isCurrentAdmin() != true) return new Response("La sessió ha expirat");
		
		$em = $this->getDoctrine()->getManager();
		
		
		
		/* Obtenir totes les preguntes */
		$strQuery = "SELECT p FROM FecdasBundle\Entity\Enquestes\EntityPregunta p";
		$strQuery .= " WHERE p.tipus <> 'OPEN' ORDER BY p.id";
			
		$query = $em->createQuery($strQuery);
		
		$preguntes = $query->getResult();
		
		/* Seleccionar enquestes entre dues dates */		
		$strQuery = "SELECT e FROM FecdasBundle\Entity\Enquestes\EntityEnquesta e";
		$strQuery .= " ORDER BY e.datainici";
		$query = $em->createQuery($strQuery);
		
		$enquestes = $query->getResult();		
		
		/* Pregunta per mostrar resultats */
		$preguntaid = 1;
		if ($request->query->has('preguntaid')) $preguntaid = $request->query->get('preguntaid');
		$pregunta = $this->getDoctrine()->getRepository('FecdasBundle:Enquestes\EntityPregunta')->find($preguntaid);
		
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
					/*error_log($enquesta->getId() . " - " . $pregunta->getId(), 0);*/
					$totals = $enquesta->getTotalPreguntaRang($pregunta);
					$totalRespostes = $totals[0]+$totals[1]+$totals[2]+$totals[3]+$totals[4];
					/*error_log($enquesta->getId() . " - " . $pregunta->getId() . " fi : " . $totalRespostes 
							. "(" . $totals[0]." ".$totals[1]." ".$totals[2]." ".$totals[3]." ".$totals[4] .")" , 0);*/
					/*$dadespreguntagens[] = ($totalRespostes == 0)?0:($totals[0]/$totalRespostes)*100;
					$dadespreguntapoc[] = ($totalRespostes == 0)?0:($totals[1]/$totalRespostes)*100;
					$dadespreguntasuficient[] = ($totalRespostes == 0)?0:($totals[2]/$totalRespostes)*100;
					$dadespreguntabastant[] = ($totalRespostes == 0)?0:($totals[3]/$totalRespostes)*100;
					$dadespreguntamolt[] = ($totalRespostes == 0)?0:($totals[4]/$totalRespostes)*100;*/
					$dadespreguntagens[] = $totals[0];
					$dadespreguntapoc[] = $totals[1];
					$dadespreguntasuficient[] = $totals[2];
					$dadespreguntabastant[] = $totals[3];
					$dadespreguntamolt[] = $totals[4];
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
		
		return $this->render('FecdasBundle:Enquestes:estadistiquesTab2.html.twig',
				array('preguntes' => $preguntes, 'pselected' => $preguntaid, 
						'valors' => json_encode($valors), 'dades' => json_encode($dades), 'mesures' => json_encode($mesures)));
	}
	
}
