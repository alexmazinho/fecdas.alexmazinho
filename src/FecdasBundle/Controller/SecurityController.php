<?php
namespace FecdasBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FecdasBundle\Form\FormLogin;
use FecdasBundle\Form\FormUser;
use FecdasBundle\Form\FormClub;
use FecdasBundle\Form\FormClubAdmin;
use FecdasBundle\Form\FormClubUser;
use FecdasBundle\Entity\EntityUser;
use FecdasBundle\Entity\EntityClub;


class SecurityController extends BaseController
{
	
	public function changeroleAction(Request $request) {
		if (!$request->query->has('currentrole') || $request->query->get('currentrole') == '') return "";
		
		$currentrole = $request->query->get('currentrole');		// Usuaris: parella role;codi	Admin: només role
		
		$checkRole = $this->get('fecdas.rolechecker');	
		
		if ($this->isCurrentAdmin()) {
			if (!$request->query->has('currentclub') || $request->query->get('currentclub') == '') return "";
			
			$currentclub = $request->query->get('currentclub');
		} else {
			$currentroleArray = explode(";", $currentrole);
			//if (count($currentroleArray) != 2)  return "";
			$currentrole = $currentroleArray[0];
			$currentclub = isset($currentroleArray[1])?$currentroleArray[1]:"";
		}
		$checkRole->setCurrentClubRole( $currentclub, $currentrole );

		if ($this->frontEndLoginCheck($request->isXmlHttpRequest())) return new Response("reload"); //return $redirect;
		// Params => currentrole: role, currentclub: club
		
		if (!$this->isCurrentAdmin()) return new Response("reload");
		return new Response("");
	}
	
    public function loginAction(Request $request)
    {
    	if ($this->isAuthenticated()) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
    	
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
        if ($this->isAuthenticated()) if($redirect = $this->frontEndLoginCheck()) return $redirect;
        
        $request->getSession()->getFlashBag()->clear();
    	
		$checkRole = $this->get('fecdas.rolechecker');
		
		$user = null;
		$userId = 0;
    	$username = '';
		$token = '';
		$newsletter = true;
		$terms = true;
     
        if ($this->isAuthenticated()) {
            if ($request->getMethod() == 'GET') {
                if ($request->query->has('id')) {
                    if (!$this->isCurrentAdmin() && !$checkRole->isCurrentClub()) {
                        $this->logEntryAuth('USER UPD FORBIDDEN', 'user : ' . $checkRole->getCurrentUser()->getUser());
                        $this->get('session')->getFlashBag()->add('error-notice', 'L\'usuari actual no té permisos per fer aquesta operació');
                        return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
                    }
                    $userId = $request->query->get('id');
                }
            } else {
                $userdata = $request->request->get('user');
                $userId = $userdata['id'];
            }
                
            if ($userId > 0) $user = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($userId);
            
            if ($user == null) {
                // Canvi password normal
                $user = $checkRole->getCurrentUser();
            }
            $username = $user->getUser();
            $newsletter = $user->getNewsletter();
        } else {
    		// Recuperació de de password
        	if ($request->getMethod() == 'GET') {
        		$username =  $request->query->get('user');
        		$newsletter =  $request->query->get('newsletter', 1);
        		$newsletter = ($newsletter == 1?true:false);
        		$token = sha1($request->query->get('token'));
        	} else {
        		$userdata = $request->request->get('user');
        		$username =  $userdata['usertoken'];
        		$token = $userdata['recoverytoken'];
        	}
        	$user = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->findOneBy(array('user' => trim($username)));
        		 
        	if ($request->getMethod() == 'GET' && $user != null) $user->setNewsletter($newsletter);
        		
        	if ($user == null || $username == '' || $token = '' || $token != $user->getRecoverytoken()) {
       			$this->get('session')->getFlashBag()->add('error-notice', 'L\'enllaç per recuperar la clau ja no és vigent');
        		return $this->redirect($this->generateUrl('FecdasBundle_login'));
        	}
        		
        	if ($user->getRecoveryexpiration() == null 
        			||  $this->getCurrentDate('now') > $user->getRecoveryexpiration()) {
        		$this->get('session')->getFlashBag()->add('error-notice', 'L\'enllaç per recuperar la clau ha caducat, cal tornar a demanar-la.');
        		return $this->redirect($this->generateUrl('FecdasBundle_login'));
        	}
        		
        	// Actualització del mail de les persones associades a la metapersona corresponent a l'usuari si escau
        	if ($user->getMetapersona() != null) {
        	    if ($user->roleNeedLlicencia() && $user->getMetapersona()->getLlicenciaVigent() == null) throw new \Exception("Per accedir al sistema amb aquestes dades cal una llicència vigent per la data actual");
        	    
        	    foreach ($user->getMetapersona()->getPersonesSortedById() as $persona) {
        	        $persona->setMail($username);
                }
        	}
        }
        	
        $form = $this->createForm(new FormUser(), $user);
        
        try {
        	
            if ($request->getMethod() == 'POST') {
        		$userdata = $request->request->get('user');
      			
        		if ($userdata['pwd']['first'] != $userdata['pwd']['second']) throw new \Exception("No coincideixen les claus!"); 

        		$form->handleRequest($request);
        		
    	    	if (!$form->isValid()) throw new \Exception("Error amb les dades del formulari, contacti amb l'administrador");
    	    			
    	    	$em = $this->getDoctrine()->getManager();

    	    	$newsletter = $form->get('newsletter')->getData();
    	    	$newsletter = ($newsletter == 0?false:true);
    	    	$terms = $form->get('terms')->getData();
    	    	$terms = ($terms == 0?false:true);
    	    	
    	    	if (!$terms) throw new \Exception("Cal acceptar els termes i condicions d'ús per poder registrar l'usuari");
    	    			
    	    	$user->setPwd(sha1($user->getPwd())); 
    
    			if (!$this->isAuthenticated()) $checkRole->authenticateUser($user, $this->getActiveEnquesta());  // Pot fallar
    	    			
    			$user->setNewsletter($newsletter);
    					
    	    	$em->flush();
    
    	    	if ($this->isAuthenticated()) $this->logEntryAuth('USER UPDATE', 'user : ' . $username);
    	    	else $this->logEntryAuth('PWD RESET', 'user : ' . $username);
    	    			
    	    	
    	    	if($redirect = $this->frontEndLoginCheck()) return $redirect;

    	    	
    	    	$this->get('session')->getFlashBag()->clear();
    	    	$this->get('session')->getFlashBag()->add('sms-notice', "Dades actualitzades correctament!");
    	    			
    	    	if (!$this->isAuthenticated()) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
        	}
        	
        } catch (\Exception $e) {
            // Ko, mostra form amb errors
            if ($this->isAuthenticated()) $this->logEntryAuth('PWD RESET KO', 'user : ' . $username);
            else $this->logEntryAuth('PWD RESET KO', 'user : ' . $username);
            
            $this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
        }
    
    	return $this->render('FecdasBundle:Security:user.html.twig',
    	    $this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'user' => $user)) );
    }
    
    public function registreAction(Request $request)
    {
        if ($this->isAuthenticated()) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
        
        $form = $this->createForm(new FormUser());
        
        try {
            if ($request->getMethod() == 'POST') {
                
                $form->handleRequest($request);
                
                $formdata = $request->request->get('user');

                $dniShown = isset($formdata['dni']);
                $dni = !isset($formdata['dni'])?"":$formdata['dni'];
                
                $mail = $form->get('user')->getData();
                
                $newsletter = $form->get('newsletter')->getData();
                $newsletter = ($newsletter == 0?false:true);
                $terms = $form->get('terms')->getData();
                $terms = ($terms == 0?false:true);

                if (!$mail) throw new \Exception("Cal indicar una adreça de correu");
                
                $em = $this->getDoctrine()->getManager();
                
                $user = $em->getRepository('FecdasBundle:EntityUser')->findOneByUser($mail);
                
                if (!$terms) throw new \Exception("Cal acceptar els termes i condicions d'ús per poder registrar l'usuari");
                
                $metapersona = null;
                if ($user != null) {
                    $metapersona = $user->getMetapersona();
                        
                    if ($user->anulat()) {
                        // Existeix usuari però de baixa
                    } else {
                        // Existeix usuari actiu
                        $role = $user->getRoleFederat();
                        
                        if ($role != null) {
                            // Té rol federat, enviar a recuperació de clau
                            $this->get('session')->getFlashBag()->add('sms-notice',	"Les dades per aquesta adreça de correu ja estan registrades, per recuperar l'accés cal enviar la següent petició");
                            
                            return $this->redirect($this->generateUrl('FecdasBundle_pwdrecovery', array( 'email' => $mail)));
                        }
                    }
                } else {
                    // No existeix usuari
                }
                
                if ($metapersona == null) {
                    // Cercar persones amb aquest mail
                    
                    $persones = $this->getPersonesByMail($mail);
                    // $persones = $em->getRepository('FecdasBundle:EntityPersona')->findByMail($mail);
                    
                    $metapersones = array();
                    foreach ($persones as $persona) {
                        $metapersona = $persona->getMetapersona();
                        
                        if ($dni == "" || $dni == $metapersona->getDni()) {
                            $existeix = array_filter(
                                $metapersones,
                                function ($e) use ($metapersona) {
                                    return $e->getId() == $metapersona->getId();
                                }
                            );
                            if (count($existeix) == 0) $metapersones[] = $metapersona;
                        }
                    }
                    
                    $options = array();
                    if (count($metapersones) > 1 || $dniShown) {
                        // Existeixen vàries persones amb aquest mail, afegir camp i actualitzar $form
                        $options['dni'] = $dni;
                    }
                    
                    $form = $this->createForm(new FormUser( $options ));
                    
                    $form->get('user')->setData($mail);
                    $form->get('newsletter')->setData(true);
                    
                    if (count($metapersones) != 1) {
                        if (count($metapersones) == 0) {
                            // No trobat    
                            if (!$dniShown) {
                                //throw new \Exception("No existeixen dades per aquesta adreça de correu");
                                
                                return $this->registreIndependent($mail, $newsletter, $user);
                                
                            }
                            
                            throw new \Exception("No existeixen dades per aquesta adreça de correu i document d'identitat");
                        } 
                        // Trobats varis
                        if ($dni == "") throw new \Exception("Cal indicar un document d'identitat");
                            
                        // No s'hauria de produïr, vàries persones amb el mail i mateix dni
                        throw new \Exception("Per poder registrar un nou usuari amb les dades indicades cal que et posis en contacte amb la Federació, gràcies");
                    }
                    
                    // Només una metapersona
                    $metapersona = $metapersones[0];
                } else {
                    // metapersona ja associada a l'usuari
                }
                
                if ($metapersona->getLlicenciaVigent() == null) throw new \Exception("Només es pot accedir al sistema si les dades associades a l’usuari tenen una llicència vigent per la data actual");
                
                $oldUser = null;
                $subjectMail = $user == null && $metapersona->getUsuari() == null?"Creació d'un nou usuari per accedir":"Activació d'un usuari existent per accedir"; // " a l'Aplicació ..."
                if ($metapersona->getUsuari() != null) {
                    if ($user != null && $metapersona->getUsuari() != $user) {
                        // $user passa a gestionar $metapersona. oldUser perd el rol FEDERAT, i si no li queda cap rol actiu es dona de baixa
                        $oldUser = $metapersona->getUsuari();
                        $oldUser->setMetapersona(null);
                        $oldUser->desactivarUsuariRole(BaseController::ROLE_FEDERAT);
                        $metapersona->setUsuari($user);
                        $user->setMetapersona($metapersona);
                    }

                    // Ja existia usuari però no l'ha trobat abans perquè té altre mail. Aprofitar usuari amb el mateix mail
                    $user = $metapersona->getUsuari();
                    $user->setUser($mail);
                }
                
                if ($user != null) {
                    // Mirar si és baixa i activar
                    $user->activarUsuariRole($metapersona, BaseController::ROLE_FEDERAT);
                    $user->setNewsletter($newsletter);
                } else {
                    // Crear nou usuari
                    $user = new EntityUser($mail, sha1($this->generateRandomPassword()), $newsletter, $metapersona);
                    $em->persist($user);
                }

                foreach ($metapersona->getPersonesSortedById() as $persona) {
                    // Revisar si existeix el rol federat pels clubs de les persones actives associades a metapersona
                    $club = $persona->getClub();
                    if (!$user->hasRoleClub($club, BaseController::ROLE_FEDERAT)) {
                        $userClubRole = $club->addUsuariRole($user, BaseController::ROLE_FEDERAT);
                        $em->persist($userClubRole);
                    }
                }
                if ($oldUser != null) $em->flush($oldUser); // Si es fa tot en un únic flush error clau única a m_users (metapersona duplicada)
                $em->flush(); 

                $this->sendMailRecuperacioAccess($user, $subjectMail, $newsletter);
                
                $this->logEntry($mail, 'USER REGISTER', 'Petició registre nou usuari '.$mail);
                
                $this->get('session')->getFlashBag()->clear();
                $this->get('session')->getFlashBag()->add('sms-notice', 'S\'han enviat instruccions per a finalitzar el registre a l\'adreça de correu ' . $mail);
                
                return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
            }
        } catch (\Exception $e) {
            // Ko, mostra form amb errors
            $this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
        }
        
        return $this->render('FecdasBundle:Security:registre.html.twig',
            array('form' => $form->createView(), 'admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    private function getPersonesByMail($mail) {
        // cerca LIKE %$mail%
        $persones = array();
        
        $em = $this->getDoctrine()->getManager();
        
        $strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p WHERE p.databaixa IS NULL AND p.mail LIKE :mailcerca ORDER BY p.cognoms, p.nom ";
        $query = $em->createQuery($strQuery)->setParameter('mailcerca', '%'.$mail.'%');
        
        // La query retorna cerca parcial, cal comprovar que $mail coincideixi exactament amb algun dels mails de la persona
        $candidats = $query->getResult();
        
        foreach ($candidats as $candidat) {
            foreach ($candidat->getMails() as $mailCandidat) {
                if ($mail == $mailCandidat) $persones[] = $candidat;
            }
        }
        
        return $persones;
    }
    
    
    private function registreIndependent($mail, $newsletter, $user = null)  {       // tramitació federatives: Pesca
        // Alta usuari rol FEDERAT sense club => INDE F (tramitació pesca)
        // Sense metapersona => obligació a informar dades al login
        $em = $this->getDoctrine()->getManager();
        
        if ($user != null) {
            
            $roleClubs = $user->getClubs();
            // Afegir usuari FEDERAT als clubs associats a l'usuari
            foreach ($roleClubs as $roleClub) {
                $club = $roleClub->getClub(); 
                if ($roleClub->getRole() === BaseController::ROLE_FEDERAT && $roleClub->anulat()) {
                    $roleClub->activarRole(BaseController::ROLE_FEDERAT);
                } else {
                    $userClubRole = $club->addUsuariRole($user, BaseController::ROLE_FEDERAT);
                    $em->persist($userClubRole);
                }
            }
            
            $subjectMail = "Activació de l'usuari per accedir";
        } else {
            // Crear nou usuari
            $user = new EntityUser($mail, sha1($this->generateRandomPassword()), $newsletter, null);
            $em->persist($user);
            
            $club = $em->getRepository('FecdasBundle:EntityClub')->findOneByCodi(BaseController::CODI_CLUBLLICWEB);
            
            $userClubRole = $club->addUsuariRole($user, BaseController::ROLE_FEDERAT);
            $em->persist($userClubRole);
            
            $subjectMail = "Creació d'un nou usuari per accedir";
        }
        $em->flush();
        
        $this->sendMailRecuperacioAccess($user, $subjectMail, $newsletter);
        
        $this->logEntry($mail, 'INDE F REGISTER', 'Registre nou usuari '.$mail.' INDE F');
        
        $this->get('session')->getFlashBag()->clear();
        $this->get('session')->getFlashBag()->add('sms-notice', 'S\'han enviat instruccions per a finalitzar el registre a l\'adreça de correu ' . $mail);
        
        return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
    }
    
    public function baixausuariAction(Request $request)
    {
        if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
            
        $checkRole = $this->get('fecdas.rolechecker');
        
        $currentuser = $checkRole->getCurrentUser();
        
        $userId = $request->query->get('id');

        $user = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($userId);
        
        try {
            // Administrador, club o el propi usuari
            if (!$this->isCurrentAdmin() && !$checkRole->isCurrentClub() && $user->getId() != $currentuser->getId()) {
                $this->logEntryAuth('USER DEL FORBIDDEN', 'user : ' . $checkRole->getCurrentUser()->getUser());
                throw new \Exception("L'usuari actual no té permisos per fer aquesta operació");
            }
            $em = $this->getDoctrine()->getManager();
            
            $user->baixaUsuari();
            
            $em->flush();
            
            // Logout
            $this->get('session')->clear();
            
            $this->get('session')->getFlashBag()->add('sms-notice', 'Baixa de l\'usuari tramitada correctament ');
            
            $this->logEntryAuth('USER DEL OK', 'user : ' . $user->getUser());
            
        } catch (\Exception $e) {
            $this->logEntryAuth('USER DEL KO', 'user : ' . $checkRole->getCurrentUser()->getUser().' error '.$e->getMessage());
            
            $response = new Response($e->getMessage());
            $response->setStatusCode(500);
            return $response;
        }
        
        return new Response();
    }
    
    public function pwdrecoveryAction(Request $request)
    {
    	if ($this->isAuthenticated()) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
    	    	
    	$userEmail = $request->query->get('email', '');
    	
    	$formbuilder = $this->createFormBuilder()->add('user', 'email', array('data' => $userEmail));
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
    			    $newsletter = $user->getNewsletter();
    			    
    			    $this->sendMailRecuperacioAccess($user, "Recuperació de l'accés", $newsletter);
    				
    			    $em->flush();
    			    
    				$this->logEntry($userEmail, 'PWD RECOVER');
    				
    				$this->get('session')->getFlashBag()->clear();
    				$this->get('session')->getFlashBag()->add('sms-notice', 'S\'han enviat instruccions per a recuperar la clau a l\'adreça de correu ' . $userEmail);
    				
    				return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
    			}
    		} 
    	}
    			
    	return $this->render('FecdasBundle:Security:pwdrecovery.html.twig', 
    			array('form' => $form->createView(), 'admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    private function sendMailRecuperacioAccess($user, $action, $newsletter = false)
    {
        $token = base64_encode(openssl_random_pseudo_bytes(30));
        $expiration = $this->getCurrentDate('now');
        $expiration->add(new \DateInterval('PT4H'));
        
        // Save token information encrypted
        $user->setRecoverytoken(sha1($token));
        $user->setRecoveryexpiration($expiration);
        
        if ($this->get('kernel')->getEnvironment() != 'prod') {
            $tomails = array($this->getParameter('MAIL_ADMIN'));  // Entorns de test
        } else {
            $tomails = array($user->getUser());
        }
        
        $message = \Swift_Message::newInstance()
            ->setSubject("::".$action." a l'Aplicació de Gestió de FECDAS::")
            ->setFrom($this->getParameter('MAIL_FECDASGESTIO'))
            ->setTo($tomails);
        
        $logosrc = $message->embed(\Swift_Image::fromPath('images/fecdaslogo.png'));
        
        $body = $this->renderView('FecdasBundle:Security:emailrecuperacioaccess.html.twig',
            array('user' => $user, 'token' => $token, 'action' => $action, 'newsletter' => $newsletter, 'logo' => $logosrc));
        
        $message->setBody($body, 'text/html');
        
        $this->get('mailer')->send($message);
    }
    
    public function termesicondicionsAction(Request $request)
    {
        return $this->render('FecdasBundle:Security:termesicondicions.html.twig',
            array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    public function politicacookiesAction(Request $request)
    {
        return $this->render('FecdasBundle:Security:politicacookies.html.twig',
            array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));
    }
    
    public function politicaprivacitatAction(Request $request)
    {
        return $this->render('FecdasBundle:Security:politicaprivacitat.html.twig',
            array('admin' => $this->isCurrentAdmin(), 'authenticated' => false));
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
			
			switch ($carrec) {
			    case BaseController::CARREC_VOCAL:
			    case BaseController::CARREC_VICEPRESIDENT:
			        
			        foreach ($jsonCarrecs as $value) {
			            if ($value->cid == $carrec) $num++;
			        }
			        $jsonCarrecs[] = (object) array('id' =>	$id, 'cid'	=>	$carrec, 'nc' => $num, 'nom' => $nommembre);
			        
			        break;
			     
			    default:
			        // Altres càrrecs, substituir
			        foreach ($jsonCarrecs as $k => $value) {
			            if ($carrec == $value->cid) $key = $k;
			        }
			        // No existeix
			        if ($key < 0) $jsonCarrecs[] = (object) array('id' =>	$id, 'cid'	=>	$carrec, 'nc' => 1, 'nom' => $nommembre); // add
			        else $jsonCarrecs[$key] = (object)  array('id' =>	$id, 'cid'	=>	$carrec, 'nc' => 1, 'nom' => $nommembre);		// upd
			        
			        break;
			}
			
			usort($jsonCarrecs, function($a, $b) {
			    if ($a === $b) {
			        return 0;
			    }
			    
			    if ($a->cid == $b->cid) return ($a->nc < $b->nc? -1:1);
			    
			    return ($a->cid*1 < $b->cid*1? -1:1);
			});
			
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
        if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
    
    	/* De moment administradors */
    	/*if ($this->isCurrentAdmin() != true)
    		return $this->redirect($this->generateUrl('FecdasBundle_login'));*/
    	
    	$club  = $this->getCurrentClub();
		$tab	= $request->query->get('tab', 0);;    	
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
				//$currentMails = $club->getMails(); // Array
				
	   			$form->handleRequest($request);
	   			if (!$form->isValid()) throw new \Exception("error validant les dades ". $form->getErrors(true, true));
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
				
				$strMails = $this->validateMails(explode(";", trim($club->getMail()) ));
				$club->setMail($strMails);
	   			
				if ($club->getTelefon() > BaseController::MAX_TELEFON) {
				    $tab = 0;
				    throw new \Exception("El número de telèfon no és correcte"); 
				}
				
				if ($club->getFax() > BaseController::MAX_TELEFON) {
				    $tab = 0;
				    throw new \Exception("El número de fax no és correcte");
				}
				
				if ($club->getMobil() > BaseController::MAX_TELEFON) {
				    $tab = 0;
				    throw new \Exception("El número de mòbil no és correcte");
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
		   			
					if ($club->getDatabaixa() != null) {
					    if ($club->getActivat()) {
					        $club->setActivat(false);
					        $tab = 3;
					        throw new \Exception("No es pot activar el club ".$club->getNom()." mentre tingui indicada la data de baixa");
					    }
					}
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
					$this->checkUsuariClub(true, $club, $info, BaseController::ROLE_CLUB, $mails[0], $randomPassword);

    				$this->get('session')->getFlashBag()->add('sms-notice', 'Club creat correctament. '.$info);
    			} else {
    				$this->get('session')->getFlashBag()->add('sms-notice', 'Dades del club desades correctament ');
    			}
	    		$em->flush(); // Error
	   			$this->logEntryAuth(($nouclub)?"CLUB NEW ":"CLUB UPD " . 'OK', 'club : ' . $club->getCodi());
				return $this->redirect($this->generateUrl('FecdasBundle_club', array( 'codiclub' => $club->getCodi(), 'tab' => $tab )));
				
	   		}
		} catch (\Exception $e) {
			//$em->clear();
		    if (!$nouclub) $em->refresh($club);

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
		
		$totalVice = 0;
		foreach ($jsonCarrecs as $value) {
		    if ($value->cid==BaseController::CARREC_VICEPRESIDENT) $totalVice++;
		}
		
		foreach ($jsonCarrecs as $value) {
			
		    $mostrarNumeracio = $value->cid==BaseController::CARREC_VOCAL || ($totalVice > 1 && $value->cid==BaseController::CARREC_VICEPRESIDENT);
		    
			if ($value->id == 0 || $value->id != '') {
				$carrecs[$value->cid."-".$value->nc] = array(
						'id' 		=> 0, 
				        'carrec' 	=> BaseController::getCarrec($value->cid).($mostrarNumeracio?' '.$value->nc:''), 
						'nom' 	 	=> (isset($value->nom) != true?'desconegut':$value->nom )
				);
			} else {
				$membreJunta = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($value->id);
				$carrecs[$value->cid."-".$value->nc] = array(
						'id' 		=> $value->id, 
				        'carrec' 	=> BaseController::getCarrec($value->cid).($mostrarNumeracio?' '.$value->nc:''), 
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
        if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest(), false, true)) return $redirect;
    	
		$codi	= $request->query->get('club', '');    	

		$em = $this->getDoctrine()->getManager();
	    
		$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		
		try {
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
		$action = "";
		$codiclub = "";
	
    	try {
			if ($request->getMethod() != 'POST') {
			    $id = $request->query->get('id');
			    $codiclub = $request->query->get('club', '');
			}
			else {
				$requestParams = $request->request->all();
				$useruser = $requestParams['user']['user'];
	    		$randomPassword = $requestParams['user']['pwd']['first'];
	    		$userrole = $requestParams['user']['role'];
				$idFederat = $requestParams['user']['auxinstructordni'];
				$id = isset($requestParams['user']['id'])?$requestParams['user']['id']:0;
				$codiclub = isset($requestParams['user']['club'])?$requestParams['user']['club']:"";
			}
			$checkRole = $this->get('fecdas.rolechecker');
			//$codiclub = $checkRole->getCurrentClubRole();

            if ($codiclub != "" && $this->isCurrentAdmin())  $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codiclub);
			else $club = $checkRole->getCurrentClub();
			
			$optionsForm = array( 'admin' => $this->isCurrentAdmin(), 'club' => $codiclub );
			
			if ($club == null) throw new \Exception("Error. Contacti amb l'administrador (200)");
			
			if (!$checkRole->isCurrentAdmin() && !$checkRole->isCurrentClub()) throw new \Exception("Acció no permesa");
	    
			if (!$request->isXmlHttpRequest()) throw new \Exception("Error. Contacti amb l'administrador (100)");
			
			if ($request->getMethod() == 'POST') {
				// Validar i recuperar usuari existent si escau 
	    		//$forceupdate = (isset($requestParams['club']['forceupdate']))? true: false;
			    $userclub = $this->checkUsuariClub($id == 0, $club, $info, $userrole, $useruser, $randomPassword, $idFederat);
				
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
						
						break;
					case 'removerole':
						
						$userClubRole = $this->getDoctrine()->getRepository('FecdasBundle:EntityUserClub')->find($id);
			
						if ($userClubRole == null) throw new \Exception('Error. Posa\'t en contacte amb l\'administrador (300)');
						$userrole = $userClubRole->getRole();
		
						$userclub = $userClubRole->getUsuari();
						
						$userClubRole->setDatabaixa(new \DateTime('now'));
						
						if (!$userclub->isFederat() && !$userclub->isInstructor() && $userclub->getMetapersona() != null) {
						    $userclub->setMetapersona(null);
						}
						
						$checkRole->updateUserRoles($userclub);
						
			 			$this->get('session')->getFlashBag()->add("sms-notice", "Accés " . $userrole. " anul·lat per l'usuari ".$userclub->getUser());
						
						break;
					
					case 'removeuser':
						$userClub = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->find($id);
						if ($userClub == null) throw new \Exception("Error. Contacti amb l'administrador (302)");
						
						$userClub->baixaUsuari();
						
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
				
				$this->logEntryAuth('USER '. strtoupper($action) . ' OK', 'club : ' . $club->getCodi() . ' user: ' . $userclub->getUser(). ' role: '.$userrole);
			
   				return $this->render('FecdasBundle:Security:clubllistausers.html.twig',
   						array('club' => $club, 'admin' =>$this->isCurrentAdmin()));
			} else {
                $pwd = $userclub->getPwd(); // Guardem per evitar actualització pwd null
				$form = $this->createForm(new FormClubUser( $optionsForm ), $userclub);
				
	    		// Alta nou usuari de club o afegir rol
	    		$form->handleRequest($request);

	    		if (!$form->isValid()) {
	    		    $string = (string) $form->getErrors(true, false);
	    		    
	    		    throw new \Exception("error validant les dades ". $string);
	    		}

                $userclub->setPwd($pwd);
	    		$checkRole->updateUserRoles($userclub);
								
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

			if ($em->isOpen()) $em->clear();
			
			if ($request->getMethod() != 'POST') $this->logEntryAuth('USER '.$action.' KO', $e->getMessage().$extra);	
			else $this->logEntryAuth('USER SUBMIT KO', $e->getMessage());
			
			return $response;	// Error
		}
		
		$response = new Response('error 1234');
	    $response->setStatusCode(500);
				
		return $response;	// No hauria d'arribar mai
    }

    private function checkUsuariClub($nou, $club, &$info, $userrole, $useruser, $randomPassword, $idFederat = 0) {
		$em = $this->getDoctrine()->getManager();
		
		$checkuser = $this->getDoctrine()->getRepository('FecdasBundle:EntityUser')->findOneBy(array('user' => trim($useruser)));

		$metaPersona = null;
		if ($checkuser != null) $metaPersona = $checkuser->getMetapersona();
		// Check Roles existents pel mateix mail
		
		//if ($checkuser != null && count($checkuser) > 1) throw new \Exception("Hi ha varis usuaris amb el mateix correu "); 
		
		// Check NEW Role				
		if ($userrole == BaseController::ROLE_INSTRUCTOR ||
		    $userrole == BaseController::ROLE_FEDERAT) {
			// Cal haver indicat un instructor
		    $federat = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($idFederat);
			
		    if ($federat == null) throw new \Exception("Federat no trobat ".$idFederat);
									
		    if ($metaPersona != null && $metaPersona !== $federat->getMetapersona())  throw new \Exception("Aquest usuari pertany a una altra persona");
		    
		    $metaPersona = $federat->getMetapersona();
		    
		    if ($metaPersona->getUsuari() != null && $metaPersona->getUsuari() !== $checkuser)  throw new \Exception("Aquest federat està associat a un altre usuari \"".$metaPersona->getUsuari()->getUser()."\"");
		    
		    // Validar llicència instructor / federat
			$llicenciaVigent = $metaPersona->getLlicenciaVigent();
			if ($llicenciaVigent == null) throw new \Exception("Aquesta persona no té cap llicència vigent ");
			if ($userrole == BaseController::ROLE_INSTRUCTOR && !$llicenciaVigent->esTecnic()) throw new \Exception("La llicència actual d'aquesta persona no permet afegir-la com instructor ");
			
			$mailsPersona = $metaPersona->getMails();
			if (!in_array($useruser, $mailsPersona)) throw new \Exception("El mail no és d'aquesta persona ");
			
			if ($checkuser != null && $checkuser->getMetapersona() == null)  $checkuser->setMetapersona($metaPersona);
			
		} else {
			// Només poden fer altes Administradors els propis Administradors
			if ($userrole == BaseController::ROLE_ADMIN && !$this->isCurrentAdmin()) throw new \Exception("Privilegis insuficients per afegir Administradors");
				
			// Un mail pot tenir role administrador només a la FEDERACIÓ ¿? 
			if ($userrole == BaseController::ROLE_ADMIN && !$club->esFederacio()) throw new \Exception("Només es poden afegir Administradors a la Federació");
		}
				
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
						$club === $checkUserRole->getClub()) throw new \Exception("Aquest usuari ja disposa d'accés amb rol ".$userrole." per aquest usuari: ".$useruser); 	
					
					if ($userrole != $checkUserRole->getRole() &&
						$nou &&
						$club === $checkUserRole->getClub()) throw new \Exception("Aquest usuari ja disposa d'accés, es poden afegir permisos des de la taula d'usuaris ");
						
					if ($userrole == BaseController::ROLE_CLUB && 
						$checkUserRole->getRole() == BaseController::ROLE_CLUB &&
						$club !== $checkUserRole->getClub()) throw new \Exception("Aquest usuari pertany a un altre club: ".$useruser.
																				($this->isCurrentAdmin()?".(Admins) Club ".$checkUserRole->getClub()->getNom():"") ); 	
				}
			}
			
		} else {
		    $checkuser = new EntityUser($useruser, sha1($randomPassword), true, $metaPersona);
		    $em->persist($checkuser);
			
			$info = "Nou usuari ".$userrole." creat correctament amb clau ".$randomPassword.PHP_EOL;
		}
				
		// Tot OK afegir role usuari al club
		$userClubRole = $club->addUsuariRole($checkuser, $userrole);
		$info .= "Nou accés ".$userrole." per a  ".$useruser;
		$em->persist($userClubRole);
		return $checkuser;
	}
	
}
