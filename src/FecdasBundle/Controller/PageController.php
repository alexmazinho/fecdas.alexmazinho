<?php 
namespace FecdasBundle\Controller;

use FecdasBundle\Classes\CSVReader;
use FecdasBundle\Classes\Funcions;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

//use Symfony\Bundle\FrameworkBundle\Controller\Controller;


use FecdasBundle\Form\FormContact;
use FecdasBundle\Form\FormPayment;
use FecdasBundle\Form\FormParte;
use FecdasBundle\Form\FormPersona;
use FecdasBundle\Form\FormLlicencia;
use FecdasBundle\Form\FormDuplicat;
use FecdasBundle\Form\FormParteRenew;
use FecdasBundle\Entity\EntityParteType;
use FecdasBundle\Entity\EntityContact;
use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Entity\EntityPayment;
use FecdasBundle\Entity\EntityUser;
use FecdasBundle\Entity\EntityClub;
use FecdasBundle\Entity\EntityDuplicat;
use FecdasBundle\Entity\EntityCarnet;
use FecdasBundle\Entity\EntityImatge; 
use FecdasBundle\Entity\EntityFactura; 
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
			$form->bind($request);

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
		
		$request->getSession()->getFlashBag()->clear();
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		if (!$this->getCurrentClub()->potTramitar()) {
			$this->get('session')->getFlashBag()->add('error-notice',$this->getCurrentClub()->getInfoLlistat());
			$response = $this->redirect($this->generateUrl('FecdasBundle_partes', array('club'=> $this->getCurrentClub()->getCodi())));
			return $response;
		}
		
		$em = $this->getDoctrine()->getManager();
		
		/* Form importcsv */
		$currentClub = $this->getCurrentClub();
		$dataalta = $this->getCurrentDate('now');
		$currentMonth = $dataalta->format('m');
		$currentDay = $dataalta->format('d');
		$factura = null;
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
						$dataalta->setTime($this->getCurrentDate()->format('H'), $this->getCurrentDate()->format('i') + 20);// Add 20 minutes
					}
					$currentMonth = $dataalta->format('m');
					$currentDay = $dataalta->format('d');
				}
				if (isset($formdata['tipus'])) {
					$tipusparte = $em->getRepository('FecdasBundle:EntityParteType')->find($formdata['tipus']);
				}
		} else {
			//$dataalta->add(new \DateInterval('PT1200S')); // Add 20 minutes
		}
		
		$llistatipus = BaseController::getLlistaTipusParte($this->getCurrentClub(), $currentDay, $currentMonth);
		
		$atributs = array('accept' => '.csv');
		$formbuilder = $this->createFormBuilder()->add('importfile', 'file', array('attr' => $atributs, 'required' => false));
		
		$formbuilder->add('dataalta', 'text', array(
				'read_only' => true,
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
		
		$form = $formbuilder->getForm();
		
		if ($request->getMethod() == 'POST') {
			$form->bind($request);
			
			if ($form->isValid()) {
				$file = $form->get('importfile')->getData();
				
				try {
					if ($file == null) throw new \Exception('Cal escollir un fitxer');
					
					if (!$file->isValid()) throw new \Exception('La mida màxima del fitxer és ' . $file->getMaxFilesize());

					$this->logEntryAuth('IMPORT CSV SUBMIT', $file->getFileName());
					
					if ($dataalta->format('y') > $this->getCurrentDate()->format('y')) {
						// Només a partir 10/12 poden fer llicències any següent
						if ($this->getCurrentDate()->format('m') < self::INICI_TRAMITACIO_ANUAL_MES || 
								($this->getCurrentDate()->format('m') == self::INICI_TRAMITACIO_ANUAL_MES &&
								$this->getCurrentDate()->format('d') < self::INICI_TRAMITACIO_ANUAL_DIA)) 
								throw new \Exception('Encara no es poden tramitar llicències per a l\'any vinent');					
					}
					
					$tipusparte = $form->get('tipus')->getData();
					if ($tipusparte == null) throw new \Exception('Cal indicar un tipus de llista');
					
					$factura = $this->crearFactura($dataalta);
					$parte = $this->crearComandaParte($factura, $dataalta, $tipusparte);

					/* id 4 - Competició --> és la única que es pot fer */
					/* id 9 i 12 - Tecnocampus també es pot fer */
					/*if ($parte->getTipus()->getEs365() == true && $parte->getTipus()->getId() != 4
						&& $parte->getTipus()->getId() != 9 && $parte->getTipus()->getId() != 12) {
						throw new \Exception('El procés de contractació d’aquesta modalitat d’assegurances està suspès temporalment.
						Si us plau, contacteu amb la FECDAS –93 356 05 43– per dur a terme la contractació de la llicència.
						Gràcies per la vostra comprensió.');
					}*/
					/* Fi modificacio 10/10/2014. Missatge no es poden tramitar 365 */
					/* Valida tipus actiu --> és la única que es pot fer */
					if ($parte->getTipus()->getActiu() == false) {
						throw new \Exception('Aquest tipus de llicència no es pot tramitar. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');
					}
					/* Fi modificacio 12/12/2014. Missatge no es poden tramitar */
					

					$errData = $this->validaDataLlicencia($parte->getDataalta(), $parte->getTipus());
					if ($errData != "") throw new \Exception($errData);
					
					if ($form->get('importfile')->getData()->guessExtension() != 'txt'
						or $form->get('importfile')->getData()->getMimeType() != 'text/plain' ) throw new \Exception('El fitxer no té el format correcte');
					
					$temppath = $file->getPath()."/".$file->getFileName();
					
					$this->importFileCSVData($temppath, $parte);					
					
					$this->get('session')->getFlashBag()->add('error-notice','Fitxer correcte, validar dades i confirmar per tramitar les llicències');
					
					$tempname = $this->getCurrentDate()->format('Ymd')."_".$currentClub->getCodi()."_".$file->getFileName();
					
					/* Copy file for future confirmation */
					$file->move($this->getTempUploadDir(), $tempname);
					/* Generate URL to send CSV confirmation */
					
					$urlconfirm = $this->generateUrl('FecdasBundle_confirmcsv', array(
							'tipus' => $parte->getTipus()->getId(), 'dataalta' => $parte->getDataalta()->format('YmdHi'),
							'tempfile' => $this->getTempUploadDir()."/".$tempname
					));
					
					// Redirect to confirm page		
					return $this->render('FecdasBundle:Page:importcsvconfirm.html.twig',
							$this->getCommonRenderArrayOptions(array('parte' => $parte, 'urlconfirm' => $urlconfirm)));
				} catch (\Exception $e) {
					if ($factura != null) $em->detach($factura);
					if ($parte != null) $em->detach($parte);
					
					$this->logEntryAuth('IMPORT CSV KO', $e->getMessage());
					
					$this->get('session')->getFlashBag()->add('error-notice',$e->getMessage());
				}					
			} else {
				// Fitxer massa gran normalment
				$this->logEntryAuth('IMPORT CSV ERROR', "Error desconegut");
				
				$this->get('session')->getFlashBag()->add('error-notice',"Error important el fitxer".$form->getErrorsAsString());
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

		if (!$request->query->has('tipus') or 
			!$request->query->has('dataalta') or !$request->query->has('tempfile'))
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		/* Registre abans de tractar fitxer per evitar flush en cas d'error */
		$this->logEntryAuth('CONFIRM CSV', $request->query->get('tempfile'));
		
		$currentClub = $this->getCurrentClub();
		
		$tipusparte = $request->query->get('tipus');
		$dataalta = \DateTime::createFromFormat('YmdHi', $request->query->get('dataalta'));
		
		$temppath = $request->query->get('tempfile');
		
		try {
			$em = $this->getDoctrine()->getManager();
				
			$tipus = $this->getDoctrine()->getRepository('FecdasBundle:EntityParteType')->find($tipusparte);
			
			$factura = $this->crearFactura($dataalta);
			
			$parte = $this->crearComandaParte($factura, $dataalta, $tipus);
			
			$this->importFileCSVData($temppath, $parte, true);
			$em->flush();
			
			$this->get('session')->getFlashBag()->add('error-notice',"Llicències enviades correctament");
			
			return $this->redirect($this->generateUrl('FecdasBundle_parte', array('id' => $parte->getId(), 'action' => 'view')));
			
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error-notice',$e->getMessage());
		}
		
		/* No hauria de passar mai, el fitxer està validat */
		$urlconfirm = $this->generateUrl('FecdasBundle_confirmcsv', array(
				'tipus' => $parte->getTipus()->getId(), 'dataalta' => $parte->getDataalta()->getTimestamp(),
				'club' => $parte->getClub()->getCodi(), 'tempfile' => $temppath
		));
		
		return $this->render('FecdasBundle:Page:importcsvconfirm.html.twig', 
				$this->getCommonRenderArrayOptions(array('parte' => $parte, 'urlconfirm' => $urlconfirm)));
	}
	
	private function importFileCSVData ($file, $parte, $persist = false) {
		$reader = new CSVReader();
		$reader->setCsv($file);
		$reader->readLayoutFromFirstRow();
		//$reader->setLayout(array('first_name', 'last_name'));
		
		$em = $this->getDoctrine()->getManager();

		// Marcar pendent per a clubs pagament immediat
		if ($persist == true) $em->persist($parte);
		
		$fila = 0;
		
		implode($reader->getLayout());
		
		while($reader->process()) {
			$fila++;
			
			$row = $reader->getRow();
			//our logic here
			if(!isset($row['dni']) or $row['dni'] == null or $row['dni'] == "") throw new \Exception('Hi ha una llicència sense dni (fila: ' . $fila . '), o el format és incorrecte');
						
			if(!isset($row['categoria']) or $row['categoria'] == null) throw new \Exception('Hi ha una llicència sense categoria (DNI: ' . $row['dni'] . ')');
			
			if ($row['categoria'] != 'A' and $row['categoria'] != 'I' and $row['categoria'] != 'T')
				throw new \Exception('Hi ha una llicència amb una categoria incorrecte, els valors vàlids són A, I, T (DNI: ' . $row['dni'] . ')');
			
			$categoria = $em->getRepository('FecdasBundle:EntityCategoria')
					->findOneBy(array('tipusparte' => $parte->getTipus()->getId(), 'simbol' => $row['categoria']));
			
			if ($categoria == null) throw new \Exception('No existeix aquesta categoria per al tipus de llista indicat (DNI: ' . $row['dni'] . ')');
			
			/* Gestionar dades personals */
			$persona = $em->getRepository('FecdasBundle:EntityPersona')->findOneBy(array('dni' => $row['dni'], 'club' => $parte->getClub()->getCodi()));

			if ($persona == null) {
				/* Noves dades personals. Nom, cognoms, data naixement i sexe obligatoris */
				if(!isset($row['nom']) or $row['nom'] == null or $row['nom'] == "") throw new \Exception('Manca el nom de la persona en una llicència (DNI: ' . $row['dni'] . ')');
				if(!isset($row['cognoms']) or $row['cognoms'] == null or $row['cognoms'] == "") throw new \Exception('Manquen els cognoms de la persona en una llicència (DNI: ' . $row['dni'] . ')');
				if(!isset($row['sexe']) or $row['sexe'] == null) throw new \Exception('Manca indicar el sexe de la persona en una llicència (DNI: ' . $row['dni'] . ')');
				if(!isset($row['naixement']) or $row['naixement'] == null or $row['naixement'] == "") throw new \Exception('Manca indicar la data de naixement de la persona en una llicència (DNI: ' . $row['dni'] . ')');
				if(!isset($row['nacionalitat']) or $row['nacionalitat'] == null or $row['nacionalitat'] == "") throw new \Exception('Manca indicar la nacionalitat de la persona en una llicència (DNI: ' . $row['dni'] . ')');
				
				if ($row['sexe'] != 'H' and $row['sexe'] != 'D') 
					throw new \Exception('Manca indicar correctament el sexe de la persona en una llicència, els valors vàlids són H i D (DNI: ' . $row['dni'] . ')');
				
				$nacio = $em->getRepository('FecdasBundle:EntityNacio')->findOneByCodi($row['nacionalitat']);
				
				if ($nacio == null) throw new \Exception('La nacionalitat de la persona és incorrecte (DNI: ' . $row['dni'] . ')');
				
				$datanaixement = \DateTime::createFromFormat('Y-m-d', $row['naixement']);

				if ($datanaixement == null) throw new \Exception('La data de naixement de la persona és incorrecte, el format és YYYY-MM-DD (DNI: ' . $row['dni'] . ')');
				
				$persona = new EntityPersona($this->getCurrentDate());
				$persona->setClub($parte->getClub());
				$persona->setDni($row['dni']);

				$persona->setNom(mb_convert_case($row['nom'], MB_CASE_TITLE, "utf-8"));
				$persona->setCognoms(mb_strtoupper($row['cognoms'], "utf-8"));
				
				
				$persona->setSexe($row['sexe']);
				$persona->setDatanaixement($datanaixement);
				$persona->setAddrnacionalitat($row['nacionalitat']);
				
				if (mb_strtoupper($row['estranger'], "utf-8") == 'N') {
					/* Només validar DNI nacionalitat espanyola */
					$dnivalidar = $row['dni'];
					/* Tractament fills sense dni, prefix M o P + el dni del progenitor */
					if ( substr ($dnivalidar, 0, 1) == 'P' or substr ($dnivalidar, 0, 1) == 'M' ) $dnivalidar = substr ($dnivalidar, 1,  strlen($dnivalidar) - 1);
					
					if ($this->esDNIvalid($dnivalidar) != true) throw new \Exception('El DNI ' . $dnivalidar . ' d\'una de les persones és incorrecte (fila: ' . $fila . ')');
				}
				
				if ($persist == true) $em->persist($persona); 
				
			} else {
				/* Dades personals existents. Nom, cognoms, data naixement i sexe no es modifiquen, la resta s'actualitza segons valors del fitxer */
			}
			
			$persona->setDatamodificacio($this->getCurrentDate());
			
			if (isset($row['telefon1']) and $row['telefon1'] != null and $row['telefon1'] != "") $persona->setTelefon1($row['telefon1']);
			if (isset($row['telefon2']) and $row['telefon2'] != null and $row['telefon2'] != "") $persona->setTelefon2($row['telefon2']);
			if (isset($row['mail']) and $row['mail'] != null and $row['mail'] != "") $persona->setMail($row['mail']);
			if (isset($row['adreca']) and $row['adreca'] != null and $row['adreca'] != "") $persona->setAddradreca($row['adreca']);
			if (isset($row['poblacio']) and $row['poblacio'] != null and $row['poblacio'] != "") $persona->setAddrpob($row['poblacio']);
			if (isset($row['cp']) and $row['cp'] != null and $row['cp'] != "") $persona->setAddrcp($row['cp']);
			if (isset($row['provincia']) and $row['provincia'] != null and $row['provincia'] != "") $persona->setAddrprovincia(mb_convert_case($row['provincia'], MB_CASE_TITLE, "utf-8"));
			if (isset($row['comarca']) and $row['comarca'] != null and $row['comarca'] != "") $persona->setAddrcomarca(mb_convert_case($row['comarca'], MB_CASE_TITLE, "utf-8"));
			
			/* Creació i validació de la llicència */
			
			$llicencia = new EntityLlicencia($this->getCurrentDate());
			$llicencia->setDatamodificacio($this->getCurrentDate());
			$llicencia->setCategoria($categoria);
			$llicencia->setPersona($persona);
			$llicencia->setDatacaducitat($parte->getDatacaducitat($this->getLogMailUserData("importFileCSVData ")));
			
			if ($persist == true) $em->persist($llicencia);
			
			if ($this->validaDNIRepetit($parte, $llicencia) == false) {
				throw new \Exception('Una de les persones ja té una llicència en aquesta llista (DNI: ' . $row['dni'] . ')');
			}

			$parte->addLlicencia($llicencia);

			if ($this->validaLlicenciaInfantil($llicencia) == false) {
				throw new \Exception('L\'edat d\'una de les persones no correspon amb el tipus de llicència (DNI: ' . $row['dni'] . ')');
			}
			
			$parteoverlap = $this->validaPersonaTeLlicenciaVigent($llicencia, $llicencia->getPersona());
			if ($parteoverlap != null) {
				// Comprovar que no hi ha llicències vigents
				// Per la pròpia persona
				throw new \Exception('Una de les persones ja té una llicència per a l\'any actual en aquest club, en data ' .
							$parteoverlap->getDataalta()->format('d/m/Y') . ' (DNI: ' . $row['dni'] . ')');
			}
		} 
		
		if ($fila == 0) throw new \Exception('No s\'ha trobat cap llicència al fitxer');
		
		//$parte->setImportparte($parte->getPreuTotalIVA());  // Canviar preu parte
	}
	
	public function partesAction(Request $request) {

		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		$club = $this->getCurrentClub();
		
		$desdeDefault = "01/01/".(date("Y") - 1);
		$desde = \DateTime::createFromFormat('d/m/Y', $request->query->get('desde', $desdeDefault));
		
		$finsDefault = "31/12/".(date("Y"));
		if (date("m") == self::INICI_TRAMITACIO_ANUAL_MES and date("d") >= self::INICI_TRAMITACIO_ANUAL_DIA) $finsDefault = "31/12/".(date("Y")+1);		
		$fins = \DateTime::createFromFormat('d/m/Y', $request->query->get('fins', $finsDefault));
		
		$tipus = $request->query->get('tipus', 0);
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'p.dataalta');
		$direction = $request->query->get('direction', 'desc');
		
		if ($request->getMethod() == 'POST') {

			return $this->redirect($this->generateUrl('FecdasBundle_parte'));
			
		} else {
			if ($request->query->has('desde') || $request->query->has('fins') || $request->query->has('tipus')) {
				$this->logEntryAuth('VIEW PARTES SEARCH', $club->getCodi()." ".$tipus.":".
									$desde->format('Y-m-d')."->".$fins->format('Y-m-d'));
			}
			else $this->logEntryAuth('VIEW PARTES', $club->getCodi());
		}
		
		if (date("m") == self::INICI_TRAMITACIO_ANUAL_MES and date("d") >= self::INICI_TRAMITACIO_ANUAL_DIA) {
			// A partir 10/12 poden fer llicències any següent
			$request->getSession()->getFlashBag()->add('error-notice', 'Ja es poden començar a tramitar les llicències del ' . (date("Y")+1));
		}
				
		$formBuilder = $this->createFormBuilder()->add('desde', 'text', array(
				'read_only' => true,
				'data' => $desde->format('d/m/Y'),
		));
		$formBuilder->add('fins', 'text', array('read_only'  => true, 'data' => $fins->format('d/m/Y')));
		
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
							'empty_value' => 'Qualsevol...',
							'data' => $tipus,
		));
		
		$query = $this->consultaPartesClub($club->getCodi(), $tipus, $desde, $fins, $sort);
		$paginator  = $this->get('knp_paginator');
		
		$partesclub = $paginator->paginate(
				$query,
				$page,
				10/*limit per page*/
		);
		$partesclub->setParam('desde',$desde->format('d/m/Y'));
		
		
		
		/* Recollir estadístiques */
		$stat = $club->getDadesDesde( $tipus, $desde, $fins );
		$stat['saldo'] = $club->getSaldoweb();
		
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
	
	public function asseguratsAction(Request $request) {
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$currentClub = $this->getCurrentClub()->getCodi();
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'e.cognoms, e.nom');
		$direction = $request->query->get('direction', 'desc');
		$currentDNI = $request->query->get('dni', '');
		$currentNom = $request->query->get('nom', '');
		$currentCognoms = $request->query->get('cognoms', '');
		
		$currentVigent = true;
		if ($request->query->has('vigent') && $request->query->get('vigent') == 0) $currentVigent = false;
		    
		$currentTots = false; // Admins poden cerca tots els clubs
		if ($this->isCurrentAdmin() && $request->query->has('tots') && $request->query->get('tots') == 1) $currentTots = true;
		
		$currentTots = $this->isCurrentAdmin() && $request->query->get('tots', false);
				
		if ($request->getMethod() == 'POST') {
			// Criteris de cerca.Desactivat JQuery 
			$this->logEntryAuth('VIEW PERSONES POST', "club: ". $currentClub." ".$currentNom.", ".$currentCognoms . "(".$currentDNI. ") ".$currentTots);
			
		} else {
			$this->logEntryAuth('VIEW PERSONES', "club: " . $currentClub);
		}
	
		$formBuilder = $this->createFormBuilder()->add('dni', 'search', array('required'  => false, 'data' => $currentDNI)); 
		$formBuilder->add('nom', 'search', array('required'  => false, 'data' => $currentNom));
		$formBuilder->add('cognoms', 'search', array('required'  => false, 'data' => $currentCognoms));
		$formBuilder->add('vigent', 'checkbox', array('required'  => false, 'data' => $currentVigent));
		$formBuilder->add('tots', 'checkbox', array('required'  => false, 'data' => $currentTots) );
		$form = $formBuilder->getForm(); 
	
		$query = $this->consultaAssegurats($currentTots, $currentDNI, $currentNom, $currentCognoms, $currentVigent, $sort);
		$paginator  = $this->get('knp_paginator');
		$persones = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		); 
		/* Paràmetres URL sort i pagination */
		if ($currentDNI != '') $persones->setParam('dni',$currentDNI);
		if ($currentNom != '') $persones->setParam('nom',$currentNom);
		if ($currentCognoms != '') $persones->setParam('cognoms',$currentCognoms);
		if ($currentVigent == false) $persones->setParam('vigent',false);
		if ($currentTots == true) $persones->setParam('tots',true);
		
		return $this->render('FecdasBundle:Page:assegurats.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'persones' => $persones, 
						'sortparams' => array('sort' => $sort,'direction' => $direction)) 
						));
	}
	
	public function historialLlicenciesAction(Request $request) {
		
		if ($this->isAuthenticated() != true) return new Response("");

		if (!$request->query->has('id')) return new Response("");
		
		$em = $this->getDoctrine()->getManager();
				
		$asseguratId = $request->query->get('id');
			
		$persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($asseguratId);
			
		if (!$persona) return new Response("");
			
		if ($this->isCurrentAdmin()) {
			/* !!!!!!!!!!!! Administradors historia de tots els clubs per DNI !!!!!!!!!!!!!!!!!!!! */
			$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
			$strQuery .= " WHERE p.dni = :dni ";
			$strQuery .= " AND p.databaixa IS NULL ";
				
			$query = $em->createQuery($strQuery)->setParameter('dni', $persona->getDni()); 
			$persones = $query->getResult();

			$llicencies = array();
			foreach ($persones as $persona_iter) {
				$llicencies = array_merge($llicencies, $persona_iter->getLlicenciesSortedByDate());
			}
			
			/* Ordenades de última a primera 
			 * SELECT e.dni, COUNT(DISTINCT p.club) FROM m_partes p 
			 * INNER JOIN m_llicencies l ON p.id = l.parte 
			 * INNER JOIN m_persones e ON l.persona = e.id 
			 * GROUP BY e.dni HAVING COUNT(DISTINCT p.club) > 1
			 * */
			usort($llicencies, function($a, $b) {
				if ($a === $b) {
					return 0;
				}
				return ($a->getParte()->getDatacaducitat("getLlicenciesSortedByDate") > $b->getParte()->getDatacaducitat("getLlicenciesSortedByDate"))? -1:1;;
			});
			
		} else {
			$llicencies = $persona->getLlicenciesSortedByDate();			
		}

		return $this->render('FecdasBundle:Page:assegurathistorial.html.twig', array('llicencies' => $llicencies));
		
	}
	
	public function busseigAction(Request $request) {

		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		if ($request->getMethod() == 'POST') {
			$formdata = $request->request->get('form');
			$dni = $formdata['dni'];

			$smsko = 'No hi ha cap llicència vigent per al DNI : ' . $dni;
			$smsok = 'El DNI : ' . $dni . ', té una llicència vigent fins ';
			
			$em = $this->getDoctrine()->getManager();
			
			$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
			$strQuery .= " WHERE p.dni = :dni ";
			$strQuery .= " AND p.databaixa IS NULL ";
			
			$query = $em->createQuery($strQuery)->setParameter('dni', $dni);
			$persones = $query->getResult();

			$trobada = false;

			if (count($persones) == 0) {
				$lastletter = substr($dni, -1);
				$dniprefix = substr($dni, 0, -1);
				if (!is_numeric($lastletter)) {
					// Si el darrer dígit és una lletra es torna a fer la cerca sense lletra
					$query = $em->createQuery($strQuery)->setParameter('dni', $dniprefix);
					$persones = $query->getResult();
				}
			}
			
			if (count($persones) > 0) {
				foreach ($persones as $persona) {
					/* Obtenir llicències encara no caducades per aquesta persona*/
					$strQuery = "SELECT l FROM FecdasBundle\Entity\EntityLlicencia l ";
					$strQuery .= " WHERE l.datacaducitat >= :dataactual ";
					$strQuery .= " AND l.persona = :persona ";
					$strQuery .= " AND l.databaixa IS NULL ";

					$dataactual = $this->getCurrentDate('today');
					$query = $em->createQuery($strQuery)
					->setParameter('dataactual', $dataactual)
					->setParameter('persona', $persona->getId());
					$llicencies = $query->getResult();
					
					if (count($llicencies) > 0) {
						foreach ($llicencies as $llicencia) {
							/* Comprovar si la llicència està vigent i no és futura */
							$inicivigencia = $llicencia->getParte()->getDataalta();
							if ($inicivigencia <= $dataactual) {
								$trobada = true;
								$smsok .= $llicencia->getParte()->getDatacaducitat($this->getLogMailUserData("busseigAction "))->format('d/m/Y');
							}
						}
					}
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
	
		/* Validació impedir modificacions altres clubs */
		if ($this->isCurrentAdmin() != true and $partearenovar->getClub()->getCodi() != $currentClub)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		/* Si abans data caducitat renovació per tot el periode
		 * En cas contrari només des d'ara
		*/
		$dataalta = $this->getCurrentDate('now');
		if ($partearenovar->getDataCaducitat($this->getLogMailUserData("renovarAction 1 ")) >= $dataalta) {
			$dataalta = $partearenovar->getDataCaducitat($this->getLogMailUserData("renovarAction 2 "));
			$dataalta->setTime(00, 00);
			$dataalta->add(new \DateInterval('P1D')); // Add 1
		}

		$factura = $this->crearFactura($dataalta);
		$parte = $this->crearComandaParte($factura, $dataalta, $partearenovar->getTipus(), $partearenovar->getComentaris());
		
		// Clone llicències
		$parte->cloneLlicencies($partearenovar, $this->getCurrentDate());
	
		$form = $this->createForm(new FormParteRenew(), $parte);
		
		$form->get('cloneid')->setData($parteid); 

		$avisos = "";
		if ($request->getMethod() == 'POST') {
			$form->bind($request);

			/* Modificacio 10/10/2014. Missatge no es poden tramitar 365 */
			/* id 4 - Competició --> és la única que es pot fer */
			/*if ($parte->getTipus()->getEs365() == true && $parte->getTipus()->getId() != 4) {
				$this->get('session')->getFlashBag()->clear();
				$this->get('session')->getFlashBag()->add('error-notice',	'El procés de contractació d’aquesta modalitat d’assegurances està suspès temporalment.
						Si us plau, contacteu amb la FECDAS –93 356 05 43– per dur a terme la contractació de la llicència.
						Gràcies per la vostra comprensió.');
			} else {*/
			/* Fi modificacio 10/10/2014. Missatge no es poden tramitar 365 */			
				/* Valida tipus actiu --> és la única que es pot fer */
			if ($parte->getTipus()->getActiu() == false) {
				$this->get('session')->getFlashBag()->add('error-notice', 'Aquest tipus de llicència no es pot tramitar. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');
			} else {
			/* Fi modificacio 12/12/2014. Missatge no es poden tramitar */
	
			if ($form->isValid() && $request->request->has('parte_renew')) {
				$em = $this->getDoctrine()->getManager();
				
				$p = $request->request->get('parte_renew');
				$i = 0; 
				foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
					if (!isset($p['llicencies'][$i]['renovar'])) {
						// Treure llicències que no es volen renovar
						$parte->removeLlicencia($llicencia_iter);
					} else {
						$em->persist($llicencia_iter);
					}
					$i++;
				}
						
				// Marquem com renovat
				$partearenovar->setRenovat(true);
				
				//$parte->setImportparte($parte->getPreuTotalIVA());  // Actualitza preu si escau després de treure llicències
				
				$em->persist($parte);
				$em->flush();

				$this->logEntryAuth('RENOVAR OK', $parte->getId());
				
				$this->get('session')->getFlashBag()->add('error-notice',	'Llista de llicències enviada correctament');
						
				return $this->redirect($this->generateUrl('FecdasBundle_parte', array('id' => $parte->getId(), 'action' => 'view')));
				
			} else {
				$this->get('session')->getFlashBag()->add('error-notice',	'Error validant les dades. Contacta amb l\'adminitrador'.$form->getErrorsAsString());
			}
			/* Modificacio 10/10/2014. Missatge no es poden tramitar 365 */
			/* id 4 - Competició --> és la única que es pot fer */
			}
			/* Fi modificacio 10/10/2014. Missatge no es poden tramitar 365 */
		} else {
			/*
			 * Validacions  de les llicències
			* */
			foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
				// Comprovar que no hi ha llicències vigents
				// Per la pròpia persona
				$parteoverlap = $this->validaPersonaTeLlicenciaVigent($llicencia_iter, $llicencia_iter->getPersona());
				if ($parteoverlap != null) {
					$form->get('llicencies')->get($c)->get('renovar')->setData(false);
					$form->get('llicencies')->get($c)->remove('renovar');
					
					$avisos .= "- El Federat " . $llicencia_iter->getPersona()->getNom() . " " . $llicencia_iter->getPersona()->getCognoms();
					$avisos .= " ja té una llicència vigent en aquest club, en data ";
					$avisos .= $parteoverlap->getDataalta()->format('d/m/Y') . "<br/>";
					continue;
				}
			
				if ($this->validaLlicenciaInfantil($llicencia_iter) == false) {
					$novacategoria = $this->getDoctrine()->getRepository('FecdasBundle:EntityCategoria')
										->findOneBy(array('tipusparte' => $llicencia_iter->getParte()->getTipus()->getId(), 'simbol' => 'A'));
					$llicencia_iter->setCategoria($novacategoria);
					
					$avisos .= "- El Federat " . $llicencia_iter->getPersona()->getNom() . " " . $llicencia_iter->getPersona()->getCognoms();
					$avisos .= " ha canviat de categoria infantil a aficionat<br/>";
					continue;
				}
			}
			/* Modificacio 10/10/2014. Missatge no es poden tramitar 365 */
			/* id 4 - Competició --> és la única que es pot fer */
			/*if ($parte->getTipus()->getEs365() == true && $parte->getTipus()->getId() != 4) {
				$this->get('session')->getFlashBag()->clear();
				$this->get('session')->getFlashBag()->add('error-notice',	'El procés de contractació d’aquesta modalitat d’assegurances està suspès temporalment.
						Si us plau, contacteu amb la FECDAS –93 356 05 43– per dur a terme la contractació de la llicència.
						Gràcies per la vostra comprensió.');
			}*/
			/* Fi modificacio 10/10/2014. Missatge no es poden tramitar 365 */
			/* Valida tipus actiu --> és la única que es pot fer */
			if ($parte->getTipus()->getActiu() == false) {
				$this->get('session')->getFlashBag()->add('error-notice', 'Aquest tipus de llicència no es pot tramitar. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');
			}
			/* Fi modificacio 12/12/2014. Missatge no es poden tramitar */
			

			$this->logEntryAuth('RENOVAR VIEW', $parte->getId() . "-" .$avisos);
		}
			
		return $this->render('FecdasBundle:Page:renovar.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'parte' => $parte, 'avisos' => $avisos)));
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
		$currentClub = $this->getCurrentClub();
		
		if ($request->getMethod() == 'POST') {
			if ($request->request->has('parte')) { 
				$response = $this->forward('FecdasBundle:Page:pagament');  // Pagament continuar
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
			$dataalta->add(new \DateInterval('PT1200S')); // Add 20 minutes
			
			$this->logEntryAuth('PARTE NEW', $parteid);
			
			$factura = $this->crearFactura($dataalta);
			$parte = $this->crearComandaParte($factura, $dataalta);
		}
		
		$form = $this->createForm(new FormParte(), $parte);
		
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
		$detall = null;
		$factura = null;
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
			$p = $requestParams['parte'];
			$l = $requestParams['llicencia'];

			$id = is_numeric($p['id']) && $p['id'] > 0?$p['id']:0;
			$lid = is_numeric($l['id']) && $l['id'] > 0?$l['id']:0;
			
			if (isset($requestParams['currentperson'])) $currentPerson = $requestParams['currentperson'];
				
			if ($lid == 0) {
				// Insert
				if ($id == 0) {
					// Nou parte
					if (!isset($p['dataalta'])) throw new \Exception('Error data alta. Contacti amb la Federació');
					if (!isset($p['tipus'])) throw new \Exception('Error tipus. Contacti amb la Federació</div>');
	
					$partedataalta = \DateTime::createFromFormat('d/m/Y H:i', $p['dataalta']);
					$tipusid = $p['tipus'];
	
					$tipus = $this->getDoctrine()->getRepository('FecdasBundle:EntityParteType')->find($tipusid);
						
					// Crear parte nou per poder carregar llista
					$factura = $this->crearFactura($partedataalta);
					
					$parte = $this->crearComandaParte($factura, $partedataalta, $tipus);
				} else {
					$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($id);
	
					if ($parte == null) throw new \Exception('No s\'ha trobat la llista '.$id);
				}
					
				// Noves llicències, permeten edició no pdf
				$llicencia = $this->prepareLlicencia($tipusid, $parte->getDataCaducitat($this->getLogMailUserData("llicenciaAction  ")));
				$em->persist($llicencia);
				
				$parte->addLlicencia($llicencia);
				
			} else {
				// Cercar llicència a actualitzar
				$llicencia = $this->getDoctrine()->getRepository('FecdasBundle:EntityLlicencia')->find($lid);
					
				if ($llicencia == null) throw new \Exception('No s\'ha trobat la llicència '.$lid);
					
				$parte = $llicencia->getParte();
			}
				
			$tipusid = $parte->getTipus()->getId();
			$partedataalta = $parte->getDataalta();
	
			// Person submitted
			if ($currentPerson > 0) {
				$persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($currentPerson);
				if ($persona == null) throw new \Exception('No s\'ha trobat les dades personals '.$currentPerson);
				$llicencia->setPersona($persona);
			}
				
			if ($request->getMethod() == 'POST' &&
				isset($requestParams['action']) && 
				$requestParams['action'] != 'persona') {
				
				if ($requestParams['action'] == 'remove') {
					// Errors generen excepció
					$this->validaRemoveLlicencia($parte);
					
					$this->removeParteDetall($parte, $llicencia);
					
					$this->get('session')->getFlashBag()->add('sms-notice', 'Llicència esborrada correctament');
					
				} else {
					// Update / insert llicència
					$form = $this->createForm(new FormParte(), $parte);
					$formLlicencia = $this->createForm(new FormLlicencia(),$llicencia);

					$form->bind($request);
					$formLlicencia->bind($request);
	
					if (!$formLlicencia->isValid()) throw new \Exception('Error validant les dades de la llicència: '.$formLlicencia->getErrorsAsString());
						
					if (!$form->isValid()) throw new \Exception('Error validant les dades de la llista: '.$form->getErrorsAsString());
	
					// Errors generen excepció
					$this->validaParteLlicencia($parte, $llicencia);

					$llicencia->setDatamodificacio($this->getCurrentDate());
					
					$detall = $this->addParteDetall($parte, $llicencia);
					
					// Comprovació datacaducitat
					if ($llicencia->getDatacaducitat()->format('d/m/Y') != $parte->getDataCaducitat($this->getLogMailUserData("updateParte  "))->format('d/m/Y')) {
						error_log("DataCaducitat llicencia incorrecte " . $llicencia->getDatacaducitat()->format('d/m/Y') . " " . $parte->getDataCaducitat($this->getLogMailUserData("updateParte 1 "))->format('d/m/Y') , 0);
						$llicencia->setDatacaducitat($parte->getDataCaducitat($this->getLogMailUserData("updateParte 2 ")));
					}
					$this->get('session')->getFlashBag()->add('sms-notice', 'Llicència enviada correctament. Encara es poden afegir més llicències a la llista');
					
				}

				// Generar nova factura si s'ha enviat a comptabilitat. Revisar anulacions etc..
				// Desvincular rebut si escau
				$this->actualitzarFacturaRebut($this->getCurrentDate(), $parte);
				
				$em->flush();

				$this->logEntryAuth('LLICENCIA '.$requestParams['action'].' OK', 'Parte:' . $parte->getId() . ' llicencia: ' . $llicencia->getId());
				
				$response = $this->render('FecdasBundle:Page:partellistallicencies.html.twig',
						array('parte' => $parte, 'admin' =>$this->isCurrentAdmin()));
				
			} else {
				// Mostrar formulari
				$formllicencia = $this->createForm(new FormLlicencia(), $llicencia);
				if ($formllicencia->has('datacaducitatshow') == true)
					$formllicencia->get('datacaducitatshow')->setData($formllicencia->get('datacaducitat')->getData());
	
					$response = $this->render('FecdasBundle:Page:partellicencia.html.twig',
							array('llicencia' => $formllicencia->createView(),
									'asseguranca' => $parte->isAsseguranca(),
									'llicenciadades' => $llicencia));
			}
		} catch (\Exception $e) {
				
			if ($parte != null) {
				if ($parte->esNova()) {
					if ($detall != null) $em->detach($detall);
					if ($factura != null) $em->detach($factura);
			
					$em->detach($parte);
				}
				else {
					if ($detall != null) $em->refresh($detall);
					if ($factura != null) $em->refresh($factura);
						
					$em->refresh($parte);
				}
			}
				
			if ($llicencia != null) {
				if ($llicencia->esNova()) $em->detach($llicencia);
				else $em->refresh($llicencia);
			}

			$this->logEntryAuth('LLICENCIA KO', ' Parte '.$id.'- Llicencia '.$lid.' : ' .$e->getMessage());
	
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
		}
	
		return $response;
	}
	
	private function validaRemoveLlicencia($parte) {
		// No admin
		if (!$this->isCurrentAdmin()) throw new \Exception('Contacteu amb la Federació per anul·lar les llicències');

		// Compte si deixem esborrar als clubs cal activar següents validacions
		
		// Comprovació Pagat
		//if ($parte->comandaPagada() == true) throw new \Exception('No es poden esborrar llicències que ja estan pagades');
		
		// Comprovació comptabilitzat
		//if ($parte->estaComptabilitzada() == true) throw new \Exception('La llista està facturada. Contacteu amb la Federació per anul·lar les llicències');
		

	}
	
	private function validaParteLlicencia($parte, $llicencia) {
		
		$em = $this->getDoctrine()->getManager();

		$dataalta = $parte->getDataalta();
		$tipus = $parte->getTipus();
		$current = $this->getCurrentDate();
		$errData = $this->validaDataLlicencia($dataalta, $tipus);
			
		if ($errData != "") throw new \Exception($errData);
		// NO llicències amb data passada. Excepte administradors
		// Data alta molt aprop de la caducitat.

		if ($dataalta->format('y') > $current->format('y')) {
			// Només a partir 10/12 poden fer llicències any següent
			if ($current->format('m') < self::INICI_TRAMITACIO_ANUAL_MES ||
				($current->format('m') == self::INICI_TRAMITACIO_ANUAL_MES &&
				 $current->format('d') < self::INICI_TRAMITACIO_ANUAL_DIA)) {
				throw new \Exception('Encara no es poden tramitar llicències per a l\'any vinent');
			}
		}
		
		/* Modificacio 10/10/2014. Missatge no es poden tramitar 365 */
		/* id 4 - Competició --> és la única que es pot fer */
		/* id 9 i 12 - Tecnocampus també es pot fer */
		/*if ($parte->getTipus()->getEs365() == true && $parte->getTipus()->getId() != 4
			&& $parte->getTipus()->getId() != 9 && $parte->getTipus()->getId() != 12) {					
			
			$this->get('session')->getFlashBag()->clear();
			$this->get('session')->getFlashBag()->add('sms-notice','El procés de contractació d’aquesta modalitat d’assegurances està suspès temporalment.
				Si us plau, contacteu amb la FECDAS –93 356 05 43– per dur a terme la contractació de la llicència.
				Gràcies per la vostra comprensió.');
			$valida = false;
		}*/
		/* Fi modificacio 10/10/2014. Missatge no es poden tramitar 365 */
		/* Valida tipus actiu --> és la única que es pot fer */
		if ($tipus->getActiu() == false) throw new \Exception('Aquest tipus de llicència no es pot tramitar. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');
		/* Fi modificacio 12/12/2014. Missatge no es poden tramitar */

		// Comprovar data llicències reduïdes. Alta posterior 01/09 any actual
		$datainici_reduida = new \DateTime(date("Y-m-d", strtotime($dataalta->format('Y') . "-09-01")));
		if (($tipus->getId() == 5 or $tipus->getId() == 6) &&
			($dataalta->format('Y-m-d') < $datainici_reduida->format('Y-m-d'))) { // reduïdes
			throw new \Exception('Les llicències reduïdes només a partir de 1 de setembre');
		}
			
		if ($this->validaLlicenciaInfantil($llicencia) == false) throw new \Exception('L\'edat de la persona no correspon amb el tipus de llicència');
						
		if ($this->validaPersonaRepetida($parte, $llicencia) == false) throw new \Exception('Aquesta persona ja té una llicència en aquesta llista');

		// Comprovar que no hi ha llicències vigents 
		// Per la pròpia persona
		$parteoverlap = $this->validaPersonaTeLlicenciaVigent($llicencia, $llicencia->getPersona()); 
		if ($parteoverlap != null) throw new \Exception('Aquesta persona ja té una llicència per a l\'any actual en aquest club, en data ' . 
															$parteoverlap->getDataalta()->format('d/m/Y'));

		$datainiciRevisarSaldos = new \DateTime(date("Y-m-d", strtotime(date("Y") . "-".self::INICI_REVISAR_CLUBS_MONTH."-".self::INICI_REVISAR_CLUBS_DAY)));
			
		if ($current->format('Y-m-d') >= $datainiciRevisarSaldos->format('Y-m-d') && 
			$parte->getClub()->controlCredit() == true) {
			// Comprovació de saldos clubs DIFE
/***************  SALDOS ENCARA NO ************************************************************************************************************************/					
			/*if ($parte->getPreuTotalIVA() > $parte->getClub()->getSaldoweb() + $parte->getClub()->getLimitcredit()) {
					throw new \Exception('L\'import de les tramitacions que heu fet a dèbit en aquest sistema ha arribat als límits establerts.
					Per poder fer noves gestions, cal que contacteu amb la FECDAS');
			}*/
		}
	}
 
	private function validaDataLlicencia(\DateTime $dataalta, $tipus) {
		$avui = $this->getCurrentDate('now');
		if (!$this->isCurrentAdmin() and $dataalta < $avui) return 'No es poden donar d\'alta ni actualitzar llicències amb data passada';
		
		if ($tipus->getEs365() == true and $tipus->getFinal() != null) {
			// Llicències anuals per curs. Si dataalta + 2 mesos > datafinal del tipus vol dir que la intenten donar d'alta quasi quan caduca per error
			$dataClone = clone $dataalta;
			$dataClone->add(new \DateInterval('P2M')); // Add 2 Months
			if ($dataalta->format('m-d') <= $tipus->getFinal() and $dataClone->format('m-d') > $tipus->getFinal()) {
				return 'L\'inici de les llicències està molt proper a la caducitat';
			}
		}
		
		return ''; 
	} 

	public function personaAction(Request $request) {

		$options = array();
		/* Get provincies, comarques, nacions*/
		$options['edit'] = false;
		$options['provincies'] = $this->getProvincies();
		$options['comarques'] = $this->getComarques();
		$options['nacions'] = $this->getNacions();
		
		if ($request->getMethod() == 'POST') {
			$p = $request->request->get('persona', null);
			if ($p == null) return new Response("dnierror");

			if ($p['id'] != "") {
				$persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($p['id']);
				if ($this->isCurrentAdmin()) $options['edit'] = true;  // Admins poden modificar nom i cognoms
				
			} else {
				$persona = new EntityPersona($this->getCurrentDate());
				// Assignar club
				$persona->setClub($this->getCurrentClub());
				$options['edit'] = true;
			}

			$formpersona = $this->createForm(new FormPersona($options), $persona);
			
			$formpersona->bind($request);
		
			if ($formpersona->isValid()) {
				if ($persona->getNom() == "" or $persona->getCognoms() == "") {
					$this->logEntryAuth('PERSONA NEW NOM KO');
					return new Response("nomerror");
				}
				if ($persona->getDni() == "") {
					$this->logEntryAuth('PERSONA NEW DNI KO');
					return new Response("dnierror");
				}
				
				$em = $this->getDoctrine()->getManager();
				$persona->setDatamodificacio($this->getCurrentDate());

				if ($request->request->get('action') == "save") {
					/* Check persona amb dni no repetida al mateix club */
					if ($persona->getId() == null) {
						$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
						$strQuery .= " WHERE p.dni = :dni ";
						$strQuery .= " AND p.club = :club ";
						$strQuery .= " AND p.databaixa IS NULL";
					
						$query = $em->createQuery($strQuery)
							->setParameter('dni', $persona->getDni())
							->setParameter('club', $persona->getClub()->getCodi());
				
						$personaexisteix = $query->getResult();
						
						if (count($personaexisteix) > 0) {
							$this->logEntryAuth('PERSONA NEW DUPLI KO', $persona->getDni());
							return new Response("dnicluberror");
						}
					}
					$persona->setValidat(false);  // No validat, detecció ACCESS

					if ($persona->getId() != null)	{
						$logaction = "PERSONA UPD OK";
						$this->get('session')->getFlashBag()->add('error-notice',	"Dades modificades correctament");
					}
					else {
						// Canviar format Nom i COGNOMS
						$logaction = "PERSONA NEW OK";
						
						// Specials chars ñ, à, etc... 
						$persona->setCognoms(mb_strtoupper($persona->getCognoms(), "utf-8"));
						$persona->setNom(mb_convert_case($persona->getNom(), MB_CASE_TITLE, "utf-8"));
						$this->get('session')->getFlashBag()->add('error-notice', "Dades personals afegides correctament");
					}
					$em->persist($persona);
					$em->flush();
					// Després de flush, noves entitats tenen id
					$this->logEntryAuth($logaction, $persona->getId());
		
					$request->request->set('currentperson', $persona->getId());
				} else { // Esborrar
					// Check si persona té alguna llicència associada
					$llicenciesPersona = $em->getRepository('FecdasBundle:EntityLlicencia')
					->findBy(array('persona' => $persona->getId(), 'databaixa' => null));

					if ($llicenciesPersona != null) { 
						$logaction = "PERSONA DEL KO";
						$this->get('session')->getFlashBag()->add('error-notice',	"Aquesta persona té llicències i no es pot esborrar");
						$request->request->set('currentperson', $persona->getId());
					} else {
						$logaction = "PERSONA DEL OK";
						$persona->setDatamodificacio($this->getCurrentDate());
						$persona->setDatabaixa($this->getCurrentDate());
						$em->persist($persona); // Per delete seria remove
						$em->flush();
						$request->request->set('currentperson', 0);
						$this->get('session')->getFlashBag()->add('error-notice', "Dades personals esborrades correctament");
					}
					
					$this->logEntryAuth($logaction, $persona->getId());
				}
				$request->request->set('action', 'persona');
			} else {
				// get a ConstraintViolationList
				$errors = $this->get('validator')->validate($persona);
				// iterate on it
				foreach ($errors as $error) {
					if ($error->getPropertyPath() == "telefon1" or 
						$error->getPropertyPath() == "telefon2") return new Response("telefonerror");
					if ($error->getPropertyPath() == "mail") return new Response("mailerror");
				}
				return new Response("novaliderror");
			}

			if ($request->request->get('origen') == 'llicencia') { 
				$response = $this->forward('FecdasBundle:Page:llicencia');
				return $response;
			} else {
				return new Response();
			}
		}
		
		if ($request->isXmlHttpRequest()) {
			// Reload form persona
			$persona = new EntityPersona($this->getCurrentDate());
			$options['edit'] = true;
			$persona->setDatanaixement(	new \DateTime(date("Y-m-d", strtotime(date("Y-m-d") . " -40 year"))));
			$persona->setSexe("H");
			$persona->setAddrnacionalitat("ESP");
				
			if ($request->query->get('persona', 0) != 0) { // Select diferent person
				$persona = $this->getDoctrine()
						->getRepository('FecdasBundle:EntityPersona')
						->find($request->query->get('persona'));
				if (!$this->isCurrentAdmin()) $options['edit'] = false;
			}
			$formpersona = $this->createForm(new FormPersona($options), $persona);
			
			return $this->render('FecdasBundle:Page:persona.html.twig',
					array('formpersona' => $formpersona->createView(),));
		}

		return new Response("Error personaAction ");
	}
	
	public function duplicatsAction(Request $request) {
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		
		$em = $this->getDoctrine()->getManager();
		 
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'd.datapeticio');
		$direction = $request->query->get('direction', 'desc');
		
		$currentClub = $this->getCurrentClub()->getCodi();
		$duplicat = new EntityDuplicat();
		$form = $this->createForm(new FormDuplicat(array('club' => $currentClub)), $duplicat);
		
		if ($request->getMethod() == 'POST') {
			$form->submit($request); 
			if ($form->isValid()) {
				try {
					$duplicat->setClub($this->getCurrentClub());
					$duplicat->setDatapeticio($this->getCurrentDate());
					
					// Carnets llicències sense títol, la resta amb títol corresponent 
					if ($duplicat->getTitol() == null and $duplicat->getCarnet()->getId() != 1) throw new \Exception('Cal indicar un títol');  
					if ($duplicat->getTitol() != null and $duplicat->getCarnet()->getId() == 1) throw new \Exception('Dades del títol incorrectes'); 

					$em->persist($duplicat);
					
					// Comprovar canvis en el nom / cognoms
					$nom = "";
					if ($form->has('nom')) $nom = $form->get('nom')->getData();
					$cognoms = "";
					if ($form->has('cognoms')) $cognoms = $form->get('cognoms')->getData();
					
					if ($form->has('fotoupld'))  {
						$file = $form->get('fotoupld')->getData();
						
						if ($file == null) throw new \Exception('Cal carregar una foto per demanar el duplicat');
	
						if (!($file instanceof UploadedFile) or !is_object($file))  throw new \Exception('1.No s\'ha pogut carregar la foto');
							
						if (!$file->isValid()) throw new \Exception('2.No s\'ha pogut carregar la foto ('.$file->isValid().')'); // Codi d'error
						
						$uploaded = $this->uploadAndScale($file, $duplicat->getPersona()->getDni(), 300, 200);
						
						$foto = new EntityImatge($uploaded['path']);
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
					if ($duplicat->getPersona()->getNom() != $nom or
					$duplicat->getPersona()->getCognoms() != $cognoms) {
						$observacionsMail = "<p>Ha canviat el nom, abans " .
								$duplicat->getPersona()->getNom() . " " . $duplicat->getPersona()->getCognoms() ."</p>";
						$duplicat->getPersona()->setNom($nom);
						$duplicat->getPersona()->setCognoms($cognoms);
						$duplicat->getPersona()->setDatamodificacio($this->getCurrentDate());
						$duplicat->getPersona()->setValidat(false);
					}
					
					/* Crear factura */
					$factura = $this->crearFactura($this->getCurrentDate(), $duplicat, $duplicat->getTextCarnet(false)." ".$duplicat->getPersona()->getCognomsNom());
							
					$em->flush();
					
					// Enviar notificació mail
					$subject = ":: Petició de duplicat. " . $duplicat->getCarnet()->getTipus() . " ::";
					
					//$tomails = $this->getLlicenciesMails();
					$tomails = array();
					if ($duplicat->getCarnet()->esLlicencia() == true) $tomails = $this->getLlicenciesMails(); // Llicències Remei
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
					
					$this->get('session')->getFlashBag()->add('error-notice',"Petició enviada correctament");
					
				} catch (\Exception $e) {
					$em->detach($duplicat);
					
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
				$this->logEntryAuth('INVALID DUPLICAT', 'club ' . $currentClub . ' ' .$form->getErrorsAsString());
				
				$this->get('session')->getFlashBag()->add('error-notice',"Dades incorrectes .".$form->getErrorsAsString());
			}

			/* reenvia pàgina per evitar F5 */
			return $this->redirect($this->generateUrl('FecdasBundle_duplicats', array('sort' => $sort,'direction' => $direction)));
		} else { 
			$this->logEntryAuth('VIEW DUPLICATS', 'club ' . $currentClub); 
		}
		
		$strQuery = "SELECT d, p, c FROM FecdasBundle\Entity\EntityDuplicat d JOIN d.persona p JOIN d.carnet c";
		/* Administradors totes les peticions, clubs només les seves*/
		if (!$this->isCurrentAdmin()) {
			$strQuery .= " WHERE d.club = :club ORDER BY d.datapeticio";  
			$query = $em->createQuery($strQuery)
				->setParameter('club', $currentClub);
		} else {
			$strQuery .= " ORDER BY d.datapeticio";
			$query = $em->createQuery($strQuery);
		}
		
		$paginator  = $this->get('knp_paginator');
		$duplicats = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		);
		
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
		
		$duplicat = new EntityDuplicat();
		$carnet = $em->getRepository('FecdasBundle:EntityCarnet')->find($request->query->get('carnet'));
		$persona = $em->getRepository('FecdasBundle:EntityPersona')->find($request->query->get('persona'));
		$duplicat->setCarnet($carnet);
		$duplicat->setPersona($persona);
		$fotocarnet = false;
		
		if ($carnet != null and $carnet->getFoto() == true) $fotocarnet = true;
			
		$form = $this->createForm(new FormDuplicat(array('persona' => $persona, 'carnet' => $carnet, 'foto' => $fotocarnet)), $duplicat);   // Només select titols

		$this->logEntryAuth('LOAD DUPLICAT', $request->query->get('persona'));
		
		return $this->render('FecdasBundle:Page:duplicatsform.html.twig', $this->getCommonRenderArrayOptions(array('form' => $form->createView()))); 
	} 
	
	public function pagamentpeticioAction(Request $request) {
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
	
		$duplicatid = 0;
		if ($request->query->has('id')) {
			$duplicatid = $request->query->get('id');
			$duplicat = $this->getDoctrine()->getRepository('FecdasBundle:EntityDuplicat')->find($duplicatid);
	
			if ($duplicat != null  && $duplicat->getPagament() == null) {
				$club = $duplicat->getClub();
				// Get factura detall
				$detallfactura = $duplicat->getDetallFactura();
				// Get factura totals
				$totalfactura = $this->getTotalsFactura($detallfactura);
	
				$desc = 'Pagament a FECDAS ' . $duplicat->getTextCarnet(false) . ", del club "  . $club->getCodi() . ' en data ' . $duplicat->getDatapeticio()->format('d/m/Y');
				$payment = new EntityPayment($duplicatid, $this->get('kernel')->getEnvironment(),
						$totalfactura['total'], $desc, $club->getNom(), self::PAGAMENT_DUPLICAT);
				
				$formpayment = $this->createForm(new FormPayment(), $payment);
	
				$this->logEntryAuth('PAG. DUPLI VIEW', $duplicatid);
	
				return $this->render('FecdasBundle:Page:pagament.html.twig',
						$this->getCommonRenderArrayOptions(array('formpayment' => $formpayment->createView(),
								'titol' => 'Pagament petició de duplicat', 'payment' => $payment, 'club' => $club,
								'iva' => 0, 'detall' => $detallfactura, 'totals' => $totalfactura,
								'backurl' => $this->generateUrl('FecdasBundle_duplicats'), 'backtext' => 'Duplicats',
								'menuactive' => 'menu-duplicats'
						)));
			}
		}
	
		/* Error */
		$this->logEntryAuth('PAG. DUPLI KO', $duplicatid);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut accedir al pagament, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	
	public function pagamentAction(Request $request) {
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));

		
		$parteid = 0;
		if ($request->query->has('id')) {
			$parteid = $request->query->get('id');
			$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
		
			if ($parte != null  && $parte->getDatapagament() == null) {

				// Get factura detall
				$detallfactura = $parte->getDetallFactura();
				// Get factura totals
				$totalfactura = $this->getTotalsFactura($detallfactura);
				
				$desc = 'Pagament a FECDAS, llista d\'assegurats del club ' . $parte->getClub()->getCodi() . ' en data ' . $parte->getDataalta()->format('d/m/Y');
				$payment = new EntityPayment($parteid, $this->get('kernel')->getEnvironment(),
						$totalfactura['total'], $desc, $parte->getClub()->getNom(), self::PAGAMENT_LLICENCIES);
				$formpayment = $this->createForm(new FormPayment(), $payment);
				
				$this->logEntryAuth('PAGAMENT VIEW', $parteid);
				
				return $this->render('FecdasBundle:Page:pagament.html.twig',
						$this->getCommonRenderArrayOptions(array('formpayment' => $formpayment->createView(),
								'titol' => 'Pagament de llicències', 'payment' => $payment, 'club' => $parte->getClub(),
								'iva' => $parte->getTipus()->getIva(), 'detall' => $detallfactura, 'totals' => $totalfactura,
								'backurl' => $this->generateUrl('FecdasBundle_parte', array('id' => $parteid )), 
								'backtext' => 'Llista de llicències', 'menuactive' => 'menu-partes'
						)));
			}
		}
		
		/* Error */
		$this->logEntryAuth('PAGAMENT KO', $parteid);
		$this->get('session')->getFlashBag()->add('sms-notice', 'No s\'ha pogut accedir al pagament, poseu-vos en contacte amb la Federació' );
		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	}
	
	public function notificacioOkAction(Request $request) {
		// Resposta TPV on-line, genera resposta usuaris correcte
		return $this->notificacioOnLine($request);
	}
	
	public function notificacioKoAction(Request $request) {
		// Resposta TPV on-line, genera resposta usuaris incorrecte		
		return $this->notificacioOnLine($request);
	}
	
	
	public function notificacioOnLine(Request $request) {
	
		$tpvresponse = $this->tpvResponse($request->query);

		$result = 'KO';
		$url = $this->generateUrl('FecdasBundle_homepage');
		if ($tpvresponse['itemId'] > 0) {
			if ($tpvresponse['pendent'] == true) $result = 'PEND';
			else $result = 'OK';
			
			if ($tpvresponse['source'] = self::PAGAMENT_LLICENCIES)
				$url = $this->generateUrl('FecdasBundle_parte', array('id'=> $tpvresponse['itemId']));
			if ($tpvresponse['source'] = self::PAGAMENT_DUPLICAT)
				$url = $this->generateUrl('FecdasBundle_duplicats');
		}
		$this->logEntryAuth('TPV NOTIFICA '. $result, $tpvresponse['logEntry']);
		
		return $this->render('FecdasBundle:Page:notificacio.html.twig',
				array('result' => $result, 'itemId' => $tpvresponse['itemId'], 'url' => $url) );
	}
	
	
	public function notificacioAction(Request $request) {
		// Crida asincrona des de TPV. Actualització dades pagament del parte

		$tpvresponse = $this->tpvResponse($request->request);
		
		if ($request->getMethod() == 'POST') {
			if ($tpvresponse['Ds_Response'] == 0) {
				// Ok
				$updOK = $this->actualitzarPagament($tpvresponse['itemId'], $tpvresponse['source'], $tpvresponse['Ds_Order']);
			
				if ($updOK == true) $this->logEntryAuth('TPV OK', $tpvresponse['logEntry']);
				else $this->logEntryAuth('TPV KO', $tpvresponse['logEntry']);
			} else {
				if ($tpvresponse['pendent'] == true) {
					// Pendent, enviar mail 
					$subject = ":: TPV. Pagament pendent de confirmació ::";
					$bccmails = array();
					$tomails = array(self::MAIL_ADMINTEST);
						
					$body = "<h1>Parte pendent</h1>";
					$body .= "<p>". $tpvresponse['logEntry']. "</p>"; 
					$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
					
					
					$this->logEntryAuth('TPV PEND', $tpvresponse['logEntry']);
				} else {
					// Altres. Error
					$this->logEntryAuth('TPV ERROR', $tpvresponse['logEntry']);
				}
			}
		} else {
			$this->logEntryAuth('TPV NO POST', $tpvresponse['logEntry']);
			
			$subject = ":: Incidència configuració TPV ::";
			$bccmails = array();
			$tomails = array(self::MAIL_ADMINTEST);
			
			$body = "<h1>TPV NO POST</h1>";
			$body .= "<h2>URL de notificación ha de ser: http://www.fecdasgestio.cat/notificacio</h2>";
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			
		}
		return new Response("");
	}
	
	private function actualitzarPagament($itemId, $source, $ordre) {
		
		if ($source == self::PAGAMENT_LLICENCIES) {
			$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($itemId);
			
			if ($parte != null) {
				$em = $this->getDoctrine()->getManager();
				// Actualitzar dades pagament
				$parte->setEstatPagament("TPV OK");
				$parte->setPendent(false);
				
				
				$this->crearRebut($this->getCurrentDate(), BaseController::TIPUS_PAGAMENT_TPV, $parte, $ordre);
				$parte->setDatamodificacio($this->getCurrentDate());
			
				$em->flush();
				return true;
			}
		}

		if ($source == self::PAGAMENT_DUPLICAT) {
			
			$duplicat = $this->getDoctrine()->getRepository('FecdasBundle:EntityDuplicat')->find($itemId);
			
			if ($duplicat != null) {
				$this->crearRebut($this->getCurrentDate(), BaseController::TIPUS_PAGAMENT_TPV, $duplicat, $ordre);
				$duplicat->setDatamodificacio($this->getCurrentDate());
				
				$em->flush();
				return true;
			}
		}

		// Origen desconegut	
		return false;
	}
	
	private function tpvResponse($tpvdata) {
	
		$tpvresponse = array('itemId' => 0, 'environment' => '', 'source' => '',
				'Ds_Response' => '', 'Ds_Order' => 0, 'Ds_Date' => '', 'Ds_Hour' => '',
				'Ds_PayMethod' => '', 'logEntry' => '', 'pendent' => false);
		if ($tpvdata->has('Ds_MerchantData') and $tpvdata->get('Ds_MerchantData') != '') {
			$dades = $tpvdata->get('Ds_MerchantData');
			$dades_array = explode(";", $dades);
				
			$tpvresponse['itemId'] = $dades_array[0];
			$tpvresponse['source'] = $dades_array[1];  /* Origen del pagament. Partes, duplicats */
			$tpvresponse['environment'] = $dades_array[2];
		}
	
		if ($tpvdata->has('Ds_Response')) $tpvresponse['Ds_Response'] = $tpvdata->get('Ds_Response');
		if ($tpvdata->has('Ds_Order')) $tpvresponse['Ds_Order'] = $tpvdata->get('Ds_Order');
		if ($tpvdata->has('Ds_Date')) $tpvresponse['Ds_Date'] = $tpvdata->get('Ds_Date');
		if ($tpvdata->has('Ds_Hour')) $tpvresponse['Ds_Hour'] = $tpvdata->get('Ds_Hour');
		if ($tpvdata->has('Ds_PayMethod')) $tpvresponse['Ds_PayMethod'] = $tpvdata->get('Ds_PayMethod');
	
		if (($tpvresponse['Ds_Response'] == '0930' or $tpvresponse['Ds_Response'] == '9930') and $tpvdata->get('Ds_PayMethod') == 'R') {
			$tpvresponse['pendent'] = true;
		}
		
		$tpvresponse['logEntry'] = $tpvresponse['itemId'] . "-" . $tpvresponse['source'] . "-" . 
				$tpvresponse['Ds_Response'] . "-" . $tpvresponse['environment'] . "-" . $tpvresponse['Ds_Date'] . "-" .
				$tpvresponse['Ds_Hour'] . "-" . $tpvresponse['Ds_Order'] . "-" . $tpvresponse['Ds_PayMethod'];
	
		return $tpvresponse;
	}
	
	public function notificacioTestAction(Request $request) {
	
		$formBuilder = $this->createFormBuilder()->add('Ds_Response', 'text');
		$formBuilder->add('Ds_MerchantData', 'text', array('required' => false));
		$formBuilder->add('Ds_Date', 'text');
		$formBuilder->add('Ds_Hour', 'text');
		$formBuilder->add('Ds_Order', 'text');
		$formBuilder->add('Ds_PayMethod', 'text', array('required' => false));
		$formBuilder->add('accio', 'choice', array(
				'choices'   => array($this->generateUrl('FecdasBundle_notificacio') => 'FecdasBundle_notificacio',
						$this->generateUrl('FecdasBundle_notificacioOk') => 'FecdasBundle_notificacioOk',
						$this->generateUrl('FecdasBundle_notificacioKo') => 'FecdasBundle_notificacioKo'),
				'required'  => true,
		));
	
		$form = $formBuilder->getForm();
		$form->get('Ds_Response')->setData(0);
		$form->get('Ds_MerchantData')->setData("4;".self::PAGAMENT_DUPLICAT.";dev");
		$form->get('Ds_Date')->setData(date('d/m/Y'));
		$form->get('Ds_Hour')->setData(date('h:i'));
		$form->get('Ds_Order')->setData(date('Ymdhi'));
		$form->get('Ds_PayMethod')->setData('');
	
		return $this->render('FecdasBundle:Page:notificacioTest.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView())));
	}
	
	private function allowEdit(EntityParte $parte) {
		return (boolean) ($parte->getDatapagament() == null); // Allow edition
	}

	private function prepareLlicencia($tipusparteId, $datacaducitat) {
		//dummy llicencia by default
		$llicencia = new EntityLlicencia($this->getCurrentDate());
		$llicencia->name = 'llicencia-nova';
		$llicencia->setDatacaducitat($datacaducitat);
		
		$llicencia->setEnviarllicencia(true);

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
		
		$llistatipus = BaseController::getLlistaTipusParte($this->getCurrentClub(), $day, $month);
		
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
		$search = $this->consultaAjaxPoblacions($request->get('term')); 
		$response = new Response();
		$response->setContent(json_encode($search));
		
		return $response;
	}

}