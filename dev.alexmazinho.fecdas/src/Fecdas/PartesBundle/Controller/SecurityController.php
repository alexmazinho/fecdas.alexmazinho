<?php
namespace Fecdas\PartesBundle\Controller;


use Symfony\Bundle\AsseticBundle\Factory\Worker\UseControllerWorker;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Fecdas\PartesBundle\Form\FormLogin;
use Fecdas\PartesBundle\Form\FormUser;
use Fecdas\PartesBundle\Form\FormPwdRecovery;
use Fecdas\PartesBundle\Form\FormClub;
use Fecdas\PartesBundle\Form\FormUserClub;
use Fecdas\PartesBundle\Entity\EntityUser;
use Fecdas\PartesBundle\Entity\EntityClub;


class SecurityController extends BaseController
{
	
	public function getLoginFormAction()
	{
		// Només genera form pel template layout.html.twig
		$userlogin = new EntityUser();
		$form = $this->createForm(new FormLogin(), $userlogin);
		
		return $this->render('FecdasPartesBundle:Security:loginform.html.twig', array(
				'form' => $form->createView() ));
	}
	
    public function loginAction()
    {
    	if ($this->get('session')->has('username')){
    		return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
    	}
    	
    	$userlogin = new EntityUser();
    	$form = $this->createForm(new FormLogin(), $userlogin);
    
    	$request = $this->getRequest();
    	
    	if ($request->getMethod() == 'POST') {
    		$form->bindRequest($request);
    		if ($form->isValid()) {
    			$em = $this->getDoctrine()->getEntityManager();
    			$repository = $em->getRepository('FecdasPartesBundle:EntityUser');
    			$user = $repository->findOneByUser($form->getData()->getUser());
    			if (!$user || $user->getDatabaixa() != null || $user->getClub()->getActivat() == false) {
    				$this->get('session')->setFlash('sms-notice', 'Usuari incorrecte!');
    				/* Manteniment  */
    				//$this->get('session')->setFlash('sms-notice', 'Aplicació en manteniment, disculpeu les molèsties!');
    			} else {
    				if ($user->getPwd() != sha1($form->getData()->getPwd())) {
    					$this->get('session')->setFlash('sms-notice', 'Paraula clau incorrecta!');
    					
    					$this->logEntry($form->getData()->getUser(), 'LOGIN KO',
    							$this->getRequest()->server->get('REMOTE_ADDR'),
    							$this->getRequest()->server->get('HTTP_USER_AGENT'));
    						
    				} else {
	    				/* Manteniment */
    					/*$this->get('session')->setFlash('sms-notice', 'Lloc web en manteniment, espereu una estona si us plau');
    					return $this->render('FecdasPartesBundle:Security:login.html.twig',
    							array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));*/
    					/* Fi Manteniment */
    					
    					// 	Redirect - This is important to prevent users re-posting
    					// 	the form if they refresh the page
    					
    					$remote_addr = $this->getRequest()->server->get('REMOTE_ADDR');
    					$this->get('session')->set('username', $form->getData()->getUser());
    					$this->get('session')->set('remote_addr', $remote_addr);

    					/* Comprovar enquestes pendents */
    					$enquestapendent = $this->getActiveEnquesta();
    					if ($enquestapendent != null) {
    						$this->get('session')->set('enquestapendent', $enquestapendent->getId());
    						$this->get('session')->setFlash('sms-notice', 'Hi ha una enquesta activada pendent de contestar');
    					}
    					
    					$em = $this->getDoctrine()->getEntityManager();
    					if ($user->getRecoverytoken() != null) {
    						// Esborrar token de recuperació de password, si entra amb login normal
    						$user->setRecoverytoken(null);
    						$user->setRecoveryexpiration(null);
    					}
    					$user->setLastaccess($this->getCurrentDate('now'));
    					
    					$em->flush();
    					
    					$this->logEntry($this->get('session')->get('username'), 'LOGIN',
    							$this->get('session')->get('remote_addr'),
    							$this->getRequest()->server->get('HTTP_USER_AGENT'));

    					if ($this->get('session')->has('url_request')) {
    						/* Comprovar petició url abans de login. Exemple mail renovacions*/
    						$url = $this->get('session')->get('url_request');
    						$this->get('session')->remove('url_request');
    						return $this->redirect($url);
    					}

    					if ($user->getForceupdate() == true) {
    						return $this->redirect($this->generateUrl('FecdasPartesBundle_user'));
    					}
    					
    					return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
    				}
    			}
    		}
    	}
    
    	return $this->render('FecdasPartesBundle:Security:login.html.twig', 
						array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    public function logoutAction()
    {
    	$this->logEntry($this->get('session')->get('username'), 'LOGOUT',
    			$this->get('session')->get('remote_addr'),
    			$this->getRequest()->server->get('HTTP_USER_AGENT'));
    	
    	$this->get('session')->clear();
    	
    	$this->get('session')->setFlash('sms-notice', 'Sessió finalitzada!');
    	
    	return $this->render('FecdasPartesBundle:Security:logout.html.twig',
						array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    public function userAction()
    {
    	$request = $this->getRequest();
    
    	$request->getSession()->clearFlashes();
    	
    	$username = '';
    	if ($this->isAuthenticated() == true) { 
    		// Canvi password normal
    		$username = $this->get('session')->get('username');
    		$user = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityUser')->find($username);
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
    		$user = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityUser')->find($username);
    		 
    		if ($user == null || $username == '' || $token = '' || $token != $user->getRecoverytoken()) {
   				$this->get('session')->setFlash('sms-notice', 'L\'enllaç per recuperar la clau ja no és vigent');
    			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
    		}
    		
    		if ($user->getRecoveryexpiration() == null 
    				||  $this->getCurrentDate('now') > $user->getRecoveryexpiration()) {
    			$this->get('session')->setFlash('sms-notice', 'L\'enllaç per recuperar la clau ha caducat, cal tornar a demanar-la.');
    			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
    		}
    	}
    	
    	$form = $this->createForm(new FormUser(), $user);
    	$form->get('usertoken')->setData($username);
    	
    	if ($request->getMethod() == 'POST') {
    		$userdata = $request->request->get('user');
  			
    		if ($userdata['pwd']['first'] != $userdata['pwd']['second']) $this->get('session')->setFlash('error-notice', "No coincideixen les claus!"); 
    		else {
	    		$form->bindRequest($request);
	    		
	    		if ($form->isValid()) {
	    			$em = $this->getDoctrine()->getEntityManager();
	    			
	    			$user->setPwd(sha1($user->getPwd())); 
	    			$user->setRecoverytoken(null);
	    			$user->setRecoveryexpiration(null);
	    			
	    			$em->flush();
	    			
	    			if ($this->isAuthenticated() == false) {
	    				$remote_addr = $this->getRequest()->server->get('REMOTE_ADDR');
	    				$this->get('session')->set('username', $user->getUser());
	    				$this->get('session')->set('remote_addr', $remote_addr);
	    			}
	    			
	    			$this->logEntry($this->get('session')->get('username'), 'PWD RESET',
	    					$this->get('session')->get('remote_addr'),
	    					$this->getRequest()->server->get('HTTP_USER_AGENT'));
	    			
	    			$this->get('session')->setFlash('error-notice', "Paraula clau actualitzada correctament!");
	    		} else {
	    			$this->get('session')->setFlash('error-notice', "Error, contacti amb l'administrador");
	    		}
    		}
    	}
    	$form->get('forceupdate')->setData(false);
    	
    	return $this->render('FecdasPartesBundle:Security:user.html.twig',
    			array('form' => $form->createView(),'admin' => $this->isCurrentAdmin(), 'authenticated' => $this->isAuthenticated(),
    					'busseig' => $this->isCurrentBusseig(),
    					'enquestausuari' => $this->get('session')->has('enquestapendent')));
    }
    
    
    public function pwdrecoveryAction()
    {
    	if ($this->get('session')->has('username')){
    		return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
    	}
    	 
    	$userlogin = new EntityUser();
    	$form = $this->createForm(new FormPwdRecovery(), $userlogin);
    
    	$request = $this->getRequest();
    	 
    	if ($request->getMethod() == 'POST') {
    		$form->bindRequest($request);
    		if ($form->isValid()) {
    			$em = $this->getDoctrine()->getEntityManager();
    			$repository = $em->getRepository('FecdasPartesBundle:EntityUser');
    			$user = $repository->findOneByUser($form->getData()->getUser());
    			if (!$user) {
    				$this->get('session')->setFlash('sms-notice', 'Aquest usuari no existeix a la base de dades');
    			} else {
    				$token = base64_encode(openssl_random_pseudo_bytes(30));
    				$expiration = $this->getCurrentDate('now');
    				$expiration->add(new \DateInterval('PT4H'));
    				
    				$em = $this->getDoctrine()->getEntityManager();
    				// Save token information encrypted
    				$user->setRecoverytoken(sha1($token));
    				$user->setRecoveryexpiration($expiration);
    				$em->flush();
    				
    				$message = \Swift_Message::newInstance()
    				->setSubject('::Recuperació accés aplicació gestió FECDAS::')
    				->setFrom($this->container->getParameter('fecdas_partes.emails.contact_email'))
    				->setTo(array($form->getData()->getUser()));

    				$logosrc = $message->embed(\Swift_Image::fromPath('images/fecdaslogo.png'));    				
    				
    				$body = $this->renderView('FecdasPartesBundle:Security:recuperacioClauEmail.html.twig',
    						array('user' => $user, 'token' => $token, 'logo' => $logosrc));
    				
    				$message->setBody($body, 'text/html');
    				
    				$this->get('mailer')->send($message);
    				
    				$this->logEntry($form->getData()->getUser(), 'PWD RECOVER',
    						$this->getRequest()->server->get('REMOTE_ADDR'),
    						$this->getRequest()->server->get('HTTP_USER_AGENT'));
    				
    				$this->get('session')->clear();
    				$this->get('session')->setFlash('sms-notice', 'S\'han enviat instruccions per a recuperar la clau a l\'adreça de correu ' . $form->getData()->getUser());
    				
    				return $this->render('FecdasPartesBundle:Security:logout.html.twig',
    						array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    			}
    		} 
    	}
    			
    	return $this->render('FecdasPartesBundle:Security:pwdrecovery.html.twig', 
    			array('form' => $form->createView(), 'admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    
    public function clubAction() {
    	/*
    	*
    	*/
    	$this->get('session')->clearFlashes();
    	$request = $this->getRequest();
    	if ($this->isAuthenticated() != true)
    		return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
    
    	/* Només canvi de club administradors. Per defecte club de l'usuari */
    	$club = null;
    	$action = "";
    	$clubcodi = "";
    
   		if ($request->getMethod() == 'POST') {
   			/* Alta o modificació de clubs */
   			if ($request->request->has('club')) {
   				$formdata = $request->request->get('club');
   				$codiclub = $formdata['codi'];
   				$club = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')->find($codiclub);
   			}
   			if ($club == null) {
   				$club = new EntityClub();
   				$codiclub = $formdata['codishow'];
   			}
   
   			$options = array('codiclub' => $club->getCodi(),
   					'clubs' => $this->getClubsSelect(), 'admin' => $this->isCurrentAdmin());
   			$form = $this->createForm(new FormClub($options), $club);
   
   			$form->bindRequest($request);

   			if ($form->isValid()) {
   				$valida = true;
   				$strErrorLog = "";
   				if  ($club->getCodi() == "") $strACtionLog = "CLUB NEW ";
   				else $strACtionLog = "CLUB UPD ";

   				$em = $this->getDoctrine()->getEntityManager();
   					
   				/* Validacions dades obligatories*/
   				if ($codiclub == "" || $club->getNom() == "" ||
   						$club->getCif() == "" || $club->getMail() == "") {
   					$strErrorLog = "Dades insuficients";
   					$valida = false;
   				}
   					
   				/* Validacions mail no existeix en altres clubs */
   				$checkuser = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityUser')->find($club->getMail());
   				if ($valida == true && $checkuser != null) {
   					if  (($club->getCodi() == "") ||
   						($club->getCodi() != ""  && $checkuser->getClub()->getCodi() != $club->getCodi())) {
   						$strErrorLog = 'Aquest mail ja existeix per un altre club, ' . $club->getMail();
   						$this->get('session')->setFlash('error-notice', $strErrorLog);
   						$valida = false;
   					}
   				}
   					
   				if ($valida == true && $club->getCodi() == "") {
   					// Nou club
   					$checkclub = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')
			   					->find($codiclub);
   					if ($checkclub != null) {
    					$strErrorLog = 'Aquest codi de club ja existeix';
    					$this->get('session')->setFlash('error-notice', $strErrorLog);
    					$valida = false;
    				}
    			}
    			if ($valida == true) {
    				if ($club->getCodi() == "") {
    					// Nou club
    					$club->setCodi($codiclub);
    					$em->persist($club);
    				
	    				// Crear el primer usuari de club, amb el mail del club
    					$userclub = new EntityUser();
    					$userclub->setUser($club->getMail());
    					$userclub->setClub($club);
    					$randomPassword = $this->generateRandomPassword();
    					$userclub->setPwd(sha1($randomPassword));
    					$userclub->setRole("user");
    					$userclub->setForceupdate(true);
    					$club->addEntityUser($userclub);

    					$em->persist($userclub);
    					
    					$this->get('session')->setFlash('error-notice', 'Club creat correctament. Nou usuari ' .
    										$userclub->getUser() . ' , amb clau ' . $randomPassword);
    				} else {
    					$this->get('session')->setFlash('error-notice', 'Dades del club desades correctament');
    				}
    
    				$em->flush();
    						
   					$this->logEntry($this->get('session')->get('username'), $strACtionLog . 'OK',
   							$this->get('session')->get('remote_addr'),
   							$this->getRequest()->server->get('HTTP_USER_AGENT'),
   							'club : ' . $club->getCodi());
   				} else {
   					$this->get('session')->setFlash('error-notice', $strErrorLog);
   					$this->logEntry($this->get('session')->get('username'), $strACtionLog. 'KO',
   							$this->get('session')->get('remote_addr'),
   							$this->getRequest()->server->get('HTTP_USER_AGENT'),
   							'club : ' . $club->getCodi() . ' - ' . $strErrorLog);
   				}
   			} else {
   				// get a ConstraintViolationList
   				//print_r($this->getErrorMessages($form));
   				$this->get('session')->setFlash('error-notice', "error validant les dades".implode(",",$this->getErrorMessages($form)));
   			}
   		} else {
   			if ($this->isCurrentAdmin() != true) {
   				$club  = $this->getCurrentClub();
   				 
   				$this->logEntry($this->get('session')->get('username'), 'CLUB VIEW OK',
   						$this->get('session')->get('remote_addr'),
   						$this->getRequest()->server->get('HTTP_USER_AGENT'),
   						'club : ' . $club->getCodi());
   			} else {
   				if ($request->query->has('action')) $action = $request->query->get('action');
    			if ($action == "nouclub") {
    				$club = new EntityClub();
    					
    				$this->logEntry($this->get('session')->get('username'), 'CLUB NEW VIEW',
    						$this->get('session')->get('remote_addr'),
    						$this->getRequest()->server->get('HTTP_USER_AGENT'),
    						'club : ' . $club->getCodi());
    			} else {
    				if ($request->query->has('codiclub')) {
    					$codiclub = $request->query->get('codiclub');
    					$club = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')->find($codiclub);
    				} else {
    					$club  = $this->getCurrentClub();
    				}
    					
    				$this->logEntry($this->get('session')->get('username'), 'CLUB UPD VIEW',
   						$this->get('session')->get('remote_addr'),
   						$this->getRequest()->server->get('HTTP_USER_AGENT'),
   						'club : ' . $club->getCodi());
    			}
   			}
   		}

   		$options = array('codiclub' => $club->getCodi(),
   				'clubs' => $this->getClubsSelect(), 'admin' => $this->isCurrentAdmin());
   		$form = $this->createForm(new FormClub($options), $club);
   		if ($this->isCurrentAdmin() == true) {
   			$form->get('codishow')->setData($club->getCodi());
   		}

    	return $this->render('FecdasPartesBundle:Security:club.html.twig',
    			array('form' => $form->createView(), 'club' => $club, 
    					'admin' => $this->isCurrentAdmin(),
    					'authenticated' => $this->isAuthenticated(), 
    					'busseig' => $this->isCurrentBusseig(),
    					'enquestausuari' => $this->get('session')->has('enquestapendent')));
    }
    
    public function usuariclubAction() {
    	$request = $this->getRequest();
    	//$this->get('session')->clearFlashes();
    
    	if ($request->isXmlHttpRequest()) {
    		if ($request->getMethod() == 'POST') {
    			// Alta nou usuari de club
    			$requestParams = $request->request->all();
    			
    			$codiclub = $requestParams['club']['codi'];
    			$useruser = $requestParams['club']['user'];
    			$randomPassword = $requestParams['club']['pwd']['first'];
    			$userrole = $requestParams['club']['role'];
    			$forceupdate = (isset($requestParams['club']['forceupdate']))? true: false;
    			
    			$userclub = new EntityUser();
    			
    			$club = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityClub')->find($codiclub);

    			$checkuser = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityUser')->find($useruser);
    			
    			if ($checkuser == null) {
   					// No existeix
    				$userclub->setClub($club);
    				$userclub->setUser($useruser);
    				
    				$userclub->setPwd(sha1($randomPassword));
    				$userclub->setRole($userrole);
    				$userclub->setForceupdate($forceupdate);
    				$club->addEntityUser($userclub);
    				
   					$em = $this->getDoctrine()->getEntityManager();
   					$em->persist($userclub);
   				
   					$em->flush();
   				
   					$this->get('session')->setFlash('error-notice', 'Nou usuari ' .
   							$userclub->getUser() . ', amb clau: ' . $randomPassword);
    						
   					$this->logEntry($this->get('session')->get('username'), 'USER CLUB NEW OK',
   							$this->get('session')->get('remote_addr'),
   							$this->getRequest()->server->get('HTTP_USER_AGENT'),
   							'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser());
    			} else {
    					// Existeix -> error
    				$this->get('session')->setFlash('error-notice', 'Aquest usuari ja existeix');
    				$this->logEntry($this->get('session')->get('username'), 'USER CLUB NEW KO',
    						$this->get('session')->get('remote_addr'),
    						$this->getRequest()->server->get('HTTP_USER_AGENT'),
    						'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser());
    			}
    			
    			return $this->render('FecdasPartesBundle:Security:clubllistausers.html.twig',
    					array('club' => $club, 'admin' =>$this->isCurrentAdmin()));
    		} else {
    			if ($request->query->has('action')) {
    				// Activar o desactivar usuaris
    				$action = $request->query->get('action');
    				
    				$userclub = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityUser')->find($request->query->get('user'));

    				if ($userclub != null) {
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
    						$this->get('session')->setFlash('error-notice', 'Clau de l\'usuari ' .
    								$userclub->getUser() . ', canviada: ' . $randomPassword);
    					}
    					
    					$club = $userclub->getClub();

    					$em = $this->getDoctrine()->getEntityManager();
    					$em->flush();
    					
    					$this->logEntry($this->get('session')->get('username'), 'USER '. strtoupper($action) . ' OK',
    							$this->get('session')->get('remote_addr'),
    							$this->getRequest()->server->get('HTTP_USER_AGENT'),
    							'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser());
    				} else {
    					// Error
    					$this->get('session')->setFlash('error-notice', 'Error. Posa\'t en contacte amb l\'administrador');
    					$club = $this->getCurrentClub();
    				}
    				
    				return $this->render('FecdasPartesBundle:Security:clubllistausers.html.twig',
    						array('club' => $club, 'admin' =>$this->isCurrentAdmin()));
    			} 
    		}
    	}
    
    	return new Response("Error. Contacti amb l'administrador (userclubAction)");
    }
}
