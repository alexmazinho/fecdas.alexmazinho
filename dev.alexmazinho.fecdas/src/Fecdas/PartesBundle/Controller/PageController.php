<?php 
namespace Fecdas\PartesBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fecdas\PartesBundle\Form\FormContact;
use Fecdas\PartesBundle\Form\FormPayment;
use Fecdas\PartesBundle\Form\FormParte;
use Fecdas\PartesBundle\Form\FormPersona;
use Fecdas\PartesBundle\Form\FormLlicencia;
use Fecdas\PartesBundle\Form\FormParteRenew;
use Fecdas\PartesBundle\Entity\EntityParteType;
use Fecdas\PartesBundle\Entity\EntityContact;
use Fecdas\PartesBundle\Entity\EntityParte;
use Fecdas\PartesBundle\Entity\EntityPersona;
use Fecdas\PartesBundle\Entity\EntityLlicencia;
use Fecdas\PartesBundle\Entity\EntityPayment;
use Fecdas\PartesBundle\Entity\EntityUser;
use Fecdas\PartesBundle\Entity\EntityClub;
//use Fecdas\PartesBundle\Classes\TcpdfBridge;

class PageController extends BaseController {
	public function indexAction() {
		return $this->render('FecdasPartesBundle:Page:index.html.twig',
				array('admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(), 'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}

	public function contactAction() {
		$request = $this->getRequest();

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
			$form->bindRequest($request);

			if ($form->isValid()) {
				$message = \Swift_Message::newInstance()
				->setSubject('::Contacte de Fecdas::'. $form->getData()->getSubject())
				->setFrom($form->getData()->getEmail())
				->setTo($this->getContactMails())
						->setBody($this->renderView('FecdasPartesBundle:Page:contactEmail.txt.twig',
								array('contact' => $contact)));

				$this->get('mailer')->send($message);
				$this->get('session')
					->setFlash('sms-notice','Petició enviada correctament. Gràcies!');

				// Redirect - This is important to prevent users re-posting
				// 	the form if they refresh the page
				return $this->redirect($this->generateUrl('FecdasPartesBundle_contact'));
			}
		}

		return $this->render('FecdasPartesBundle:Page:contact.html.twig',
				array('form' => $form->createView(), 'admin' => $this->isCurrentAdmin(),
						'authenticated' => $this->isAuthenticated(), 'busseig' => $this->isCurrentBusseig(), 
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}

	public function recentsAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		// Només jo
		/*if ($this->get('session')->get('username') != 'alexmazinho@gmail.com')
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));*/
		
		$em = $this->getDoctrine()->getEntityManager();
	
		$currentWeb = 0;
		$currentSincro = 0;
		$currentEstat = "100";
		$currentClub = "";
		
		if ($request->getMethod() == 'POST') {
			// Criteris de cerca 
			if ($request->request->has('form')) { 
				$formdata = $request->request->get('form');
				
				if (isset($formdata['codi'])) $currentClub = $formdata['codi'];
				if (isset($formdata['estat'])) $currentEstat = $formdata['estat'];
				if (isset($formdata['web'])) $currentWeb = 1;
				if (isset($formdata['sincro'])) $currentSincro = 1;

				$this->logEntry($this->get('session')->get('username'), 'ADMIN PARTES SEARCH',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), 
						"club: " . $currentClub . " estat: " . 
						$currentEstat . " web: " . $currentWeb .
						$currentSincro . " sync: " . $currentSincro );
			}
		} else {
			$this->logEntry($this->get('session')->get('username'), 'ADMIN PARTES',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'));
		}
		
		$formBuilder = $this->createFormBuilder()->add('clubs', 'search', array('required' => false));
		
		$formBuilder->add('codi', 'hidden');
		
		$formBuilder->add('estat', 'choice', array(
    				'choices'   => array('100' => 'Darrers 100', 't' => 'Tots', 'n' => 'No pagats', 'p' => 'Pendents', 'f' => 'Pagats'), 
					'preferred_choices' => array('100'),
				));
		$formBuilder->add('web', 'checkbox', array(
    				'required'  => false,
				));
		$formBuilder->add('sincro', 'checkbox', array(
    				'required'  => false,
				));
		$form = $formBuilder->getForm();
		
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityParte p JOIN p.tipus t ";
		$strQuery .= "WHERE p.databaixa IS NULL ";
		$strQuery .= " AND ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365))";
		
		if ($currentClub != "") {
			$clubcercar = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')->find($currentClub);
			$form->get('clubs')->setData($clubcercar->getNom());
			$form->get('codi')->setData($clubcercar->getCodi());
			$strQuery .= " AND p.club = '" .$clubcercar  . "' "	;
		}
			
		if ($currentWeb != 0) $strQuery .= " AND p.web = 1 ";
		/* Quan es sincronitza es posa la data modificació a NULL de partes i llicències (No de persones que funcionen amb el check validat). 
		 * Els canvis des del gestor també deixen la data a NULL per detectar canvis del web que calgui sincronitzar */ 
		if ($currentSincro != 0) $strQuery .= " AND (p.idparte_access IS NULL OR (p.idparte_access IS NOT NULL AND p.datamodificacio IS NOT NULL) ) ";
		//if ($currentEstat != "t" && $currentEstat != "100") {
			if ($currentEstat == "n") $strQuery .= " AND p.datapagament IS NULL ";
			if ($currentEstat == "p") $strQuery .= " AND p.numfactura = -1 ";
			if ($currentEstat == "f") $strQuery .= " AND p.datapagament IS NOT NULL ";
		//}
		$strQuery .= " ORDER BY p.id DESC"; 

		$query = $em->createQuery($strQuery)
			->setParameter('ininormal', $this->getSQLIniciAnual())
			->setParameter('ini365', $this->getSQLInici365());
		
		if ($currentEstat == "100") $query->setMaxResults(100);
		
		$partesrecents = $query->getResult();
	
		if ($currentWeb == 1) $form->get('web')->setData(true);
		if ($currentSincro == 1) $form->get('sincro')->setData(true);
		$form->get('estat')->setData($currentEstat);		
		
		return $this->render('FecdasPartesBundle:Page:recents.html.twig',
				array('form' => $form->createView(), 'partes' => $partesrecents,
						'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}
	
	
	public function sincroaccessAction() {
		$request = $this->getRequest();
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		$em = $this->getDoctrine()->getEntityManager();

		$parteid = $request->query->get("id");
		
		$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteid);
		
		if ($parte != null) {
			$parte->setDatamodificacio($this->getCurrentDate());
			
			foreach ($parte->getLlicencies() as $llicencia_iter) {
				if ($llicencia_iter->getDatabaixa() == null) $llicencia_iter->setDatamodificacio($this->getCurrentDate());
			}
			
			$em->flush();

			$this->get('session')->setFlash('error-notice', 'Llista preparada per tornar a sincronitzar');
			
			$this->logEntry($this->get('session')->get('username'), 'SINCRO ACCESS',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parteid);
		} else {
			$this->get('session')->setFlash('error-notice', 'Error en el procés de sincronització');
			
			$this->logEntry($this->get('session')->get('username'), 'SINCRO ACCESS ERROR',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parteid);
		}
		
		$response = $this->forward('FecdasPartesBundle:Page:recents');
		return $response;
	}
	
	public function partesAction() {
		$request = $this->getRequest();

		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));

		$currentClub = $this->getCurrentClub()->getCodi();

		if ($request->getMethod() == 'POST') {
			if ($request->request->has('formpartes-button-new')) { // Nou parte
				$response = $this->forward('FecdasPartesBundle:Page:parte');
				return $response;
			}
			
			if ($request->request->has('parte')) { // Esborra't des de Parte
				$parte = $request->request->get('parte');  
				if (isset($parte['club'])) $currentClub = $parte['club'];
			}
			
			if ($request->request->has('form')) { // Reload select clubs de Partes
				$formdata = $request->request->get('form');  
				if (isset($formdata['clubs'])) $currentClub = $formdata['clubs'];
			}
		} else {
			if ($request->query->has('club')) {  // Esborra't des de Llistes Partes
				$currentClub = $request->query->get('club');
			} else {
				$request->getSession()->clearFlashes();
			}
		}

		$this->logEntry($this->get('session')->get('username'), 'VIEW PARTES',
				$this->get('session')->get('remote_addr'),
				$this->getRequest()->server->get('HTTP_USER_AGENT'), $currentClub);
		
		$form = $this->createClubsForm($currentClub)->getForm(); 

		$partesclub = $this->consultaPartesClub($currentClub);
		
		return $this->render('FecdasPartesBundle:Page:partes.html.twig',
				array('form' => $form->createView(), 'partes' => $partesclub, 
						'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}

	public function asseguratsAction() {
		$request = $this->getRequest();
	
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
	
		$em = $this->getDoctrine()->getEntityManager();
	
		$currentClub = $this->getCurrentClub()->getCodi();
		$currentDNI = "";
		$currentNom = "";
		$currentCognoms = "";
		$currentVigent = false;
		
		if ($request->getMethod() == 'POST') {
			// Criteris de cerca 
			if ($request->request->has('form')) { // Reload select clubs de Partes
				$formdata = $request->request->get('form');
				if (isset($formdata['clubs'])) $currentClub = $formdata['clubs'];
				if (isset($formdata['dni'])) $currentDNI = $formdata['dni'];
				if (isset($formdata['nom'])) $currentNom = $formdata['nom'];
				if (isset($formdata['cognoms'])) $currentCognoms = $formdata['cognoms'];
				if (isset($formdata['vigent'])) $currentVigent = ($formdata['vigent'] == 1)?true:false;
				
				$this->logEntry($this->get('session')->get('username'), 'VIEW PERSONES SEARCH',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'),
						"club: " . $currentClub . " dni: " . $currentDNI . " nom/cog: " . $currentNom . ", " . $currentCognoms );
				
			}
		} else {
			$this->logEntry($this->get('session')->get('username'), 'VIEW PERSONES',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'));
		}
	
		$formBuilder = $this->createClubsForm($currentClub); 
		$formBuilder->add('dni', 'search', array('required'  => false,));
		$formBuilder->add('nom', 'search', array('required'  => false,));
		$formBuilder->add('cognoms', 'search', array('required'  => false,));
		$formBuilder->add('vigent', 'checkbox', array('required'  => false, 'data' => $currentVigent,
				'attr' => (array('onchange' => 'this.form.submit()'))));
		$form = $formBuilder->getForm(); 

		$em = $this->getDoctrine()->getEntityManager();
		
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityPersona p ";
		$strQuery .= " WHERE p.club = :club ";
		$strQuery .= " AND p.databaixa IS NULL ";
		
		if ($currentDNI != "") $strQuery .= " AND p.dni LIKE :dni ";
		if ($currentNom != "") $strQuery .= " AND p.nom LIKE :nom ";
		if ($currentCognoms != "") $strQuery .= " AND p.cognoms LIKE :cognoms ";

		$strQuery .= " ORDER BY p.cognoms, p.nom";
		
		$query = $em->createQuery($strQuery)->setParameter('club', $currentClub);
		
		if ($currentDNI != "") {
			$query->setParameter('dni', "%" . $currentDNI . "%");
			$form->get('dni')->setData($currentDNI);
		}
		if ($currentNom != "") {
			$query->setParameter('nom', "%" . $currentNom . "%");
			$form->get('nom')->setData($currentNom);
		}
		if ($currentCognoms != "") {
			$query->setParameter('cognoms', "%" . $currentCognoms . "%");
			$form->get('cognoms')->setData($currentCognoms);
		}
		
		$persones = $query->getResult();
	
		return $this->render('FecdasPartesBundle:Page:assegurats.html.twig',
				array('form' => $form->createView(), 'persones' => $persones, 'vigents' => $currentVigent, 'club' => $currentClub,
						'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}
	
	private function createClubsForm($currentClub) {
		$clubsvalues = $this->getClubsSelect();
		
		if ($this->isCurrentAdmin()) {
			$formBuilder = $this->createFormBuilder()
			->add('clubs', 'choice', array('choices' => $clubsvalues,
					'data' => $currentClub,
					'attr' => (array('onchange' => 'this.form.submit()'))));
		} else {
			$formBuilder = $this->createFormBuilder()
			->add('clubs', 'choice', array('choices' => $clubsvalues,
					'data' => $currentClub,
					'attr' => (array('disabled' => 'true'))));
		}
		
		return $formBuilder;  
	}
	
	public function busseigAction() {
		$request = $this->getRequest();

		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));

		if ($request->getMethod() == 'POST') {
			$formdata = $request->request->get('form');
			$dni = $formdata['dni'];

			$this->logEntry($this->get('session')->get('username'), 'CHECK DNI',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $dni);
			
			$smsko = 'No hi ha cap llicència vigent per al DNI : ' . $dni;
			$smsok = 'El DNI : ' . $dni . ', té una llicència vigent fins ';
			
			$em = $this->getDoctrine()->getEntityManager();
			
			$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityPersona p ";
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
				foreach ($persones as $c => $persona) {
					/* Obtenir llicències encara no caducades per aquesta persona*/
					$strQuery = "SELECT l FROM Fecdas\PartesBundle\Entity\EntityLlicencia l ";
					$strQuery .= " WHERE l.datacaducitat >= :dataactual ";
					$strQuery .= " AND l.persona = :persona ";
					$strQuery .= " AND l.databaixa IS NULL ";

					$dataactual = $this->getCurrentDate('today');
					$query = $em->createQuery($strQuery)
					->setParameter('dataactual', $dataactual)
					->setParameter('persona', $persona->getId());
					$llicencies = $query->getResult();
					
					if (count($llicencies) > 0) {
						foreach ($llicencies as $c => $llicencia) {
							/* Comprovar si la llicència està vigent i no és futura */
							$inicivigencia = $llicencia->getParte()->getDataalta();
							if ($inicivigencia <= $dataactual) {
								$trobada = true;
								$smsok .= $llicencia->getParte()->getDatacaducitat()->format('d/m/Y');
							}
						}
					}
				}
			}

			if ($trobada == true) $this->get('session')->setFlash('error-notice', $smsok);
			else $this->get('session')->setFlash('error-notice', $smsko);
				
		} else {
			$request->getSession()->clearFlashes();
		}

		$form = $this->createFormBuilder()->add('dni', 'text')->getForm();

		return $this->render('FecdasPartesBundle:Page:consultadni.html.twig',
				array('form' => $form->createView(), 'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}

	public function renovarAction() {
		$this->get('session')->clearFlashes();
		$request = $this->getRequest();
		if ($this->isAuthenticated() != true) {
			// keep url. Redirect after login
			$url_request = $this->getRequest()->server->get('REQUEST_URI');
			$this->get('session')->set('url_request', $url_request);
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
		}
		
		/* Desactivar funcionalitat temporal */
		/*
		$this->get('session')->setFlash('error-notice',	'Aquesta funcionalitat encara no està disponible');
		$response = $this->forward('FecdasPartesBundle:Page:partes', array(), array('club' => $this->getCurrentClub()->getCodi()));
		return $response;
		*/
		/* Fi desactivar funcionalitat temporal */
	
		$parteid = 0;
		$currentClub = $this->getCurrentClub()->getCodi();
		if ($request->getMethod() == 'POST') {
			if ($request->request->has('parte_renew')) {
				$p = $request->request->get('parte_renew');
				$parteid = $p['cloneid'];
				$currentClub = $p['club'];
			}
		} else {
			if ($request->query->has('id') and $request->query->get('id') != "")
				$parteid = $request->query->get('id');
		}
			
		$partearenovar = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteid);
			
		if ($partearenovar == null) return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	
		/* Validació impedir modificacions altres clubs */
		if ($this->isCurrentAdmin() != true and $partearenovar->getClub()->getCodi() != $currentClub)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	
		/* nou parte. Mètodes clone inicialitzen camps */
		$parte = clone $partearenovar;
		/* Si abans data caducitat renovació per tot el periode
		 * En cas contrari només des d'ara
		*/
		$data_alta = $this->getCurrentDate('now');
		if ($partearenovar->getDataCaducitat() >= $data_alta) {
			$data_alta = $partearenovar->getDataCaducitat();
			$data_alta->setTime(00, 00);
			$data_alta->add(new \DateInterval('P1D')); // Add 1
		}
		$parte->setDataalta($data_alta);
		$parte->setDataEntrada($this->getCurrentDate());
		$parte->setDatamodificacio($this->getCurrentDate());
		
		$parte->cloneLlicencies($this->getCurrentDate());
	
		$options = $this->getFormOptions();
		$options['nova'] = false;  // No permet selecció data
		$options['admin'] = false; // No permet selecció club
		$options['edit'] = false; // No permet selecció tipus
		$options['codiclub'] = $parte->getClub()->getCodi();
		$options['tipusparte'] = $parte->getTipus()->getId();
		$options['any'] = $parte->getAny(); // Mostrar preu segons any parte
	
		$form = $this->createForm(new FormParteRenew($options), $parte);
		$form->get('any')->setData($parte->getAny());
		$form->get('cloneid')->setData($parteid); 

		$avisos = "";
		if ($request->getMethod() == 'POST') {
			$form->bindRequest($request);
	
			if ($form->isValid() && $request->request->has('parte_renew')) {
				$em = $this->getDoctrine()->getEntityManager();
				
				$p = $request->request->get('parte_renew');
				$i = 0; 
				foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
					if (!isset($p['llicencies'][$i]['renovar'])) {
						// Treure llicències que no es volen renovar
						$parte->removeEntityLlicencia($llicencia_iter);
					} else {
						$em->persist($llicencia_iter);
					}
					$i++;
				}
						
				// Marquem com renovat
				$partearenovar->setRenovat(true);
				
				$em->persist($parte);
				$em->flush();

				$this->logEntry($this->get('session')->get('username'), 'RENOVAR OK',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), $parte->getId());
				
				$this->get('session')->setFlash('error-notice',	'Llista creada correctament');
						
				return $this->redirect($this->generateUrl('FecdasPartesBundle_parte', array('id' => $parte->getId(), 'action' => 'view')));
				
			} else {
				$this->get('session')->setFlash('error-notice',	'Error validant les dades. Contacta amb l\'adminitrador');
			}
		} else {
			/*
			 * Validacions  de les llicències
			* */
			foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
				// Comprovar que no hi ha llicències vigents
				// Per la pròpia persona
				$dataoverlapllicencia = $this->validaPersonaTeLlicenciaVigent($llicencia_iter, $llicencia_iter->getPersona());
				if ($dataoverlapllicencia != null) {
					$form->get('llicencies')->get($c)->get('renovar')->setData(false);
					$form->get('llicencies')->get($c)->remove('renovar');
					
					$avisos .= "- El Federat " . $llicencia_iter->getPersona()->getNom() . " " . $llicencia_iter->getPersona()->getCognoms();
					$avisos .= " ja té una llicència vigent en aquest club, en data ";
					$avisos .= $dataoverlapllicencia->format('d/m/Y') . "<br/>";
					continue;
				}
			
				// Comprovar que no hi ha llicències vigents de la persona en difents clubs, per DNI
				// Les persones s'associen a un club, mirar si existeix a un altre club
				$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityPersona p ";
				$strQuery .= " WHERE p.dni = :dni ";
				$strQuery .= " AND p.club <> :club ";
				$strQuery .= " AND p.databaixa IS NULL";
			
				$em = $this->getDoctrine()->getEntityManager();
				$query = $em->createQuery($strQuery)
				->setParameter('dni', $llicencia_iter->getPersona()->getDni())
				->setParameter('club', $llicencia_iter->getPersona()->getClub()->getCodi());
			
				$personaaltresclubs = $query->getResult();
			
				foreach ($personaaltresclubs as $p => $persona_iter) {
					$dataoverlapllicencia = $this->validaPersonaTeLlicenciaVigent($llicencia_iter, $persona_iter);
					if ($dataoverlapllicencia != null) {
						$form->get('llicencies')->get($c)->get('renovar')->setData(false);
						$form->get('llicencies')->get($c)->remove('renovar');
						
						$avisos .= "- El Federat " . $llicencia_iter->getPersona()->getNom() . " " . $llicencia_iter->getPersona()->getCognoms();
						$avisos .= " ja té una llicència vigent<br/>";
						continue;
					}
				}
			
				if ($this->validaLlicenciaInfantil($llicencia_iter) == false) {
					$novacategoria = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityCategoria')
										->findOneBy(array('tipusparte' => $llicencia_iter->getParte()->getTipus()->getId(), 'simbol' => 'A'));
					$llicencia_iter->setCategoria($novacategoria);
					
					$avisos .= "- El Federat " . $llicencia_iter->getPersona()->getNom() . " " . $llicencia_iter->getPersona()->getCognoms();
					$avisos .= " ha canviat de categoria infantil a aficionat<br/>";
					continue;
				}
			}
			
			$this->logEntry($this->get('session')->get('username'), 'RENOVAR VIEW',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parte->getId() . "-" .$avisos);
				
		}
			
		return $this->render('FecdasPartesBundle:Page:renovar.html.twig',
				array('form' => $form->createView(), 'parte' => $parte, 'avisos' => $avisos, 'admin' => $this->isCurrentAdmin(),
						'authenticated' => $this->isAuthenticated(), 'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}

	public function parteAction() {
		$this->get('session')->clearFlashes();
		$request = $this->getRequest();
		
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));

		$options = $this->getFormOptions();
		$parteid = 0;
		$action = "";
				
		if ($request->getMethod() == 'POST') {
			/*if ($request->request->has('formparte-button-payment') or
				$request->request->has('formparte-button-payment_x')) { // Pagament. _x si imatge coordenades
				$response = $this->forward('FecdasPartesBundle:Page:pagament');
				return $response;
			}*/
			if ($request->request->has('parte')) { // Esborrar parte
				$p = $request->request->get('parte');
				$parteid = $p['id'];
				
				$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteid);
				// Es pot confirmar qualsevol Parte, encara que estigui facturat
				//if ($parte != null and $parte->getDataFacturacio() != null) {
				//	$this->get('session')->setFlash('error-notice',	'Aquesta llista ja no es pot pagar on-line');
				//} else {
					if (isset ($p['datapagat'])) { // confirmar Pagament pendent
						$action = "pagat";
					} else {
						//$action = "remove"; // Opció eliminada
						$response = $this->forward('FecdasPartesBundle:Page:pagament');  // Pagament continuar
						return $response;
					}
				//}
			} 
			if ($request->request->has('form')) { // Nou parte des de Partes
				$formdata = $request->request->get('form');  
				if (isset($formdata['clubs'])) {
					$currentClub = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')
						->find($formdata['clubs']);
				}
			}
		} else {
			if ($request->query->has('id') and $request->query->get('id') != "")
				$parteid = $request->query->get('id');
			if ($request->query->has('action'))
				$action = $request->query->get('action');
		}
		
		if ($parteid > 0) {
			// 	Update or delete
			$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteid);
				
			if ($action == 'pagat') {
				if (!$this->isCurrentAdmin()) {
					$this->get('session')->setFlash('error-notice',	'Només pot confirmar pagaments l\'administrador');
				} else {
					// Persistència
					$em = $this->getDoctrine()->getEntityManager();
				
					// Actualitzar data pagament
					$datapagat = \DateTime::createFromFormat('d/m/Y', $p['datapagat']); 
					/*
					 $numfactura = $this->getMaxNumFactura();
					$parte->setNumFactura($numfactura);*/
					$parte->setDatapagament($datapagat);
					$parte->setEstatpagament($p['estatpagat']); 
					if ($p['dadespagat'] != '') $parte->setDadespagament($p['dadespagat']);
					if ($p['comentaripagat'] != '') $parte->setComentari($p['comentaripagat']);
					$parte->setImportpagament($parte->getPreuTotalIVA());
					$parte->setDatamodificacio($this->getCurrentDate());

					$this->get('session')->setFlash('error-notice',	'Pagament confirmat ');
					
					$em->flush();
					
					$this->logEntry($this->get('session')->get('username'), 'CONFIRMAR PAGAMENT',
							$this->get('session')->get('remote_addr'),
							$this->getRequest()->server->get('HTTP_USER_AGENT'), $parte->getId());
					
				}
			}
			
			if ($action == 'remove') { // Delete
				/*  No es poden esborrar partes 
				$valida = true;
				if (!$this->isCurrentAdmin()) {
					$this->get('session')->setFlash('error-notice',
							'Només pot esborrar l\'administrador');
					$valida = false;
				}
				// Comprovació any actual no pagat. NO hauria de passar								
				if ($parte->isPagat()) {
					$this->get('session')->setFlash('error-notice',
							'No es poden esborrar llistes que ja estan facturades');
					$valida = false;
				}
				if ($valida == true) {
					// Persistència
					$em = $this->getDoctrine()->getEntityManager();
						
					$parte->setDatamodificacio($this->getCurrentDate());
					$parte->setDatabaixa($this->getCurrentDate());
					foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
						$llicencia_iter->setDatamodificacio($this->getCurrentDate());
						$llicencia_iter->setDatabaixa($this->getCurrentDate());
					}

					$this->get('session')->setFlash('error-notice',
							'Llista esborrada correctament. Club ' . $parte->getClub()->getCodi() . ' en data ' . $parte->getDataalta()->format('d/m/Y'));
					$em->flush();
				}

				$response = $this->forward('FecdasPartesBundle:Page:partes', array(),array('club' => $parte->getClub()->getCodi()));
				return $response;
				*/
			}
		} else {
			$parte = new EntityParte($this->getCurrentDate());
			$options['nova'] = true;
			/* NO gestionar NumRelació
			 $em = $this->getDoctrine()->getEntityManager();
			$query = $em->createQuery("SELECT MAX(p.numrelacio)	FROM Fecdas\PartesBundle\Entity\EntityParte p
					WHERE p.dataalta >= :ini AND p.dataalta <= :fi")
			->setParameter('ini', date("Y-m-d", strtotime(date("Y") . "-01-01")))
			->setParameter('fi', date("Y-m-d", strtotime(date("Y") . "-12-31")));
			$nounumrelacio = $query->getSingleScalarResult() + 1;
			$parte->setNumrelacio($nounumrelacio);
			*/
			$data_alta = $this->getCurrentDate('now');
			$data_alta->add(new \DateInterval('PT1200S')); // Add 20 minutes
			$parte->setDataalta($data_alta);
			if (!isset($currentClub)) $currentClub = $this->getCurrentClub();
			$parte->setClub($currentClub);
			// El tipus de parte es necessari per saber els checks de llicència que cal ocultar
			$parte->setTipus($this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParteType')->find(1));

			$options['llistatipus'] = $this->getLlistaTipusParte($parte->getDataalta()->format('d'), $parte->getDataalta()->format('m'));
		}
		
		$pdf = $this->showPDF($parte);
		$edit = $this->allowEdit($parte);

		if ($edit == true) $options['edit'] = true;  
		if ($this->isCurrentAdmin()) $options['admin'] = true; 

		$options['codiclub'] = $parte->getClub()->getCodi();
		$options['tipusparte'] = $parte->getTipus()->getId();
		
		$form = $this->createForm(new FormParte($options), $parte);
		
		$form->get('any')->setData($parte->getAny());
		//$form->get('numrelacioshow')->setData($parte->getNumrelacio());
		
		if ($request->isXmlHttpRequest()) {

		}
		return $this->render('FecdasPartesBundle:Page:parte.html.twig',
				array('form' => $form->createView(), 'parte' => $parte, 'pdf' => $pdf, 'edit' => $edit, 'admin' =>$this->isCurrentAdmin(),
						'tipusparte' => $parte->getTipus()->getId(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}

	private function updateParte(Request $request) {
		/* Des de llicència XMLRequest, update llicència i potser nou parte*/
		$requestParams = $request->request->all();
		$p = $requestParams['parte'];
		$this->get('session')->clearFlashes();

		$em = $this->getDoctrine()->getEntityManager();
		
		if ($p['id'] != "") {
			$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($p['id']);
			$partedataalta = $parte->getDataalta();  // El posterior bind no carrega data pq està disabled al form
		} else {
			$parte = new EntityParte($this->getCurrentDate());
			$em->persist($parte);
		}
		
		if ($requestParams['action'] == 'remove') {
			// Cercar llicència a esborrar
			foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
				if ($llicencia_iter->getId() == $requestParams['llicenciaId']) $llicencia = $llicencia_iter;
			}
			$llicencia->setDatamodificacio($this->getCurrentDate());
			$llicencia->setDatabaixa($this->getCurrentDate());

			// No admin. No hauria de passar mai
			$valida = true;
			if (!$this->isCurrentAdmin()) {
				$this->get('session')->setFlash('error-notice',
						'Només pot esborrar l\'administrador');
				$em->refresh($llicencia);
				$valida = false;
			}
			// Comprovació Pagat. No hauria de passar mai			
			if ($parte->getDatapagament() != null) {
				$this->get('session')->setFlash('error-notice',
						'No es poden esborrar llicències que ja estan pagades');
				$em->refresh($llicencia);
				$valida = false;
			}
			if ($valida == true) {
				// Persistència
				$parte->setDatamodificacio($this->getCurrentDate());
				
				$parte->setImportparte($parte->getPreuTotalIVA());  // Canviar preu parte
				
				$em->flush();
				$logaction = 'LLICENCIA DEL OK';
			} else {
				$logaction = 'LLICENCIA DEL KO';
			}
			$extrainfo = 'parte:' . $parte->getId() . ' llicencia: ' . $llicencia->getId();
			$this->logEntry($this->get('session')->get('username'), $logaction,
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $extrainfo);
		} else {
			$l = $requestParams['llicencia'];
			
			if ($l['id'] != "") {
				// Update
				foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
					if ($llicencia_iter->getId() == $l['id'])
						$llicencia = $llicencia_iter;
				}
			} else {
				// Insert
				$llicencia = new EntityLlicencia($this->getCurrentDate());
				$parte->addEntityLlicencia($llicencia);
				$em->persist($llicencia);
			}
			
			$options = $this->getFormOptions();
			
			$options['codiclub'] = $p['club'];
			$options['tipusparte'] = $p['tipus'];
			
			array_push($options['llistatipus'], $p['tipus']);
			$options['edit'] = true;
			
			$options['admin'] = true;
			$options['nova'] = true;
			if ($p['id'] != "") {
				$options['any'] = $parte->getAny(); 
			} else {
				$options['any'] = $p['dataalta']['date']['year']; // Mostrar preu segons any parte
			}
			
			$form = $this->createForm(new FormParte($options), $parte);
			$formLlicencia = $this->createForm(new FormLlicencia($options),$llicencia);
			
			$form->bindRequest($request);
			$formLlicencia->bindRequest($request);
			
			if ($formLlicencia->isValid() and $form->isValid()) {
				$parte->setDatamodificacio($this->getCurrentDate());
				$llicencia->setDatamodificacio($this->getCurrentDate());
				
				if ($parte->getId() != null) $parte->setDataalta($partedataalta); // Restore dataalta				
				
				$valida = true;
				if (!$this->isCurrentAdmin() and $this->validaDataLlicencia($parte->getDataalta()) == false) {
					// NO llicències amb data passada. Excepte administradors
					$this->get('session')->setFlash('error-notice',
							'No es poden donar d\'alta ni actualitzar llicències amb data passada');
					$valida = false;
				}				
				if ($valida == true) {
					if ($this->validaLlicenciaInfantil($llicencia) == false) {
						$this->get('session')->setFlash('error-notice',
								'L\'edat de la persona no correspon amb el tipus de llicència');
						$valida = false;
					}
				}

				if ($valida == true) {
					if ($this->validaPersonaRepetida($parte, $llicencia) == false) {
						$this->get('session')->setFlash('error-notice',
								'Aquesta persona ja té una llicència en aquesta llista');
						$valida = false;
					}
				}
				
				if ($valida == true) {
					// Comprovar que no hi ha llicències vigents 
					// Per la pròpia persona
					$dataoverlapllicencia = $this->validaPersonaTeLlicenciaVigent($llicencia, $llicencia->getPersona()); 
					if ($dataoverlapllicencia != null) {
						$this->get('session')->setFlash('error-notice',
								'Aquesta persona ja té una llicència per a l\'any actual en aquest club, en data ' . 
								$dataoverlapllicencia->format('d/m/Y'));
						$valida = false;
					}
				}
				
				if ($valida == true) {
					// Comprovar que no hi ha llicències vigents de la persona en difents clubs, per DNI
					// Les persones s'associen a un club, mirar si existeix a un altre club
					$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityPersona p ";
					$strQuery .= " WHERE p.dni = :dni ";
					$strQuery .= " AND p.club <> :club ";
					$strQuery .= " AND p.databaixa IS NULL";
							
					$query = $em->createQuery($strQuery)
						->setParameter('dni', $llicencia->getPersona()->getDni())
						->setParameter('club', $llicencia->getPersona()->getClub()->getCodi());
					
					$personaaltresclubs = $query->getResult();
							
					foreach ($personaaltresclubs as $c => $persona_iter) {
						$dataoverlapllicencia = $this->validaPersonaTeLlicenciaVigent($llicencia, $persona_iter);
						if ($dataoverlapllicencia != null) {
							// Enviar mail a FECDAS
							$mails = $this->getAdminMails();
							$this->sendMailLlicenciaDuplicada($mails, $llicencia->getPersona(), $persona_iter, $dataoverlapllicencia);
						}
					}
				}
				
				// Persistència or rollback
				if ($valida == true) {
					if ($parte->getId() != null) {
						if ($llicencia->getId() != null) {
							$logaction = 'LLICENCIA UPD OK';
						} else {
							$logaction = 'LLICENCIA NEW OK';
						}
					}
					else $logaction = 'PARTE NEW OK';
					
					$parte->setImportparte($parte->getPreuTotalIVA());  // Canviar preu parte
					
					$this->get('session')->setFlash('error-notice', 'Llicència enviada correctament');
										
					$em->flush(); 
				} else {
					if ($llicencia->getId() != null) {
						// Modificar llicència
						$logaction = 'LLICENCIA UPD KO';
						$em->refresh($llicencia);
						$em->refresh($parte);
					}
					else {
						if ($parte->getId() != null) {
							// Nova llicència
							$logaction = 'LLICENCIA NEW KO';
							$em->detach($llicencia);
							$em->refresh($parte); 
						} else {
							// Nou parte
							$logaction = 'PARTE NEW KO';
							$parte->removeEntityLlicencia($llicencia); 
							$em->detach($llicencia);
							$em->detach($parte);
						}
					}
				}
				$extrainfo = '';
				if ($parte->getId() != null) $extrainfo .= 'parte:' . $parte->getId();
				if ($llicencia->getId() != null) $extrainfo .= ' llicencia: ' . $llicencia->getId();
				if ($this->get('session')->getFlash('error-notice') != null) $extrainfo .= ' ' . $this->get('session')->getFlash('error-notice');
				$this->logEntry($this->get('session')->get('username'), $logaction,
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), $extrainfo);
				
			} else {
				// get a ConstraintViolationList
				$errorstr = "";
				$errors = $this->get('validator')->validate($parte);
				foreach ($errors as $error)
					$errorstr = $errorstr . " campp: "
					. $error->getPropertyPath() . "("
					. $error->getMessage() . ") \n";

				$errors = $this->get('validator')->validate($llicencia);
				foreach ($errors as $error)
					$errorstr = $errorstr . " campl: "
					. $error->getPropertyPath() . "("
					. $error->getMessage() . ") \n";
				
				$errors = $this->get('validator')->validate($form);
				foreach ($errors as $error)
					$errorstr = $errorstr . " campl: "
						. $error->getPropertyPath() . "("
						. $error->getMessage() . ") \n";
				
				$this->get('session')->setFlash('error-notice', "error validant les dades".$errorstr); 
				
			}
		}
		$pdf = $this->showPDF($parte);

		return $this->render('FecdasPartesBundle:Page:partellistallicencies.html.twig',
				array('parte' => $parte, 'pdf' => $pdf, 'admin' =>$this->isCurrentAdmin()));
	}

	private function validaDataLlicencia(\DateTime $dataalta) {
		$avui = $this->getCurrentDate('now');
		if ($dataalta < $avui) return false;
		return true;
	} 
	
	private function validaLlicenciaInfantil(EntityLlicencia $llicencia) {
		// Valida menors, nascuts després del 01-01 any actual - 12
		$nascut = $llicencia->getPersona()->getDatanaixement();
		
		/*$nascut = new \DateTime(date("Y-m-d", strtotime($llicencia->getPersona()->getDatanaixement()->format('Y-m-d'))));
		 echo $nascut->format("Y-m-d");*/
		$limit = \DateTime::createFromFormat('Y-m-d', ($llicencia->getParte()->getAny()-12) . "-01-01");
		if ($llicencia->getCategoria()->getSimbol() == "I" && $nascut < $limit) return false;
		if ($llicencia->getCategoria()->getSimbol() != "I" && $nascut > $limit) return false;
		return true;
	}
	
	private function validaPersonaRepetida(EntityParte $parte, EntityLlicencia $llicencia) {
		// Parte ja té llicència aquesta persona
		foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
			if ($llicencia_iter->getId() != $llicencia->getId() and 
				$llicencia_iter->getDatabaixa() == null) {
				// NO valido la pròpia llicència, en cas d'update
				if ($llicencia_iter->getPersona()->getId() == $llicencia->getPersona()->getId()) return false;
			}
		}
		return true;
	} 
	
	private function validaPersonaTeLlicenciaVigent(EntityLlicencia $llicencia, EntityPersona $persona) {
		// Comprovar que no hi ha altres llicències vigents per a la persona
		// Que solapin amb la llicència  
		$em = $this->getDoctrine()->getEntityManager();
		
		// Consulta actives i futures de la persona
		// Pot ser que es coli alguna llicència un dia any actual anterior data d'avui
		$strQuery = "SELECT l FROM Fecdas\PartesBundle\Entity\EntityLlicencia l ";
		$strQuery .= " JOIN l.parte p JOIN p.tipus t";
		$strQuery .= " WHERE l.persona = :persona ";
		$strQuery .= " AND p.databaixa IS NULL ";
		$strQuery .= " AND ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365))";
			
		$query = $em->createQuery($strQuery)
			->setParameter('persona', $persona->getId())
			->setParameter('ininormal', $this->getSQLIniciAnual())  // 01/01 de l'any actual 
			->setParameter('ini365', $this->getSQLInici365());		// Avui fa un any
		
		$lpersonaarevisar = $query->getResult();
		
		$inicivigencia_nova = $llicencia->getParte()->getDataalta();
		$fivigencia_nova = $llicencia->getParte()->getDataCaducitat();
		
		foreach ($lpersonaarevisar as $c => $llicencia_iter) {
			if ($llicencia_iter->getId() != $llicencia->getId() and 
				$llicencia_iter->getDatabaixa() == null ) {
				// No comprovo la pròpia llicència
 
				$inicivigencia_existent = $llicencia_iter->getParte()->getDataalta();
				
				// Cal anar en compte, les llicències importades tenen un dia més
				//$fivigencia_existent = $llicencia_iter->getDatacaducitat();
				$fivigencia_existent = $llicencia_iter->getParte()->getDataCaducitat();
				
				// Comprovar si sol·lapen
				if (($fivigencia_nova >= $inicivigencia_existent) && 
					($inicivigencia_nova <= $fivigencia_existent)) {
					return $llicencia_iter->getParte()->getDataalta(); // Error, sol·lapen
				}
			}
		}
		return null;		
	}
	
	// Totes les llicències entren en vigència en data d'alta
	/*
	private function getIniciLlicencia(EntityLlicencia $llicencia) {
		if ($llicencia->getParte()->getTipus()->getEs365() == true) { // Llicencia entrada 365
			$inicivigencia = $llicencia->getParte()->getDataalta();
		} else {
			// Llicència entrada anual (o un dia o reduïda)
			switch ($llicencia->getParte()->getTipus()->getId()) {
				case 5:
					// Reduïda
					$anyinicivigencia = $llicencia->getParte()->getDataalta()->format('Y');
					$inicivigencia = \DateTime::createFromFormat('Y-m-d H:i:s', $anyinicivigencia . "-09-01 00:00:00");
					break;
				case 9:
					// Un dia
					$inicivigencia = $llicencia->getDatacaducitat();
					break;
				default:
					$anyinicivigencia = $llicencia->getParte()->getDataalta()->format('Y');
					$inicivigencia = \DateTime::createFromFormat('Y-m-d H:i:s', $anyinicivigencia . "-01-01 00:00:00");
			}
		}
		return $inicivigencia;
	}*/
	
	private function sendMailLlicenciaDuplicada ($mails, EntityPersona $personaNova, EntityPersona $personaExistent, \DateTime $datallicencia) {
		$message = \Swift_Message::newInstance()
			->setSubject('::Llicència Duplicada Diferents Clubs::')
			->setFrom($this->container->getParameter('fecdas_partes.emails.contact_email'))
			->setTo($mails)
			->setBody($this->renderView('FecdasPartesBundle:Page:llicenciaDuplicadaEmail.txt.twig', 
					array('personanova' => $personaNova, 'personaexistent' => $personaExistent, 'datallicencia' => $datallicencia)));
		$this->get('mailer')->send($message);
	}
	
	public function llicenciaAction() {
		$request = $this->getRequest();

		if ($request->isXmlHttpRequest()) {
			$options = $this->getFormOptions();

			$llicenciaId = 0;
			$dataalta_parte = $this->getCurrentDate('today');
			$tipusid = 1; // Tipus de parte
			$codiclub = "";
			$parte = null;
			$currentPerson = 0;
			if ($request->getMethod() == 'POST') {
				if ($request->request->get('personaAction') != "") {
					// source: FormPersona
					$requestParams = $request->request->all();
				} else {
					// source: FormLlicencia
					$response = $this->updateParte($request);
					return $response;
				}
			} else {
				$this->get('session')->clearFlashes();
				$requestParams = $request->query->all();
			}
			if (isset($requestParams['codiclub'])) $codiclub = $requestParams['codiclub'];
			if (isset($requestParams['tipusparte'])) $tipusid = $requestParams['tipusparte'];
			if (isset($requestParams['dataalta'])) 	{
				$dataalta_parte = \DateTime::createFromFormat('d/m/Y H:i:s', $requestParams['dataalta']);
			}
			if (isset($requestParams['llicenciaId']) and $requestParams['llicenciaId'] > 0)
				$llicenciaId = $requestParams['llicenciaId'];
			if (isset($requestParams['currentperson']))
				$currentPerson = $requestParams['currentperson'];
			if ($llicenciaId > 0) {
				$llicencia = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityLlicencia')->find($llicenciaId);
				$parte = $llicencia->getParte();
				$codiclub = $parte->getClub()->getCodi();
				$tipusid = $parte->getTipus()->getId();
				$pdf = $this->showPDF($parte);
				$edit = $this->allowEdit($parte);
			} else {
				$parte = new EntityParte($this->getCurrentDate());
				$parte->setDataalta($dataalta_parte);
				$parte->setTipus($this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParteType')->find($tipusid));

				// Noves llicències, permeten edició no pdf
				$llicencia = $this->prepareLlicencia($tipusid, $parte->getDataCaducitat());
				
				$edit = true;
				$pdf = false;
			}
			// Person submitted
			if ($currentPerson > 0)
				$llicencia->setPersona($this->getDoctrine()->getRepository('FecdasPartesBundle:EntityPersona')
						->find($currentPerson));
			$options['codiclub'] = $codiclub;
			$options['tipusparte'] = $tipusid;
			if ($edit == true) $options['edit'] = true;
			$options['any'] = $parte->getAny(); // Mostrar preu segons any parte
			
			$formllicencia = $this->createForm(new FormLlicencia($options), $llicencia);
			if ($formllicencia->has('datacaducitatshow') == true)
				$formllicencia->get('datacaducitatshow')->setData($formllicencia->get('datacaducitat')->getData());

			// Comprovar data llicències reduïdes. Alta posterior 01/09 any actual
			$datainici_reduida = new \DateTime(date("Y-m-d", strtotime(date("Y") . "-09-01")));
			if (($tipusid == 5 or $tipusid == 6) and ($dataalta_parte < $datainici_reduida)) { // reduïdes
				$this->get('session')->setFlash('error-notice',	'Les llicències reduïdes només a partir de 1 de setembre');
			}
			
			return $this->render('FecdasPartesBundle:Page:partellicencia.html.twig',
					array('llicencia' => $formllicencia->createView(),
							'pdf' => $pdf, 'edit' => $edit,
							'asseguranca' => $parte->isAsseguranca(),
							'llicenciadades' => $llicencia));
		}

		return new Response("Error. Contacti amb l'administrador (llicenciaAction)");
	}

	public function personaAction() {
		$request = $this->getRequest();

		$options = array();
		/* Get provincies, comarques, nacions*/
		$options['nova'] = false;
		$options['provincies'] = $this->getProvincies();
		$options['comarques'] = $this->getComarques();
		$options['nacions'] = $this->getNacions();
		
		if ($request->getMethod() == 'POST') {
			
			$p = $request->request->get('persona');

			$codiclub = "";

			if ($request->request->has('codiclub'))
				$codiclub = $request->request->get('codiclub');

			if ($p['id'] != "") {
				$persona = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityPersona')->find($p['id']);
			} else {
				$persona = new EntityPersona($this->getCurrentDate());
				// Assignar club
				$persona->setClub($this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')->find($codiclub));
				$options['nova'] = true;
			}

			$formpersona = $this->createForm(new FormPersona($options), $persona);
			
			$formpersona->bindRequest($request);
			
			if ($formpersona->isValid()) {
				if ($persona->getNom() == "" or $persona->getCognoms() == "") {
					$this->logEntry($this->get('session')->get('username'), 'PERSONA NEW NOM KO',
							$this->get('session')->get('remote_addr'),
							$this->getRequest()->server->get('HTTP_USER_AGENT'));
					return new Response("nomerror");
				}
				if ($persona->getDni() == "") {
					$this->logEntry($this->get('session')->get('username'), 'PERSONA NEW DNI KO',
							$this->get('session')->get('remote_addr'),
							$this->getRequest()->server->get('HTTP_USER_AGENT'));
					return new Response("dnierror");
				}
				
				$em = $this->getDoctrine()->getEntityManager();

				$persona->setDatamodificacio($this->getCurrentDate());
				
				if ($request->request->get('action') == "save") {
					/* Check persona amb dni no repetida al mateix club */
					if ($persona->getId() == null) {
						$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityPersona p ";
						$strQuery .= " WHERE p.dni = :dni ";
						$strQuery .= " AND p.club = :club ";
						$strQuery .= " AND p.databaixa IS NULL";
					
						$query = $em->createQuery($strQuery)
							->setParameter('dni', $persona->getDni())
							->setParameter('club', $persona->getClub()->getCodi());
				
						$personaexisteix = $query->getResult();
						
						if (count($personaexisteix) > 0) {
							$this->logEntry($this->get('session')->get('username'), 'PERSONA NEW DUPLI KO',
									$this->get('session')->get('remote_addr'),
									$this->getRequest()->server->get('HTTP_USER_AGENT'), $persona->getDni());
							return new Response("dnicluberror");
						}
					}
					$persona->setValidat(false);  // No validat, detecció ACCESS

					if ($persona->getId() != null)	{
						$logaction = "PERSONA UPD OK";
						$this->get('session')->setFlash('error-notice',	"Dades modificades correctament");
					}
					else {
						// Canviar format Nom i COGNOMS
						$logaction = "PERSONA NEW OK";
						
						// Specials chars ñ, à, etc... 
						$persona->setCognoms(mb_strtoupper($persona->getCognoms(), "utf-8"));
						$persona->setNom(mb_convert_case($persona->getNom(), MB_CASE_TITLE, "utf-8"));
						$this->get('session')->setFlash('error-notice', "Dades personals afegides correctament");
					}
						
					$em->persist($persona);
					
					$em->flush();
					
					// Després de flush, noves entitats tenen id
					$this->logEntry($this->get('session')->get('username'), $logaction,
							$this->get('session')->get('remote_addr'),
							$this->getRequest()->server->get('HTTP_USER_AGENT'), $persona->getId());
					
					$request->request->set('currentperson', $persona->getId());
				} else { // Esborrar
					// Check si persona té alguna llicència associada
					$llicenciesPersona = $em->getRepository('FecdasPartesBundle:EntityLlicencia')
					->findBy(array('persona' => $persona->getId(), 'databaixa' => null));

					if ($llicenciesPersona != null) { 
						$logaction = "PERSONA DEL KO";
						$this->get('session')->setFlash('error-notice',	"Aquesta persona té llicències i no es pot esborrar");
						$request->request->set('currentperson', $persona->getId());
					} else {
						$logaction = "PERSONA DEL OK";
						$persona->setDatabaixa($this->getCurrentDate());
						$em->persist($persona); // Per delete seria remove
						$em->flush();
						$request->request->set('currentperson', 0);
						$this->get('session')->setFlash('error-notice', "Dades personals esborrades correctament");
					}
					
					$this->logEntry($this->get('session')->get('username'), $logaction,
							$this->get('session')->get('remote_addr'),
							$this->getRequest()->server->get('HTTP_USER_AGENT'),$persona->getId());
				}
				$request->request->set('personaAction', 'personaAction');
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

			if ($request->request->get('llicencia') == true) { 
				$response = $this->forward('FecdasPartesBundle:Page:llicencia');
				return $response;
			} else {
				return new Response();
			}
		}
		if ($request->isXmlHttpRequest()) {
			
			// Reload form persona
			$persona = new EntityPersona($this->getCurrentDate());
			
			if ($request->query->has('persona')) {
				if ($request->query->get('persona') != "") { // Select diferent person
					$persona = $this->getDoctrine()
					->getRepository('FecdasPartesBundle:EntityPersona')
					->find($request->query->get('persona'));
				} else {
					$options['nova'] = true;
					$persona->setDatanaixement(	new \DateTime(date("Y-m-d", strtotime(date("Y-m-d") . " -40 year"))));
					$persona->setSexe("H");
					$persona->setAddrnacionalitat("ESP");
				}
			}
			
			$formpersona = $this->createForm(new FormPersona($options), $persona);
			
			return $this->render('FecdasPartesBundle:Page:persona.html.twig',
					array('formpersona' => $formpersona->createView(),));
		}

		return new Response("Error personaAction ");
	}

	public function pagamentAction() {
		$request = $this->getRequest();

		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));

		$parteid = 0;

		if ($request->getMethod() == 'POST') {
			$p = $request->request->get('parte');
			$parteid = $p['id'];

			$this->logEntry($this->get('session')->get('username'), 'PAGAMENT VIEW',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parteid);
			
			$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteid);

			if ($parte == null || $parte->getDatapagament() != null)
				return $this->redirect($this->generateUrl('FecdasPartesBundle_partes'));

			// Get factura detall
			$detallfactura = $this->getDetallFactura($parte);

			// Get factura totals
			$totalfactura = $this->getTotalsFactura($detallfactura);
		} else {
			// NO podem entrar per GET
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
		}

		$preu = $totalfactura['total'];
		$desc = 'Pagament a FECDAS, llista d\'assegurats del club ' . $parte->getClub()->getCodi() . ' en data ' . $parte->getDataalta()->format('d/m/Y');
		$dades =  $parte->getId() . ";" . $this->get('kernel')->getEnvironment() . ";" . $this->get('session')->get('username');
		$payment = new EntityPayment($preu, $desc, $parte->getClub()->getNom(), $dades);
		$formpayment = $this->createForm(new FormPayment(), $payment);

		return $this->render('FecdasPartesBundle:Page:pagament.html.twig',
				array('formpayment' => $formpayment->createView(), 'payment' => $payment,
						'parte' => $parte, 'detall' => $detallfactura, 'admin' => $this->isCurrentAdmin(),
						'totals' => $totalfactura, 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}
	
	public function notificacioTestAction() {
		
		$formBuilder = $this->createFormBuilder()->add('Ds_Response', 'text');
		$formBuilder->add('Ds_MerchantData', 'text', array('required' => false));
		$formBuilder->add('Ds_Date', 'text');
		$formBuilder->add('Ds_Hour', 'text');
		$formBuilder->add('Ds_Order', 'text');
		$formBuilder->add('Ds_PayMethod', 'text', array('required' => false));
		$formBuilder->add('accio', 'choice', array(
				'choices'   => array($this->generateUrl('FecdasPartesBundle_notificacio') => 'FecdasPartesBundle_notificacio', 
									$this->generateUrl('FecdasPartesBundle_notificacioOk') => 'FecdasPartesBundle_notificacioOk',
									$this->generateUrl('FecdasPartesBundle_notificacioKo') => 'FecdasPartesBundle_notificacioKo'),
				'required'  => true,
		));
		
		$form = $formBuilder->getForm();
		$form->get('Ds_Response')->setData(0);
		$form->get('Ds_MerchantData')->setData("1;dev;alexmazinho@gmail.com");
		$form->get('Ds_Date')->setData(date('d/m/Y'));
		$form->get('Ds_Hour')->setData(date('h:i'));
		$form->get('Ds_Order')->setData(date('Ymdhi'));
		$form->get('Ds_PayMethod')->setData('');
		
		return $this->render('FecdasPartesBundle:Page:notificacioTest.html.twig',
				array('form' => $form->createView(), 
						'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
						'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}
	
	public function notificacioOkAction() {
		// Resposta TPV on-line, genera resposta usuaris correcte
		$request = $this->getRequest();
	
		$tpvresponse = $this->tpvResponse($request->query);
		$remoteaddr = $this->getRequest()->server->get('REMOTE_ADDR');
		$useragent = $this->getRequest()->server->get('HTTP_USER_AGENT');
	
		if ($tpvresponse['parteId'] > 0) {
			$this->logEntry($tpvresponse['username'], 'TPV NOTIFICA OK', $remoteaddr, $useragent, $tpvresponse['logEntry']);
	
			return $this->render('FecdasPartesBundle:Page:notificacio.html.twig',
					array('result' => 'ok', 'parteId' => $tpvresponse['parteId']));
		}
	
		$this->logEntry($tpvresponse['username'], 'TPV NOTIFICA NO DATA', $remoteaddr, $useragent, $tpvresponse['logEntry']);
	
		return $this->render('FecdasPartesBundle:Page:notificacio.html.twig',
				array('result' => 'ko', 'parteId' => 0));
	}
	
	public function notificacioKoAction() {
		// Resposta TPV on-line, genera resposta usuaris incorrecte		
		$request = $this->getRequest();
	
		$tpvresponse = $this->tpvResponse($request->query);
		$remoteaddr = $this->getRequest()->server->get('REMOTE_ADDR');
		$useragent = $this->getRequest()->server->get('HTTP_USER_AGENT');
	
		if ($tpvresponse['pendent'] == true) {
			$this->logEntry($tpvresponse['username'], 'TPV NOTIFICA PEND', $remoteaddr, $useragent, $tpvresponse['logEntry']);
				
			return $this->render('FecdasPartesBundle:Page:notificacio.html.twig',
					array('result' => 'pend', 'parteId' => $tpvresponse['parteId'] ) );
		}
	
		$this->logEntry($tpvresponse['username'], 'TPV NOTIFICA KO', $remoteaddr, $useragent, $tpvresponse['logEntry']);
	
		return $this->render('FecdasPartesBundle:Page:notificacio.html.twig',
				array('result' => 'ko'));
	}
	
	public function notificacioAction() {
		// Crida asincrona des de TPV. Actualització dades pagament del parte
		$request = $this->getRequest();

		$tpvresponse = $this->tpvResponse($request->request);
		$remoteaddr = $this->getRequest()->server->get('REMOTE_ADDR');
		$useragent = $this->getRequest()->server->get('HTTP_USER_AGENT');
		
		if ($request->getMethod() == 'POST') {
			if ($tpvresponse['Ds_Response'] == 0) {
				// Ok
				$updOK = $this->actualitzarPagament($tpvresponse['parteId'], $tpvresponse['Ds_Order']);
			
				if ($updOK == true) {
					$this->writeNotificacio("************** Notificació OK finalitzada **************", $tpvresponse); 

					$this->logEntry($tpvresponse['username'], 'TPV OK',	$remoteaddr, $useragent, $tpvresponse['logEntry']);
				} else {
					$this->writeErrorPagament("************** Notificació KO error actualitzant parte **************", $tpvresponse);
					
					$this->logEntry($tpvresponse['username'], 'TPV KO',	$remoteaddr, $useragent, $tpvresponse['logEntry']);
				}
			} else {
				if ($tpvresponse['pendent'] == true) {
					// Pendent
					// Enviar mail a Remei 
					$this->sendMailPagamentPendent($tpvresponse['parteId'], $tpvresponse['Ds_Order']);
						
					$this->writeNotificacio(" ************** Pagament pendent de revisió **************", $tpvresponse);
						
					$this->logEntry($tpvresponse['username'], 'TPV PEND', $remoteaddr, $useragent, $tpvresponse['logEntry']);
				} else {
					// Altres. Error
					$this->writeErrorPagament("************** Notificació error **************", $tpvresponse);
						
					$this->logEntry($tpvresponse['username'], 'TPV ERROR', $remoteaddr, $useragent, $tpvresponse['logEntry']);
				}
			}
		} else {
			$this->writeErrorPagament("************** Error NO POST **************", $tpvresponse);
			
			$this->logEntry($tpvresponse['username'], 'TPV NO POST', $remoteaddr, $useragent, $tpvresponse['logEntry']);
		}
			
		return new Response("");
	}
	
	private function actualitzarPagament($parteId, $ordre) {
		
		$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteId);
	
		if ($parte != null) {
			$em = $this->getDoctrine()->getEntityManager();
			// Actualitzar data pagament
			/*
				$numfactura = $this->getMaxNumFactura();
			$parte->setNumFactura($numfactura);*/
			$parte->setEstatPagament("TPV OK");
			$parte->setDadespagament($ordre);
			$parte->setDatapagament($this->getCurrentDate());
			$parte->setImportpagament($parte->getPreuTotalIVA());
			$parte->setDatamodificacio($this->getCurrentDate());
				
			$em->flush();
			return true;
		}
		return false;
	}
	
	private function sendMailPagamentPendent ($parteId, $ordre) {
		$mails = $this->getFacturacioMails();
		
		$em = $this->getDoctrine()->getEntityManager();
		$parte = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParte')->find($parteId);
	
		if ($parte != null) {
			//$parte->setNumFactura(-1);
			$parte->setEstatpagament("TPV PEND");
			$parte->setDadespagament($ordre);
			$parte->setDatamodificacio($this->getCurrentDate());
				
			//$parte->setDatamodificacio($this->getCurrentDate()); No canviar res que calgui detectar ACCESS
			$em->flush();
				
			$message = \Swift_Message::newInstance()
			->setSubject('::Parte pendent de confirmació::')
			->setFrom($this->container->getParameter('fecdas_partes.emails.contact_email'))
			//->setTo($mails)
			->setTo(array("alexmazinho@gmail.com"))
			->setBody($this->renderView('FecdasPartesBundle:Page:partePendentEmail.txt.twig', array('parte' => $parte)));
			$this->get('mailer')->send($message);
		} else {
			// Error, no hauria de passar
			$message = \Swift_Message::newInstance()
			->setSubject('::Parte pendent de confirmació (Error)::')
			->setFrom($this->container->getParameter('fecdas_partes.emails.contact_email'))
			->setTo(array("alexmazinho@gmail.com"))
			->setBody("Error mail pendent confirmació -" . $parteId . "-");
			$this->get('mailer')->send($message);
		}
	}

	private function tpvResponse($tpvdata) {
	
		$tpvresponse = array('parteId' => 0, 'environment' => '', 'username' => 'logerror@fecdasgestio.cat',
				'Ds_Response' => '', 'Ds_Order' => 0, 'Ds_Date' => '', 'Ds_Hour' => '',
				'Ds_PayMethod' => '', 'logEntry' => '', 'pendent' => false);
		if ($tpvdata->has('Ds_MerchantData') and $tpvdata->get('Ds_MerchantData') != '') {
			$dades = $tpvdata->get('Ds_MerchantData');
			$dades_array = explode(";", $dades);
				
			$tpvresponse['parteId'] = $dades_array[0];
			$tpvresponse['environment'] = $dades_array[1];
			$tpvresponse['username'] = $dades_array[2];
		}
	
		if ($tpvdata->has('Ds_Response')) $tpvresponse['Ds_Response'] = $tpvdata->get('Ds_Response');
		if ($tpvdata->has('Ds_Order')) $tpvresponse['Ds_Order'] = $tpvdata->get('Ds_Order');
		if ($tpvdata->has('Ds_Date')) $tpvresponse['Ds_Date'] = $tpvdata->get('Ds_Date');
		if ($tpvdata->has('Ds_Hour')) $tpvresponse['Ds_Hour'] = $tpvdata->get('Ds_Hour');
		if ($tpvdata->has('Ds_PayMethod')) $tpvresponse['Ds_PayMethod'] = $tpvdata->get('Ds_PayMethod');
	
		if (($tpvresponse['Ds_Response'] == '0930' or $tpvresponse['Ds_Response'] == '9930') and $tpvdata->get('Ds_PayMethod') == 'R') {
			$tpvresponse['pendent'] = true;
		}
		
		$tpvresponse['logEntry'] = $tpvresponse['parteId'] . "-" . $tpvresponse['Ds_Response'] . "-" .
				$tpvresponse['environment'] . "-" . $tpvresponse['Ds_Date'] . "-" .
				$tpvresponse['Ds_Hour'] . "-" . $tpvresponse['Ds_Order'] . "-" . $tpvresponse['Ds_PayMethod'];
	
		return $tpvresponse;
	}
	
	private function writeNotificacio($sms, $tpvresponse)  {
		$this->writeFile("notificacions.txt", $sms, $tpvresponse);
	} 

	private function writeErrorPagament($sms, $tpvresponse)  {
		$this->writeFile("errorspagament.txt", $sms, $tpvresponse);
	}
	
	private function writeFile($file, $sms, $tpvresponse)  {
		$fh = fopen($file, 'a') or die("can't open file"); 

		fwrite($fh, $sms . "\n");
		fwrite($fh, "ara : " . date("d/m/Y H:i:s", time()) .  "\n");
		fwrite($fh, "entorn: ". $tpvresponse['environment']."\n");
		fwrite($fh, "parte: ". $tpvresponse['parteId']."\n");
		fwrite($fh, "data : ".$tpvresponse['Ds_Date']."\n");
		fwrite($fh, "hora : ".$tpvresponse['Ds_Hour']."\n");
		fwrite($fh, "ordre: ".$tpvresponse['Ds_Order']."\n");
		fwrite($fh, "metode: ".$tpvresponse['Ds_PayMethod']."\n");
		fwrite($fh, "resposta: ".$tpvresponse['Ds_Response']."\n");
		fclose($fh);
	}
	
	private function showPDF(EntityParte $parte) {
		return true;
	}

	private function allowEdit(EntityParte $parte) {
		return (boolean) ($parte->getDatapagament() == null); // Allow edition
	}

	private function getFormOptions() {
		return array('edit' => false, 'admin' => false, 'nova' => false,
				'codiclub' => '', 'tipusparte' => 1, 'llistatipus' => array(), 'any' => Date('Y'));
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
			
		$llistatipus = $this->getLlistaTipusParte($day, $month);
		
		$tipuspermesos = "";
		foreach ($llistatipus as $c => $tipus) {
			$entitytipus = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityParteType')->find($tipus);
			$tipuspermesos .= "<option value=" . $tipus . ">" . $entitytipus->getDescripcio() . "</option>";
		}
		
		$response = new Response();
		$response->setContent($tipuspermesos);
		
		return $response;
	}  

	private function getLlistaTipusParte($day, $month) {
		$llistatipus = array();

		$currentmonthday = sprintf("%02d", $month) . "-" . sprintf("%02d", $day);

		$em = $this->getDoctrine()->getEntityManager();
		$repository = $em->getRepository('FecdasPartesBundle:EntityUser');
		$user = $repository->findOneByUser($this->get('session')->get('username'));

		if ($user != null) {
			$club = $user->getClub();
			$tipuspartes = $club->getTipusparte();
			
			foreach ($tipuspartes as $c => $tipusparte) {
				$inici = '01-01';
				$final = '12-31';
				if ($tipusparte->getInici() != null) $inici = $tipusparte->getInici();
				if ($tipusparte->getFinal() != null) $final = $tipusparte->getFinal();
				
				if ($currentmonthday >= $inici and $currentmonthday <= $final) {
					array_push($llistatipus, $tipusparte->getId());
				}
			}
		}
		
		return $llistatipus; 
	}
	
	public function ajaxpoblacionsAction(Request $request) {
		$search = $this->consultaAjaxPoblacions($request->get('term')); 
		$response = new Response();
		$response->setContent(json_encode($search));
		
		return $response;
	}

	public function ajaxclubsnomsAction(Request $request) {
		$search = $this->consultaAjaxClubs($request->get('term'));
		$response = new Response();
		$response->setContent(json_encode($search));
	
		return $response;
	}
	
	/*	
	private function getMaxNumFactura() {
		$em = $this->getDoctrine()->getEntityManager();
		$query = $em->createQuery("SELECT MAX(p.numfactura) FROM Fecdas\PartesBundle\Entity\EntityParte p
				WHERE p.numfactura IS NOT NULL");
		$numfactura = $query->getSingleScalarResult();
		if ($numfactura == null) $numfactura = 0; 
		if ($numfactura == -1) $numfactura = 0; // Pendents
		return $numfactura + 1;
	}*/
}
