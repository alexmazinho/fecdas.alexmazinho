<?php 
namespace FecdasBundle\Controller;

use FecdasBundle\Classes\CSVReader;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use FecdasBundle\Form\FormContact;
use FecdasBundle\Form\FormParte;
use FecdasBundle\Form\FormParteRenovar;
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
	    if ($this->isAuthenticated()) if($redirect = $this->frontEndLoginCheck()) return $redirect;
	    
	    if ($this->isCurrentClub() || $this->isCurrentAdmin()) {
    	    /*$current = $this->getCurrentDate();
    	    $current->setTime(00, 00);
    	    $current->sub(new \DateInterval('P6M')); // 6 Months before*/
    	    $desde = \DateTime::createFromFormat('Y-m-d', date("Y") . "-01-01");
    	    $desde->setTime(00, 00);
    	    
    	    $senseLlicencia = "";
    	    $query = $this->consultaFederatsSenseLlicencia($desde);
    	    foreach ($query->getResult() as $persona) {
                // Sense darrera llicència
    	        if ($persona->getLastLlicencia() == null && 
    	            count($persona->getMetapersona()->getTitulacionsClubAny($this->getCurrentClub(), date("Y"))) == 0) {
    
    	            $senseLlicencia .= " - ".$persona->getNomCognoms()." (".$persona->getDni().")".BR;
    	        }
    	    }
    	    
    	    if ($senseLlicencia != "") {
    	        $senseLlicencia = 'Persones registrades aquest any pendents de tramitació de la llicència vigent i'.BR.'que tampoc han fet cap curs:'.BR.BR.$senseLlicencia;
    	        $this->get('session')->getFlashBag()->add('error-notice',$senseLlicencia); // error-notice sms-notice
    	    }
	    }
		return $this->render('FecdasBundle:Page:index.html.twig', $this->getCommonRenderArrayOptions()); 
	}

	private function consultaFederatsSenseLlicencia($desde) {
	    $em = $this->getDoctrine()->getManager();
	    
	    $club = $this->getCurrentClub();
	    
	    $strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e ";
	    $strQuery .= " WHERE e.databaixa IS NULL ";
	    $strQuery .= " AND e.club = :club ";
	    $strQuery .= " AND e.dataentrada >= :desde ";
	    $strQuery .= " ORDER BY e.cognoms, e.nom ";
	    
	    $query = $em->createQuery($strQuery);
	    
	    $query->setParameter('club', $club->getCodi());
	    $query->setParameter('desde', $desde->format('Y-m-d').' 00:00:00');
	    
	    return $query;
	}
	
	
	public function contactAction(Request $request) {
	    if ($this->isAuthenticated()) if($redirect = $this->frontEndLoginCheck()) return $redirect;
	    
		$contact = new EntityContact();

		$checkRole = $this->get('fecdas.rolechecker');
		
		if ($checkRole->isCurrentFederat() || $checkRole->isCurrentInstructor()) {
		    $user = $checkRole->getCurrentUser();
		    $contact->setEmail($user->getUser());
		    $metapersona = $user->getMetapersona();
		    if ($metapersona != null) $contact->setName($metapersona->getNomCognoms());
		} else {
		    $currentClub = $this->getCurrentClub();
		    if ($currentClub != null) $contact->setName($currentClub->getNom());
		}
		
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
			    
			    $formdata = $request->request->get('contact');
			    if (isset($formdata['telephone']) && $formdata['telephone'] != '') {
			        
			        $this->logEntry($contact->getEmail(), 'CONTACT SPAM', 
			            $request->server->has('REMOTE_ADDR')?$request->server->get('REMOTE_ADDR'):'NO ADDRESS', 
			            $request->server->has('HTTP_USER_AGENT')?$request->server->get('HTTP_USER_AGENT'):'NO AGENT',
			            'subject: '.$contact->getSubject().' tel:'.$formdata['telephone'].' sms: '.$contact->getBody());
			        return $this->redirect($this->generateUrl('FecdasBundle_contact'));
			    }
			    
				$message = \Swift_Message::newInstance()
				->setSubject('::Contacte de Fecdas::'. $form->getData()->getSubject())
				->setFrom($form->getData()->getEmail())
				->setTo(array($this->getParameter('MAIL_FECDAS')))
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
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
	    
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
		
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;

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
				$this->validarDadesPersona($persona, !$estranger);
			
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
	
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest(), true)) return $redirect;
	
		$checkRole = $this->get('fecdas.rolechecker');
    	
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

	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;

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
			$request->getSession()->getFlashBag()->add('sms-notice', 'Ja es poden començar a tramitar les llicències del ' . (date("Y")+1));
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
	
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
	
		if (!$request->query->has('id')) return new Response("");
	
		$parteId = $request->query->get('id');
			
		$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteId);
			
		if (!$parte) return new Response("");
			
		$llicencies = $parte->getLlicenciesSortedByName();
	
		return $this->render('FecdasBundle:Page:partesllicencies.html.twig', array('parte' => $parte, 'llicencies' => $llicencies));
	}

	public function busseigAction(Request $request) {

	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;

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
		
		if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
		
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
	
	public function renovaranualAction(Request $request) {
	    $this->get('session')->getFlashBag()->clear();
	    
	    if ($this->isAuthenticated() != true) {
	        // keep url. Redirect after login
	        $url_request = $request->server->get('REQUEST_URI');
	        $this->get('session')->set('url_request', $url_request);
	        return $this->redirect($this->generateUrl('FecdasBundle_login'));
	    }
	    
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
	    
	    if (!$this->getCurrentClub()->potTramitar()) {
	        $this->get('session')->getFlashBag()->add('error-notice',$this->getCurrentClub()->getInfoLlistat());
	        $response = $this->redirect($this->generateUrl('FecdasBundle_partes', array('club'=> $this->getCurrentClub()->getCodi())));
	        return $response;
	    }

	    $club = null;
	    $codi = '';
	    $anyrenova = $this->getCurrentDate()->format('Y');
	    $p = array();
	    $error = '';
	    $page = 1;
	    $llicenciesAnteriors = 0;
	    $llicenciesPosteriors = 0;
	    $pageSize = 50;
	    $uncheckpersones = '';
	    $anual = true;
	    if ($request->getMethod() == 'POST') {
	        $p = $request->request->get('parte_renovar');
	        
	        $anyrenova = isset($p['anyrenova'])?$p['anyrenova']:$this->getCurrentDate()->format('Y');
	        
	        $codi = isset($p['clubs'])?$p['clubs']:'';
	        
	        $page = isset($p['page'])?$p['page']:1;
	        $uncheckpersones = isset($p['uncheckpersones'])?$p['uncheckpersones']:'';
	    } else {
	        $currentYear = date('Y');
	        $anysRenova = BaseController::getArrayAnysPreus(
	            date('m-d') < BaseController::INICI_TRAMITACIO_QUATRIMESTRE_MES."-".BaseController::INICI_TRAMITACIO_QUATRIMESTRE_DIA?$currentYear - 1:$currentYear,
	            date('m-d') >= BaseController::INICI_TRAMITACIO_QUATRIMESTRE_MES."-".BaseController::INICI_TRAMITACIO_QUATRIMESTRE_DIA?$currentYear:$currentYear
	            );
	        
	        $anyrenova = $request->query->get('anyrenova', reset($anysRenova));
	        
	        $codi = $request->query->get('clubs', ''); // filtra club
	        $page = $request->query->get('page', 1);
	        $uncheckpersones = $request->query->get('uncheckpersones', '');
	    }
	    $uncheckpersones = explode(";", $uncheckpersones);
    
	    if ($this->isCurrentAdmin() && $codi != '') { // Cada club lo seu, els administradors tot
	        $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
	    }
	    
	    if ($club == null) $club = $this->getCurrentClub();
	    $dataalta = \DateTime::createFromFormat('Y-m-d H:i:s', ($anyrenova+1). "-01-01 00:00:00");
	    if ($dataalta->format('Y-m-d') < $this->getCurrentDate()->format('Y-m-d')) $dataalta = $this->getCurrentDate();
	    
	    if ($this->getCurrentDate()->format('Y-m-d') != $dataalta->format('Y-m-d')) {
	        $dataalta->setTime(0, 1); // No és el mateix dia
	    }
	    else {
	        $dataalta->add($this->getIntervalConsolidacio()); // Add 20 minutes
	    }
	    
	    /*if (!$this->validaTramitacioAnySeguent($dataalta)) {
	        $anual = false;
	        $error = 'Encara no es poden tramitar llicències per a l\'any vinent';
	        //$this->logEntryAuth('RENOVAR ANUAL KO', ' Club '.$club->getCodi().' Any '.$anyrenova.' '.$error);
	        $this->get('session')->getFlashBag()->add('error-notice',	$error);
	    }*/
	    
	    // Les llicències es renoven tipus A 
	    $tipus = $this->getDoctrine()->getRepository('FecdasBundle:EntityParteType')->find(BaseController::ID_TIPUS_PARTE_LLICENCIES_A);
	    
	    $parte = $this->crearComandaParte($dataalta, $tipus, $club, 'Renovació anual llicències');
	    
	    // Crear factura
	    $this->crearFactura($parte);

	    $em = $this->getDoctrine()->getManager();
	    
	    $totals = array('total' => 0, 'preu' => 0, 'detalls' => array());
	    
	    if (!$request->isXmlHttpRequest()) {
	        // Accés inicial
	        $form = $this->createForm(new FormParteRenovar($anyrenova, $uncheckpersones), $parte);
	        
	        return $this->render('FecdasBundle:Page:renovaranual.html.twig',
	            $this->getCommonRenderArrayOptions(array('form' => $form->createView(),
	                'parte' => $parte, 'totals' => $totals, 'pagesize' => $pageSize, 'pagination' => null,
	                'anteriors' => $llicenciesAnteriors, 'posteriors' => $llicenciesPosteriors, 'anual' => $anual
	            )));
	    }
	    
	    $llicenciesRenovar = $this->consultaLlicenciesAnualsClub($club, $anyrenova);
	    
	    // Clone llicències
	    $llicenciesCloned = EntityParte::cloneLlicenciesParte($parte, $llicenciesRenovar, $this->getCurrentDate());
	    
	    foreach ($tipus->getCategories() as $categoria) {
	        $totals['detalls'][$categoria->getCategoria()] = 0;
	    }
    
	    // Default page size 50
	    $from = ($page - 1) * $pageSize;   // pàgina 1 comença 0, pàgina 2 comença índex 50
	    $to = $from + $pageSize - 1;
        
	    for ($i = 0; $i < count($llicenciesCloned); $i++) {
	        if ($i < $from) $llicenciesAnteriors++;
	        if ($i > $to) $llicenciesPosteriors++;
	        $totals['total']++;
	        $persona = $llicenciesCloned[$i]->getPersona();
	        $unchecked = in_array($persona->getId(), $uncheckpersones);
	        $llicenciaExistent = $persona->getLastLlicencia($dataalta, $parte->getDatacaducitat());
	        if ($llicenciaExistent == null) {
	            if ($i >= $from && $i <= $to) $parte->addLlicencia($llicenciesCloned[$i]);
	            
	            if (!$unchecked) {
    	            $totals['preu'] += $llicenciesCloned[$i]->getCategoria()->getPreuAny($dataalta->format('Y'));
    	            $totals['detalls'][$llicenciesCloned[$i]->getCategoria()->getCategoria()]++;
	            }
	        } else {
	            if ($i >= $from && $i <= $to) $parte->addLlicencia($llicenciesCloned[$i]);
	            if (!$unchecked) $uncheckpersones[] = $persona->getId();
	        }
	        
        }	    
        //$pagination = array_fill(0, $totals['total'], '');
        $pagination = null;
        if ($totals['total'] > 0) {
            $paginator  = $this->get('knp_paginator');
            
            $pagination = $paginator->paginate(
                array_fill(0, $totals['total'], ''),
                $page,
                $pageSize   /*limit per page*/
            );
        }
	    /*foreach ($llicenciesCloned as $llicencia) {
	        $persona = $llicencia->getPersona();
	        $llicenciaExistent = $persona->getLastLlicencia($dataalta, $parte->getDatacaducitat());
	        if ($llicenciaExistent == null) {
                $parte->addLlicencia($llicencia);
                $totals['total'] += $llicencia->getCategoria()->getPreuAny($dataalta->format('Y'));
	            $totals['detalls'][$llicencia->getCategoria()->getCategoria()]++;
	        } else {
                $parte->addLlicencia($llicencia);
	        }
	    }*/
	    
	    // Form
        $form = $this->createForm(new FormParteRenovar($anyrenova, $uncheckpersones), $parte);
	    try {
	        if (!$this->validaTramitacioAnySeguent($dataalta)) {
	            $anual = false;
	            throw new \Exception('Encara no es poden tramitar llicències per a l\'any vinent');
	        }
	        $avisos = "";
	        if ($request->getMethod() == 'POST') {
	            //$form->handleRequest($request);
                //if (!$form->isValid()) throw new \Exception('Error validant les dades. Contacta amb l\'adminitrador '.$form->getErrors(true, false) );
	            
	            /*
                 * Validacions  de les llicències
                 */
	            $avisos = array();
                //$llicenciesPerEsborrar = array();
                //$i = 0;
	            $parte->resetLlicencies(); // remove all
	            
                for ($i = 0; $i < count($llicenciesCloned); $i++) {
                    $llicencia = $llicenciesCloned[$i];
                    $persona = $llicencia->getPersona();
                    $unchecked = in_array($persona->getId(), $uncheckpersones);
                    
                    try {
                        if (!$unchecked) {
                            $parte->addLlicencia($llicenciesCloned[$i]);
                            $this->validaParteLlicencia($parte, $llicencia);
                            $em->persist($llicencia);
                            $this->addParteDetall($parte, $llicencia);
                        }
                    } catch (\Exception $e) {
                        if (!in_array($e->getMessage(), $avisos)) $avisos[] = $e->getMessage();
                    }
                }
                
                if (count($parte->getLlicencies()) == 0) $avisos[] = 'No hi ha cap llicència per renovar';
                
                /*                
                foreach ($parte->getLlicencies() as $llicencia) {
                    try {
                        if (!isset($p['llicencies'][$i]['renovar'])) {
    	                   // Treure llicències que no es volen renovar
                            $llicenciesPerEsborrar[] = $llicencia;
                        } else {
                            $this->validaParteLlicencia($parte, $llicencia);
                            
                            $em->persist($llicencia);
                            $this->addParteDetall($parte, $llicencia);
                        }
                    } catch (\Exception $e) {
                        $avisos[] = $e->getMessage();
                    }
                    $i++;
                }
                */
                if (count($avisos) > 0) throw new \Exception(implode(BR, $avisos));
                /*
                foreach ($llicenciesPerEsborrar as $llicenciaEsborrar) {
                    $parte->removeLlicencia($llicenciaEsborrar);
                }*/
                
                
                $parte->setComentaris('Renovació anual llicències'.' '.$parte->getComentariDefault());
	            $em->flush();
	            $this->logEntryAuth('RENOVAR ANUAL OK', $parte->getId());
	                
                $this->get('session')->getFlashBag()->add('sms-notice',	'Llicències renovades correctament');
	            
                $resposta = array(
                    'url'       => $this->generateUrl('FecdasBundle_parte', array('id' => $parte->getId(), 'action' => 'view')),
                    'error'     => '',
                    'data'      => '',
                    'dataalta'  => ''
                );
                $response = new Response(json_encode($resposta));
                
                return $response;
	        }
	    } catch (\Exception $e) {
	        $em->clear();
	            
	        $this->logEntryAuth('RENOVAR ANUAL KO', ' Club '.$club->getCodi().' Any '.$anyrenova.' '.$e->getMessage());
	    
	        $error = $e->getMessage();
	    }
	    
        $resposta = array(
            'url'  => '',
            'error'=> $error, 
            'dataalta' => $parte->getDataalta()->format('d/m/Y'),
            'data' => $this->renderView('FecdasBundle:Page:renovaranualtaula.html.twig',
                $this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'parte' => $parte, 'totals' => $totals, 
                                                                    'pagesize' => $pageSize, 'pagination' => $pagination, 'anual' => $anual,
                                                                    'anteriors' => $llicenciesAnteriors, 'posteriors' => $llicenciesPosteriors
                        )))
        );
        $response = new Response(json_encode($resposta));
	        
        return $response;
	    
	    /*return $this->render('FecdasBundle:Page:renovaranual.html.twig',
	        $this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'parte' => $parte, 'totals' => $totals)));*/
	}

	
	protected function consultaLlicenciesAnualsClub($club, $anyrenova) {
	    
	    $em = $this->getDoctrine()->getManager();
	        
	    $inicianual = $anyrenova.'-01-01 00:00:00';
	    $finalanual = $anyrenova.'-12-31 23:59:59';
	        
	    // Crear índex taula partes per data entrada
	    /*
	     * SELECT * FROM m_llicencies l JOIN m_partes p ON p.id = l.parte JOIN m_comandes c ON c.id = p.id 
	     * JOIN m_tipusparte t ON p.tipus = t.id JOIN m_persones e ON e.id = l.persona 
	     * WHERE l.databaixa IS NULL AND c.databaixa IS NULL AND t.actiu = 1 AND t.es365 = 0 AND t.admin = 0 
	     * AND c.club = 'CAT162' AND p.dataalta >= '2018-01-01 00:00:00' AND p.dataalta <= '2018-12-31 23:59:59' 
	     * ORDER BY e.cognoms, e.nom 
	     */
	    $strQuery = "SELECT l FROM FecdasBundle\Entity\EntityLlicencia l JOIN l.parte p JOIN p.tipus t ";
	    $strQuery .= " JOIN l.persona e ";
	    $strQuery .= " WHERE l.databaixa IS NULL AND p.databaixa IS NULL ";
	    $strQuery .= " AND t.actiu = 1 AND t.es365 = 0 AND t.admin = 0 ";
	    $strQuery .= " AND p.club = :club AND p.dataalta >= :inicianual AND p.dataalta <= :finalanual ";
	    $strQuery .= " ORDER BY e.cognoms, e.nom ";
	        
	    $query = $em->createQuery($strQuery)
	                ->setParameter('club', $club != null?$club->getCodi():'')
        	        ->setParameter('inicianual', $inicianual)
        	        ->setParameter('finalanual', $finalanual);
	            
        return $query->getResult();
	}
	
	
	public function parteAction(Request $request) {

		if ($request->query->has('source') == false) $this->get('session')->getFlashBag()->clear(); // No ve de renovació
		
		if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;

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
	
		//if (!$request->isXmlHttpRequest())  return new Response("<div class='sms-notice'>Error d'accés</div>");
	
		if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
		
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

	public function tramitaciollicenciaAction(Request $request) {
	    
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest(), true)) return $redirect;    // Accés federats
	    
	    $checkRole = $this->get('fecdas.rolechecker');
	    $user = $checkRole->getCurrentUser();
	    
	    $em = $this->getDoctrine()->getManager();
	    
	    $metapersona = $user->getMetapersona();
	    
	    $llicenciesVigents = $metapersona->getLlicenciesSortedByDate(false, true, $this->getCurrentDate(), $this->getCurrentDate());
	    foreach ($llicenciesVigents as $vigent) {
	        if ($vigent->getParte()->comandaUsuari() && $vigent->getParte()->getPendent())
	            return $this->redirect($this->generateUrl('FecdasBundle_llicenciesfederat'));
	    }
	    
	    if ($request->getMethod() != 'POST') $this->logEntryAuth('TRAMITA LLICENCIA', $user->getUser());

	    $dataalta = $this->getCurrentDate();
	    //$dataalta->add($this->getIntervalConsolidacio()); // Add 20 minutes
	    $tipus = $this->getDoctrine()->getRepository('FecdasBundle:EntityParteType')->find(BaseController::ID_TIPUS_PARTE_LLICENCIES_F);
	    //$club = $this->getCurrentClub();
	    $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find(BaseController::CODI_CLUBLLICWEB);
	    
	    $parte = $this->crearComandaParte($dataalta, $tipus, $club, 'Tramitació llicència usuari');
	    
	    $parte->setUsuari($user);
	    
	    $this->crearFactura($parte);

	    $persona = $metapersona->getPersona($club);
	    
	    // Noves llicències, permeten edició no pdf
	    $llicencia = $this->prepareLlicencia(BaseController::ID_TIPUS_PARTE_LLICENCIES_F, $parte->getDataCaducitat());
	    $em->persist($llicencia);
	    $parte->addLlicencia($llicencia);
	    
	    $llicencia->setPersona($persona);
	    
	    $formllicencia = $this->createForm(new FormLlicencia($this->isCurrentAdmin()),$llicencia);
	    
        $formllicencia->get('datacaducitatshow')->setData($formllicencia->get('datacaducitat')->getData());
	    
        if ($request->getMethod() == 'POST') {
            // POST
            try {
                $formllicencia->handleRequest($request);
                if (!$formllicencia->isValid()) throw new \Exception('Error validant les dades de la llicència: '.$formllicencia->getErrors(true, false). ', poseu-vos en contacte amb la Federació');
                
                // Errors generen excepció
                $this->validaParteLlicencia($parte, $llicencia);
                $llicencia->setDatamodificacio($this->getCurrentDate());
                
                $this->addParteDetall($parte, $llicencia);
                $parte->setPendent(true);
                // Comprovació datacaducitat
                if ($llicencia->getDatacaducitat()->format('d/m/Y') != $parte->getDataCaducitat()->format('d/m/Y')) {
                    $llicencia->setDatacaducitat($parte->getDataCaducitat());
                }
                
                $em->flush();
                
                $this->get('session')->getFlashBag()->add('sms-notice', 'Llicència tramitada correctament. Cal procedir amb el pagament');
                
                $this->logEntryAuth('PAGAR LLICENCIA', $user->getUser().' Parte:' . $parte->getId() . ' llicencia: ' . $llicencia->getId());
                
                return $this->redirect($this->generateUrl('FecdasBundle_pagamentcomanda', array( 'id' => $parte->getId())));
                
                
            } catch (\Exception $e) {
                
                //$em->clear();
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
                
                $this->get('session')->getFlashBag()->add('error-notice', $e->getMessage(). ', poseu-vos en contacte amb la Federació');
                
                $this->logEntryAuth('PAGAR LLICENCIA KO', $user->getUser().' Error: '.$e->getMessage());
            }
        }
        
        
	    return $this->render('FecdasBundle:Page:tramitaciollicencia.html.twig',
	        $this->getCommonRenderArrayOptions(array('llicencia' => $formllicencia->createView())));

	}
	
	public function plasticllicenciesAction(Request $request) {
	    
	    $action = $request->query->get('action', '');
	    
	    $parteid = $request->query->get('id', 0);
	    
	    $idsLlicencies = $request->query->get('llicencies', '');

	    $arrayIdsLlicencies = array();
	    if ($idsLlicencies != '') {
	        $idsLlicencies = urldecode($idsLlicencies);
	        
	        $arrayIdsLlicencies = json_decode($idsLlicencies); // Array
	    }
	    
	    $parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
	    
	    $total = 0;
	    $extra = array();
	    foreach ($parte->getLlicencies() as $llicencia) {
	        $key = array_search($llicencia->getId(), $arrayIdsLlicencies);
	        
	        if ($key !== false && !$llicencia->esBaixa() && !$llicencia->getImprimir() && !$llicencia->getImpresa()) { // Trobat, no és baixa i no marcada per imprimir
	            $total++;
	            $llicencia->setImprimir(true);
	            $extra[] = $llicencia->getPersona()->getNomCognoms();
	        } 
	    }
	    // Buidar carrito
	    $cartcheckout = $this->get('fecdas.cartcheckout');
	    $cartcheckout->initSessionCart();
	    
	    try {
	        //if (!$this->isAuthenticated()) throw new \Exception("Acció no permesa. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació");
	        if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
	        
	        $cartcheckout->addProducteToCart(BaseController::PRODUCTE_IMPRESS_PLASTIC_ID, $total, $extra);
	    
	        if ($action == 'consultar') {
	            
	           $formtransport = $cartcheckout->formulariTransport();
	           
	           return $this->render('FecdasBundle:Facturacio:graellaproductescistellaform.html.twig',
	               array('formtransport' => $formtransport, 'cart' => $cartcheckout->getSessionCart(), 'tipus' => BaseController::TIPUS_PRODUCTE_ALTRES, 
	                     'allowremove' => false, 'admin' => $this->isCurrentAdmin()));  
	        }
	        // Tramitar comanda
	        $request->query->set('tipus', BaseController::TIPUS_PRODUCTE_ALTRES);
	        $request->query->set('club', $parte->getClub()->getCodi());
	        
	        $response = $this->forward('FecdasBundle:Facturacio:tramitarcistella', array(
	            'request'  => $request
	        ));
	    } catch (\Exception $e) {
	        // Ko, mostra form amb errors
	        $response = new Response($e->getMessage());
	        $response->setStatusCode(500);
	    }
	    
	    return $response;
	}
	
	public function baixallicenciesAction(Request $request) {

	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;

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

	
	public function llicenciespermailbulkAction($persones = array(), $club = null) {
	    
	    if (!$this->getCurrentClub()->potTramitar()) {
	        $response = new Response('Per poder fer aquest tràmit cal us poseu en contacte amb la Federació');
	        $response->setStatusCode(500);
	        return $response;
	    }
	    
	    if (!$this->isCurrentAdmin() || $club == null) $club = $this->getCurrentClub();
	    
        // CREAR FORMULARI federats amb checkbox filtrats opcionalment per nom
        $llicenciesId = array();
        foreach ($persones as $persona) {
            $vigent = $persona->getLlicenciaVigent();
            // Llicència vigent pertany al club i es pot enviar per correu
            if ($vigent != null && 
                $vigent->getParte()->getClub() == $club && 
                $vigent->getParte()->perEnviarFederat()) {
                    $llicenciesId[] = $vigent->getId();
                }
        }
	            
        // CREAR FORMULARI federats amb checkbox filtrats opcionalment per nom
        $formBuilder = $this->createFormSortidaLlicencies('mail', null, 0, $llicenciesId, '', false);
        
        $this->get('session')->getFlashBag()->add('sms-notice', 'Les llicències de les persones sense correu s\'enviaran a l\'adreça de correu del club ');
        
	    $this->logEntryAuth('MAIL LLICENCIES FORM BULK', $club->getNom());
	    
	    // Temps des de la darrera llicència
	    $form = $formBuilder->getForm();
	    
	    return $this->render('FecdasBundle:Admin:sortidallicenciesform.html.twig',
	        $this->getCommonRenderArrayOptions( array( 'form' => $form->createView(),
	            'action' => $this->generateUrl('FecdasBundle_llicenciespermail'),
	            'includetaula' => 'FecdasBundle:Admin:sortidallicenciesformtaulamail.html.twig',
	            'club' => $club,
	            'showFiltre' => true, 'filtre' => '' ) )
	        );
	}
	
	
	public function llicenciespermailAction(Request $request) {
	        
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
	    
	    if (!$this->getCurrentClub()->potTramitar()) {
	        $response = new Response('Per poder fer aquest tràmit cal us poseu en contacte amb la Federació');
	        $response->setStatusCode(500);
	        return $response;
	    }
	    
	    $em = $this->getDoctrine()->getManager();
	    
	    $parteid = 0;
	    $llicenciaid = 0;
	    if ($request->getMethod() == 'POST') {
	        $formdata = $request->request->get('form');
	        $parteid = isset($formdata['id'])?$formdata['id']:0;
	    } else {
	        $parteid = $request->query->get('id', 0);
	        $llicenciaid = $request->query->get('llicencia', 0);
	    }
	    
	    $parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
	    
	    if ($parte != null) $club = $parte->getClub();
	    else $club = $this->getCurrentClub();
        
	        
        try {
            //if ($parte == null) throw new \Exception ('Llista no trobada');
	            
            if (!$this->isCurrentAdmin() && $parte != null && !$parte->comandaPagada() && $club->getSaldo() < 0) throw new \Exception ('Llista pendent de pagament. Poseu-vos en contacte amb la Federació');
            
            if ($parte != null && !$parte->perEnviarFederat()) throw new \Exception ('Aquest tipus de llicència no es pot enviar per correu. Poseu-vos en contacte amb la Federació');
	            
            if ($request->getMethod() == 'POST') {
                if (!isset($formdata['llicencies'])) throw new \Exception ('No hi ha cap llicència seleccionada');
                
                $llicencies = $formdata['llicencies'];      // PHP límit max_input_vars = 5000. El formulari té 6 camps per llicència => 166 llicències max.
                // Valor canviat a 3000 => 500 llicències
                $enviades = 0;
                $res = '';
                $log = '';
                foreach ($llicencies as $llicenciaArray) {
                    $llicenciaId = $llicenciaArray['id'];
                    
                    if (isset($llicenciaArray['enviar']) && $llicenciaArray['enviar'] == 1) {
	                        
                        $llicencia = $this->getDoctrine()->getRepository('FecdasBundle:EntityLlicencia')->find($llicenciaId);
	                        
                        if ($llicencia != null) {
                            $this->enviarMailLlicencia($request, $llicencia);
	                            
                            $enviades++;
                            $res .= $llicenciaArray['nom']. ' '.($llicenciaArray['mail'] != ''?$llicenciaArray['mail']:'(Correu del club) '.$club->getMail()).'</br>';
                            $log .= $llicenciaArray['id'].' - '.$llicenciaArray['nom']. ' '.$llicenciaArray['mail'].' ; ';
                        }
                        
                    }
                }
                
                if ($enviades == 0)  throw new \Exception ('No s\'ha enviat cap llicència digital');
                // Marcar el parte com enviat (imprès)
                if ($parte != null) $parte->setDatamodificacio($this->getCurrentDate());
                $em->flush();
	                
                $this->logEntryAuth('MAIL LLICENCIES OK', 'club'.$club->getNom().  ' parte ' . $parteid . ' enviades  '.$enviades.' : '.$log );
	                
                if ($enviades > 1)  $res = 'Total de llicències enviades '.$enviades.'<br/>'.$res;
                else $res = 'Llicència enviada a '.$res;
                
                return new Response($res);
            } else {
                // CREAR FORMULARI federats amb checkbox filtrats opcionalment per nom
                $formBuilder = $this->createFormSortidaLlicencies('mail', $parte, $llicenciaid, array(), '', false);
                
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
	        
        return $this->render('FecdasBundle:Admin:sortidallicenciesform.html.twig',
            $this->getCommonRenderArrayOptions( array( 'form' => $form->createView(),
	                'action' => $this->generateUrl('FecdasBundle_llicenciespermail'),
	                'includetaula' => 'FecdasBundle:Admin:sortidallicenciesformtaulamail.html.twig',
                    'club' => $club,
	                'parte' => $parte, 'showFiltre' => ($llicenciaid == 0), 'filtre' => '' ) )
            );
	}
	
	public function taulallicenciesfiltreAction(Request $request) {
	    
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
	    
	    if (!$this->getCurrentClub()->potTramitar()) {
	        $response = new Response('Per poder fer aquest tràmit cal que us poseu en contacte amb la Federació');
	        $response->setStatusCode(500);
	        return $response;
	    }
	    
	    if ($request->getMethod() != 'POST') {
	        $response = new Response('Petició incorrecta. Cal que us poseu en contacte amb la Federació');
	        $response->setStatusCode(500);
	        return $response;
	    }
	        
	    $formdata = $request->request->get('form');
       
	    $action = isset($formdata['action'])?$formdata['action']:'mail';
	    $parteid = isset($formdata['id'])?$formdata['id']:0;
	    $llicenciaid = isset($formdata['llicenciaid'])?$formdata['llicenciaid']:0;
	    $llicenciesIdJson = isset($formdata['llicenciesid'])?$formdata['llicenciesid']:'';
	    $llicenciesId = json_decode($llicenciesIdJson);
	    $filtre = isset($formdata['filtre'])?$formdata['filtre']:'';
	    $checkall = isset($formdata['checkall']) && $formdata['checkall']==1?true:false;

	    $parte = null;
	    if ($parteid > 0) $parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
	    
	    // CREAR FORMULARI federats amb checkbox filtrats opcionalment per nom
	    $formBuilder = $this->createFormSortidaLlicencies($action, $parte, $llicenciaid, $llicenciesId, $filtre, $checkall);
	 
	    $form = $formBuilder->getForm();
	    
	    if ($action == 'mail') {
    	    return $this->render('FecdasBundle:Admin:sortidallicenciesformtaulamail.html.twig',
    	        $this->getCommonRenderArrayOptions( array( 'form' => $form->createView(), 'parte' => $parte, 'showFiltre' => true, 'filtre' => $filtre ) )
    	    );
	    }
	    
	    return $this->render('FecdasBundle:Admin:sortidallicenciesformtaulaimpressio.html.twig',
	        $this->getCommonRenderArrayOptions( array( 'form' => $form->createView(), 'parte' => $parte,'showFiltre' => true,  'filtre' => $filtre ) )
	    );
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
				    $club = $this->getCurrentClub();
				    $codi = $request->request->get('club', $club->getCodi());
				    if ($this->isCurrentAdmin() && $codi != $club->getCodi()) {
				        $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
				    }
				    
				    $persona = new EntityPersona($metapersona, $club);
					$options['edit'] = true;
					$em->persist($persona);
				}
				
				$formpersona = $this->createForm(new FormPersona($options), $persona);
			
				$formpersona->handleRequest($request);
			
				if ($formpersona->isValid()) {
					
					$baixaPersona = $request->request->get('action') != 'save';
					
					if (!$baixaPersona) {
						
						$estranger = ( isset($p['estranger']) && $p['estranger'] == 1 )?true:false;
						
						$this->validarDadesPersona($persona, !$estranger, $formpersona);

						$foto = $formpersona->get('fotoupld')->getData();
						$this->gestionarArxiuPersona($persona, false, $foto, true);
						
						$arxiu = $formpersona->get('arxiuupld')->getData();
						$this->gestionarArxiuPersona($persona, false, $arxiu);
						
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
		    
		    $club = $this->getCurrentClub();
		    
		    if ($request->getMethod() == 'POST') $codi = $request->request->get('club', $club->getCodi());
		    else $codi = $request->query->get('club', $club->getCodi());
  
		    if ($this->isCurrentAdmin() && $codi != $club->getCodi()) {
		        $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		    }
		    
			$metapersona = new EntityMetaPersona();
			$em->persist($metapersona);
			$persona = new EntityPersona($metapersona, $club);
			$em->persist($persona);
			$options['edit'] = true;
			//$persona->setSexe("H");
			$persona->setAddrnacionalitat("ESP");
		}	
		if ($formpersona == null) $formpersona = $this->createForm(new FormPersona($options), $persona);

		return $this->render('FecdasBundle:Page:persona.html.twig',
					array('formpersona' => $formpersona->createView(), 'persona' => $persona, 'admin' => $this->isCurrentAdmin()));
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
	
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
		
		$em = $this->getDoctrine()->getManager();
		 
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'd.datapeticio');
		$direction = $request->query->get('direction', 'desc');
		$totes = $request->query->get('totes', 0) == 1?true:false;
		
		$currentClub = $this->getCurrentClub()->getCodi();
		
		if ($request->getMethod() != 'POST') $this->logEntryAuth('VIEW DUPLICATS', 'club ' . $currentClub);
		
		$duplicat = $this->crearComandaDuplicat();
		$detall = null;
		$detallTransport = null;
		$factura = null;
		
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
				    $transport = (isset($data['transport']) && $data['transport'] == 0?true:false);
			    
					//$duplicat->setClub($this->getCurrentClub());
					//$duplicat->setDatapeticio($this->getCurrentDate());
					
					// Carnets llicències sense títol, la resta amb títol corresponent 
					if ($duplicat->getTitol() == null && $duplicat->getCarnet()->getId() != 1) throw new \Exception('Cal indicar un títol');  
					if ($duplicat->getTitol() != null && $duplicat->getCarnet()->getId() == 1) throw new \Exception('Dades del títol incorrectes'); 

					if ($duplicat->getCarnet()->validarLlicenciaVigent()) {
					    /* Validar duplicat llicència, llicència vigent */
					    $vigent = $duplicat->getPersona()->getLlicenciaVigent();
					    if ($vigent == null) throw new \Exception('Per poder tramitar el duplicat cal que aquest federat disposi de llicència vigent'); 
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
					
					$detall = $this->addDuplicatDetall($duplicat);

					if ($transport) {
					    $detallTransport = $this->addTransportToComanda($duplicat);
					}

					$factura = $this->crearFactura($duplicat, $this->getCurrentDate(), $duplicat->getComentariDefault());
					
					$em->flush();
					
					// Buidar carrito
					$cartcheckout = $this->get('fecdas.cartcheckout');
					$cartcheckout->initSessionCart();
					
					// Enviar notificació mail
					$subject = ":: Petició de duplicat. " . $duplicat->getCarnet()->getTipus() . " ::";
					
					$tomails = array();
					if ($duplicat->getCarnet()->esLlicencia()) $tomails[] = $this->getParameter('MAIL_LLICENCIES'); 
					else $tomails[] = $this->getParameter('MAIL_FECDAS');
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
					if ($detall != null) $em->detach($detall);
					if ($detallTransport != null) $em->detach($detallTransport);
					if ($factura != null) $em->detach($factura);
					
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
			$strQuery .= " WHERE d.club = :club AND d.databaixa IS NULL ORDER BY d.datapeticio";  
			$query = $em->createQuery($strQuery)
				->setParameter('club', $currentClub);
		} else {
			if ($totes == false) $strQuery .= " WHERE d.dataimpressio IS NULL AND d.databaixa IS NULL AND d.finalitzat = 0 "; 

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
		
		$cartcheckout = $this->get('fecdas.cartcheckout');
		$formtransport = $cartcheckout->formulariTransport();
		
		return $this->render('FecdasBundle:Page:duplicats.html.twig',
		    $this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'formtransport' => $formtransport, 'duplicats' => $duplicats,
						'sortparams' => array('sort' => $sort,'direction' => $direction))
						));
	}
	
	public function duplicatsformAction(Request $request) {
		// retorna els camps del formulari de duplicats de la petició   	
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
	    
		if (!$request->query->has('carnet') ||
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
		
		$cartcheckout = $this->get('fecdas.cartcheckout');
		$cartcheckout->initSessionCart();
		
		$producte = $carnet->getProducte();
		$cartcheckout->addProducteToCart($producte->getId(), 1);
		
		$form = $this->createForm(new FormDuplicat(array('persona' => $persona, 'carnet' => $carnet, 'foto' => $fotocarnet)), $duplicat);   // Només select titols
		
		$formtransport = $cartcheckout->formulariTransport();
		
		return $this->render('FecdasBundle:Page:duplicatsform.html.twig', $this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'formtransport' => $formtransport))); 
	} 
	
	private function allowEdit(EntityParte $parte) {
		return (boolean) ($parte->getDatapagament() == null); // Allow edition
	}

	private function prepareLlicencia($tipusparteId, $datacaducitat) {
		//dummy llicencia by default
		$llicencia = new EntityLlicencia($this->getCurrentDate());
		$llicencia->name = 'llicencia-nova';
		$llicencia->setDatacaducitat($datacaducitat);
		
		$llicencia->setImprimir(false); // Per defecte no

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
