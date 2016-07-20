<?php
namespace FecdasBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FecdasBundle\Form\FormLogin;
use FecdasBundle\Form\FormUser;
use FecdasBundle\Form\FormClub;
use FecdasBundle\Form\FormClubAdmin;
use FecdasBundle\Entity\EntityUser;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityClub;


class SecurityController extends BaseController
{
	
    public function loginAction(Request $request)
    {
    	if ($this->get('session')->has('username')){
    		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
    	}
    	
    	$userlogin = new EntityUser();
    	$form = $this->createForm(new FormLogin(), $userlogin);
    
    	if ($request->getMethod() == 'POST') {
    		$form->bind($request);
    		if ($form->isValid()) {
    			$em = $this->getDoctrine()->getManager();
    			$repository = $em->getRepository('FecdasBundle:EntityUser');
    			$user = $repository->findOneByUser($form->getData()->getUser());
    			if (!$user || $user->getDatabaixa() != null || $user->getClub()->getActivat() == false) {
    				if (!$user || $user->getDatabaixa() != null) $this->get('session')->getFlashBag()->add('sms-notice', 'Usuari incorrecte!');
					else $this->get('session')->getFlashBag()->add('sms-notice', 'Accés desactivat, poseu-vos en contacte amb la Federació');
    				/* Manteniment  */
    				//$this->get('session')->getFlashBag()->add('sms-notice', 'Aplicació en manteniment, disculpeu les molèsties!');
    			} else {
    				if ($user->getPwd() != sha1($form->getData()->getPwd())) {
    					$this->get('session')->getFlashBag()->add('sms-notice', 'Paraula clau incorrecta!');
    					
    					$this->logEntry($form->getData()->getUser(), 'LOGIN KO',
    							$request->server->get('REMOTE_ADDR'),
    							$request->server->get('HTTP_USER_AGENT'));
    						
    				} else {
	    				/* Manteniment */
    					//$this->get('session')->getFlashBag()->add('sms-notice', 'Lloc web en manteniment, espereu una estona si us plau');
    					/*return $this->render('FecdasBundle:Security:login.html.twig',
    							array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));*/
    					//return $this->redirect($this->generateUrl('FecdasBundle_login'));		
    					/* Fi Manteniment */
    					
    					// 	Redirect - This is important to prevent users re-posting
    					// 	the form if they refresh the page
    					
    					$remote_addr = $request->server->get('REMOTE_ADDR');
    					$this->get('session')->set('username', $form->getData()->getUser());
    					$this->get('session')->set('remote_addr', $remote_addr);

    					/* Comprovar enquestes pendents */
    					$enquestaactiva = $this->getActiveEnquesta();
    					if ($enquestaactiva != null) {
    						$realitzada = $enquestaactiva->getRealitzada($this->get('session')->get('username'));
    						
    						$this->get('session')->set('enquesta', $enquestaactiva->getId());
    						
    						if ($realitzada == null || $realitzada->getDatafinal() == null) {
    							$this->get('session')->set('enquestapendent', $enquestaactiva->getId());
    							//$this->get('session')->getFlashBag()->add('sms-notice', 'Hi ha una enquesta activada pendent de contestar');
    						}
    					}
    					
    					$em = $this->getDoctrine()->getManager();
    					if ($user->getRecoverytoken() != null) {
    						// Esborrar token de recuperació de password, si entra amb login normal
    						$user->setRecoverytoken(null);
    						$user->setRecoveryexpiration(null);
    					}
    					$user->setLastaccess($this->getCurrentDate('now'));
    					
    					$em->flush();
    					
    					$this->logEntry($this->get('session')->get('username'), 'LOGIN',
    							$this->get('session')->get('remote_addr'),
    							$request->server->get('HTTP_USER_AGENT'));

    					if ($this->get('session')->has('url_request')) {
    						/* Comprovar petició url abans de login. Exemple mail renovacions*/
    						$url = $this->get('session')->get('url_request');
    						$this->get('session')->remove('url_request');
    						return $this->redirect($url);
    					}

    					/*if ($user->getForceupdate() == true) {
    						return $this->redirect($this->generateUrl('FecdasBundle_user'));
    					}*/
    					
    					return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
    				}
    			}
    		}
    	}
    
    	return $this->render('FecdasBundle:Security:login.html.twig', 
						array('form' => $form->createView(), 'admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    public function logoutAction(Request $request)
    {
    	$this->logEntry($this->get('session')->get('username'), 'LOGOUT',
    			$this->get('session')->get('remote_addr'),
    			$request->server->get('HTTP_USER_AGENT'));
    	
    	$this->get('session')->clear();
    	
    	$this->get('session')->getFlashBag()->add('sms-notice', 'Sessió finalitzada!');
    	
    	return $this->render('FecdasBundle:Security:logout.html.twig',
						array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    public function userAction(Request $request)
    {
    
    	$request->getSession()->getFlashBag()->clear();
    	
    	$username = '';
    	if ($this->isAuthenticated() == true) { 
    		// Canvi password normal
    		$username = $this->get('session')->get('username');
    		$user = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($username);
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
    		$user = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($username);
    		 
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
	    		$form->bind($request);
	    		
	    		if ($form->isValid()) {
	    			$em = $this->getDoctrine()->getManager();
	    			
	    			$user->setPwd(sha1($user->getPwd())); 
	    			$user->setRecoverytoken(null);
	    			$user->setRecoveryexpiration(null);
	    			
	    			$em->flush();
	    			
	    			if ($this->isAuthenticated() == false) {
	    				$remote_addr = $request->server->get('REMOTE_ADDR');
	    				$this->get('session')->set('username', $user->getUser());
	    				$this->get('session')->set('remote_addr', $remote_addr);
	    			}
	    			
	    			$this->logEntry($this->get('session')->get('username'), 'PWD RESET',
	    					$this->get('session')->get('remote_addr'),
	    					$request->server->get('HTTP_USER_AGENT'));
	    			
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
    	if ($this->get('session')->has('username')){
    		return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
    	}
    	 
    	$formbuilder = $this->createFormBuilder()->add('user', 'email');
    	$form = $formbuilder->getForm();
    	
    	if ($request->getMethod() == 'POST') {
    		$form->bind($request);
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
    				
    				$this->logEntry($userEmail, 'PWD RECOVER',
    						$request->server->get('REMOTE_ADDR'),
    						$request->server->get('HTTP_USER_AGENT'));
    				
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
		$optionsForm = array( 'comarques' => $this->getComarques(), 'provincies' => $this->getProvincies());
	
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
	   			$form->handleRequest($request);
	   			
	   			if ($form->isValid()) {
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
	   			
					if ($this->isCurrentAdmin() != true) {	
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
	   				$checkuser = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($club->getMail());
	   				if ($checkuser != null) {
	   					if  ($nouclub || 
	   						(!$nouclub && $checkuser->getClub()->getCodi() != $club->getCodi())	) {
	   						$tab = 0;	
							throw new \Exception("Aquest mail ja existeix per un altre club, " . $club->getMail());
	   					}
	   				}
	   					
    				if ($nouclub) {
    					// Nou club
    					$club->setEstat($this->getDoctrine()->getRepository('FecdasBundle:EntityClubEstat')->find(self::CLUB_PAGAMENT_DIFERIT));
    					$em->persist($club);
	    				
	    				// Crear el primer usuari de club, amb el mail del club
    					$userclub = new EntityUser();
    					$userclub->setUser($club->getMail());
    					$userclub->setClub($club);
   					
    					$randomPassword = $this->generateRandomPassword();
    					$userclub->setPwd(sha1($randomPassword));
    					$userclub->setRole("user");
    					$club->addEntityUser($userclub);

    					$em->persist($userclub);
    					
    					$this->get('session')->getFlashBag()->add('sms-notice', 'Club creat correctament. Nou usuari ' .
	    										$userclub->getUser() . ' , amb clau ' . $randomPassword);
    					
    				} else {
    					$this->get('session')->getFlashBag()->add('sms-notice', 'Dades del club desades correctament ');
    				}
	    			$em->flush(); // Error
	   				$this->logEntryAuth(($nouclub)?"CLUB NEW ":"CLUB UPD " . 'OK', 'club : ' . $club->getCodi());
					return $this->redirect($this->generateUrl('FecdasBundle_club', array( 'codiclub' => $club->getCodi(), 'tab' => $tab )));
	   			} else {
	   				$this->get('session')->getFlashBag()->add('error-notice', "error validant les dades". $form->getErrorsAsString());
	   			}
	   		}
		} catch (\Exception $e) {
			$em->clear();
			$this->get('session')->getFlashBag()->add('error-notice', $e->getMessage());
			$this->logEntryAuth(($nouclub)?"CLUB NEW ":"CLUB UPD ". 'KO', 'club : ' . $clubCodi . ' - ' . $e->getMessage());
		}
   		
		if ($form == null) $form = $this->createForm(new FormClubAdmin( $optionsForm ), $club);
		
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
    	try {
    		
	    	if ($this->isCurrentAdmin() != true) throw new \Exception("Acció no permesa");
	    
			if ($request->isXmlHttpRequest() != true) throw new \Exception("Error. Contacti amb l'administrador (100)");
			
	    	if ($request->getMethod() == 'POST') {
	    		// Alta nou usuari de club
	    		$requestParams = $request->request->all();
	    		
	    		$codiclub = $requestParams['club']['codi'];
	    		$useruser = $requestParams['club']['user'];
	    		$randomPassword = $requestParams['club']['pwd']['first'];
	    		$userrole = $requestParams['club']['role'];
	    		//$forceupdate = (isset($requestParams['club']['forceupdate']))? true: false;
	    		
	    		$userclub = new EntityUser();
	    		
	   			$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codiclub);
	   			$checkuser = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($useruser);
	   			
	   			if ($checkuser != null)  throw new \Exception("Aquest usuari ja existeix: ".$useruser);
				
				// No existeix
   				$userclub->setClub($club);
    			$userclub->setUser($useruser);
    				
    			$userclub->setPwd(sha1($randomPassword));
    			$userclub->setRole($userrole);
    			//$userclub->setForceupdate($forceupdate);
    			$club->addEntityUser($userclub);
	    				
   				$em = $this->getDoctrine()->getManager();
   				$em->persist($userclub);
	   				
   				$em->flush();
	   				
   				$this->get('session')->getFlashBag()->add('error-notice', 'Nou usuari ' . $userclub->getUser() . ', amb clau: ' . $randomPassword);
	    		
				$this->logEntryAuth('USER CLUB NEW OK', 'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser());	

	    	} else {
	    	
		   		if ($request->query->has('action') != true) return new Response("Error. Contacti amb l'administrador (101)"); 
		
				// Activar o desactivar usuaris
				$action = $request->query->get('action');
		    			
				$userclub = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($request->query->get('user'));
		
				if ($userclub == null) throw new \Exception('Error. Posa\'t en contacte amb l\'administrador');

				if ($action == 'remove') {
					/* Change email, add prefix . Actualitza en cascada el registre per no perdre'l 
					 * Permet tornar a fer servir l'usuari */
					$userclub->setDatabaixa($this->getCurrentDate());
	   						
					$upduser = $userclub->getUser();
					for($i = 0; $i <= 6; $i++) {
						$upduser = chr(rand(97, 122)) . $upduser;
					}
					$userclub->setUser($upduser);
		 						
				}
				if ($action == 'resetpwd') {
	   				$randomPassword = $this->generateRandomPassword();
	   				$userclub->setPwd(sha1($randomPassword));
	   				$this->get('session')->getFlashBag()->add('error-notice', 'Clau de l\'usuari ' .
	   						$userclub->getUser() . ', canviada: ' . $randomPassword);
	   			}
				
	   			$club = $userclub->getClub();
		
	   			$em = $this->getDoctrine()->getManager();
	   			$em->flush();
				
				$this->logEntryAuth('USER '. strtoupper($action) . ' OK', 'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser());	
	   		}
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error-notice', $e->getMessage());
	   		
			$extra = '. Club : ' . ($club != null?$club->getCodi():'') . ' user: ' . ($userclub != null?$userclub->getUser():'');
			$this->logEntryAuth('USER KO', $e->getMessage().$extra);	
		}
			
		if ($club == null) $club = $this->getCurrentClub();
				
   		return $this->render('FecdasBundle:Security:clubllistausers.html.twig',
   				array('club' => $club, 'admin' =>$this->isCurrentAdmin()));
   	 	
    }
}
