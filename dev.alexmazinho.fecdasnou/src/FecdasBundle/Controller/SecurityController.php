<?php
namespace FecdasBundle\Controller;


use Symfony\Bundle\AsseticBundle\Factory\Worker\UseControllerWorker;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FecdasBundle\Form\FormLogin;
use FecdasBundle\Form\FormUser;
use FecdasBundle\Form\FormClub;
use FecdasBundle\Form\FormUserClub;
use FecdasBundle\Entity\EntityUser;
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
    				$this->get('session')->getFlashBag()->add('sms-notice', 'Usuari incorrecte!');
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
    					/*$this->get('session')->getFlashBag()->add('sms-notice', 'Lloc web en manteniment, espereu una estona si us plau');
    					return $this->render('FecdasBundle:Security:login.html.twig',
    							array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));*/
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

    					if ($user->getForceupdate() == true) {
    						return $this->redirect($this->generateUrl('FecdasBundle_user'));
    					}
    					
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
    
    
    public function clubAction(Request $request) {
    	$this->get('session')->getFlashBag()->clear();
    	
    	if ($this->isAuthenticated() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_login'));
    
    	/* De moment administradors */
    	if ($this->isCurrentAdmin() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_login'));
    	
    	$club  = $this->getCurrentClub();
    	
    	$nouclub = false;
    
   		if ($request->getMethod() == 'POST') {
   			/* Alta o modificació de clubs */
   			$formdata = $request->request->get('club');
   			if (isset($formdata['nouclub'])) {
   				$nouclub = true;
   				$club = new EntityClub();
   			} else {
				// Codi del club read_only
   				$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($formdata['codi']);
   			}

   			$options = array('nou' => $nouclub, 'admin' => $this->isCurrentAdmin());
   			$form = $this->createForm(new FormClub($options), $club);
   
   			$form->handleRequest($request);
   			
   			if ($form->isValid()) {
   				$valida = true;
   				$strErrorLog = "";
   				$strACtionLog = ($nouclub)?"CLUB NEW ":"CLUB UPD ";
   				
   				$em = $this->getDoctrine()->getManager();

   				/* Validacions dades obligatories*/
   				if ($club->getCodi() == "" || $club->getNom() == "" ||
   						$club->getCif() == "" || $club->getMail() == "") {
   					$strErrorLog = "Dades insuficients, cal indicar: codi, nom, cif i mail";
   					$valida = false;
   				}

   				if ($valida == true && $nouclub) {
   					// Nou club
   					$checkclub = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($club->getCodi());
   					if ($checkclub != null) {
   						$strErrorLog = 'Aquest codi de club ja existeix';
   						$valida = false;
   					}
   				}
   					
   				/* Validacions mail no existeix en altres clubs */
   				$checkuser = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($club->getMail());
   				if ($valida == true && $checkuser != null) {
   					if  ($nouclub ||
   						(!$nouclub && $checkuser->getClub()->getCodi() != $club->getCodi())) {
   						$strErrorLog = 'Aquest mail ja existeix per un altre club, ' . $club->getMail();
   						$valida = false;
   					}
   				}
   					
    			if ($valida == true) {
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
    					$userclub->setForceupdate(true);
    					$club->addEntityUser($userclub);

    					$em->persist($userclub);
    					
    					$this->get('session')->getFlashBag()->add('error-notice', 'Club creat correctament. Nou usuari ' .
    										$userclub->getUser() . ' , amb clau ' . $randomPassword);
    					$nouclub = false;
    				} else {
    					$this->get('session')->getFlashBag()->add('error-notice', 'Dades del club desades correctament ');
    				}
    
    				$em->flush();
    						
   					$this->logEntryAuth($strACtionLog . 'OK', 'club : ' . $club->getCodi());
   				} else {
   					$this->get('session')->getFlashBag()->add('error-notice', $strErrorLog);
   					$this->logEntryAuth($strACtionLog. 'KO', 'club : ' . $club->getCodi() . ' - ' . $strErrorLog);
   				}
   			} else {
   				// get a ConstraintViolationList
   				$this->get('session')->getFlashBag()->add('error-notice', "error validant les dades". $form->getErrorsAsString());
   				
   				$club  = $this->getCurrentClub();
   				$form = $this->createForm(new FormClub($options), $club);
   				
   				return $this->render('FecdasBundle:Security:club.html.twig',
   						$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'club' => $club)));
   			}
   		} else {
   			if ($this->isCurrentAdmin() != true) {
   				$this->logEntryAuth('CLUB VIEW OK',	'club : ' . $club->getCodi());
   			} else {
   				if ($request->query->has('action') and $request->query->get('action') == "nouclub") {
    				$club = new EntityClub();
    				$nouclub = true;
    				$this->logEntryAuth('CLUB NEW VIEW', 'club : ' . $club->getCodi());
    			} else {
    				if ($request->query->has('codiclub')) {
    					// Edit club, external GET 
    					$codiclub = $request->query->get('codiclub');
    					$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codiclub);
    				}
    				
    				if (!$club) $club  = $this->getCurrentClub();
    					
    				$this->logEntryAuth('CLUB UPD VIEW', 'club : ' . $club->getCodi());
    			}
   			}
   		}
   		
   		$options = array('nou' => $nouclub, 'admin' => $this->isCurrentAdmin());
   		$form = $this->createForm(new FormClub($options), $club);
   		$form->get('saldoclub')->setData($club->getSaldoweb());
   		$form->get('totalllicenciesweb')->setData($club->getTotalLlicenciesWeb());

    	return $this->render('FecdasBundle:Security:club.html.twig', 
    			$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'club' => $club)));
    }
    
    public function usuariclubAction(Request $request) {
    	//$this->get('session')->getFlashBag()->clear();
    
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
    			
    			$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codiclub);

    			$checkuser = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($useruser);
    			
    			if ($checkuser == null) {
   					// No existeix
    				$userclub->setClub($club);
    				$userclub->setUser($useruser);
    				
    				$userclub->setPwd(sha1($randomPassword));
    				$userclub->setRole($userrole);
    				$userclub->setForceupdate($forceupdate);
    				$club->addEntityUser($userclub);
    				
   					$em = $this->getDoctrine()->getManager();
   					$em->persist($userclub);
   				
   					$em->flush();
   				
   					$this->get('session')->getFlashBag()->add('error-notice', 'Nou usuari ' .
   							$userclub->getUser() . ', amb clau: ' . $randomPassword);
    						
   					$this->logEntry($this->get('session')->get('username'), 'USER CLUB NEW OK',
   							$this->get('session')->get('remote_addr'),
   							$request->server->get('HTTP_USER_AGENT'),
   							'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser());
    			} else {
    					// Existeix -> error
    				$this->get('session')->getFlashBag()->add('error-notice', 'Aquest usuari ja existeix');
    				$this->logEntry($this->get('session')->get('username'), 'USER CLUB NEW KO',
    						$this->get('session')->get('remote_addr'),
    						$request->server->get('HTTP_USER_AGENT'),
    						'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser());
    			}
    			
    			return $this->render('FecdasBundle:Security:clubllistausers.html.twig',
    					array('club' => $club, 'admin' =>$this->isCurrentAdmin()));
    		} else {
    			if ($request->query->has('action')) {
    				// Activar o desactivar usuaris
    				$action = $request->query->get('action');
    				
    				$userclub = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($request->query->get('user'));

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
    						$this->get('session')->getFlashBag()->add('error-notice', 'Clau de l\'usuari ' .
    								$userclub->getUser() . ', canviada: ' . $randomPassword);
    					}
    					
    					$club = $userclub->getClub();

    					$em = $this->getDoctrine()->getManager();
    					$em->flush();
    					
    					$this->logEntry($this->get('session')->get('username'), 'USER '. strtoupper($action) . ' OK',
    							$this->get('session')->get('remote_addr'),
    							$request->server->get('HTTP_USER_AGENT'),
    							'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser());
    				} else {
    					// Error
    					$this->get('session')->getFlashBag()->add('error-notice', 'Error. Posa\'t en contacte amb l\'administrador');
    					$club = $this->getCurrentClub();
    				}
    				
    				return $this->render('FecdasBundle:Security:clubllistausers.html.twig',
    						array('club' => $club, 'admin' =>$this->isCurrentAdmin()));
    			} 
    		}
    	}
    
    	return new Response("Error. Contacti amb l'administrador (userclubAction)");
    }
}
