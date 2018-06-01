<?php 
namespace FecdasBundle\Controller;

use FecdasBundle\Classes\CSVReader;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use FecdasBundle\Form\FormContact;
use FecdasBundle\Form\FormLlicenciaMail;
use FecdasBundle\Form\FormParte;
use FecdasBundle\Form\FormPersona;
use FecdasBundle\Form\FormLlicencia;
use FecdasBundle\Form\FormDuplicat;
use FecdasBundle\Form\FormParteRenew;
use FecdasBundle\Entity\EntityContact;
use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityMetaPersona;
use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Entity\EntityArxiu; 
use Symfony\Component\HttpFoundation\File\UploadedFile;


class PageController extends BaseController {
	
	public function indexAction() {
		return $this->render('FecdasBundle:Page:index.html.twig', $this->getCommonRenderArrayOptions()); 
	}

	public function contactAction(Request $request) {

		$contact = new EntityContact();

		if ($this->get('session')->has('username')) $contact->setEmail($this->get('session')->get('username'));
		$currentClub = $this->getCurrentClub();
		if ($currentClub != null) $contact->setName($currentClub->getNom());

		if ($request->query->has('subject')) {
			$subject = $request->query->get('subject');
			$contact->setSubject($subject);
			$form = $this->createForm(new FormContact(array('disable_subject' => true)),$contact);
		} else {
			$form = $this->createForm(new FormContact(), $contact);
		}

		if ($request->getMethod() == 'POST') {
			$form->handleRequest($request);

			if ($form->isValid()) {
				$message = \Swift_Message::newInstance()
				->setSubject('::Contacte de Fecdas::'. $form->getData()->getSubject())
				->setFrom($form->getData()->getEmail())
				->setTo($this->getContactMails())
						->setBody($this->renderView('FecdasBundle:Page:contactEmail.txt.twig',
								array('contact' => $contact)));

				$this->get('mailer')->send($message);
				$this->get('session')
					->getFlashBag()->add('sms-notice','Petició enviada correctament. Gràcies!');

				// Redirect - This is important to prevent users re-posting
				// 	the form if they refresh the page
				return $this->redirect($this->generateUrl('FecdasBundle_contact'));
			}
		}
		return $this->render('FecdasBundle:Page:contact.html.twig', $this->getCommonRenderArrayOptions(array('form' => $form->createView())));
	}

	public function importcsvAction(Request $request) {
		
		//$request->getSession()->getFlashBag()->clear();
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		if (!$this->getCurrentClub()->potTramitar()) {
			$this->get('session')->getFlashBag()->add('error-notice',$this->getCurrentClub()->getInfoLlistat());
			$response = $this->redirect($this->generateUrl('FecdasBundle_partes', array('club'=> $this->getCurrentClub()->getCodi())));
			return $response;
		}
		
		$em = $this->getDoctrine()->getManager();
		
		/* Form importcsv */
		$currentClub = null;
		$codi = '';
		$dataalta = $this->getCurrentDate('now');
		$parte = null;
		
		// Data modificada, refer llista tipus
		$tipusparte = null;
		if ($request->getMethod() == 'POST') {
				$formdata = $request->request->get('form');
				if (isset($formdata['dataalta'])) {
					$dataalta = \DateTime::createFromFormat('d/m/Y', $formdata['dataalta']);
					if ($this->getCurrentDate() != $dataalta) {
						$dataalta->setTime(0, 1); // No és el mateix dia 
					}
					else {
						//$dataalta->setTime($this->getCurrentDate()->format('H'), $this->getCurrentDate()->format('i') + 20);// Add 20 minutes
						$dataalta->add($this->getIntervalConsolidacio()); // Add 20 minutes
					}
				}
				if (isset($formdata['tipus'])) {
					$tipusparte = $em->getRepository('FecdasBundle:EntityParteType')->find($formdata['tipus']);
				}
				if (isset($formdata['clubs'])) {
				    $codi = $formdata['clubs']; // Admin filtra club
				}
		} else {
		    $codi = $request->query->get('cerca', ''); // Admin filtra club
		}
		
		if ($this->isCurrentAdmin() && $codi != '') {  // Users normals només consulten comandes pròpies
		    $currentClub = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		}
		if ($currentClub == null) $currentClub = $this->getCurrentClub();
		
		$llistatipus = BaseController::getLlistaTipusParte($currentClub, $dataalta, $this->isCurrentAdmin());
		
		$atributs = array('accept' => '.csv');
		$formbuilder = $this->createFormBuilder()->add('importfile', 'file', array('attr' => $atributs, 'required' => false));
		
		$formbuilder->add('dataalta', 'text', array(
				//'attr'		=>	array('readonly' => !$this->isCurrentAdmin()),
				'data' => $dataalta->format('d/m/Y')
		));
		
		//$repository = $this->getDoctrine()->getRepository('FecdasBundle:EntityParteType');
		$formbuilder->add('tipus', 'entity', 
					array('class' => 'FecdasBundle:EntityParteType',
						'query_builder' => function($repository) use ($llistatipus) {
						return $repository->createQueryBuilder('t')->orderBy('t.descripcio', 'ASC')
							->where($repository->createQueryBuilder('t')->expr()->in('t.id', ':llistatipus'))
							->setParameter('llistatipus', $llistatipus);
						}, 'choice_label' => 'descripcio', 'required'  => count($llistatipus) == 1
					));
		
		$this->addClubsActiusForm($formbuilder, $currentClub);
		
		$form = $formbuilder->getForm();
		
		if ($request->getMethod() == 'POST') {
			$form->handleRequest($request);
			
			if ($form->isValid()) {
				$file = $form->get('importfile')->getData();
				
				try {
					if ($file == null) throw new \Exception('Cal escollir un fitxer');
					
					if (!$file->isValid()) throw new \Exception('La mida màxima del fitxer és ' . $file->getMaxFilesize());
					$this->logEntryAuth('IMPORT CSV SUBMIT', $file->getFileName());
					
					$tipusparte = $form->get('tipus')->getData();
					if ($tipusparte == null) throw new \Exception('Cal indicar un tipus de llista');
					
					$parte = $this->crearComandaParte($dataalta, $tipusparte, $currentClub, 'Importació llicències');

					$this->crearFactura($parte);

					if ($form->get('importfile')->getData()->guessExtension() != 'txt'
						|| $form->get('importfile')->getData()->getMimeType() != 'text/plain' ) throw new \Exception('El fitxer no té el format correcte');
					
					$temppath = $file->getPath()."/".$file->getFileName();
					
					$this->importFileCSVData($temppath, $parte);					
					$this->get('session')->getFlashBag()->add('sms-notice','Fitxer correcte, validar dades i confirmar per tramitar les llicències');
					
					$tempname = $this->getCurrentDate()->format('Ymd')."_".$currentClub->getCodi()."_".$file->getFileName();
					
					/* Copy file for future confirmation */
					$file->move($this->getTempUploadDir(), $tempname);
					/* Generate URL to send CSV confirmation */
					
					$urlconfirm = $this->generateUrl('FecdasBundle_confirmcsv', array(
					        'club' => $currentClub->getCodi(), 'tipus' => $parte->getTipus()->getId(), 'dataalta' => $parte->getDataalta()->format('YmdHi'),
							'tempfile' => $this->getTempUploadDir()."/".$tempname
					));
			
					// Redirect to confirm page		
					return $this->render('FecdasBundle:Page:importcsvconfirm.html.twig',
							$this->getCommonRenderArrayOptions(array('parte' => $parte, 'urlconfirm' => $urlconfirm)));
				
				} catch (\Exception $e) {
					/*if ($factura != null) $em->detach($factura);
					if ($parte != null) $em->detach($parte);*/
					$em->clear();
					
					$this->logEntryAuth('IMPORT CSV KO', $e->getMessage());
					
					$this->get('session')->getFlashBag()->add('error-notice',$e->getMessage());
				}					
			} else {
				$em->clear();
				
				// Fitxer massa gran normalment
				$this->logEntryAuth('IMPORT CSV ERROR', "Error desconegut");
				
				$this->get('session')->getFlashBag()->add('error-notice',"Error important el fitxer".$form->getErrors(true, true));
			}

		} else {
			$this->logEntryAuth('IMPORT CSV VIEW');
		}

		return $this->render('FecdasBundle:Page:importcsv.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'datesparte' => $this->datesAltaParte())));
	}
	
	public function confirmcsvAction(Request $request) {
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		if (!$request->query->has('club') || 
		    !$request->query->has('tipus') || 
			!$request->query->has('dataalta') || 
		    !$request->query->has('tempfile'))
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		/* Registre abans de tractar fitxer per evitar flush en cas d'error */
		$this->logEntryAuth('CONFIRM CSV', $request->query->get('tempfile'));
		
		
		$codi = $request->query->get('club', ''); // Admin filtra club
		if ($this->isCurrentAdmin() && $codi != '') {  // Users normals només consulten comandes pròpies
		    $currentClub = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		}
		if ($currentClub == null) $currentClub = $this->getCurrentClub();
		
		$tipusparte = $request->query->get('tipus');
		$dataalta = \DateTime::createFromFormat('YmdHi', $request->query->get('dataalta'));
		$temppath = $request->query->get('tempfile');
		
		try {
			$em = $this->getDoctrine()->getManager();
				
			$tipus = $this->getDoctrine()->getRepository('FecdasBundle:EntityParteType')->find($tipusparte);
			
			$parte = $this->crearComandaParte($dataalta, $tipus, $currentClub, 'Importació llicències');

			$this->crearFactura($parte);
			
			$this->importFileCSVData($temppath, $parte, true);
			
			$parte->setComentaris('Importació llicències:'.' '.$parte->getComentariDefault());
			
			$em->flush();

			$this->get('session')->getFlashBag()->add('sms-notice',"Llicències enviades correctament");
			
			return $this->redirect($this->generateUrl('FecdasBundle_parte', array('id' => $parte->getId(), 'action' => 'view')));

		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error-notice',$e->getMessage());
		}
		
		return $this->redirect($this->generateUrl('FecdasBundle_importcsv'));
		
	}
	
	private function importFileCSVData ($file, $parte, $persist = false) {
		$reader = new CSVReader();
		$reader->setCsv($file);
		$reader->readLayoutFromFirstRow();
		//$reader->setLayout(array('first_name', 'last_name'));
		
		$em = $this->getDoctrine()->getManager();

		// Marcar pendent per a clubs pagament immediat
		if ($persist == true) $em->persist($parte);
		
		$club = $parte->getClubparte();
		$fila = 0;
		
		implode($reader->getLayout());
		
		try {
			while($reader->process()) {
				$fila++;
				
				$row = $reader->getRow();
				//our logic here
				$categoria = $em->getRepository('FecdasBundle:EntityCategoria')
						->findOneBy(array('tipusparte' => $parte->getTipus()->getId(), 'simbol' => $row['categoria']));
				
				if ($categoria == null) throw new \Exception('No existeix aquesta categoria per al tipus de llista indicat');
				
				/* Gestionar dades personals */
				$persona = null;
				
				/* Revisar si existeix metapersona */
				$metapersona = $em->getRepository('FecdasBundle:EntityMetaPersona')->findOneBy(array('dni' => $row['dni']));
				
				if ($metapersona == null) {
					$metapersona = new EntityMetaPersona( $row['dni'] );
					
					if ($persist == true) $em->persist($metapersona);
				} else {
				    $persona = $metapersona->getPersonaClub($club);	
				}	
	
				if ($persona == null) {
				    $persona = new EntityPersona($metapersona, $club);
	
					$persona->setNom(mb_convert_case($row['nom'], MB_CASE_TITLE, "utf-8"));
					$persona->setCognoms(mb_strtoupper($row['cognoms'], "utf-8"));
					
					$datanaixement = \DateTime::createFromFormat('Y-m-d', $row['naixement']);
					if ($row['sexe'] == 'D' || $row['sexe'] == 'd') $row['sexe'] = BaseController::SEXE_DONA;
					$persona->setSexe($row['sexe']);
					$persona->setDatanaixement($datanaixement);
					$persona->setAddrnacionalitat($row['nacionalitat']);
	
					if ($persist == true) $em->persist($persona); 
				}
			
				/* Dades personals existents. Nom, cognoms, data naixement i sexe no es modifiquen, la resta s'actualitza segons valors del fitxer */
				if (isset($row['telefon1']) and $row['telefon1'] != null and $row['telefon1'] != "") $persona->setTelefon1($row['telefon1']);
				if (isset($row['telefon2']) and $row['telefon2'] != null and $row['telefon2'] != "") $persona->setTelefon2($row['telefon2']);
				if (isset($row['mail']) and $row['mail'] != null and $row['mail'] != "") $persona->setMail($row['mail']);
				if (isset($row['adreca']) and $row['adreca'] != null and $row['adreca'] != "") $persona->setAddradreca($row['adreca']);
				if (isset($row['poblacio']) and $row['poblacio'] != null and $row['poblacio'] != "") $persona->setAddrpob($row['poblacio']);
				if (isset($row['cp']) and $row['cp'] != null and $row['cp'] != "") $persona->setAddrcp($row['cp']);
				if (isset($row['provincia']) and $row['provincia'] != null and $row['provincia'] != "") $persona->setAddrprovincia(mb_convert_case($row['provincia'], MB_CASE_TITLE, "utf-8"));
				if (isset($row['comarca']) and $row['comarca'] != null and $row['comarca'] != "") $persona->setAddrcomarca(mb_convert_case($row['comarca'], MB_CASE_TITLE, "utf-8"));

				$estranger = mb_strtoupper($row['estranger'], "utf-8") == 'S';
				$this->validarDadesPersona($persona, $estranger);
			
				/* Creació i validació de la llicència */
				$llicencia = new EntityLlicencia($this->getCurrentDate());
				$llicencia->setDatamodificacio($this->getCurrentDate());
				$llicencia->setCategoria($categoria);
				$llicencia->setPersona($persona);
				$llicencia->setDatacaducitat($parte->getDatacaducitat());
				
				if ($persist == true) $em->persist($llicencia);
		
				$llicencia->setDatamodificacio($this->getCurrentDate());
				$parte->addLlicencia($llicencia);
				
                $this->addParteDetall($parte, $llicencia);
		
				// Errors generen excepció
				$this->validaParteLlicencia($parte, $llicencia);
				
	 		} 
  		} catch (\Exception $e) {
  			if(isset($row['dni'])) throw new \Exception($e->getMessage().' (DNI: ' . $row['dni'] . ')'); // Afegir DNI
			else throw new \Exception($e->getMessage().' (fila: ' . $fila . ')' ); // Afegir fila 
		}
		
		
		if ($fila == 0) throw new \Exception('No s\'ha trobat cap llicència al fitxer');
		 
	}
	
	public function llicenciesfederatAction(Request $request) {
	
		if ($this->isAuthenticated() != true) return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$checkRole = $this->get('fecdas.rolechecker');
    	
		if (!$checkRole->isCurrentInstructor() && !$checkRole->isCurrentFederat())
					 return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$user = $checkRole->getCurrentUser();
		$llicencies = array();
		$metapersona = null;
		
		try {
			if ($user == null || $user->getMetapersona() == null) throw new \Exception('No es poden mostrar les dades d\'aquest usuari');
		
			$metapersona = $user->getMetapersona();
			$llicencies = $user->getMetapersona()->getLlicenciesSortedByDate();
			
		} catch (\Exception $e) {
    		// Ko, 
    		$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
    	}
		
		return $this->render('FecdasBundle:Page:llicenciesfederat.html.twig',
				$this->getCommonRenderArrayOptions(array('metapersona' => $metapersona, 'llicencies' => $llicencies)));
	}
	
	
	public function partesAction(Request $request) {

		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		$club = $this->getCurrentClub();
		
		$interval = $this->intervalDatesPerDefecte($request);
		$desde = (isset($interval['desde']) && $interval['desde'] != null?$interval['desde']:null);
		$fins = (isset($interval['fins']) && $interval['fins'] != null?$interval['fins']:null);
		
		$tipus = $request->query->get('tipus', 0);
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'p.dataalta');
		$direction = $request->query->get('direction', 'desc');
		
		if ($request->getMethod() == 'POST') {

			return $this->redirect($this->generateUrl('FecdasBundle_parte'));
			
		} else {
			if ($request->query->has('desde') || $request->query->has('fins') || $request->query->has('tipus')) {
				$this->logEntryAuth('VIEW PARTES SEARCH', $club->getCodi()." ".$tipus.":".
									($desde != null?$desde->format('Y-m-d'):"null")."->".($fins != null?$fins->format('Y-m-d'):"null"));
			}
			else $this->logEntryAuth('VIEW PARTES', $club->getCodi());
		}
		
		if (date("m") == self::INICI_TRAMITACIO_ANUAL_MES and date("d") >= self::INICI_TRAMITACIO_ANUAL_DIA) {
			// A partir 10/12 poden fer llicències any següent
			$request->getSession()->getFlashBag()->add('error-notice', 'Ja es poden començar a tramitar les llicències del ' . (date("Y")+1));
		}

		$formBuilder = $this->createFormBuilder()->add('desde', 'text', array(
				'required' => false,
				'data' => ($desde != null?$desde->format('d/m/Y'):''),
				'attr' => array( 'placeholder' => '--', 'readonly' => false)
		));
		$formBuilder->add('fins', 'text', array(
				'required' 	=> false, 
				'data' => ($fins != null?$fins->format('d/m/Y'):''),
				'attr' => array( 'placeholder' => '--', 'readonly' => false) 
		));
		
		$tipusSearch =  $this->getTotsTipusParte();
		$formBuilder->add('tipus', 'choice', array(
							/*'class' => 'FecdasBundle:EntityParteType', 
							'query_builder' => function($repository) use ($tipusSearch) {
							return $repository->createQueryBuilder('t')->orderBy('t.descripcio', 'ASC')
								->where($repository->createQueryBuilder('t')->expr()->in('t.id', ':llistatipus'))
								->setParameter('llistatipus', $tipusSearch);
							}, 
							'choice_label' => 'descripcio', */
							'choices' => $tipusSearch,
							'required'  => false, 
							'placeholder' => 'Qualsevol...',
							'data' => $tipus,
		));
		
		$query = $this->consultaPartesClub($club->getCodi(), $tipus, $desde, $fins, $sort);
		$paginator  = $this->get('knp_paginator');
		
		$partesclub = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
		$partesclub->setParam('desde',($desde != null?$desde->format('d/m/Y'):''));
		
		/* Recollir estadístiques */
		$stat = $club->getDadesDesde( $tipus, $desde, $fins );
		$stat['saldo'] = $club->getSaldo();
		
		return $this->render('FecdasBundle:Page:partes.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(), 
						'partes' => $partesclub,  'club' => $club, 'stat' => $stat, 
						'sortparams' => array('sort' => $sort,'direction' => $direction))
						));
	}
	
	public function llicenciesParteAction(Request $request) {
	
		if ($this->isAuthenticated() != true) return new Response("");
	
		if (!$request->query->has('id')) return new Response("");
	
		$parteId = $request->query->get('id');
			
		$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteId);
			
		if (!$parte) return new Response("");
			
		$llicencies = $parte->getLlicenciesSortedByName();
	
		return $this->render('FecdasBundle:Page:partesllicencies.html.twig', array('parte' => $parte, 'llicencies' => $llicencies));
	}

	public function busseigAction(Request $request) {

		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		if ($request->getMethod() == 'POST') {
			$formdata = $request->request->get('form');
			$dni = trim($formdata['dni']);

			$smsko = 'No hi ha cap llicència vigent per al DNI : ' . $dni;
			$smsok = 'El DNI : ' . $dni . ', té una llicència vigent fins ';
			
			$em = $this->getDoctrine()->getManager();
			
			$metapersona = $em->getRepository('FecdasBundle:EntityMetaPersona')->findOneBy(array('dni' => $dni));
			
			if ($metapersona == null) {
				$lastletter = substr($dni, -1);
				$dniprefix = substr($dni, 0, -1);
				if (!is_numeric($lastletter)) {
					$metapersona = $em->getRepository('FecdasBundle:EntityMetaPersona')->findOneBy(array('dni' => $dniprefix));
				}
			}
				
			$trobada = false;
			if ($metapersona != null) {
				$vigent = $metapersona->getLlicenciaVigent();
				
				if ($vigent != null) {
					$trobada = true;
					$smsok .= $vigent->getParte()->getDatacaducitat()->format('d/m/Y');
				}
			}
			
			$this->logEntryAuth('CONSULTA DNI', $dni . " " . ($trobada == true)?"ok":"ko");
			
			if ($trobada == true) $this->get('session')->getFlashBag()->add('error-notice', $smsok);
			else $this->get('session')->getFlashBag()->add('error-notice', $smsko);
				
		} else {
			$request->getSession()->getFlashBag()->clear();
		}

		$form = $this->createFormBuilder()->add('dni', 'text')->getForm();

		return $this->render('FecdasBundle:Page:consultadni.html.twig', 
				$this->getCommonRenderArrayOptions(array('form' => $form->createView())));
	}

	public function renovarAction(Request $request) {
		$this->get('session')->getFlashBag()->clear();
		
		if ($this->isAuthenticated() != true) {
			// keep url. Redirect after login
			$url_request = $request->server->get('REQUEST_URI');
			$this->get('session')->set('url_request', $url_request);
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		}
		
		if (!$this->getCurrentClub()->potTramitar()) {
			$this->get('session')->getFlashBag()->add('error-notice',$this->getCurrentClub()->getInfoLlistat());
			$response = $this->redirect($this->generateUrl('FecdasBundle_partes', array('club'=> $this->getCurrentClub()->getCodi())));
			return $response;
		}
		
		/* Desactivar funcionalitat temporal */
		/*
		$this->get('session')->getFlashBag()->add('error-notice',	'Aquesta funcionalitat encara no està disponible');
		$response = $this->forward('FecdasBundle:Page:partes', array(), array('club' => $this->getCurrentClub()->getCodi()));
		return $response;
		*/
		/* Fi desactivar funcionalitat temporal */
	
		$parteid = 0;
		$currentClub = $this->getCurrentClub()->getCodi();
		if ($request->getMethod() == 'POST') {
			if ($request->request->has('parte_renew')) {
				$p = $request->request->get('parte_renew');
				$parteid = $p['cloneid'];
				//$currentClub = $p['club'];
			}
		} else {
			if ($request->query->has('id') and $request->query->get('id') != "")
				$parteid = $request->query->get('id');
		}
 
		$partearenovar = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
			
		if ($partearenovar == null) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$clubrenovar = $partearenovar->getClubparte();
		/* Validació impedir modificacions altres clubs */
		if ($this->isCurrentAdmin() != true && $clubrenovar->getCodi() != $currentClub)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		/* Si abans data caducitat renovació per tot el periode
		 * En cas contrari només des d'ara
		*/
		$dataalta = $this->getCurrentDate('now');
		if ($partearenovar->getDataCaducitat() >= $dataalta) {
			$dataalta = $partearenovar->getDataCaducitat();
			$dataalta->setTime(00, 00);
			$dataalta->add(new \DateInterval('P1D')); // Add 1
		} else {
			$dataalta->add($this->getIntervalConsolidacio()); // Add 20 minutes
		}

		$parte = $this->crearComandaParte($dataalta, $partearenovar->getTipus(), $clubrenovar, 'Renovació llicències');
		
		// Crear factura
		$this->crearFactura($parte);

		// Clone llicències
		$parte->cloneLlicencies($partearenovar, $this->getCurrentDate());

		$form = $this->createForm(new FormParteRenew($this->isCurrentAdmin()), $parte);
		$form->get('cloneid')->setData($parteid);

		$em = $this->getDoctrine()->getManager();
		try {
		    if (!$partearenovar->allowRenovar()) throw new \Exception('Aquest tipus de llicència no es pot renovar. Contacta amb la Federació');
		    
			$avisos = "";
			if ($request->getMethod() == 'POST') {
				$form->handleRequest($request);
				if ($parte->getTipus()->getActiu() == false) throw new \Exception('Aquest tipus de llicència no es pot tramitar. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');
				if (!$form->isValid() || !$request->request->has('parte_renew')) throw new \Exception('Error validant les dades. Contacta amb l\'adminitrador '.$form->getErrors(true, true) ); 
				$p = $request->request->get('parte_renew');
				$i = 0; 
				/*
				 * Validacions  de les llicències
				 */
				$avisos = '';
				foreach ($parte->getLlicencies() as $llicencia) {
					try {
						if (!isset($p['llicencies'][$i]['renovar'])) {
							// Treure llicències que no es volen renovar
							$parte->removeLlicencia($llicencia);
						} else {
							$this->validaParteLlicencia($parte, $llicencia);
						}
					} catch (\Exception $e) {
						$avisos .= $e->getMessage().'<br/>';
					}
					$i++;
				}
				if ($avisos != '') throw new \Exception($avisos);
				 
				foreach ($parte->getLlicencies() as $llicencia) {
					$em->persist($llicencia);
					$this->addParteDetall($parte, $llicencia);
				}
					
				// Marquem com renovat
				$partearenovar->setRenovat(true);
					
				$parte->setComentaris('Renovació llicències:'.' '.$parte->getComentariDefault());
				$em->flush();
				$this->logEntryAuth('RENOVAR OK', $parte->getId());
					
				$this->get('session')->getFlashBag()->add('sms-notice',	'Llista de llicències enviada correctament');
							
				return $this->redirect($this->generateUrl('FecdasBundle_parte', array('id' => $parte->getId(), 'action' => 'view')));
			}
		} catch (\Exception $e) {
				
			$em->clear();
			
			$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
			
			$this->logEntryAuth('RENOVAR KO', ' Parte renovar '.$partearenovar->getId().' '.$e->getMessage());
		}
		
		return $this->render('FecdasBundle:Page:renovar.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'parte' => $parte)));
	}

	public function parteAction(Request $request) {

		if ($request->query->has('source') == false) $this->get('session')->getFlashBag()->clear(); // No ve de renovació
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		if (!$this->getCurrentClub()->potTramitar()) {
			$this->get('session')->getFlashBag()->add('error-notice',$this->getCurrentClub()->getInfoLlistat());
			$response = $this->redirect($this->generateUrl('FecdasBundle_partes', array('club'=> $this->getCurrentClub()->getCodi())));
			return $response;
		}
		
		$parteid = 0;
		
		
		if ($request->getMethod() == 'POST') {
			if ($request->request->has('parte')) { 
				$response = $this->forward('FecdasBundle:Facturacio:pagamentcomanda');  // Pagament continuar
				return $response;
			}
			// Nou parte des de Partes
			// ...
		} else {
			if ($request->query->has('id') and $request->query->get('id') != "")
				$parteid = $request->query->get('id');
		}
		
		if ($parteid > 0) {
			// 	Update or delete
			$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
			$this->logEntryAuth('PARTE VIEW', $parteid);
		} else {
			$dataalta = $this->getCurrentDate();
			$dataalta->add($this->getIntervalConsolidacio()); // Add 20 minutes
			
			$this->logEntryAuth('PARTE NEW', $parteid);
			
			$parte = $this->crearComandaParte($dataalta);
			$this->crearFactura($parte);
		}
		
		$form = $this->createForm(new FormParte($this->isCurrentAdmin()), $parte);
		
		return $this->render('FecdasBundle:Page:parte.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 
						'parte' => $parte, 'datesparte' => $this->datesAltaParte())));
	}

	private function datesAltaParte(){
		// Dates mínima i màxima del selector en l'alta de partes (nou parte, import csv...)
		$datesparte = array();
		
		$current = $this->getCurrentDate();
		$datemin = $current; 
		if ($this->isCurrentAdmin()) $datemin = \DateTime::createFromFormat('Y-m-d H:i:s', $datemin->format('Y') . '-01-01 00:00:00'); 
		
		$datesparte['min'] = array('any' => $datemin->format('Y'), 'mes' => $datemin->format('m'), 'dia' => $datemin->format('d'));

		$datemax = \DateTime::createFromFormat('Y-m-d H:i:s', $current->format('Y') . '-12-31 23:59:00');
		if ($current->format('m') == self::INICI_TRAMITACIO_ANUAL_MES and $current->format('d') >= self::INICI_TRAMITACIO_ANUAL_DIA) $datemax->add(new \DateInterval('P2M')); // Add 2 mesos
		
		$datesparte['max'] = array('any' => $datemax->format('Y'), 'mes' => $datemax->format('m'), 'dia' => $datemax->format('d'));
		
		return $datesparte;
	}

	public function llicenciaAction(Request $request) {
	
		if (!$request->isXmlHttpRequest())  return new Response("<div class='sms-notice'>Error d'accés</div>");
	
		$em = $this->getDoctrine()->getManager();

		$id = 0;
		$lid = 0;
		$parte = null;
		$llicencia = null;
		$currentPerson = 0;
		$tipusid = 0;
		$response = '';
		
		if ($request->getMethod() == 'POST') {
			$requestParams = $request->request->all();
		} else {
			$requestParams = $request->query->all();
		}

		// source: FormPersona
		if (!isset($requestParams['action']) || $requestParams['action'] != 'persona') 			
					$this->get('session')->getFlashBag()->clear();
		
		try {
			$p = is_array($requestParams['parte'])?$requestParams['parte']:json_decode($requestParams['parte'], true);
			$l = is_array($requestParams['llicencia'])?$requestParams['llicencia']:json_decode($requestParams['llicencia'], true);

			$id = is_numeric($p['id']) && $p['id'] > 0?$p['id']:0;
			$lid = is_numeric($l['id']) && $l['id'] > 0?$l['id']:0;
			//if (isset($requestParams['currentperson'])) $currentPerson = $requestParams['currentperson'];
			$currentPerson = isset($l['persona']) && is_numeric($l['persona']) && $l['persona'] > 0?$l['persona']:0;
			if ($currentPerson == 0 && isset($requestParams['currentperson']) && $requestParams['currentperson'] > 0) $currentPerson = $requestParams['currentperson'];
			
			if ($lid == 0) {
				// Insert
				if ($id == 0) {

					// Nou parte
					if (!isset($p['dataalta'])) throw new \Exception('Error data alta. Contacti amb la Federació');
					if (!isset($p['tipus'])) throw new \Exception('Error tipus. Contacti amb la Federació</div>');
					
					$partedataalta = \DateTime::createFromFormat('d/m/Y H:i', $p['dataalta']);
					$tipusid = $p['tipus'];
	
					$tipus = $this->getDoctrine()->getRepository('FecdasBundle:EntityParteType')->find($tipusid);
                    
					$club = null;
					$clubparte = null;

					if ($this->isCurrentAdmin() && isset($p['club'])) {  // Admins poden escollir el club
                        $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($p['club']);

                        if ($tipus->esLlicenciaDespeses()) { // Comanda FECDAS, parte al club. Llicències col·laboradors FECDAS A compte de despeses 659.0002 de FECDAS
                            $clubparte = $club;
                            $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find(BaseController::CODI_FECDAS);
                        }
                        
					} else {
    					$club = $this->getCurrentClub();
					}
					if ($clubparte == null) $clubparte = $club;
					
					// Crear parte nou per poder carregar llista
					$parte = $this->crearComandaParte($partedataalta, $tipus, $club, 'Comanda llicències');

					$parte->setClubparte($clubparte); // Pot ser diferent del club de la comanda  
					$this->crearFactura($parte);
				} else {
					$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($id);
	
					if ($parte == null) throw new \Exception('No s\'ha trobat la llista '.$id);
				}
					
				// Noves llicències, permeten edició no pdf
				$llicencia = $this->prepareLlicencia($tipusid, $parte->getDataCaducitat());
				$em->persist($llicencia);
				$parte->addLlicencia($llicencia);

			} else {
				// Cercar llicència a actualitzar
				$llicencia = $this->getDoctrine()->getRepository('FecdasBundle:EntityLlicencia')->find($lid);
					
				if ($llicencia == null) throw new \Exception('No s\'ha trobat la llicència '.$lid);
				
				$llicenciaOriginal = clone $llicencia;	
					
				$parte = $llicencia->getParte();
			}
				
			$tipusid = $parte->getTipus()->getId();
			$partedataalta = $parte->getDataalta();

			// Person submitted
			if ($currentPerson > 0) {
				$persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($currentPerson);
				$llicencia->setPersona($persona);
			}
				
			if ($request->getMethod() == 'POST' &&
				isset($requestParams['action']) && 
				$requestParams['action'] != 'persona') {
				
				if ($requestParams['action'] == 'remove') {
				
					if (!$parte->allowRemoveLlicencia($this->isCurrentAdmin())) throw new \Exception('Esteu fora de termini per poder esborrar llicències d\'aquesta llista. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');

					$strDatafacturacio = isset($requestParams['datafacturacio'])?$requestParams['datafacturacio']:'';
					$dataFacturacio = null;    // Per defecte calcular segons data comanda
					if ($strDatafacturacio != '') $dataFacturacio = \DateTime::createFromFormat('d/m/Y', $strDatafacturacio);

					$llicenciesBaixa = array( $llicencia );
					$this->removeParteDetalls($parte, $llicenciesBaixa, $dataFacturacio); // Crea factura si escau (comanda consolidada)
					
					$this->get('session')->getFlashBag()->add('sms-notice', 'Llicència esborrada correctament');
				} else {
				    if ($llicencia->getPersona() == null) throw new \Exception('No s\'ha pogut escollir aquesta persona '.$currentPerson);
				    
					// Update / insert llicència
					$form = $this->createForm(new FormParte($this->isCurrentAdmin()), $parte);
					$formLlicencia = $this->createForm(new FormLlicencia($this->isCurrentAdmin()),$llicencia);
					$form->handleRequest($request);
					$formLlicencia->handleRequest($request);
	
					if (!$formLlicencia->isValid()) throw new \Exception('Error validant les dades de la llicència: '.$formLlicencia->getErrors(true, false));
						
					if (!$form->isValid()) throw new \Exception('Error validant les dades de la llista: '.$form->getErrors(true, false));

					// Errors generen excepció
					$this->validaParteLlicencia($parte, $llicencia);

					$llicencia->setDatamodificacio($this->getCurrentDate());
					
					if ($lid == 0) {
						$this->addParteDetall($parte, $llicencia);
					} else {
						$this->updateParteDetall($parte, $llicencia, $llicenciaOriginal);
					}
					// Comprovació datacaducitat
					if ($llicencia->getDatacaducitat()->format('d/m/Y') != $parte->getDataCaducitat()->format('d/m/Y')) {
							$llicencia->setDatacaducitat($parte->getDataCaducitat());
					}
					$this->get('session')->getFlashBag()->add('sms-notice', 'Llicència enviada correctament. Encara es poden afegir més llicències a la llista');
					
				}

				$parte->setComentaris('Comanda llicències:'.' '.$parte->getComentariDefault());

				$em->flush();

				$this->logEntryAuth('LLICENCIA '.$requestParams['action'].' OK', 'Parte:' . $parte->getId() . ' llicencia: ' . $llicencia->getId());
				
				$response = $this->render('FecdasBundle:Page:partellistallicencies.html.twig',
						array('parte' => $parte, 'admin' =>$this->isCurrentAdmin()));
				
			} else {
				// Mostrar formulari
			    $formllicencia = $this->createForm(new FormLlicencia($this->isCurrentAdmin()), $llicencia);
				if ($formllicencia->has('datacaducitatshow') == true)
					$formllicencia->get('datacaducitatshow')->setData($formllicencia->get('datacaducitat')->getData());
	
					$response = $this->render('FecdasBundle:Page:partellicencia.html.twig',
							array('admin' => $this->isCurrentAdmin(),
									'llicencia' => $formllicencia->createView(),
									'asseguranca' => $parte->isAsseguranca(),
									'llicenciadades' => $llicencia));
			}
		} catch (\Exception $e) {
			
			$em->clear();
			/*	
			if ($llicencia != null) {
				if ($llicencia->getId() == 0) $em->detach($llicencia);
				else $em->refresh($llicencia);
			} 
			if ($parte != null) {
				if ($parte->getId() == 0) $em->detach($parte);
				else $em->refresh($parte);
			} 
			if ($factura != null) {
				if ($factura->getId() == 0) $em->detach($factura);
				else $em->refresh($factura);
			} */
			
			$this->logEntryAuth('LLICENCIA KO', ' Parte '.$id.'- Llicencia '.$lid.' : ' .$e->getMessage());
	
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
		}
	
		return $response;
	}

	public function baixallicenciesAction(Request $request) {

		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		if (!$this->getCurrentClub()->potTramitar()) {
			$this->get('session')->getFlashBag()->add('error-notice',$this->getCurrentClub()->getInfoLlistat());
			$response = $this->redirect($this->generateUrl('FecdasBundle_partes', array('club'=> $this->getCurrentClub()->getCodi())));
			return $response;
		}
		
		$em = $this->getDoctrine()->getManager();
		
		$parteid = $request->query->get('id', 0);

		$strDatafacturacio = $request->query->get('datafacturacio', '');
		$dataFacturacio = null;   // Per defecte calcular segons data comanda
		if ($strDatafacturacio != '') $dataFacturacio = \DateTime::createFromFormat('d/m/Y', $strDatafacturacio);
		
		$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
		
		$llicenciesBaixa = array( );
		
		$idsLlicencies = $request->query->get('llicencies', '');

		try {
			if ($parte == null) throw new \Exception("S\'ha produït un error esborrant les llicències. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació");		

			if (!$parte->allowRemoveLlicencia($this->isCurrentAdmin())) throw new \Exception('Esteu fora de termini per poder esborrar llicències d\'aquesta llista. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');

			if ($idsLlicencies != '') {
				$idsLlicencies = urldecode($idsLlicencies); 
		
				$arrayIdsLlicencies = json_decode($idsLlicencies); // Array
				
				foreach ($parte->getLlicencies() as $llicencia) {

					$key = array_search($llicencia->getId(), $arrayIdsLlicencies); 
					
					if (!$llicencia->esBaixa() && $key !== false) { // Trobat, no és baixa

						array_splice($arrayIdsLlicencies, $key, 1); // Treure Id de la llicència trobada
						
						$llicenciesBaixa[] = $llicencia;
					}
				}
			}

			if (count($llicenciesBaixa) == 0) throw new \Exception("No ha estat possible esborrar aquestes llicències. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació");
			
			if (count($arrayIdsLlicencies) != 0) throw new \Exception("No ha estat possible esborrar alguna de les llicències. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació");
		
			$this->removeParteDetalls($parte, $llicenciesBaixa, $dataFacturacio);  // Crea factura si escau (comanda consolidada)

			$em->flush();
		
			$this->logEntryAuth('BAIXA LLICENCIES OK', 'parte '.$parteid . ' llicencies ' . $idsLlicencies);	
		
			$this->get('session')->getFlashBag()->add('sms-notice', 'Llicències esborrades correctament');
			
		} catch (\Exception $e) {
			
			$em->clear();
			
			$this->logEntryAuth('BAIXA LLICENCIES KO', 'parte '.$parteid . ' llicencies ' . $idsLlicencies .' '. $e->getMessage());	
			
			$this->get('session')->getFlashBag()->add('error-notice',$e->getMessage());
		}

		return $this->redirect($this->generateUrl('FecdasBundle_parte', array('id' => $parteid, 'action' => 'view') ));
		
	}

	public function llicenciespermailAction(Request $request) {
	        
	    if ($this->isAuthenticated() != true) {
	        $response = new Response('Acció no permesa. Cal tornar a iniciar la sessió');
	        $response->setStatusCode(500);
	        return $response;
	    }
	
	    if (!$this->getCurrentClub()->potTramitar()) {
	        $response = new Response('Per poder fer aquest tràmit cal us poseu en contacte amb la Federació');
	        $response->setStatusCode(500);
	        return $response;
	    }
	        
	        
	        $em = $this->getDoctrine()->getManager();
	        
	        $filtre = '';
	        $parteid = 0;
	        $llicenciaid = 0;
	        if ($request->getMethod() == 'POST') {
	            $formdata = $request->request->get('form');
	            $parteid = isset($formdata['id'])?$formdata['id']:0;
	        } else {
	            $parteid = $request->query->get('id', 0);
	            $llicenciaid = $request->query->get('llicencia', 0);
	            $filtre = $request->query->get('filtre', '');
	        }
	        
	        $parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
	        
	        
	        try {
	            if ($parte == null) throw new \Exception ('Llista no trobada');
	            
	            $club = $parte->getClub();
	            
	            if (!$this->isCurrentAdmin() && !$parte->comandaPagada() && $club->getSaldo() < 0) throw new \Exception ('Llista pendent de pagament. Poseu-vos en contacte amb la Federació');
	            
	            if ($request->getMethod() == 'POST') {
	                $llicencies = $formdata['llicencies'];      // PHP límit max_input_vars = 1000. El formulari té 6 camps per llicència => 166 llicències max.
	                // Valor canviat a 3000 => 500 llicències
	                $enviades = 0;
	                $res = '';
	                $log = '';
	                if ($parte->getTipus()->getEs365()) $cursAny = $parte->getCurs();
	                else $cursAny = $parte->getAny();
	                $template = $parte->getTipus()->getTemplate();
	                foreach ($llicencies as $llicenciaArray) {
	                    $llicenciaId = $llicenciaArray['id'];
	                    
	                    if (isset($llicenciaArray['enviar']) && $llicenciaArray['enviar'] == 1) {
	                        
	                        $llicencia = $this->getDoctrine()->getRepository('FecdasBundle:EntityLlicencia')->find($llicenciaId);
	                        
	                        if ($llicencia != null) {
	                            $this->enviarMailLlicencia($club, $llicencia, $cursAny, $template);
	                            
	                            $enviades++;
	                            $res .= $llicenciaArray['nom']. ' '.($llicenciaArray['mail'] != ''?$llicenciaArray['mail']:'(Correu del club) '.$club->getMail()).'</br>';
	                            $log .= $llicenciaArray['id'].' - '.$llicenciaArray['nom']. ' '.$llicenciaArray['mail'].' ; ';
	                        }
	                        
	                    }
	                }
	                
	                if ($enviades == 0)  throw new \Exception ('No s\'ha enviat cap llicència digital');
	                // Marcar el parte com enviat (imprès)
	                $parte->setDatamodificacio($this->getCurrentDate());
	                $em->flush();
	                
	                $this->logEntryAuth('MAIL LLICENCIES OK', 'parte ' . $parteid . ' enviades  '.$enviades.' : '.$log );
	                
	                if ($enviades > 1)  $res = 'Total de llicències enviades '.$enviades.'<br/>'.$res;
	                else $res = 'Llicència enviada a '.$res;
	                
	                return new Response($res);
	            } else {
	                // CREAR FORMULARI federats amb checkbox filtrats opcionalment per nom
	                $llicencies = array();
	                if ($llicenciaid == 0) $llicencies = $parte->getLlicenciesSortedByName( $filtre );
	                else $llicencies[] = $parte->getLlicenciaById($llicenciaid);
	                
	                $formBuilder = $this->createFormBuilder();
	                
	                $formBuilder->add('id', 'hidden', array(
	                    'data'	=> $parteid
	                ));
	                
	                $formBuilder->add('filtre', 'text', array(
	                    'data'	=> $filtre
	                ));
	                
	                $formBuilder->add('checkall', 'checkbox', array(
	                    'data'	=> true
	                ));
	                
	                $formBuilder->add('llicencies', 'collection', array(
	                    'type' 	=> new FormLlicenciaMail(),
	                    'data'	=> $llicencies
	                ));
	                
	                $this->get('session')->getFlashBag()->add('sms-notice', 'Les llicències de les persones sense correu s\'enviaran a l\'adreça de correu del club ');
	                
	            }
	            
	        } catch (\Exception $e) {
	            
	            $this->logEntryAuth('MAIL LLICENCIES KO', 'parte ' . $parteid . ' error: '.$e->getMessage() );
	            
	            $response = new Response($e->getMessage());
	            $response->setStatusCode(500);
	            return $response;
	        }
	        $this->logEntryAuth('MAIL LLICENCIES FORM', ' accio '.$request->getMethod());
	        
	        // Temps des de la darrera llicència
	        $form = $formBuilder->getForm();
	        
	        if ($request->query->has('filtre')) {  // Recàrrega de la taula
	            return $this->render('FecdasBundle:Admin:sortidallicenciesformtaulamail.html.twig',
	                $this->getCommonRenderArrayOptions( array( 'form' => $form->createView(), 'parte' => $parte, 'showFiltre' => true, 'filtre' => $filtre ) )
	                );
	        }
	        
	        return $this->render('FecdasBundle:Admin:sortidallicenciesform.html.twig',
	            $this->getCommonRenderArrayOptions( array( 'form' => $form->createView(),
	                'action' => $this->generateUrl('FecdasBundle_llicenciespermail'),
	                'includetaula' => 'FecdasBundle:Admin:sortidallicenciesformtaulamail.html.twig',
	                'parte' => $parte, 'showFiltre' => ($llicenciaid == 0), 'filtre' => $filtre ) )
	            );
	}
	
	private function enviarMailLlicencia($club, $llicencia, $cursAny, $template) {
	    if ($club == null) throw new \Exception("Error en les dades del club");
	    
	    if ($llicencia == null) throw new \Exception("Error en les dades de la llicència");
	    
	    $persona = $llicencia->getPersona();
	    
	    if ($persona == null) throw new \Exception("Error en les dades de la persona");
	    
	    $tomails = array();
	    if ($persona->getMail() == '' || $persona->getMail() == null) {
	        // Si la persona no té mail s'envia al club
	        if ($club->getMail() == '' || $club->getMail() == null) throw new \Exception($persona->getNomCognoms().' i club sense mail');
	        
	        $tomails = $club->getMails();
	    } else {
	        $tomails = $persona->getMails();
	    }
	    
	    $attachments = array();
	    
	    $method = "textLlicencia".$template."mail";
	    
	    if (!method_exists($this, $method)) throw new \Exception("Error generant el text del correu de la llicència. No existeix la plantilla");
	    
	    $textMail = $this->$method( $cursAny );
	    
	    if (!isset($textMail['subject']) || !isset($textMail['body']) || !isset($textMail['greeting']))
	        throw new \Exception("Error generant el text del correu de la llicència");
	        
	        $subject = $textMail['subject'];
	        $body = $textMail['body'];
	        $salutacio = $textMail['greeting'];
	        
	        $method = "printLlicencia".$template."pdf";
	        
	        if (!method_exists($this, $method)) throw new \Exception("Error generant la llicència. No existeix la plantilla");
	        
	        $pdf = $this->$method( $llicencia );
	        
	        $nom =  "llicencia_".$cursAny."_".$llicencia->getId()."_".$llicencia->getPersona()->getDni().".pdf";
	        
	        $attachments[] = array( 'name' => $nom,
	            //'data' => $attachmentData = $pdf->Output($attachmentName, "E") 	// E: return the document as base64 mime multi-part email attachment (RFC 2045)
	            'data' => $pdf->Output($nom, "S")  // S: return the document as a string (name is ignored).)
	        );
	        
	        $this->buildAndSendMail($subject, $tomails, $body, array(), null, $attachments, 470, $salutacio);
	        
	        $llicencia->setMailenviat( 1 );
	        $llicencia->setDatamail( new \DateTime() );
	}
	

	public function personaAction(Request $request) {

		$options = array();
		/* Get provincies, comarques, nacions*/
		$options['edit'] = false;
		$options['provincies'] = $this->getProvincies();
		$options['comarques'] = $this->getComarques();
		$options['nacions'] = $this->getNacions();

		$formpersona = null; 
		$persona = null;
		$metapersona = null; 
		$fotoPath = '';
		$certificatPath = '';
		$altrestitolscurrent = array();	
		$em = $this->getDoctrine()->getManager();
		try {
			if ($request->getMethod() == 'POST') {
				$p = $request->request->get('persona', null);
				if ($p == null) throw new \Exception("Dades incorrectes");

				if ($p['id'] != 0) {
					$persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($p['id']);
					if ($this->isCurrentAdmin()) $options['edit'] = true;  // Admins poden modificar nom i cognoms
				}

				if ($p['foto'] != '') $fotoPath = $p['foto'];
				if ($p['certificat'] != '') $certificatPath = $p['certificat'];
				
				if (isset($p['altrestitolscurrent']) && $p['altrestitolscurrent'] != '') {
					$altrestitolscurrent = 	explode(";", $p['altrestitolscurrent']);
				}

				if ($persona != null) $metapersona = $persona->getMetapersona();
				/* Revisar si existeix metapersona */
				if ($metapersona == null) $metapersona = $em->getRepository('FecdasBundle:EntityMetaPersona')->findOneBy(array('dni' => $p['dni']));
				if ($metapersona == null) {
					$metapersona = new EntityMetaPersona( $p['dni'] );
					$em->persist($metapersona);
				}
				if ($persona == null) {
					$persona = new EntityPersona($metapersona, $this->getCurrentClub());
					$options['edit'] = true;
					$em->persist($persona);
				}
				
				$formpersona = $this->createForm(new FormPersona($options), $persona);
			
				$formpersona->handleRequest($request);
			
				if ($formpersona->isValid()) {
					
					$baixaPersona = $request->request->get('action') != 'save';
					
					if (!$baixaPersona) {
						
						$estranger = ( isset($p['estranger']) && $p['estranger'] == 1 )?true:false;
						
						$this->validarDadesPersona($persona, $estranger, $formpersona);

						$foto = $formpersona->get('fotoupld')->getData();
						$certificat = $formpersona->get('certificatupld')->getData();
						
						$this->gestionarArxiusPersona($persona, $fotoPath, $certificatPath, $foto, $certificat);

						$this->actualitzarAltresTitulacionsPersona($persona, $altrestitolscurrent);

						if ($persona->getId() == 0) {
							$this->get('session')->getFlashBag()->add('sms-notice', "Dades personals afegides correctament");
						} else {
							$this->get('session')->getFlashBag()->add('sms-notice',	"Dades modificades correctament");
						}
						$persona->setValidat(false);  // No validat, detecció ACCESS
					} else { // Esborrar
						// Check si persona té alguna llicència associada
						$llicenciesPersona = $em->getRepository('FecdasBundle:EntityLlicencia')->findBy(array('persona' => $persona->getId(), 'databaixa' => null));
							
						if ($llicenciesPersona != null) throw new \Exception("Aquesta persona té llicències i no es pot esborrar"); 

						$persona->setDatamodificacio($this->getCurrentDate());
						$persona->setDatabaixa($this->getCurrentDate());
						//$em->persist($persona); // Per delete seria remove
						$em->flush();
						$this->get('session')->getFlashBag()->add('sms-notice', "Dades personals esborrades correctament");
					}

					$em->flush();
					// Després de flush, noves entitats tenen id
					$this->logEntryAuth('PERSONA '.$request->request->get('action'). ' OK', $persona->getId());
					$request->request->set('action', 'persona');
				} else {
					// get a ConstraintViolationList
					$errors = $this->get('validator')->validate($persona);
					// iterate on it
					foreach ($errors as $error) {
						if ($error->getPropertyPath() == "telefon1") throw new \Exception("El telèfon ".$persona->getTelefon1()." no és vàlid"); 
						if ($error->getPropertyPath() == "telefon2") throw new \Exception("El telèfon ".$persona->getTelefon2()." no és vàlid");  
						//if ($error->getPropertyPath() == "mail") throw new \Exception("Adreça de correu electrònica incorrecte");
					}
					throw new \Exception("Dades invàlides");
				}
					
				if ($request->request->get('origen') == 'llicencia') {
			
					if (!$baixaPersona) $request->request->set('currentperson', $persona->getId()); 
					$response = $this->forward('FecdasBundle:Page:llicencia', array(
				        'request'  => $request
				    ));
					return $response;
				} else {
					return new Response("");
				}
			}
			if ($request->isXmlHttpRequest()) {
				// Reload form persona
				$personaId = $request->query->get('id', 0);
				$persona = null;
				$options['edit'] = false;
				if ($personaId != 0) {
					$persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($personaId);
					if ($this->isCurrentAdmin()) $options['edit'] = true; 
				}
				
			}
		} catch (\Exception $e) {
			if ($em->isOpen()) {
				if ($persona != null) {
					if ($persona->getId() == 0) $em->detach($persona);
					else 	$em->refresh($persona);
					
					$metapersona = $persona->getMetapersona();
					if ($metapersona != null) {
						if ($metapersona->getId() == 0) $em->detach($metapersona);
						else $em->refresh($metapersona);
					}
				}
			} else {
				$em = $this->getDoctrine()->resetManager();
			}
			
			$this->logEntryAuth('PERSONA KO',	$e->getMessage());
				
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
			return $response;
		}

		if ($persona == null) {
			$metapersona = new EntityMetaPersona();
			$em->persist($metapersona);
			$persona = new EntityPersona($metapersona, $this->getCurrentClub());
			$em->persist($persona);
			$options['edit'] = true;
			$persona->setSexe("H");
			$persona->setAddrnacionalitat("ESP");
		}	
		if ($formpersona == null) $formpersona = $this->createForm(new FormPersona($options), $persona);

		return $this->render('FecdasBundle:Page:persona.html.twig',
					array('formpersona' => $formpersona->createView(), 'persona' => $persona, 'admin' => $this->isCurrentAdmin()));
	}
	
	private function validarDadesPersona($persona, $estranger = false, $form = null) {
		if ($persona == null || $persona->getClub() == null) throw new \Exception("Les dades no són correctes");
		
		$club = $persona->getClub();
		
		if ($persona->getNom() == null || $persona->getNom() == "") {
				//if ($form != null) $form->get('nom')->addError(new FormError('Falta el nom')); 
				throw new \Exception("Cal indicar el nom");
		}

		if ($persona->getCognoms() == null || $persona->getCognoms() == "") {
				//if ($form != null) $form->get('cognoms')->addError(new FormError('Falten els cognoms')); 
				throw new \Exception("Cal indicar els cognoms");
		}
		
		if ($persona->getDni() == "") throw new \Exception("Cal indicar el DNI");
	
		if ($persona->getSexe() != BaseController::SEXE_HOME && $persona->getSexe() != BaseController::SEXE_DONA)
				throw new \Exception('Manca indicar correctament el sexe de la persona ');

		$currentMin = $this->getCurrentDate();
		$currentMin->sub(new \DateInterval('P'.BaseController::EDAT_MINIMA.'Y')); // -4 anys 
				
		if ($persona->getDatanaixement() == null || $persona->getDatanaixement() == "") throw new \Exception('Cal indicar la data de naixement'); 
		
		if ($persona->getDatanaixement()->format('Y-m-d') > $currentMin->format('Y-m-d')) throw new \Exception('La data de naixement és incorrecte'); 

		
		if ($persona->getTelefon1() > BaseController::MAX_TELEFON) throw new \Exception("El número de telèfon no és correcte"); 
		if ($persona->getTelefon2() > BaseController::MAX_TELEFON) throw new \Exception("El número de mòbil no és correcte");
		
		/*if ($persona->getId() == 0 &&
			($persona->getTelefon1() == null || $persona->getTelefon1() == 0 || $persona->getTelefon1() == "") &&
			($persona->getTelefon2() == null || $persona->getTelefon2() == 0 || $persona->getTelefon2() == "") &&
			($persona->getMail() == null || $persona->getMail() == "")) throw new \Exception("Cal indicar alguna dada de contacte");*/
			
		/*if ($persona->getId() == 0 && 
            ($persona->getMail() == null || $persona->getMail() == "")) throw new \Exception("Cal indicar l'adreça de correu electrònica");*/	
		
		if ($persona->getMail() == "") $persona->setMail(null);
        
        if ($persona->getMail() != null) {
            $strMails = $this->validateMails($persona->getMails());
            $persona->setMail($strMails);
		}
		
		$em = $this->getDoctrine()->getManager();							
		
		$nacio = $em->getRepository('FecdasBundle:EntityNacio')->findOneByCodi($persona->getAddrnacionalitat());
		
		if ($nacio == null) $persona->setAddrnacionalitat('ESP');

		
		if ($estranger == false) {
			/* Només validar DNI nacionalitat espanyola */
			$dnivalidar = $persona->getDni();
			/* Tractament fills sense dni, prefix M o P + el dni del progenitor */
			if ( substr ($dnivalidar, 0, 1) == 'P' or substr ($dnivalidar, 0, 1) == 'M' ) $dnivalidar = substr ($dnivalidar, 1,  strlen($dnivalidar) - 1);
						
			if (BaseController::esDNIvalid($dnivalidar) != true) throw new \Exception('El DNI és incorrecte ');
		}
		
		/* Check persona amb dni no repetida al mateix club */
		if ($persona->getId() == 0) {
			
			$metapersona = $persona->getMetapersona();
			
			$personaClub = $metapersona->getPersonaClub($club);
			
			if ($personaClub != null && $personaClub->getId() > 0) throw new \Exception("Existeix una altra persona al club amb aquest DNI");
		}		
		
		// Canviar format Nom i COGNOMS
		// Specials chars ñ, à, etc... 
		$persona->setCognoms(mb_strtoupper($persona->getCognoms(), "utf-8"));
		$persona->setNom(mb_convert_case($persona->getNom(), MB_CASE_TITLE, "utf-8"));
					
		$persona->setDatamodificacio($this->getCurrentDate());
		
	}
	
	private function actualitzarAltresTitulacionsPersona($persona, $altrestitolscurrent = array()) {
		if ($persona == null || $persona->getMetapersona() == null) throw new \Exception("Dades personals errònies. Cal revisar-les");
		
		$em = $this->getDoctrine()->getManager();
		
		$metapersona = $persona->getMetapersona();
		
		
		if ($persona->getId() != 0) {
			$altrestitolsesborrar = array();
			$altrestitols = $metapersona->getAltrestitulacions();
		
			foreach ($altrestitols as $altretitol) {
				
				if (!in_array($altretitol->getId(), $altrestitolscurrent)) {
					// Remove
					$altrestitolsesborrar[] = $altretitol;
				} else {
					// Existeix Treure de l'array
					
					$pos = array_search($altretitol->getId(), $altrestitolscurrent);
					array_splice($altrestitolscurrent, $pos, 1);
				}
			}
			// Esborrar
			foreach ($altrestitolsesborrar as $altretitolesborrar) {
				$metapersona->removeAltrestitulacions($altretitolesborrar);
			}
		}	
		
		// A $altrestitolscurrent queden només les noves titulacions
		foreach ($altrestitolscurrent as $altretitolId) {
			$altretitolnou = $em->getRepository('FecdasBundle:EntityTitol')->find($altretitolId);
			
			if ($altretitolnou == null) throw new \Exception("Titulació no trobada ".$altretitolId);
			
			$metapersona->addAltrestitulacions($altretitolnou);
		}
	
		
	}
	
	public function duplicatsAction(Request $request) {
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$em = $this->getDoctrine()->getManager();
		 
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'd.datapeticio');
		$direction = $request->query->get('direction', 'desc');
		$totes = $request->query->get('totes', 0) == 1?true:false;
		
		$currentClub = $this->getCurrentClub()->getCodi();
		
		if ($request->getMethod() != 'POST') $this->logEntryAuth('VIEW DUPLICATS', 'club ' . $currentClub);
		
		$duplicat = $this->crearComandaDuplicat();
		//$duplicat = new EntityDuplicat();
		$form = $this->createForm(new FormDuplicat(array('club' => $currentClub)), $duplicat);
		
		if ($request->getMethod() == 'POST') {
			//$form->submit($request); 
			$form->handleRequest($request);
			if ($form->isValid()) {
				try {
				    
				    $data = $request->request->get('duplicat');
				    $nom = (isset($data['nom'])?$data['nom']:'');
				    $cognoms = (isset($data['cognoms'])?$data['cognoms']:'');
				    
					//$duplicat->setClub($this->getCurrentClub());
					//$duplicat->setDatapeticio($this->getCurrentDate());
					
					// Carnets llicències sense títol, la resta amb títol corresponent 
					if ($duplicat->getTitol() == null && $duplicat->getCarnet()->getId() != 1) throw new \Exception('Cal indicar un títol');  
					if ($duplicat->getTitol() != null && $duplicat->getCarnet()->getId() == 1) throw new \Exception('Dades del títol incorrectes'); 

					
					
					if ($duplicat->getCarnet()->esLlicencia()) {
					    /* Validar duplicat llicència, llicència vigent */
					    $vigent = $duplicat->getPersona()->getLlicenciaVigent();
					    if ($vigent == null) throw new \Exception('Aquest federat no té cap llicència vigent per demanar-ne un duplicat'); 
					}
					
					//$em->persist($duplicat);
					
					// Comprovar canvis en el nom / cognoms
					/*$nom = "";
					if ($form->has('nom')) $nom = $form->get('nom')->getData();
					$cognoms = "";
					if ($form->has('cognoms')) $cognoms = $form->get('cognoms')->getData();*/
					
					if (trim($nom) == "" || trim($cognoms) == "") throw new \Exception('Cal indicar el nom i cognoms de la persona');
					
					if ($form->has('fotoupld'))  {
						$file = $form->get('fotoupld')->getData();
						
						if ($file == null) throw new \Exception('Cal carregar una foto per demanar el duplicat');
	
						if (!($file instanceof UploadedFile) or !is_object($file))  throw new \Exception('1.No s\'ha pogut carregar la foto');
							
						if (!$file->isValid()) throw new \Exception('2.No s\'ha pogut carregar la foto ('.$file->isValid().')'); // Codi d'error
						
						$uploaded = $this->uploadAndScale($file, $duplicat->getPersona()->getDni(), 300, 200);
						
						$foto = new EntityArxiu($uploaded['path'], true);
						$foto->setPath($uploaded['name']);
						$foto->setTitol("Foto carnet federat " . $duplicat->getPersona()->getNom() . " " . $duplicat->getPersona()->getCognoms());
						$em->persist($foto);
						$duplicat->setFoto($foto);
					} else { 
						// Form sense foto
						if ($duplicat->getCarnet()->getFoto() == true) throw new \Exception('Cal carregar una foto per demanar el duplicat');
					}

					// Canvis en el nom i cognoms de la persona
					$observacionsMail = "";
					if ($duplicat->getPersona()->getNom() != $nom || $duplicat->getPersona()->getCognoms() != $cognoms) {
						$observacionsMail = "<p>Ha canviat el nom, abans " .
								$duplicat->getPersona()->getNom() . " " . $duplicat->getPersona()->getCognoms() ."</p>";
						$duplicat->getPersona()->setNom($nom);
						$duplicat->getPersona()->setCognoms($cognoms);
						$duplicat->getPersona()->setDatamodificacio($this->getCurrentDate());
						$duplicat->getPersona()->setValidat(false);
					}
					
					$this->addDuplicatDetall($duplicat);
							
					$em->flush();
					
					// Enviar notificació mail
					$subject = ":: Petició de duplicat. " . $duplicat->getCarnet()->getTipus() . " ::";
					
					//$tomails = $this->getLlicenciesMails();
					$tomails = array();
					if ($duplicat->getCarnet()->esLlicencia()) $tomails = $this->getLlicenciesMails(); // Llicències Remei
					else $tomails = $this->getCarnetsMails(); // Carnets Albert
					$body = "<h3>Petició de duplicat del club ". $duplicat->getClub()->getNom()."</h3>";
					$body .= "<p>". $duplicat->getTextCarnet() ."</p>";
					$body .= "<p>". $duplicat->getPersona()->getNom() . " " . $duplicat->getPersona()->getCognoms();
					$body .= " (" . $duplicat->getPersona()->getDni() .")</p>";
					if ($observacionsMail != "") $body .= "<p><em>(". $observacionsMail .")</em></p>";
					if ($duplicat->getObservacions() != null) $body .= "<p>Observacions</p><p>". $duplicat->getObservacions() ."</p>";

					if (isset($foto) and $foto != null) $this->buildAndSendMail($subject, $tomails, $body, null, $foto->getAbsolutePath()); 
					else $this->buildAndSendMail($subject, $tomails, $body);
					
					$this->logEntryAuth('OK DUPLICAT', 'club ' . $currentClub . ' persona ' . $duplicat->getPersona()->getId());
					
					$this->get('session')->getFlashBag()->add('sms-notice',"Petició enviada correctament");
					
				} catch (\Exception $e) {
					if ($duplicat != null) $em->detach($duplicat);
					
					$this->logEntryAuth('ERROR DUPLICAT', 'club ' . $currentClub . ' ' .$e->getMessage());
						
					$this->get('session')->getFlashBag()->add('error-notice',$e->getMessage());
				}
			} else {
				// Ampliació error validació
				/*$errors = "";
				foreach ($form->getErrors() as $key => $error) {
					$errors .= $error->getMessage() . " ******* ";
				}
				foreach ($form->all() as $child) {
					if (!$child->isValid()) {
						$errors .=  "(" . $child->getName() . ")". $this->getErrorMessages($child);
					}
				}*/
				if ($duplicat != null) $em->detach($duplicat);
				$this->logEntryAuth('INVALID DUPLICAT', 'club ' . $currentClub . ' ' .$form->getErrors(true, true));
				
				$this->get('session')->getFlashBag()->add('error-notice',"Dades incorrectes ".$form->getErrors(true, true));
			}

			/* reenvia pàgina per evitar F5 */
			return $this->redirect($this->generateUrl('FecdasBundle_duplicats', array('sort' => $sort,'direction' => $direction, 'page' => $page, 'totes' => $totes)));
		}
		
		$strQuery = "SELECT d, p, c FROM FecdasBundle\Entity\EntityDuplicat d JOIN d.persona p JOIN d.carnet c";
		/* Administradors totes les peticions, clubs només les seves*/
		if (!$this->isCurrentAdmin()) {
			$strQuery .= " WHERE d.club = :club ORDER BY d.datapeticio";  
			$query = $em->createQuery($strQuery)
				->setParameter('club', $currentClub);
		} else {
			if ($totes == false) $strQuery .= " WHERE d.dataimpressio IS NULL AND d.databaixa IS NULL ";  

			$strQuery .= " ORDER BY d.datapeticio";
			
			$query = $em->createQuery($strQuery);
		}
		
		$paginator  = $this->get('knp_paginator');
		$duplicats = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		);
		$duplicats->setParam('totes',$totes);
		
		return $this->render('FecdasBundle:Page:duplicats.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'duplicats' => $duplicats,
						'sortparams' => array('sort' => $sort,'direction' => $direction))
						));
	}
	
	public function duplicatsformAction(Request $request) {
		// retorna els camps del formulari de duplicats de la petició   	
		
		if ($this->isAuthenticated() != true || 
			!$request->query->has('carnet') ||
			!$request->query->has('persona')) return new Response("");

		$em = $this->getDoctrine()->getManager();
		
		//$duplicat = new EntityDuplicat();
		$duplicat = $this->crearComandaDuplicat();
		$carnet = $em->getRepository('FecdasBundle:EntityCarnet')->find($request->query->get('carnet'));
		$persona = $em->getRepository('FecdasBundle:EntityPersona')->find($request->query->get('persona'));
		$duplicat->setCarnet($carnet);
		$duplicat->setPersona($persona);
		$fotocarnet = false;
		
		if ($carnet != null and $carnet->getFoto() == true) $fotocarnet = true;
			
		$form = $this->createForm(new FormDuplicat(array('persona' => $persona, 'carnet' => $carnet, 'foto' => $fotocarnet)), $duplicat);   // Només select titols
		
		return $this->render('FecdasBundle:Page:duplicatsform.html.twig', $this->getCommonRenderArrayOptions(array('form' => $form->createView()))); 
	} 
	
	private function allowEdit(EntityParte $parte) {
		return (boolean) ($parte->getDatapagament() == null); // Allow edition
	}

	private function prepareLlicencia($tipusparteId, $datacaducitat) {
		//dummy llicencia by default
		$llicencia = new EntityLlicencia($this->getCurrentDate());
		$llicencia->name = 'llicencia-nova';
		$llicencia->setDatacaducitat($datacaducitat);
		
		$llicencia->setEnviarllicencia(false); // Per defecte no

		// Checks depends on parte type
		switch ($tipusparteId) {
			case 2:
			case 6:
			case 10:
			case 11:
				$llicencia->setNocmas(true);
				break;
			case 7:
				break;
			case 8:
				$llicencia->setFusell(true);
				break;
		}

		return $llicencia;
	}

	public function gettipuspartesAction(Request $request) {
		$day = $request->get('day');
		$month = $request->get('month');
		$year = $request->get('year');
		$codi = $request->get('club', '');
		
		$club = null;
		if ($this->isCurrentAdmin() && $codi != '') {  // Admins poden escollir el club
		    $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		} else {
		    $club = $this->getCurrentClub();
		}
		
		$dataconsulta = \DateTime::createFromFormat('d/m/Y', $day.'/'.$month.'/'.$year );
		
		$llistatipus = BaseController::getLlistaTipusParte($club, $dataconsulta, $this->isCurrentAdmin());
		
		$tipuspermesos = "";
		if (count($llistatipus) > 1) $tipuspermesos .= "<option value=''></option>"; // Excepte decathlon i tecnocampus
		
		foreach ($llistatipus as $tipus) {
			$entitytipus = $this->getDoctrine()->getRepository('FecdasBundle:EntityParteType')->find($tipus);
			$tipuspermesos .= "<option value=" . $tipus . ">" . $entitytipus->getDescripcio() . "</option>";
		}
		
		$response = new Response();
		$response->setContent($tipuspermesos);
		
		return $response;
	}  

	private function getTotsTipusParte() {
		$llistatipus = array();
	
		/* Llista tipus parte administrador en funció del club seleccionat. Llista d'un club segons club de l'usuari */
		$club = $this->getCurrentClub();
		if ($club == null) return $llistatipus;  // Sense info del club!!?
	
		$tipuspartes = $club->getTipusparte();
			
		foreach ($tipuspartes as $tipusparte) {
			$llistatipus[$tipusparte->getId()] = $tipusparte->getDescripcio();
		}
	
		return $llistatipus;
	}
	
	public function ajaxpoblacionsAction(Request $request) {
		$search = $this->consultaAjaxPoblacions($request->get('term',''), $request->get('tipus','')); 
		$response = new Response();
		$response->setContent(json_encode($search));
		
		return $response;
	}

}
