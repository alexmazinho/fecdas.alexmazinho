<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\FormError;

use FecdasBundle\Form\FormCurs;
use FecdasBundle\Entity\EntityCurs;
use FecdasBundle\Entity\EntityDocencia;
use FecdasBundle\Entity\EntityStock;
use FecdasBundle\Entity\EntityTitulacio;

use FecdasBundle\Entity\EntityPersona;


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
			return $this->exportDadespersonals($request, $query->getResult(), $desde, $fins);
		}
		
		if ($format == 'pdf') {
			// Generar PDF
			$print = $request->query->has('print') && $request->query->get('print') == true?true:false;
			
			return $this->forward('FecdasBundle:PDF:dadespersonalstopdf', array(
							        'persones'       => $query->getResult(),
							        'print' 		=> $print,
							        'desde'			=> $desde,
							        'fins'			=> $fins,
							        'vigents'		=> $currentVigent,
							        'dni'			=> $currentDNI,
							        'nom'			=> $currentNom,
							        'cognoms'		=> $currentCognoms
		    ));
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
		$formBuilder->add('desde', 'text', array('required'  => false, 'data' => ($desde != null?$desde->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--', 'readonly' => false)));
		$formBuilder->add('fins', 'text', array('required'  => false, 'data' => ($fins != null?$fins->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--', 'readonly' => false)));

		$this->addClubsActiusForm($formBuilder, $club);
		
		$this->addTitolsFilterForm($formBuilder, $titol, true, 'titols');
		
		$this->addTitolsFilterForm($formBuilder, $titolExtern, false, 'titolsexterns');
		
		$form = $formBuilder->getForm(); 
	
		return $this->render('FecdasBundle:Titulacions:dadespersonals.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'persones' => $persones, 
						'sortparams' => array('sort' => $sort,'direction' => $direction)) 
						));

	}
	
	private function exportDadespersonals($request, $persones, $desde, $fins) {
		/* CSV Llistat de dades personals filtrades */
		$filename = "export_dadespersonals_".BaseController::getInfoTempsNomFitxer($desde, $fins).".csv";
			
		$header = EntityPersona::csvHeader( $this->isCurrentAdmin(), BaseController::getInfoTempsNomFitxer($desde, $fins, " ", "/") );
			
		$data = array(); // Get only data matrix
		$i = 1;
		
		foreach ($persones as $persona) {
			$data[] = $persona->csvRow($i, $this->isCurrentAdmin(), $desde != null?$desde->format("Y-m-d"):'', $fins != null?$fins->format("Y-m-d"):'');
			$i ++; 
		}
			
		$response = $this->exportCSV($request, $header, $data, $filename);
		return $response;
	}
	

	public function historialllicenciesAction(Request $request) {
		
		if ($this->isAuthenticated() != true) return new Response("");

		if (!$request->query->has('id')) return new Response("");
		
		//$em = $this->getDoctrine()->getManager();
				
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
			 * SELECT e.dni, COUNT(DISTINCT p.clubparte) FROM m_partes p 
			 * INNER JOIN m_llicencies l ON p.id = l.parte 
			 * INNER JOIN m_persones e ON l.persona = e.id 
			 * GROUP BY e.dni HAVING COUNT(DISTINCT p.clubparte) > 1
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
			return ($a->getParte()->getDatacaducitat() > $b->getParte()->getDatacaducitat())? -1:1;;
		});

		    return $this->render('FecdasBundle:Titulacions:llicencieshistorial.html.twig', array('llicencies' => $llicencies, 'persona' => $persona, 'admin' => $this->isCurrentAdmin()));
		
	}

	public function historialtitulacionsAction(Request $request) {
		
		if ($this->isAuthenticated() != true) return new Response("");

		if (!$request->query->has('id')) return new Response("");
		
		//$em = $this->getDoctrine()->getManager();
				
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
				    'club' => $this->getCurrentClub(), 'persona' => $persona, 'admin' => $this->isCurrentAdmin()));
		
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
		$formBuilder->add('desde', 'text', array('required'  => false, 'data' => ($desde != null?$desde->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--', 'readonly' => false)));
		$formBuilder->add('fins', 'text', array('required'  => false, 'data' => ($fins != null?$fins->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--', 'readonly' => false)));
		if ($this->isCurrentAdmin()) $this->addClubsActiusForm($formBuilder, $club);
		
		$this->addTitolsFilterForm($formBuilder, $titol, true, 'titols');
		
		return $this->render('FecdasBundle:Titulacions:cursos.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'cursos' => $cursos, 'sortparams' => array('sort' => $sort,'direction' => $direction))
				));
	}

	public function cursAction(Request $request) {
		// Consulta/edicio curs 

		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

    	$em = $this->getDoctrine()->getManager();
    	
    	$curs = null;
		/*$auxdirector = null;
		$auxcarnet = null;
		$auxcodirector = null;
		$auxcocarnet = null;
		$participants = null;
		$instructors = null;
		$collaboradors = null;*/
		$action = '';
		$titol = null;
		$kit = null;
		$stock = '';  // stock desconegut
		$requeriments = array( 'titol' => '', 'errors' => array( 'total' => 0 ));
		
		//$participantscurrent = null;

		$checkRole = $this->get('fecdas.rolechecker');
    	
		$club = $this->getCurrentClub();
		
    	if ($request->getMethod() != 'POST') {
    		$id = $request->query->get('id', 0);
			$action = $request->query->get('action', '');
			
			if ($id == 0 && !$checkRole->isCurrentInstructor()) {
				$this->get('session')->getFlashBag()->add('error-notice', 'Només els instructors poden crear un nou curs');
				return $this->redirect($this->generateUrl('FecdasBundle_cursos'));
			}
    	} else {
   			/* Alta o modificació de preus */
    		$data = $request->request->get('curs');
    		$id = (isset($data['id'])?$data['id']:0);
			$action = (isset($data['action'])?$data['action']:'');
    	}
		if ($id > 0) $curs = $this->getDoctrine()->getRepository('FecdasBundle:EntityCurs')->find($id);
    		 
    	if ($curs == null) {
    		$this->logEntryAuth('CURS NOU',	($request->getMethod() != 'POST'?'GET':'POST').' action: '.$action);
    	
    		$curs = new EntityCurs($checkRole->getCurrentUser(), null, new \DateTime(), new \DateTime(), $club);
			$em->persist($curs);
			
			$this->novaDocenciaCurs($checkRole->getCurrentUser()->getMetapersona(), array(), $curs, BaseController::DOCENT_DIRECTOR);
			
    	} else {
	    	$this->logEntryAuth($request->getMethod() != 'POST'?'CURS VIEW':'CURS EDIT', ($request->getMethod() != 'POST'?'GET':'POST').' curs : ' . $curs->getId().' '.$curs->getTitol().' '.$curs->getClubInfo());

			if (!$curs->finalitzat()) {			
				$titol = $curs->getTitol();
				if ($titol != null && $titol->getKit() != null) $kit = $titol->getKit();
				
				if ($kit != null) {
					$registrestock = $this->consultaStockProducte($kit->getId(), $club);
					if ($registrestock == null) $stock = 0;  // sense stock
					else $stock = $registrestock->getStock();	// stock disponible 
				}
			}
    	}
		
		if ($request->getMethod() == 'POST') {
			$this->initDadesPostCurs($request->request->get('curs'), $curs);
		}
			
    	$form = $this->createForm(new FormCurs( array('editor' => $curs->getEditor() === $checkRole->getCurrentUser() || $this->isCurrentAdmin(), 'stock' => $stock )), $curs);
    	try {
    		if ($request->getMethod() == 'POST') {
    		    throw new \Exception('Operació pendent. No es poden desar les dades del curs');
    		    
			 	$form->handleRequest($request);
			 	if (!$form->isValid()) throw new \Exception('Dades del formulari incorrectes '.$form->getErrors(true, true) );
				
				// Comprovacions genèriques
				$this->validacionsCurs($curs, $stock, $form, $action);
				
				// Fotos i arxius
				foreach ($form->get('participants') as $formparticipant) {
					$idMeta = $formparticipant->get('metapersona')->getData();
					$fotoPath = $formparticipant->get('foto')->getData();
					$certificatPath = $formparticipant->get('certificat')->getData();
					$foto = $formparticipant->get('fotoupld')->getData();
					$certificat = $formparticipant->get('certificatupld')->getData();
					
					$persona = null;
					foreach ($curs->getParticipantsSortedByCognomsNom() as $participant) {
						$metapersona = $participant->getMetapersona();
						if ($metapersona->getId() == $idMeta) {
							$persona = $metapersona->getPersona($curs->getClub());
							break;
						} 
					}
					if ($persona == null) throw new \Exception('Alumne no trobat '.$idMeta);

					$this->gestionarArxiusPersona($persona, $fotoPath, $certificatPath, $foto, $certificat);
					
				}

				$this->accionsPostValidacions($curs, $action);
	    		
				$curs->setDatamodificacio(new \DateTime('now'));
				
	    		$em->flush();
	    		
    			$this->get('session')->getFlashBag()->add('sms-notice',	'Canvis desats correctament');
    			
    			return $this->redirect($this->generateUrl('FecdasBundle_curs', 
    					array( 'id' => $curs->getId() )));
    		} else {
	   			
				if ($action == 'remove') {
				 	if (!$this->isCurrentAdmin())	throw new \Exception('Només els administradors poden esborrar el curs' );
						
				 	if ($curs->esNou())  throw new \Exception('No s\'ha pogut esborrar el curs' );
					
					
					/// !!!!!! PENDENT !!!! 
				
				}
	   		}
		} catch (\Exception $e) {
    		// Ko, mostra form amb errors
    		$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
    	}
		
		if (!$curs->finalitzat()) {	
			// Resultat check Requeriments titulació
			$resultat = $this->comprovaRequerimentsCurs($curs);
	
			// Dades estructurades requeriments
			$requeriments = $this->getRequerimentsEstructuraInforme($curs->getTitol(), $resultat);
		}

		return $this->render('FecdasBundle:Titulacions:curs.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'curs' => $curs, 'requeriments' => $requeriments)));
	}

	private function initDadesPostCurs($data, $curs) {
				
		if (isset($data['auxdirector']) && isset($data['auxdirector']) > 0) $auxdirector = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($data['auxdirector']);
		$auxcarnet = isset($data['auxcarnet'])?$data['auxcocarnet']:'';
			
		if (isset($data['auxcodirector']) && isset($data['auxcodirector']) > 0) $auxcodirector = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($data['auxcodirector']);
		$auxcocarnet = isset($data['auxcocarnet'])?$data['auxcocarnet']:'';
			
			//$participantscurrent = isset($data['participantscurrent'])?explode(";",$data['participantscurrent']):array();
			
			
			//curs_formalumne_fotoupld_0
			//curs_formalumne_certificat_0
			
		$instructors = isset($data['instructors'])?$data['instructors']:array();
		$collaboradors = isset($data['collaboradors'])?$data['collaboradors']:array();;
		$participants = isset($data['participants'])?$data['participants']:array();
		
		$currentDocencia = $curs->getDirector();
		if ($auxdirector != null && $auxdirector->getMetapersona() != null) {
			$meta = $auxdirector->getMetapersona();
			$currentMeta = ($currentDocencia == null?null:$currentDocencia->getMetadocent());
			
			if ($meta !== $currentMeta) {
				if ($currentDocencia != null) $currentDocencia->baixa();
				$this->novaDocenciaCurs($meta, array('carnet' => $auxcarnet), $curs, BaseController::DOCENT_DIRECTOR);
			} 
		} else {
			// Esborrar?
			if ($currentDocencia != null) $currentDocencia->baixa();
		}
			
		$currentDocencia = $curs->getCodirector();
		if ($auxcodirector != null && $auxcodirector->getMetapersona() != null) {
			$meta = $auxcodirector->getMetapersona();
			$currentMeta = ($currentDocencia == null?null:$currentDocencia->getMetadocent());
			
			if ($meta !== $currentMeta) {
				if ($currentDocencia != null) $currentDocencia->baixa();
				
				$this->novaDocenciaCurs($meta, array('carnet' => $auxcocarnet), $curs, BaseController::DOCENT_CODIRECTOR);
			} 
		} else {
			// Esborrar?
			if ($currentDocencia != null) $currentDocencia->baixa(); 
		}
			
		$currentInstructorsIds = $curs->getDocenciesIds(BaseController::DOCENT_INSTRUCTOR);
		foreach ($instructors as $docent) {		// Afegir/treure/modificar instructors
			$index = array_search(isset($docent['id'])?$docent['id']:0, $currentInstructorsIds);
			if ($index !== false) array_splice($currentInstructorsIds, $index, 1);		// Treu els existents de l'array
				
			$this->gestionarDocenciaCurs($docent, $curs, BaseController::DOCENT_INSTRUCTOR);		
		}

		$currentCollaboradorsIds = $curs->getDocenciesIds(BaseController::DOCENT_COLLABORADOR);
		foreach ($collaboradors as $docent) { 	// Afegir/treure/modificar colaboradors
			$index = array_search(isset($docent['id'])?$docent['id']:0, $currentCollaboradorsIds);
			if ($index !== false) array_splice($currentCollaboradorsIds, $index, 1);		// Treu els existents de l'array
			
			$this->gestionarDocenciaCurs($docent, $curs, BaseController::DOCENT_COLLABORADOR);
		}
		
		$docenciesIdsEsborrar = array_merge($currentInstructorsIds, $currentCollaboradorsIds);
		foreach ($docenciesIdsEsborrar as $id) {  // Esborrar
			$docencia = $curs->getDocenciaById($id);
			if ($docencia != null) $docencia->baixa();	
		} 		
			
		$currentParticipantsIds = $curs->getParticipantsIds();
		foreach ($participants as $participant) {	// Afegir/treure/modificar participants
			$index = array_search(isset($participant['id'])?$participant['id']:0, $currentParticipantsIds);
			if ($index !== false) array_splice($currentParticipantsIds, $index, 1);		// Treu els existents de l'array
		
			$this->gestionarParticipacioCurs($participant, $curs);
		}

		foreach ($currentParticipantsIds as $id) {  // Esborrar
			$participant = $curs->getParticipantById($id);
			if ($participant != null) $participant->baixa();
		} 		
		
	} 
	
	
	private function gestionarDocenciaCurs($docent, $curs, $rol) { 
		$meta = isset($docent['metadocent'])?$docent['metadocent']:0;
		$id = isset($docent['id'])?$docent['id']:0;
		
		if ($id != 0) {
			$docencia = $curs->getDocenciaByMetaId($meta);
		
			if ($docencia == null) throw new \Exception('No existeix l\'instructor '.$meta );
			
			$this->updateDocenciaCurs($docent, $docencia);
			$docencia->setRol( $rol ); 
			
		} else {
			$metadocent = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->find($meta);	
			if ($metadocent == null) throw new \Exception('Instructor no trobat '.$meta );
			
			$this->novaDocenciaCurs($metadocent, $docent, $curs, $rol);
		}	
	}
	
	private function updateDocenciaCurs($docent, $docencia) {
		$docencia->setCarnet( isset($docent['carnet'])?$docent['carnet']:'' );
		$docencia->setHteoria( isset($docent['hteoria'])&&is_numeric($docent['hteoria'])?$docent['hteoria']:0 );
		$docencia->setHaula( isset($docent['haula'])&&is_numeric($docent['haula'])?$docent['haula']:0 );
		$docencia->setHpiscina( isset($docent['hpiscina'])&&is_numeric($docent['hpiscina'])?$docent['hpiscina']:0 );
		$docencia->setHmar( isset($docent['hmar'])&&is_numeric($docent['hmar'])?$docent['hmar']:0 );
	}
	
	private function novaDocenciaCurs($metadocent, $docent = array(), $curs, $rol) {
		$em = $this->getDoctrine()->getManager();
		
		$docencia = new EntityDocencia($metadocent, $curs, $rol);	
		$this->updateDocenciaCurs($docent, $docencia); 			

		$em->persist($docencia);
		$curs->addDocencia($docencia);
		return $docencia;
	}

	private function gestionarParticipacioCurs($participant, $curs) { 
		$em = $this->getDoctrine()->getManager();
		$id = isset($participant['metapersona'])?$participant['metapersona']:0;
		
		$titulacio = $curs->getParticipantByMetaId($id);
		if ($titulacio != null) {
			$titulacio->setNum( isset($participant['num'])?$participant['num']:'' );
		} else {
			$metapersona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->find($id);	
			if ($metapersona == null) throw new \Exception('Alumne no trobat '.$id );
						
			$titulacio = new EntityTitulacio($metapersona, $curs);	
			$titulacio->setNum( isset($participant['num'])?$participant['num']:'' );
	
			$em->persist($titulacio);
			$curs->addParticipant($titulacio);
		}
	}

	private function validacionsCurs($curs, $stock, $form, $action) {
		
		$checkRole = $this->get('fecdas.rolechecker');
		
		if ($action == 'save' 	&& !$checkRole->isCurrentInstructor()) throw new \Exception('Només els instructors poden desar les dades del curs');
		if ($action == 'close' 	&& !$checkRole->isCurrentInstructor()) throw new \Exception('Només els instructors poden tancar el curs');
		
		if ($action == 'unclose' 	&& !$checkRole->isCurrentClub()) throw new \Exception('Només els clubs poden tornar a obrir el curs per editar-lo');
		if ($action == 'validate' 	&& !$checkRole->isCurrentClub()) throw new \Exception('Només els clubs poden confirmar la validesa de les dades del curs');
		
		if ($action == 'finalize' 	&& !$checkRole->isCurrentAdmin()) throw new \Exception('Només des de la Federació es pot finalitzar el curs');
		
		// Generals 
		$titol = $curs->getTitol();
		if ($titol == null) {
			$form->get('titol')->addError(new FormError('Obligatori'));
			throw new \Exception('Cal escollir el títol que s\'impartirà en aquest curs');
		}

		$desde = $curs->getDatadesde();
		$fins = $curs->getDatafins();
		if ($desde == null || $fins == null) throw new \Exception('Cal indicar les dates d\'inici i final del curs');  // Per validar llicència tècnic 

		// Validar instructor repetit
		$director = $curs->getDirector();
		$codirector = $curs->getCodirector();
		
		if ($director == null) {
			$form->get('auxdirector')->addError(new FormError('Obligatori'));
			throw new \Exception('Cal indicar un director per al curs');
		} 
		
		
		if ($codirector != null && $codirector->getMetadocent() === $director->getMetadocent()) {
			$form->get('auxcodirector')->addError(new FormError('Duplicat'));
			throw new \Exception('El director i el co-director no poden ser el mateix');
		}
		// Director / Co-director poden ser instructors també?

		$docentIds = array();
		$docenciesInstructors = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR);
		foreach ($docenciesInstructors as $docencia) {
			$meta = $docencia->getMetadocent();
			if (in_array($meta->getId(), $docentIds)) throw new \Exception('L\'instructor '.$meta->getNomCognoms().' està repetit');
			$docentIds[] = $meta->getId();

			$this->validaDocenciaHores($docencia);
			
			// Validació llicència federativa tècnic vigent 
			$finsVariable = clone $fins;
			$llicenciesPersonaPeriode = $meta->getLlicenciesSortedByDate(false, $desde, $fins); /* Ordenades de última a primera */
	
			foreach ($llicenciesPersonaPeriode as $llicencia) {
				// Totes les llicencies haurien d'estar parcialment dins el periode
				if (!$llicencia->esTecnic()) throw new \Exception('L\'instructor '.$meta->getDni().' no té llicència tècnic');
				
				$parte = $llicencia->getParte();
								
				if ($parte->getDatacaducitat()->format('Y-m-d') >= $finsVariable->format('Y-m-d') && 
					$parte->getDataalta()->format('Y-m-d') 		<= $finsVariable->format('Y-m-d')) {
							 			
					$finsVariable = clone $parte->getDataalta();
					$finsVariable->sub(new \DateInterval('P1D')); // Minus 1 Day
				} 
			}

			// Si $fins <= $desde està tot el periode cobert
			if ($finsVariable->format('Y-m-d') > $desde->format('Y-m-d')) throw new \Exception('L\'instructor '.$meta->getDni().' no té llicència tècnic durant tot el periode del curs');
			
		}
		$docenciesCollaboradors = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_COLLABORADOR);
		foreach ($docenciesCollaboradors as $docencia) {
			$meta = $docencia->getMetadocent();
			if (in_array($meta->getId(), $docentIds)) throw new \Exception('El col·laborador '.$meta->getNomCognoms().' està repetit');
			$docentIds[] = $meta->getId();
			
			$this->validaDocenciaHores($docencia);
		}

		// Validar alumne repetit
		$alumnesIds = array();
		$participants = $curs->getParticipantsSortedByCognomsNom();
		foreach ($participants as $participant) {
			$meta = $participant->getMetapersona();
			if (in_array($meta->getId(), $alumnesIds)) throw new \Exception('L\'alumne '.$meta->getNomCognoms().' està repetit');
			$alumnesIds[] = $meta->getId();
			
			if ($action == 'finalize') $this->validaDadesFinalitzacioCurs($participant);
		}
		
		// Valida stock
		if ($titol->esKitNecessari()) {
			$kit = $titol->getKit();
			// El club no té prous kits per tots els alumnes del curs. No es pot validar
			if ($action == 'validate' && count($participants) > $stock) throw new \Exception('El club no disposa de prou kits "'.$kit->getDescripcio().'" per a tots els alumnes. Cal demanar-ne més per poder validar el curs ');
		}
	}

	private function validaDocenciaHores($docencia) {
		if ($docencia == null) return;
		
		$rol = '';
		if ($docencia->getRol() == BaseController::DOCENT_INSTRUCTOR) $rol = 'instructors';
		if ($docencia->getRol() == BaseController::DOCENT_COLLABORADOR) $rol = 'col·laboradors';
		 
		
		if ($docencia->getHteoria() < 0) throw new \Exception('Les hores de teoria dels '.$rol.' no poden ser negatives');
		if ($docencia->getHaula() < 0) throw new \Exception('Les hores de pràctiques fora de l\'aigua dels '.$rol.' no poden ser negatives');
		if ($docencia->getHpiscina() < 0) throw new \Exception('Les hores de pràctiques a aigües confinades dels '.$rol.' no poden ser negatives');
		if ($docencia->getHmar() < 0) throw new \Exception('Les hores de pràctiques a la mar dels '.$rol.' no poden ser negatives');
	}

	private function validaDadesFinalitzacioCurs($participant) {
		if ($participant == null) return;
		
		if ($participant->getNum() == null || trim($participant->getNum()) == '')  throw new \Exception('Falta indicar el número per l\'alumne '.$participant->getMetapersona()->getDni());
	}

	private function accionsPostValidacions($curs, $action) {
		
		$current = new \DateTime('now');
		$club = $curs->getClub();
		$titol = $curs->getTitol();
			
		switch ($action) {
			case 'save':	// Instructor.
				$curs->setEditable(true);
				$curs->setValidat(false);
				$curs->setFinalitzat(false);
				
				break;
	
			case 'close':	// Instructor -> club
			
				$curs->setEditable(false);
				$curs->setValidat(false);
				$curs->setFinalitzat(false);
				
				// Enviar mail al club
				$subject = "Federació Catalana d'Activitats Subaquàtiques. Curs pendent de validació ";
				$tomails = $club->getMail();
				if (count($tomails) == 0) $subject .= ' (Cal avisar aquest club no té adreça de mail al sistema)';
				
				$body = "<p>Benvolgut club ".$club->getNom()."</p>";
				$body .= "<p>Les dades d'un nou curs han estat introduïdes per un dels instructors capacitats a tal efecte, ";
				$body .= "i resta pendent de la teva validació per notificar-lo a la Federació</p>";
				$body .= "<p>Curs: <b>".$curs->getTitol()->getLlistaText()."</b></p>";
				
				$this->buildAndSendMail($subject, $tomails, $body);
						
				break;
	
			case 'unclose':		// club -> instructor 
						
				$curs->setEditable(true);
				$curs->setValidat(false);
				$curs->setFinalitzat(false);
				
				// Enviar mail a l'instructor ¿?	
				break;
					
			case 'validate':	// Club  -> federació
				
				$curs->setEditable(false);		
				$curs->setValidat(true);	
				$curs->setFinalitzat(false);
				
				
				// Registrar sortida de kits
				if ($titol->esKitNecessari()) {
					$kit = $titol->getKit();
					$stockProducte = $this->consultaStockProducte($kit->getId(), $club);
					
					$em = $this->getDoctrine()->getManager();
					
					$unitats = count($curs->getParticipantsSortedByCognomsNom());
					$comentaris = 'Sortida stock '.$unitats.'x'.$kit->getDescripcio().', utilitzats en un curs';
					
					$registreStockClub = new EntityStock($club, $kit, $unitats, $comentaris, new \DateTime('today'), BaseController::REGISTRE_STOCK_SORTIDA);
					
					if ($stockProducte == null) throw new \Exception('Nombre de kits "'.$kit->getDescripcio().'" disponibles insuficient per a tots els alumnes. Cal demanar-ne més per poder validar el curs ');
					
					$registreStockClub->setStock($stockProducte->getStock() - $unitats);
					
					if ($stockProducte->getStock() < 0)  throw new \Exception('No hi ha prou kits "'.$kit->getDescripcio().'" disponibles per a tots els alumnes. Cal demanar-ne més per poder validar el curs ');
					
					$em->persist($registreStockClub);
				}
				
				// Enviar mail a Federació
				$club = $curs->getClub();
				
				$subject = ":: Nou curs validat :: ";
				$tomails = $this->getCarnetsMails(); // Albert 
				$body = "<p>Hola ".$club->getNom()."</p>";
				$body .= "<p>El club ".$club->getNom()." ha validat les dades d'un nou curs en data ".$current->format('d/m/Y');
				$body .= "<p>Curs: <b>".$curs->getTitol()->getLlistaText()."</b></p>";
				
				$this->buildAndSendMail($subject, $tomails, $body);
						
				break;
					
			case 'finalize':	// Federació
				
				$curs->setEditable(false);		
				$curs->setValidat(true);	
				$curs->setFinalitzat(true);
				
				break;
					
			default:
				throw new \Exception('Acció incorrecte '.$action );
						
						
				break;
		}
	}

	private function comprovaRequerimentsCurs($curs) {
		// Generals
		$titol =  $curs->getTitol();
		$resultat = array();  
		//if ($titol == null) throw new \Exception('Cal escollir el títol que s\'impartirà per poder comprovar els requeriments');
		if ($titol == null) return $resultat;
		
		$requeriments = $titol->getRequerimentsSortedByContextCategoria();
		 
		foreach ($requeriments as $requeriment) {
			$tipus = $requeriment->getRequeriment();
			
			if (isset($resultat[$tipus->getId()])) throw new \Exception('Error de configuració a la comprovació dels requeriments'); 
			
			$res = array('result' => 'OK', 'errors' => array());
			switch ($tipus->getCategoria()) {
				case BaseController::CATEGORIA_REQUERIMENT_MIN_HORES:
					
					$res = $this->comprovaRequerimentsMinimHores($requeriment, $curs);
					
					break;

				case BaseController::CATEGORIA_REQUERIMENT_IMMERSIONS:
					// No es pot comprovar
					
					break;
				
				case BaseController::CATEGORIA_REQUERIMENT_RATIOS:
					$res = $this->comprovaRequerimentsRatios($requeriment, $curs);
					
					break;
				
				case BaseController::CATEGORIA_REQUERIMENT_FORMACIO:
					$res = $this->comprovaRequerimentsFormacio($requeriment, $curs);
					
					break;
				
				case BaseController::CATEGORIA_REQUERIMENT_TITULACIONS:
					$res = $this->comprovaRequerimentsTitulacions($requeriment, $curs);
					
					break;
				
				default:
					// Altres validacions categoria NULL
					$res = $this->comprovaRequerimentsAltres($requeriment, $curs);
					
					break;
			}
			
			if ($res['result'] != 'OK') $resultat[$tipus->getId()] = $res;
		}
		return $resultat;
	}

	private function comprovaRequerimentsMinimHores($requeriment, $curs) {
		$tipus = $requeriment->getRequeriment();	
			
		$res = array('result' => 'OK', 'errors' => array());   
	
		switch ($tipus->getId()) {
			case 100:  // teoria.
			case 101:  // aula.
			case 102:  // piscina.
			case 103:  // mar.
			
				$horesMin = $requeriment->getValor();
				
				// Recompte total hores instructors 
				$totals = array(100 => 0, 101 => 0, 102 => 0, 103 => 0);
				foreach ($curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR) as $instructor) {
					$totals[100] += $instructor->getHteoria();
					$totals[101] += $instructor->getHaula();
					$totals[102] += $instructor->getHpiscina();
					$totals[103] += $instructor->getHmar();
				}
				
				if ($totals[$tipus->getId()] <  $horesMin) {
					$res['errors'][] = $requeriment->getText().': El total d\'hores <span>'.$totals[$tipus->getId()].'</span> és inferior al mínim ('.$horesMin.')';
				}
				break;
				
			case 104:	// funcio docent
					
					
				break;	
			case 105:	// Experiència docent (Escola Nacional de Busseig Autònom Esportiu)

					
					
				break;	
				
			default:

				break;
		}
		
		if (count($res['errors']) > 0) $res['result'] = 'KO';
		
		return $res; 
	}

	private function comprovaRequerimentsRatios($requeriment, $curs) {
		$tipus = $requeriment->getRequeriment();	
			
		$res = array('result' => 'OK', 'errors' => array());
		
		$valors = explode(";",$requeriment->getValor());
		$valor0 = isset($valors[0])?$valors[0]:''; // Alumnes 
		$valor1 = isset($valors[1])?$valors[1]:''; // Professors
		$valor2 = isset($valors[2])?$valors[2]:''; // Bussejador seguretat
		
		$alumnes = count($curs->getParticipantsSortedByCognomsNom());
		
		if ($alumnes > 0 && is_numeric($valor0) && $valor0 > 0) {
			// Recompte instructors 
			$profeTeoria = 0;
			$profePiscina = 0;
			$profeMar = 0;
			$profeAula = 0;
			foreach ($curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR) as $instructor) {
				if ($instructor->getHteoria() > 0) $profeTeoria++;
				if ($instructor->getHaula() > 0) $profeAula++;
				if ($instructor->getHpiscina() > 0) $profePiscina++;
				if ($instructor->getHmar() > 0) $profeMar++;
			}
			
			$seguretatTeoria = 0;
			$seguretatPiscina = 0;
			$seguretatMar = 0;
			$seguretatAula = 0;
			if ($valor2 != '' && $valor2 > 0) {
				foreach ($curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_COLLABORADOR) as $instructor) {
					if ($instructor->getHteoria() > 0) $seguretatTeoria++;
					if ($instructor->getHaula() > 0) $seguretatAula++;
					if ($instructor->getHpiscina() > 0) $seguretatPiscina++;
					if ($instructor->getHmar() > 0) $seguretatMar++;
				}
			}
			
			$profes = 0;
			$seguretat = 0;		
			switch ($tipus->getId()) {
				case 150:  // Ratio teoria
					$profes = $profeTeoria;
					$seguretat = $seguretatTeoria;	
					
					break;
				case 151:  // Ratio piscina
					$profes = $profePiscina;
					$seguretat = $seguretatPiscina;	
								
					break;
				case 152:  // Ratio mar
				case 153:  // Ratio mar recomanat
					$profes = $profeMar;
					$seguretat = $seguretatMar;	
								
					break;
				
				case 154:  // Ratio aula
					$profes = $profeAula;
					$seguretat = $seguretatAula;	
									
					break;
				default:
						
					break;
			}
	
			$text = $requeriment->getText();
			$text = substr($text, 0, strpos($text, ":"));
	
			if (($profes/$alumnes) < ($valor1 / $valor0)) $res['errors'][] = $text.'. Ratio professor/alumnes <span>'.$profes.'/'.$alumnes.'</span> inferior al valor requerit '.$valor1.'/'.$valor0; 
			
			if (is_numeric($valor2) && $valor2 > 0) {
				$aux = 'requerit';
				if ($tipus->getId() == 153) $aux = 'recomanat';
				
				if (($seguretat/$alumnes) < ($valor2 / $valor0)) $res['errors'][] = $text.'. Ratio bussejador seguretat/alumnes <span>'.$seguretat.'/'.$alumnes.'</span> inferior al valor '.$aux.' '.$valor2.'/'.$valor0;
			}
			
			if (count($res['errors']) > 0) $res['result'] = 'KO';
		}
		
		return $res;
	}

	private function comprovaRequerimentsFormacio($requeriment, $curs) {
		$tipus = $requeriment->getRequeriment();	
			
		$res = array('result' => 'OK', 'errors' => array());
		
		switch ($tipus->getId()) {
			case 201:  // Títols alumne suficients
			case 202:  // Títols alumne necessaris
			
				
				break;
				
			case 203:  // Experiència immersions
			case 204:  
			case 205:
			case 206:
			case 207:			
			case 208:

				// No es pot comprovar
					
				break;	
				
			default:
					
				break;
		}
		
		if (count($res['errors']) > 0) $res['result'] = 'KO';
		
		return $res;
	}
	
	private function comprovaRequerimentsTitulacions($requeriment, $curs) {
		$tipus = $requeriment->getRequeriment();	
			
		$res = array('result' => 'OK', 'errors' => array());
		
		switch ($tipus->getId()) {
			case 300:  // Director títols suficients
			case 301:  // Director títols necessaris
			
				
				break;
				
			case 302:  // Prof. teoria títols suficients
			case 303:  // Prof. teoria títols necessaris
			
				
				break;

			case 304:  // Prof. pràctica títols suficients
			case 305:  // Prof. pràctica títols necessaris
			
				
				break;

			case 304:  // Buss. seguretat títols suficients
			case 305:  // Buss. seguretat títols necessaris
			
				
				break;
				
			default:
					
				break;
		}
		
		if (count($res['errors']) > 0) $res['result'] = 'KO';
		
		return $res;
	}

	private function comprovaRequerimentsAltres($requeriment, $curs) {
		
		$tipus = $requeriment->getRequeriment();
		
		$res = array('result' => 'OK', 'errors' => array());
		
		switch ($tipus->getId()) {
			case 200:  // Edat mín.
					
				$edat = $requeriment->getValor();
				foreach ($curs->getParticipantsSortedByCognomsNom() as $participant) {
					$metapersona = $participant->getMetapersona();
					$persona = $metapersona->getPersona($curs->getClub());
					
					if ($persona != null) {
						if ($persona->getEdat() < $edat) $res['errors'][] = 'L\'alumne <span>'.$metapersona->getDni().'</span> no té l\'edat mínima';
					} else {
						$res['errors'][] = 'No es pot comprovar l\'edat de l\'alumne <span>'.$metapersona->getDni().'</span>'; 
					}
				}
				break;

			case 207:	// Federativa 
			
				$desde = $curs->getDatadesde();
				$fins = $curs->getDatafins();
				if ($desde == null || $fins == null) {
					$res['errors'][] = 'No es pot comprovar la vigencia de les llicències dels participants';
				} else {
					foreach ($curs->getParticipantsSortedByCognomsNom() as $participant) {
						$metapersona = $participant->getMetapersona();
						
						$finsVariable = clone $fins;
						$llicenciesPersonaPeriode = $metapersona->getLlicenciesSortedByDate(false, $desde, $fins); /* Ordenades de última a primera */
	
						foreach ($llicenciesPersonaPeriode as $llicencia) {
							// Totes les llicencies haurien d'estar parcialment dins el periode
							$parte = $llicencia->getParte();
							
							if ($parte->getDatacaducitat()->format('Y-m-d') >= $finsVariable->format('Y-m-d') && 
								$parte->getDataalta()->format('Y-m-d') 		<= $finsVariable->format('Y-m-d')) {
						 			
								$finsVariable = clone $parte->getDataalta();
						 		$finsVariable->sub(new \DateInterval('P1D')); // Minus 1 Day
							} 
						}

						// Si $fins <= $desde està tot el periode cobert
						if ($finsVariable->format('Y-m-d') > $desde->format('Y-m-d')) {
							$res['errors'][] = 'L\'alumne <span>'.$metapersona->getDni().'</span> no té llicència durant tot el periode del curs';
						} 
					}

				}	
				break;
				
			case 308:	// Director. # se exige haber dirigido como mínimo dos cursos de buceador de esa misma especialidad
					
					
				break;	
				
			default:
				
					
				break;
		}
		
		if (count($res['errors']) > 0) $res['result'] = 'KO';
		
		return $res; 
	}

	public function getRequerimentsEstructuraInforme($titol, $resultat)
    {
    	if ($titol == null) return array('titol' => '', 'errors' => array( 'total' => 0 ));
		
    	// Format tipus fitxa per poder fer el render en alguna vista funcionalment
		$dades = array(
			'titol' => $titol->getLlistaText(),
			'errors' => array(
				'total' 	=> count($resultat),
				'alumnes' 	=> array(),
				'hores'		=> array(),
				'docents'	=> array(),
				'ratios'	=> array() 
			),
			BaseController::CONTEXT_REQUERIMENT_ALUMNES => array(
				'edat' 			=> array('num' => 200, 'text' => 'Sense requeriments d\'edat', 'valor' => '', 'resultat' => ''),
				'llicencia'		=> array('num' => 207, 'text' => 'No cal llicència federativa', 'valor' => '', 'resultat' => ''),
				'immersions'	=> array('num' => array(203, 204, 205, 206, 208), 'text' => 'Immersions', 'valor' => '', 'resultat' => ''),
				'titols'		=> array('num' => array(201, 202), 'text' => 'Títols', 'valor' => '', 'resultat' => '')
			),
			BaseController::CONTEXT_REQUERIMENT_GENERAL => array(
				'hores' 		=> array(),
				'ratios' 		=> array()
			),
			
			BaseController::CONTEXT_REQUERIMENT_DOCENTS => array(
				'docents'		=> array(),
				'director'		=> array('num' => 308, 'text' => '', 'valor' => '', 'resultat' => ''),
			)
		);
		
		/********** LLISTA ERRORS ************/
		foreach ($resultat as $num => $error) {
			if ($num >= 300) $dades['errors']['docents'][] = implode(PHP_EOL,$error['errors']);
			
			if ($num >= 200 && $num < 300) $dades['errors']['alumnes'][] = implode(PHP_EOL,$error['errors']); 
			
			if ($num >= 150 && $num < 200) $dades['errors']['ratios'][] = implode(PHP_EOL,$error['errors']);
			
			if ($num < 150) $dades['errors']['hores'][] = implode(PHP_EOL,$error['errors']);
			
		}
		
		/********** ALUMNE *************/
		$reqAlumnes = &$dades[BaseController::CONTEXT_REQUERIMENT_ALUMNES]; 
		
		if (isset($resultat[$reqAlumnes['edat']['num']])) $reqAlumnes['edat']['resultat'] = 'KO'; // error edat
		
		$reqEdat = $titol->getRequerimentByTipus($reqAlumnes['edat']['num']);  // Edat
		if ($reqEdat != null) {
			$reqAlumnes['edat']['text'] = $reqEdat->getText();
			$reqAlumnes['edat']['valor'] = $reqEdat->getValor();
		}

		if (isset($resultat[$reqAlumnes['llicencia']['num']])) $reqAlumnes['llicencia']['resultat'] = 'KO'; // error llicencia
		$reqLlicencia = $titol->getRequerimentByTipus($reqAlumnes['llicencia']['num']);  // Llicencia
		if ($reqLlicencia != null) {
			$reqAlumnes['llicencia']['text'] = $reqLlicencia->getText();
			$reqAlumnes['llicencia']['valor'] = $reqLlicencia->getValor();
		}

		$reqImmersions = array();
		$error = false;
		foreach ($reqAlumnes['immersions']['num'] as $num) {
			$reqImmersio = $titol->getRequerimentByTipus($num);  // Immersions
			
			if ($reqImmersio != null) $reqImmersions[] = $reqImmersio->getValor().' ('.$reqImmersio->getText().')';
			
			if (isset($resultat[$num])) $error = true;
		} 
		if (count($reqImmersions) > 0) {
			$reqAlumnes['immersions']['valor'] = implode(PHP_EOL, $reqImmersions);
		}
		
		if ($error) $reqAlumnes['immersions']['resultat'] = 'KO'; // error immersions
		
		$reqTitols = $titol->getRequerimentByTipus($reqAlumnes['titols']['num'][0]);  // Titols suficients
		$separador = " o ";
		if ($reqTitols == null) {
			$reqTitols = $this->getRequerimentByTipus($reqAlumnes['titols']['num'][1]);  // Titols necessaris
			$separador = " + ";	
		}		
		
		if (isset($resultat[$reqAlumnes['titols']['num'][0]]) ||
			isset($resultat[$reqAlumnes['titols']['num'][1]])) $reqAlumnes['titols']['resultat'] = 'KO'; // error titols
		
		if ($reqTitols != null) {
			$titolsIdsArray = explode(";",$reqTitols->getValor()); // llista ids XX;YY;ZZ
			
			$titolsAbrevArray = array(); 
			foreach ($titolsIdsArray as $id) {
				$altretitol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($id);
				if ($altretitol != null) $titolsAbrevArray[] = $altretitol->getCodi(); 
			}
			
			$reqAlumnes['titols']['valor'] = implode($separador, $titolsAbrevArray);
		}
		/********** GENERAL *************/
		$reqGeneral = &$dades[BaseController::CONTEXT_REQUERIMENT_GENERAL];
		foreach ($titol->getRequerimentsSortedByContextCategoria(BaseController::CONTEXT_REQUERIMENT_GENERAL) as $req) {
			$tipus = $req->getRequeriment();
			$num = $tipus->getId();
			
			switch ($tipus->getCategoria()) {
				case BaseController::CATEGORIA_REQUERIMENT_MIN_HORES:
					
					$reqGeneral['hores'][$num] = array(
						'text' => $req->getText(), 'valor1' => $req->getValor(), 'valor2' => '', 'resultat' => ''
					);
					
					if (isset($resultat[$num])) $reqGeneral['hores'][$num]['resultat'] = 'KO'; // error hores
					
					break;
					
				case BaseController::CATEGORIA_REQUERIMENT_IMMERSIONS:

					if ($num == 120 && isset($reqGeneral['hores'][102])) $num = 102; // piscina
						
					if ($num == 121 && isset($reqGeneral['hores'][103])) $num = 103; // mar
						
					if ($num == 122 && isset($reqGeneral['hores'][104])) $num = 104; // fent una funcio
						
					//if (!isset($reqGeneral['hores'][102]) && !isset($reqGeneral['hores'][103]) && !isset($reqGeneral['hores'][104])) {
					if (!isset($reqGeneral['hores'][$num])) {	
						// Nova fila
						$reqGeneral['hores'][$num] = array(
							'text' => $req->getText(), 'valor1' => '', 'valor2' => $req->getValor(), 'resultat' => ''
						);
					} else {
						$reqGeneral['hores'][$num]['valor2'] = $req->getValor();
					}
					
					// ES CORRECTE FER SERVIR $tipus->getId() en comptes de $num !!
					if (isset($resultat[$tipus->getId()])) $reqGeneral['hores'][$num]['resultat'] = 'KO'; // error immersions
					
					break;
				
				case BaseController::CATEGORIA_REQUERIMENT_RATIOS:
					// 150 teoriques  | 151 piscina | 152 mar | 153 recomanat | 154 aula
					
					$valors = explode(";",$req->getValor());
					$valor0 = isset($valors[0])?$valors[0]:''; // Alumnes 
					$valor1 = isset($valors[1])?$valors[1]:''; // Professors
					$valor2 = isset($valors[2])?$valors[2]:''; // Bussejador seguretat
					
					
					if (!isset($reqGeneral['ratios'][$num])) {
						$reqGeneral['ratios'][$num] = array(
							'text' => $req->getText(), 'valor0' => $valor0, 'valor1' => $valor1, 'valor2' => $valor2, 'resultat' => ''
						);
					} else {
						$reqGeneral['ratios'][$num]['valor0'] = $valor0;
						$reqGeneral['ratios'][$num]['valor1'] = $valor1;
						$reqGeneral['ratios'][$num]['valor2'] = $valor2;
					}
					
					if (isset($resultat[$num])) $reqGeneral['ratios'][$num]['resultat'] = 'KO'; // error ratios
					
					break;
				
				default:
					
					break;
			}
			
		}	
		/********** DOCENTS *************/
		$reqDocents = &$dades[BaseController::CONTEXT_REQUERIMENT_DOCENTS];
		foreach ($titol->getRequerimentsSortedByContextCategoria(BaseController::CONTEXT_REQUERIMENT_DOCENTS) as $req) {
			$tipus = $req->getRequeriment();
			$num = $tipus->getId();
			
			switch ($tipus->getCategoria()) {
				case BaseController::CATEGORIA_REQUERIMENT_TITULACIONS:

					$titolsIdsArray = explode(";",$req->getValor()); // llista ids XX;YY;ZZ
					
					if (count($titolsIdsArray) > 0) {
						$titolsAbrevArray = array(); 
						foreach ($titolsIdsArray as $id) {
							$altretitol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($id);
							if ($altretitol != null) $titolsAbrevArray[] = $altretitol->getCodi(); 
						}
						
						$reqDocents['docents'][$num] = array(
							'text' => $req->getText(), 'valor' => implode($separador, $titolsAbrevArray), 'resultat' => ''
						);
						if (isset($resultat[$num])) $reqDocents['docents'][$num]['resultat'] = 'KO'; // error ratios
					}
											
					break;
			
				default:
				
					// req.  308 
					if ($num == $reqDocents['director']['num']) {  //  Director: Cursos de la especialitat dirigits prèviament
					
						$reqDocents['director']['text'] = $req->getText();
						$reqDocents['director']['valor'] = $req->getValor();
						if (isset($resultat[$reqDocents['director']['num']])) $reqDocents['director']['resultat'] = 'KO'; // error director
					}
					
				break;
			}
		}
		
		
		return $dades;
	}

	private function consultaDadespersonals($dni, $nom, $cognoms, $club = null, $desde = null, $fins = null, $vigent = true, $titol = null, $titolExtern = null, $strOrderBY = '') { 
		$em = $this->getDoctrine()->getManager();
	
		$current = $this->getCurrentDate();
		
		//if ($this->isCurrentAdmin()) $strQuery = "SELECT m FROM FecdasBundle\Entity\EntityMetaPersona m JOIN m.persones e ";
		//else $strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e ";
		
		//$strQuery = "SELECT m FROM FecdasBundle\Entity\EntityMetaPersona m JOIN m.persones e ";
		
		$strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e JOIN e.metapersona m ";
		
		if ($titol != null) $strQuery .= " JOIN m.titulacions t JOIN t.curs c ";
		if ($titolExtern != null) $strQuery .= " JOIN m.altrestitulacions at ";
		
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
		if ($dni != "") $strQuery .= " AND m.dni LIKE :dni ";
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
	
		//$current = $this->getCurrentDate();
		
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

	public function jsonpersonaAction(Request $request) {
		//fecdas.dev/jsonfactures?cerca=dni&admin=1&club=CATXXX
		
		$response = new Response();
		$em = $this->getDoctrine()->getManager();
	
		$cerca = $request->get('cerca', '');
		$cercanom = $request->get('nom', 0) == 1?true:false;
		$cercamail = $request->get('mail', 0) == 1?true:false;
		//$admin = $request->get('admin', 0) == 1?true:false;
		$codi = $request->get('club', '');
		// Validació de llicències
		$tecnic = $request->get('tecnic', 0) == 1?true:false;
		$desde = $request->get('desde', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->get('desde')):null;
		$fins = $request->get('fins', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->get('fins')):null;
		
		$club = $em->getRepository('FecdasBundle:EntityClub')->find($codi); // Per filtrar la persona correcta 
		
		$id = $request->get('id', 0);
		
		if ($id > 0) {
			$persona = $em->getRepository('FecdasBundle:EntityPersona')->find($id);
			if ($persona != null) {
				$response->headers->set('Content-Type', 'application/json');
				
				$telf  = $persona->getTelefon1()!=null?$persona->getTelefon1():'';
				$telf .= $persona->getTelefon2()!=null&&$telf=''?$persona->getTelefon2():'';
				
				$response->setContent(json_encode(array(
							"id" => $persona->getId(), 
							"text" => $persona->getDni(),
							"meta" => ($persona->getMetapersona()!=null?$persona->getMetapersona()->getId():0),
							"nom" => $persona->getNom(),
							"cognoms" => $persona->getCognoms(),
							"nomcognoms" => $persona->getNomcognoms(), 
							"mail" => $persona->getMail(),
							"telf" => $telf,
							"nascut" => $persona->getDatanaixement()->format('d/m/Y'),
							"poblacio" => $persona->getAddrpob(),
							"nacionalitat" => $persona->getAddrnacionalitat() 
				)));
				return $response;
			}
		}
		
		if ($id == 0 && $cerca == "") {
			// Res a cercar
			$response->headers->set('Content-Type', 'application/json');
			$response->setContent(json_encode(array()));
			return $response;
		}
		
		if ($tecnic == true) {
// CAL AFEGIR LLIÈNCIA VIGENT TêCNIC !!!!!!!!!!!!!!!!	
			// Consulta persones amb llicència durant tot el periode desde -> fins
			// iniciada abans de la data desde i acabada després de la data fins
			$strQuery = " SELECT p FROM FecdasBundle\Entity\EntityMetaPersona p INNER JOIN p.persones e  ";
			$strQuery .= " JOIN e.llicencies l JOIN l.parte r JOIN l.categoria a WHERE ";
			$strQuery .= " l.databaixa IS NULL ";
			$strQuery .= " AND r.dataalta <= :desde ";
			$strQuery .= " AND l.datacaducitat >= :fins ";
			$strQuery .= " AND a.simbol = :categoria ";
			
			$strQuery .= " AND ((p.dni LIKE :cerca) ";
			if ($cercanom) $strQuery .= " OR (CONCAT(e.nom,' ',e.cognoms) LIKE :cerca) ";
			if ($cercamail) $strQuery .= " OR (e.mail LIKE :cerca) ";
			$strQuery .= " ) ORDER BY e.cognoms, e.nom";  

			$query = $em->createQuery($strQuery);
			$query->setParameter('cerca', '%'.$cerca.'%');
			$query->setParameter('desde', $desde->format('Y-m-d H:i:s'));
			$query->setParameter('fins', $fins->format('Y-m-d'));
			$query->setParameter('categoria', BaseController::SIMBOL_TECNIC);
			
		} else {	
			$strQuery = " SELECT p FROM FecdasBundle\Entity\EntityMetaPersona p INNER JOIN p.persones e WHERE ";
			$strQuery .= " (p.dni LIKE :cerca) ";
			if ($cercanom) $strQuery .= " OR (CONCAT(e.nom,' ',e.cognoms) LIKE :cerca) ";
			if ($cercamail) $strQuery .= " OR (e.mail LIKE :cerca) ";
			$strQuery .= " ORDER BY e.cognoms, e.nom";  
		
			$query = $em->createQuery($strQuery);
			$query->setParameter('cerca', '%'.$cerca.'%');
		}		
		
		$search = array( );
		if ($query != null) {
			$result = $query->getResult();
			foreach ($result as $metapersona) {
				$persona = $metapersona->getPersona($club);
				if ($persona != null) {
						
					$telf  = $persona->getTelefon1()!=null?$persona->getTelefon1():'';
					$telf .= $persona->getTelefon2()!=null&&$telf=''?$persona->getTelefon2():'';
					
					$search[] = array("id" => $persona->getId(), 
									"text" => $persona->getDni(),
									"meta" => $metapersona->getId(),
									"nom" => $persona->getNom(),
									"cognoms" => $persona->getCognoms(),
									"nomcognoms" => $persona->getNomcognoms(),  
									"mail" => $persona->getMail(),
									"telf" => $telf,
									"nascut" => $persona->getDatanaixement()->format('d/m/Y'),
									"poblacio" => $persona->getAddrpob(),
									"nacionalitat" => $persona->getAddrnacionalitat()
						);
				}
			}
		}
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($search));
		
		return $response;
	}

}
