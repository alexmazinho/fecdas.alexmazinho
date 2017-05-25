<?php
namespace FecdasBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FecdasBundle\Form\FormLogin;
use FecdasBundle\Form\FormUser;
use FecdasBundle\Form\FormClub;
use FecdasBundle\Form\FormClubAdmin;
use FecdasBundle\Form\FormClubUser;
use FecdasBundle\Entity\EntityUser;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityClub;




class SecurityController extends BaseController
{
	
	public function changeroleAction(Request $request) {
		
		if (!$this->isAuthenticated()) return new Response(""); 
		// Params => currentrole: role, currentclub: club
		
		if (!$request->query->has('currentrole') || $request->query->get('currentrole') == '') return "";
		
		$currentrole = $request->query->get('currentrole');		// Usuaris: parella role;codi	Admin: només role
		
		$checkRole = $this->get('fecdas.rolechecker');	
		
		if ($this->isCurrentAdmin()) {
			if (!$request->query->has('currentclub') || $request->query->get('currentclub') == '') return "";
			
			$currentclub = $request->query->get('currentclub');
		} else {
			$currentroleArray = explode(";", $currentrole);
			if (count($currentroleArray) != 2)  return "";
			$currentrole = $currentroleArray[0];
			$currentclub = $currentroleArray[1];
		}
		$checkRole->setCurrentClubRole( $currentclub, $currentrole );
		
		if (!$this->isCurrentAdmin()) return new Response("reload");
		return new Response("");
	}
	
    public function loginAction(Request $request)
    {
    	if ($this->isAuthenticated()) {
    		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
    	}
    	$userlogin = new EntityUser();
    	$form = $this->createForm(new FormLogin(), $userlogin);
    	
		try {
	    	if ($request->getMethod() == 'POST') {
	    		$form->handleRequest($request);

	    		if (!$form->isValid()) throw new \Exception('Formulari incorrecte!');

	    		$em = $this->getDoctrine()->getManager();
	    		$repository = $em->getRepository('FecdasBundle:EntityUser');
				
	    		$user = $repository->findOneByUser($form->getData()->getUser());
				$this->checkUser($user, $form);
					
				/* Manteniment  */
	    		//$this->get('session')->getFlashBag()->add('sms-notice', 'Aplicació en manteniment, disculpeu les molèsties!');
				
				/* Manteniment */
	    		//$this->get('session')->getFlashBag()->add('sms-notice', 'Lloc web en manteniment, espereu una estona si us plau');
	    		/*return $this->render('FecdasBundle:Security:login.html.twig',
	    				array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));*/
	    		//return $this->redirect($this->generateUrl('FecdasBundle_login'));		
	    		/* Fi Manteniment */
	    		
	    		// 	Redirect - This is important to prevent users re-posting
	    		// 	the form if they refresh the page
				 
				$checkRole = $this->get('fecdas.rolechecker');
				$checkRole->authenticateUser($user, $this->getActiveEnquesta());
	
   				$em->flush();
	
				if ($this->get('session')->has('url_request')) {
					/* Comprovar petició url abans de login. Exemple mail renovacions*/
					$url = $this->get('session')->get('url_request');
					$this->get('session')->remove('url_request');
					
					$this->logEntryAuth('LOGIN + REDIRECT', $url);
						
   					return $this->redirect($url);
   				}
	
	    		/*if ($user->getForceupdate() == true) {
	    			return $this->redirect($this->generateUrl('FecdasBundle_user'));
	    		}*/
	    			
	    		$this->logEntryAuth('LOGIN');		
	    		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	    	}
		} catch (\Exception $e) {
			$this->logEntry($form->getData()->getUser(), 'LOGIN KO');
			
  			$this->get('session')->getFlashBag()->add('error-notice', $e->getMessage());	
		}
    
    	return $this->render('FecdasBundle:Security:login.html.twig', 
						array('form' => $form->createView(), 'admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
	private function checkUser($user, $form)
    {
    	if ($user == null || $user->anulat()) throw new \Exception('Usuari incorrecte!');

		$club = $user->getBaseClub();

		if ($club == null || $user->getBaseRole() == null || $user->getBaseRole()->getRole() == '') throw new \Exception('Usuari sense accés, poseu-vos en contacte amb la Federació');

		if (!$club->getActivat()) throw new \Exception('Accés desactivat, poseu-vos en contacte amb la Federació'); 

		if ($user->getPwd() != sha1($form->getData()->getPwd())) throw new \Exception('Paraula clau incorrecta!');
		
    }
	
    public function logoutAction(Request $request)
    {
    	$this->logEntryAuth('LOGOUT');	
    	
    	$this->get('session')->clear();
    	
    	$this->get('session')->getFlashBag()->add('sms-notice', 'Sessió finalitzada!');
    	
    	return $this->render('FecdasBundle:Security:logout.html.twig',
						array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    public function userAction(Request $request)
    {
    
    	$request->getSession()->getFlashBag()->clear();
    	
		$checkRole = $this->get('fecdas.rolechecker');
		
    	$username = '';
		$token = '';
    	if ($this->isAuthenticated()) { 
    		// Canvi password normal
    		
    		$user = $checkRole->getCurrentUser();
			$username = $user->getUser();
    	} else {
			// Recuperació de de password
    		if ($request->getMethod() == 'GET') {
    			$username =  $request->query->get('user');
    			$token = sha1($request->query->get('token'));
    		} else {
    			$userdata = $request->request->get('user');
    			$username =  $userdata['usertoken'];
    			$token = $userdata['recoverytoken'];
    		}
    		$user = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->findOneBy(array('user' => trim($username)));
    		 
    		if ($user == null || $username == '' || $token = '' || $token != $user->getRecoverytoken()) {
   				$this->get('session')->getFlashBag()->add('sms-notice', 'L\'enllaç per recuperar la clau ja no és vigent');
    			return $this->redirect($this->generateUrl('FecdasBundle_login'));
    		}
    		
    		if ($user->getRecoveryexpiration() == null 
    				||  $this->getCurrentDate('now') > $user->getRecoveryexpiration()) {
    			$this->get('session')->getFlashBag()->add('sms-notice', 'L\'enllaç per recuperar la clau ha caducat, cal tornar a demanar-la.');
    			return $this->redirect($this->generateUrl('FecdasBundle_login'));
    		}
    	}
    	
    	$form = $this->createForm(new FormUser(), $user);
    	$form->get('usertoken')->setData($username);
    	
    	if ($request->getMethod() == 'POST') {
    		$userdata = $request->request->get('user');
  			
    		if ($userdata['pwd']['first'] != $userdata['pwd']['second']) $this->get('session')->getFlashBag()->add('error-notice', "No coincideixen les claus!"); 
    		else {
	    		$form->handleRequest($request);
	    		
	    		if ($form->isValid()) {
	    			$em = $this->getDoctrine()->getManager();
	    			
	    			$user->setPwd(sha1($user->getPwd())); 

					if (!$this->isAuthenticated()) $checkRole->authenticateUser($user, $this->getActiveEnquesta());  
	    			
	    			$em->flush();

	    			$this->logEntryAuth('PWD RESET');
	    			
	    			$this->get('session')->getFlashBag()->add('error-notice', "Paraula clau actualitzada correctament!");
	    		} else {
					
	    			$this->get('session')->getFlashBag()->add('error-notice', "Error, contacti amb l'administrador");
	    		}
    		}
    	}
    	
    	return $this->render('FecdasBundle:Security:user.html.twig',
    			$this->getCommonRenderArrayOptions(array('form' => $form->createView())) );
    }
    
    
    public function pwdrecoveryAction(Request $request)
    {
    	if ($this->isAuthenticated()) {
    		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
    	}
    	 
    	$formbuilder = $this->createFormBuilder()->add('user', 'email');
    	$form = $formbuilder->getForm();
    	
    	if ($request->getMethod() == 'POST') {
    		$form->handleRequest($request);
    		if ($form->isValid()) {
    			
    			$userEmail = $form->get('user')->getData();
    			//$userEmail = $form->getData()->getUser();
    			
    			$em = $this->getDoctrine()->getManager();
    			$repository = $em->getRepository('FecdasBundle:EntityUser');
    			$user = $repository->findOneByUser($userEmail);
    			
    			if (!$user) {
    				$this->get('session')->getFlashBag()->add('sms-notice', 'Aquest usuari no existeix a la base de dades');
    			} else {
    				$token = base64_encode(openssl_random_pseudo_bytes(30));
    				$expiration = $this->getCurrentDate('now');
    				$expiration->add(new \DateInterval('PT4H'));
    				
    				$em = $this->getDoctrine()->getManager();
    				// Save token information encrypted
    				$user->setRecoverytoken(sha1($token));
    				$user->setRecoveryexpiration($expiration);
    				$em->flush();
    				
    				$message = \Swift_Message::newInstance()
    				->setSubject('::Recuperació accés aplicació gestió FECDAS::')
    				->setFrom($this->container->getParameter('fecdas_partes.emails.contact_email'))
    				->setTo(array($userEmail));
    				
    				$logosrc = $message->embed(\Swift_Image::fromPath('images/fecdaslogo.png'));    				
    				
    				$body = $this->renderView('FecdasBundle:Security:recuperacioClauEmail.html.twig',
    						array('user' => $user, 'token' => $token, 'logo' => $logosrc));
    				
    				$message->setBody($body, 'text/html');
    				
    				$this->get('mailer')->send($message);
    				
    				$this->logEntry($userEmail, 'PWD RECOVER');
    				
    				$this->get('session')->clear();
    				$this->get('session')->getFlashBag()->add('sms-notice', 'S\'han enviat instruccions per a recuperar la clau a l\'adreça de correu ' . $userEmail);
    				
    				return $this->render('FecdasBundle:Security:logout.html.twig',
    						array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    			}
    		} 
    	}
    			
    	return $this->render('FecdasBundle:Security:pwdrecovery.html.twig', 
    			array('form' => $form->createView(), 'admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    
	public function clubaddjuntaAction(Request $request) {
		
		$codi = $request->query->get('codi', '');
		$id = $request->query->get('id', 0)*1;   // id del federat 
		$nommembre = $request->query->get('nommembre', 0);	// nom del membre del club en cas de no ser federat
		$carrec = $request->query->get('carrec', 0)*1;
		
		try {
			/* De moment administradors */
	    	/*if ($this->isCurrentAdmin() != true) {
	    		$this->logEntryAuth('ADD JUNTA NO ADMIN',	'club : ' . $codi);
				throw new \Exception("Acció no permesa, es desarà al registre");
			}*/
			
			$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
			
			if ($club == null) {
	    		$this->logEntryAuth('ADD JUNTA ERROR',	'club no trobat : ' . $codi);
	    		throw new \Exception("Club no trobat ".$codi);
			}

			if ($id > 0) {			
				$personaOk = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($id);
					
				if ($personaOk == null) {
					$this->logEntryAuth('ADD JUNTA ERROR',	'club : ' . $codi.' persona no trobada '.$id);
		    		throw new \Exception("Persona no trobada ".$id);
				}
				$nommembre = $personaOk->getNomCognoms();
			} else {
				if ($nommembre == '') {
	    			$this->logEntryAuth('ADD JUNTA ERROR',	'Nom nou membre junta no indicat ');
	    			throw new \Exception("Cal indicar el nom del nou membre de la junta ");
				}
			}
			
			$em = $this->getDoctrine()->getManager();	
			$jsonCarrecs = ($club->getCarrecs() != ''?json_decode($club->getCarrecs()):array());
	
			$key = -1; //nou
			$num = 1;
			if ($carrec == BaseController::CARREC_VOCAL) { // Vocal, afegir nou
				foreach ($jsonCarrecs as $value) {
					if ($value->cid == BaseController::CARREC_VOCAL) $num++;
				}
				$jsonCarrecs[] = (object) array('id' =>	$id, 'cid'	=>	$carrec, 'nc' => $num, 'nom' => $nommembre);
				
			} else { // Altres càrrecs, substituir
				foreach ($jsonCarrecs as $k => $value) {
					if ($carrec == $value->cid) $key = $k;
				}
				// No existeix
				if ($key < 0) $jsonCarrecs[] = (object) array('id' =>	$id, 'cid'	=>	$carrec, 'nc' => 1, 'nom' => $nommembre); // add
				else $jsonCarrecs[$key] = (object)  array('id' =>	$id, 'cid'	=>	$carrec, 'nc' => 1, 'nom' => $nommembre);		// upd
				
				usort($jsonCarrecs, function($a, $b) {
		    		if ($a === $b) {
		    			return 0;
		    		}
					
					if ($a->cid == $b->cid) return ($a->nc < $b->nc? -1:1); 
					
		    		return ($a->cid*1 < $b->cid*1? -1:1);
	    		});
				
			}
			$club->setCarrecs(json_encode($jsonCarrecs));
			$em->flush();
				
			$this->logEntryAuth('ADD JUNTA OK',	'club : ' . $codi.' persona '.$id.'-'.$nommembre.' carrec'.$carrec);
					
		} catch (\Exception $e) {
			$response = new Response($e->getMessage());
	    	$response->setStatusCode(500);
	    	return $response;
		}

		return $this->responseJunta($jsonCarrecs, $club);
	}

	public function clubupdatejuntaAction(Request $request) {
		$codi = $request->query->get('codi', '');	
		$keyid = $request->query->get('id', 0); // Inclou cid-nc  => carrec id + '-' + número de càrrec
		$nommembre = $request->query->get('nommembre', '');
		$response = new Response('');
		
		try {
			/* De moment administradors */
	    	/*if ($this->isCurrentAdmin() != true) {
	    		$this->logEntryAuth('DEL JUNTA NO ADMIN',	'club : ' . $codi);
				throw new \Exception("Acció no permesa, es desarà al registre");
			}*/

			if ($nommembre == '') {
	    		$this->logEntryAuth('ADD JUNTA ERROR',	'Nom del membre junta no indicat ');
	    		throw new \Exception("Cal indicar el nom del membre de la junta ");
			}
			
			$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
			
			if ($club == null) {
	    		$this->logEntryAuth('UPD JUNTA ERROR',	'club no trobat : ' . $codi);
	    		throw new \Exception("Club no trobat ".$codi);
			}
			
			$em = $this->getDoctrine()->getManager();	
			$jsonCarrecs = ($club->getCarrecs() != ''?json_decode($club->getCarrecs()):array());

			$key = -1; 
			$ncUpd = 0;
			foreach ($jsonCarrecs as $k => $value) {

				if ($value->cid.'-'.$value->nc == $keyid) {
					$key = $k;
						
					$jsonCarrecs[$key] = (object) array('id' 	=>	$value->id, 
												'cid'	=>	$value->cid, 
												'nc' 	=> $value->nc, 
												'nom' 	=> $nommembre);		// upd
						
					$club->setCarrecs(json_encode($jsonCarrecs));
					$em->flush();
					$this->logEntryAuth('UPD JUNTA OK',	'club : ' . $codi.' keyid '.$keyid);
		
					return $this->responseJunta($jsonCarrecs, $club);
				}
			}
			
			$this->logEntryAuth('UPD JUNTA ERROR',	'No trobat => club : ' . $codi. ' id: '.$keyid);
			throw new \Exception("Càrrec no trobat");
			
		} catch (\Exception $e) {
			$response = new Response($e->getMessage());
	    	$response->setStatusCode(500);
		}
		return $response;
	}

	public function clubremovejuntaAction(Request $request) {
		$codi = $request->query->get('codi', '');	
		$keyid = $request->query->get('id', 0); // Inclou cid-nc  => carrec id + '-' + número de càrrec
		$club = null;
		
		try {
			/* De moment administradors */
	    	/*if ($this->isCurrentAdmin() != true) {
	    		$this->logEntryAuth('DEL JUNTA NO ADMIN',	'club : ' . $codi);
				throw new \Exception("Acció no permesa, es desarà al registre");
			}*/
			
			$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
			
			if ($club == null) {
	    		$this->logEntryAuth('DEL JUNTA ERROR',	'club no trobat : ' . $codi);
	    		throw new \Exception("Club no trobat ".$codi);
			}
			
			$em = $this->getDoctrine()->getManager();	
			$jsonCarrecs = ($club->getCarrecs() != ''?json_decode($club->getCarrecs()):array());
			
			$key = -1; 
			$ncDel = 0;
			foreach ($jsonCarrecs as $k => $value) {
				if ($value->cid.'-'.$value->nc == $keyid) {
					$key = $k;
					if ($value->cid == BaseController::CARREC_VOCAL) $ncDel = $value->nc;
				}
				if ($value->cid == BaseController::CARREC_VOCAL && $ncDel > 0 && $value->nc > $ncDel) { // upd nums
					$jsonCarrecs[$k]->nc = ($value->nc - 1);
				}
			}
			if ($key < 0) {
				$this->logEntryAuth('DEL JUNTA ERROR',	'No trobat => club : ' . $codi. ' id: '.$keyid);
				throw new \Exception("Càrrec no trobat");
			} 
			
			array_splice($jsonCarrecs, $key, 1);  // del
			$club->setCarrecs(json_encode($jsonCarrecs));
			$em->flush();
			
			$this->logEntryAuth('DEL JUNTA OK',	'club : ' . $codi.' keyid '.$keyid);
			
		} catch (\Exception $e) {
			$response = new Response($e->getMessage());
	    	$response->setStatusCode(500);
			return $response;
		}
		
		return $this->responseJunta($jsonCarrecs, $club);
	}

	private function responseJunta($jsonCarrecs, $club) {
		
		$carrecs = $this->getArrayCarrecs($jsonCarrecs);
		
		$htmlResponse = $this->renderView('FecdasBundle:Security:clubtaulajunta.html.twig',	$this->getCommonRenderArrayOptions(array('club' => $club, 'carrecs' => $carrecs) ) );
		
		return new Response( json_encode( array(
				'table' => $htmlResponse,
				'value'	=> json_encode($jsonCarrecs)
		)));
	}

	
    public function clubAction(Request $request) {
    	if ($this->isAuthenticated() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_login'));
    
    	/* De moment administradors */
    	/*if ($this->isCurrentAdmin() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_login'));*/
    	
    	$club  = $this->getCurrentClub();
		$tab	= $id = $request->query->get('tab', 0);;    	
    	$nouclub = false;
		$clubCodi = '';
		$form = null;
		$carrecs = array();
		$optionsForm = array( 'comarques' => $this->getComarques(), 'provincies' => $this->getProvincies(), 'admin' => $this->isCurrentAdmin() );
	
		try {
			$em = $this->getDoctrine()->getManager();

			if ($request->getMethod() == 'POST') {
				$formdata = $request->request->get('club');

				if (isset($formdata['nouclub']) && $this->isCurrentAdmin() != true) throw new \Exception("Acció no permesa");	// Només admins poden crear clubs
				
		   		if (isset($formdata['nouclub']) && $this->isCurrentAdmin() == true) $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find( $formdata['codi'] ); 

		   		if ($club == null) {
		   					
		   			$club = new EntityClub();

					if (!isset($formdata['codi'])) $codiNou = $this->obtenirCodiClub();
					else $codiNou = $formdata['codi'];
						
					$club->setCodi($codiNou);
					
					$em->persist($club);
	
					$nouclub = true;	
				}
			} else {
		
				if ($this->isCurrentAdmin() != true) $action = 'club view';
				else {
					$action = $request->query->get('action', 'admin club view');
					
	   				if ($request->query->has('action') && $action == "adduser") {
						$tab = 3;
					}
					
	   				if ($request->query->has('action') && $action == "nouclub") {
	   					$codiNou = $this->obtenirCodiClub();
	    				$club = new EntityClub();
						
						$club->setCodi($codiNou);
						
	    				$nouclub = true;
	    			} else {
	    				//$this->get('session')->getFlashBag()->clear();
	    				if ($request->query->has('codiclub')) {
	    					// Edit club, external GET 
	    					$codiclub = $request->query->get('codiclub');
	    					$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codiclub);
	    				}
	    			}
	   			}
				
				$this->logEntryAuth('CLUB VIEW OK',	$action. ' : ' . $club->getCodi());
				
				if ($action == "nouclub") $em->persist($club);

	   		}

	   		if ($this->isCurrentAdmin() != true) $form = $this->createForm(new FormClub( $optionsForm ), $club);
			else $form = $this->createForm(new FormClubAdmin( $optionsForm ), $club);
			
			$jsonCarrecs = ($club->getCarrecs() != ''?json_decode($club->getCarrecs()):array());
			
			$carrecs = $this->getArrayCarrecs($jsonCarrecs);
			
	   		if ($request->getMethod() == 'POST') {
	   			
				$currentMails = $club->getMails(); // Array
				
	   			$form->handleRequest($request);
	   			
	   			if (!$form->isValid()) throw new \Exception("error validant les dades". $form->getErrorsAsString());
				
	   			/* Validacions dades obligatories*/
	   			if (trim($club->getNom()) == "") {
	   				$tab = 0;	
	   				throw new \Exception("Cal indicar el nom");
				}
					
				if (trim($club->getCif()) == "") {
					$tab = 0;		
					throw new \Exception("Cal indicar CIF");
				}
					
				if (trim($club->getMail()) == "") {
					$tab = 0;	
					throw new \Exception("Cal indicar un mail");
				}
	   			
				if ($this->isCurrentAdmin() == true) {	
					if ($club->getCompte() == '' || strlen($club->getCompte()) <> 7 || !is_numeric($club->getCompte())) {
						$tab = 2;
						throw new \Exception("El compte comptable ha de tenir longitud 7 i ser numèric");
					}
					$checkcompte =  $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->findOneBy(array('compte' => $club->getCompte()));
					if ($checkcompte != null && $club->getCodi() != $checkcompte->getCodi())  {
						$tab = 2;
						throw new \Exception("El compte ".$club->getCompte()." ja existeix per al club " . $checkcompte->getNom());
					}
		   			
		   			if ($club->getDatabaixa() != null) $club->setActivat(false);
		   		}
					
	   			/* Validacions mail no existeix en altres clubs */
	   			foreach ($club->getMails() as $mail) {
		   			$checkuser = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->findOneBy(array('user' => trim($mail)));
		   			if ($checkuser != null && !$checkuser->anulat()) {
		   				$roleClubExistent = $checkuser->getRoleClub();
						
						if ($roleClubExistent != null && $nouclub) {
							// Nou club
							$tab = 0;	
							throw new \Exception("Aquest mail ja existeix per un altre club: " . $mail);
						}
							 
						if ($roleClubExistent != null && $roleClubExistent->getClub() !== $club) {
		   					// Actualització club
		   					$tab = 0;	
							throw new \Exception("Aquest mail el fa servir un altre club: " . $mail);
		   				}
		   			}
 				}
	   					
    			if ($nouclub) {
    				// Nou club
    				$club->setEstat($this->getDoctrine()->getRepository('FecdasBundle:EntityClubEstat')->find(self::CLUB_PAGAMENT_DIFERIT));
    				$em->persist($club);
	    				
	    			// Crear el primer usuari de club, amb el mail del club
	    			$randomPassword = $this->generateRandomPassword();
	    			$mails = $club->getMails();
	    				
					if (count($mails) <= 0) throw new \Exception("No s'ha trobat cap adreça de mail vàlida " . $club->getMail());
					
					// Tot OK afegir primer usuari al club role club
					$info = "";
					$userclub = $this->checkUsuariClub($club, $info, BaseController::ROLE_CLUB, $mails[0], $randomPassword);

    				$this->get('session')->getFlashBag()->add('sms-notice', 'Club creat correctament. '.$info);
    			} else {
    				$this->get('session')->getFlashBag()->add('sms-notice', 'Dades del club desades correctament ');
    			}
	    		$em->flush(); // Error
	   			$this->logEntryAuth(($nouclub)?"CLUB NEW ":"CLUB UPD " . 'OK', 'club : ' . $club->getCodi());
				return $this->redirect($this->generateUrl('FecdasBundle_club', array( 'codiclub' => $club->getCodi(), 'tab' => $tab )));
				
	   		}
		} catch (\Exception $e) {
			$em->clear();
			$this->get('session')->getFlashBag()->add('error-notice', $e->getMessage());
			$this->logEntryAuth(($nouclub)?"CLUB NEW ":"CLUB UPD ". 'KO', 'club : ' . $clubCodi . ' - ' . $e->getMessage());
		}
   		
		if ($form == null) {
			if ($this->isCurrentAdmin() != true) $form = $this->createForm(new FormClub( $optionsForm ), $club);
			else $form = $this->createForm(new FormClubAdmin( $optionsForm ), $club);		
		}
		
    	return $this->render('FecdasBundle:Security:club.html.twig', 
    			$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'club' => $club, 'nouclub' => $nouclub, 'tab' => $tab, 'carrecs' => $carrecs)));
    }

	private function getArrayCarrecs($jsonCarrecs) {

		$carrecs = array();
		foreach ($jsonCarrecs as $key => $value) {
			
			if ($value->id == 0 || $value->id != '') {
				$carrecs[$value->cid."-".$value->nc] = array(
						'id' 		=> 0, 
						'carrec' 	=> BaseController::getCarrec($value->cid).($value->cid==BaseController::CARREC_VOCAL?' '.$value->nc:''), 
						'nom' 	 	=> (isset($value->nom) != true?'desconegut':$value->nom )
				);
			} else {
				$membreJunta = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($value->id);
				$carrecs[$value->cid."-".$value->nc] = array(
						'id' 		=> $value->id, 
						'carrec' 	=> BaseController::getCarrec($value->cid).($value->cid==BaseController::CARREC_VOCAL?' '.$value->nc:''), 
						'nom' 	 	=> ($membreJunta != null? $membreJunta->getNomCognoms() : '' )
				);
			}
		}
		
		return $carrecs;
	}

    private function obtenirCodiClub() {
    	$em = $this->getDoctrine()->getManager();
		
		$strQuery = " SELECT c.codi FROM FecdasBundle\Entity\EntityClub c ORDER BY c.codi ";
		$query = $em->createQuery($strQuery);
		
		$codis = $query->getArrayResult();

		$indexTest = 0;		
		foreach ($codis as $codi) {
			$strTest = 'CAT'.str_pad($indexTest, 3, '0', STR_PAD_LEFT);

			if (strtoupper($codi['codi']) > $strTest) return $strTest;
			
			$indexTest++;
		}
		return 'ERROR';
    }

    public function baixaclubAction(Request $request) {
    	if ($this->isAuthenticated() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_login'));
    	
		$codi	= $id = $request->query->get('club', '');    	

		$em = $this->getDoctrine()->getManager();
	    
		$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		
		try {
			if ($this->isCurrentAdmin() != true) throw new \Exception('Acció no permesa');
			
			$current = $this->getCurrentDate();
			if ($club == null) throw new \Exception('No s\'ha pogut trobar el club per donar-lo de baixa');

			$club->setActivat(false);
			$club->setDatabaixa($current);
			
			// Baixa usuaris del club
			foreach ($club->getUsuaris() as $userClubRole) {
				if (!$userClubRole->anulat()) $userClubRole->setDatabaixa(new \DateTime('now'));
			}
			
			$em->flush(); // Error
	   		
			$this->logEntryAuth('BAIXA CLUB OK', 'club : ' . $club->getCodi());
		
			$response = new Response('Club donat de baixa en data '.$current->format('d/m/Y'));
		} catch (\Exception $e) {
			$response = new Response($e->getMessage());
	    	$response->setStatusCode(500);
		}

		return $response;		
    }

    public function usuariclubAction(Request $request) {
    	//$this->get('session')->getFlashBag()->clear();
    	$club = null;
		$userclub = null;
		$userrole = '';
		$em = $this->getDoctrine()->getManager();
		$id = 0;
		$info = "";
    	try {
			$optionsForm = array( 'admin' => $this->isCurrentAdmin() );
			
			if ($request->getMethod() != 'POST') $id = $request->query->get('id');
			else {
				$requestParams = $request->request->all();
				$useruser = $requestParams['user']['user'];
	    		$randomPassword = $requestParams['user']['pwd']['first'];
	    		$userrole = $requestParams['user']['role'];
				$idInstructor = $requestParams['user']['auxinstructordni'];
				$id = isset($requestParams['user']['id'])?$requestParams['user']['id']:0;
			}

			$checkRole = $this->get('fecdas.rolechecker');
			$codiclub = $checkRole->getCurrentClubRole();
    		
			$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codiclub);
			if ($club == null) throw new \Exception("Error. Contacti amb l'administrador (200)");
			
			if (!$checkRole->isCurrentAdmin() && !$checkRole->isCurrentClub()) throw new \Exception("Acció no permesa");
	    
			if (!$request->isXmlHttpRequest()) throw new \Exception("Error. Contacti amb l'administrador (100)");
			
			
			if ($request->getMethod() == 'POST') {
				// Validar i recuperar usuari existent si escau 
	    		//$forceupdate = (isset($requestParams['club']['forceupdate']))? true: false;
  			
	    		$userclub = $this->checkUsuariClub($club, $info, $userrole, $useruser, $randomPassword, $idInstructor);
			} else {
				if ($id > 0) $userclub = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($id);
				else {
					$userclub = new EntityUser();
					$em->persist($userclub);
				}
			}
			
			if ($request->getMethod() != 'POST') {
								
				if (!$request->query->has('action')) return new Response("Error. Contacti amb l'administrador (101)"); 
		
				// Activar o desactivar usuaris
				$action = $request->query->get('action');
				
				switch ($action) {
					case 'open':
						$form = $this->createForm(new FormClubUser( $optionsForm ), $userclub);				
						
						return $this->render('FecdasBundle:Security:clubformuser.html.twig',
   								array('form' => $form->createView(), 'admin' =>$this->isCurrentAdmin()));
						
					case 'addrole':
						$userrole = $request->query->get('role');
						$codiclub = $request->query->get('club');
						
						$userClub = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($id);
						if ($userClub == null) throw new \Exception("Error. Contacti amb l'administrador (301)");
						
						$form = $this->createForm(new FormClubUser( $optionsForm ), $userclub);	
						
						return $this->render('FecdasBundle:Security:clubformuser.html.twig',
   								array('form' => $form->createView(), 'admin' =>$this->isCurrentAdmin()));
						
						
						/*$randomPassword = $this->generateRandomPassword();
						
						//$userclub = $this->checkUsuariClub($club, $info, $userrole, $userClub->getUser(), $randomPassword, ????? $idInstructor);
						$userclub = $this->checkUsuariClub($club, $info, $userrole, $userClub->getUser(), $randomPassword);
						
						$this->get('session')->getFlashBag()->add('sms-notice', $info);*/
						
						break;
					case 'removerole':
						
						$userClubRole = $this->getDoctrine()->getRepository('FecdasBundle:EntityUserClub')->find($id);
			
						if ($userClubRole == null) throw new \Exception('Error. Posa\'t en contacte amb l\'administrador (300)');
		
						$userclub = $userClubRole->getUsuari();
						
						$userClubRole->setDatabaixa(new \DateTime('now'));
						
			 			$this->get('session')->getFlashBag()->add("sms-notice", "Accés " . $userClubRole->getRole(). " anul·lat per l'usuari ".$userclub->getUser());
						
						break;
					
					case 'removeuser':
						$userClub = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($id);
						if ($userClub == null) throw new \Exception("Error. Contacti amb l'administrador (302)");
						
						$userClub->setDatabaixa(new \DateTime('now'));
			
						// Baixa tots els rols
						foreach ($userClub->getRolesClubs($club) as $userClubRole) {
							if (!$userClubRole->anulat()) $userClubRole->setDatabaixa(new \DateTime('now'));
						}
						break;
						
					case 'resetpwd':
						
						$userClub = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($id);
						if ($userClub == null) throw new \Exception("Error. Contacti amb l'administrador (303)");
						
						$randomPassword = $this->generateRandomPassword();
		   				$userclub->setPwd(sha1($randomPassword));
						
		   				$this->get('session')->getFlashBag()->add("sms-notice", "Clau de l'usuari " . $userclub->getUser() . ", canviada: " . $randomPassword);
						
						break;
				}

	   			$em->flush();
				
				$this->logEntryAuth('USER '. strtoupper($action) . ' OK', 'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser(). ' role: '.$userClubRole->getRole());
				
   				return $this->render('FecdasBundle:Security:clubllistausers.html.twig',
   						array('club' => $club, 'admin' =>$this->isCurrentAdmin()));
			} else {
				$form = $this->createForm(new FormClubUser( $optionsForm ), $userclub);
				
	    		// Alta nou usuari de club
	    		$form->handleRequest($request);

	   			if (!$form->isValid()) throw new \Exception("error validant les dades -".$randomPassword."-". $form->getErrors()."     -     ".$form->getErrorsAsString());
								
   				$em->flush();
	   				
   				$this->get('session')->getFlashBag()->add('sms-notice', $info);
	    		
				$this->logEntryAuth('USER CLUB NEW OK', 'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser().' info: '.$info);	
		   			
				return $this->render('FecdasBundle:Security:clubllistausers.html.twig',
   						array('club' => $club, 'admin' =>$this->isCurrentAdmin()));
					
	   		}
		} catch (\Exception $e) {
				
			$this->get('session')->getFlashBag()->clear();
			
			$response = new Response($e->getMessage());
	    	$response->setStatusCode(500);
	    	
			$extra = '. Club : ' . ($club != null?$club->getCodi():'No club') . 
					 ' user: ' . ($userclub != null?$userclub->getUser():'No user') .
					 ' rol: ' . ($userrole != ''?$userrole:'No roles');
						
			if ($request->getMethod() != 'POST') $this->logEntryAuth('USER '.$action.' KO', $e->getMessage().$extra);	
			else $this->logEntryAuth('USER SUBMIT KO', $e->getMessage());
			
			return $response;	// Error
		}
		
		$response = new Response('error 1234');
	    $response->setStatusCode(500);
				
		return $response;	// No hauria d'arribar mai
    }

	private function checkUsuariClub($club, &$info, $userrole, $useruser, $randomPassword, $idInstructor = 0) {
		$em = $this->getDoctrine()->getManager();
		
		$metapersona = null;
		
		// Check NEW Role				
		if ($userrole == BaseController::ROLE_FEDERAT) throw new \Exception("No es poden afegir federats"); // Encara no
				
		if ($userrole == BaseController::ROLE_INSTRUCTOR) {
			// Cal haver indicat un instructor
			$instructor = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($idInstructor);
			
			if ($instructor == null) throw new \Exception("Instructor no trobat ".$idInstructor);
									
			// Validar llicència instructor
			$metaPersona = $instructor->getMetapersona();
			
			$llicenciaVigent = $metaPersona->getLlicenciaVigent();
			if ($llicenciaVigent == null) throw new \Exception("Aquesta persona no té cap llicència vigent ");
			if (!$llicenciaVigent->esTecnic()) throw new \Exception("La llicència actual d'aquesta persona no permet afegir-la com instructor ");
			
			$mailsPersona = $metaPersona->getMails();
			if (!in_array($useruser, $mailsPersona)) throw new \Exception("El mail no és d'aquesta persona ");
			
			
		} else {
			// Només poden fer altes Administradors els propis Administradors
			if ($userrole == BaseController::ROLE_ADMIN && !$this->isCurrentAdmin()) throw new \Exception("Privilegis insuficients per afegir Administradors");
				
			// Un mail pot tenir role administrador només a la FEDERACIÓ ¿? 
			if ($userrole == BaseController::ROLE_ADMIN && !BaseController::esFederacio($club)) throw new \Exception("Només es poden afegir Administradors a la Federació");
		}
				
		$checkuser = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->findOneBy(array('user' => trim($useruser)));

		// Check Roles existents pel mateix mail
		
		if (count($checkuser) > 1) throw new \Exception("Hi ha varis usuaris amb el mateix correu ");
		
		if ($checkuser != null)  {
			
			if ($checkuser->anulat()) {
				$checkuser->setDatabaixa(null); // Tornar a activar
				$info = "Aquest usuari s'ha tornat a activar".PHP_EOL;
			}
							
			/*
			 	Validacions mail per a tots els rols de l'usuari
			 		Un mail no pot tenir el mateix role al mateix  club
			 		Un mail només pot tenir role club en un sol club
					Un mail pot tenir role varis rols federat o instructor a varis clubs <== OK
			*/
			foreach ($checkuser->getClubs() as $checkUserRole) {
				
				if (!$checkUserRole->anulat()) {
					if ($userrole == $checkUserRole->getRole() &&
						$club === $checkUserRole->getClub()) throw new \Exception("Aquest usuari ja disposa d'accés ".$userrole." per aquest club: ".$useruser); 	
					
					if ($userrole != $checkUserRole->getRole() &&
						$club === $checkUserRole->getClub()) throw new \Exception("Aquest usuari ja disposa d'accés, es poden afegir permisos des de la taula d'usuaris ");
						
					if ($userrole == BaseController::ROLE_CLUB && 
						$checkUserRole->getRole() == BaseController::ROLE_CLUB &&
						$club !== $checkUserRole->getClub()) throw new \Exception("Aquest usuari pertany a un altre club: ".$useruser.
																				($this->isCurrentAdmin()?".(Admins) Club ".$checkUserRole->getClub()->getNom():"") ); 	

					// Si usuari existent validar que sigui de la mateixa persona 
					if ($metaPersona != null && $checkUserRole->getMetapersona() != null &&
						$metaPersona !== $checkUserRole->getMetapersona())  throw new \Exception("Aquest usuari pertany a una altra persona");
				}
			}
			
			$userclub = $checkuser;
					
			$metapersona = null;
										
		} else {
			$userclub = new EntityUser($useruser, sha1($randomPassword));
			$userclub->setPwd(sha1($randomPassword));
			$em->persist($userclub);
			
			$info = "Nou usuari ".$userrole." creat correctament amb clau ".$randomPassword.PHP_EOL;
		}
				
		// Tot OK afegir role usuari al club
		$userClubRole = $club->addUsuariRole($userclub, $userrole, $metapersona);
		$info .= "Nou accés ".$userrole." per al club ".$club->getNom();
		$em->persist($userClubRole);
		return $userclub;
	}
	
}
