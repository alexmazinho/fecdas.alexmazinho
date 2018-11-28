<?php
/**
* @Service()
* @Tag("rolechecker")
*/
namespace FecdasBundle\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Registry;
use FecdasBundle\Controller\BaseController;

class RoleChecker
{
	protected $session;
	protected $requestStack;
	protected static $em;
	

    public function __construct(Session $session, RequestStack $requestStack, Registry $doctrine)
    {
        $this->session = $session;	
        $this->requestStack = $requestStack;
		self::$em = $doctrine->getManager();
    }

	public function authenticateUser( $user, $enquestaactiva = null ) { 	// After Password recovery

	    if ($user == null || $user->getBaseRole() == null || $user->getBaseRole()->getRole() == '' || $user->getBaseClub() == null) return false; 	// No existeix o no té cap role o no té club associat

		$request = $this->requestStack->getCurrentRequest();
		if (!$request->server->has('REMOTE_ADDR')) return false;

		if ($user->getRecoverytoken() != null) {
			// Esborrar token de recuperació de password, si entra amb login normal
			$user->setRecoverytoken(null);
			$user->setRecoveryexpiration(null);
		}
		$user->setLastaccess(new \DateTime('now'));
		$this->session->set('remote_addr', $request->server->get('REMOTE_ADDR'));
		$this->session->set('http_agent', $request->server->get('HTTP_USER_AGENT'));
		$this->session->set('username', $user->getUser());
		$this->session->set('currentrole', $user->getBaseRole()->getRole());
		$this->session->set('userroles', $user->getRolesJSON());		// json	=>  [{'role': 'administrador', 'club': 'CAT999'}, ...]
		$this->session->set('currentclub', $user->getBaseClub()->getCodi());	// Inicialment club de l'usuari
		/* Comprovar enquestes pendents */
		if ($enquestaactiva != null) {
			$realitzada = $enquestaactiva->getRealitzada( $user->getUser() );
    						
			$this->session->set('enquesta', $enquestaactiva->getId());
    						
			if ($realitzada == null || $realitzada->getDatafinal() == null) $this->session->set('enquestapendent', $enquestaactiva->getId());
		}
		return true;
	}

	public function isAuthenticated() {
		$request = $this->requestStack->getCurrentRequest();
		
		if (!$this->session->has('username') 	||
			!$this->session->has('remote_addr') ||
		    ($this->session->get('remote_addr') != $request->server->get('REMOTE_ADDR'))) return false;	
		return true;
	}
	
	public function isCurrentAdmin() {
			
		if (!$this->isAuthenticated()) return false;

		return $this->session->get('currentrole') == BaseController::ROLE_ADMIN;		
	}
	
	public function isCurrentClub() {
			
		if (!$this->isAuthenticated()) return false;

		return $this->session->get('currentrole') == BaseController::ROLE_CLUB;		
	}
	
	public function isCurrentInstructor() {
			
		if (!$this->isAuthenticated()) return false;

		return $this->session->get('currentrole') == BaseController::ROLE_INSTRUCTOR;		
	}

	public function isCurrentFederat() {
			
		if (!$this->isAuthenticated()) return false;

		return $this->session->get('currentrole') == BaseController::ROLE_FEDERAT;		
	}

	public function isCurrentAllowedCursos() {
	    
	    return $this->isCurrentAdmin() || $this->isCurrentClub() || $this->isCurrentInstructor();
	}
	
	public function getCurrentUserName() {
		if (!$this->isAuthenticated()) return '';

		return $this->session->get('username');		
	}

	public function getCurrentUser() {
		if (!$this->isAuthenticated()) return null;

		$repository = self::$em->getRepository('FecdasBundle:EntityUser');
		$user = $repository->findOneByUser( $this->session->get('username') );
		
		return $user;		
	}
	
	public function getCurrentClub() {
	    if (!$this->isAuthenticated()) return null;
	    
	    $repository = self::$em->getRepository('FecdasBundle:EntityClub');
	    $club = $repository->find( $this->session->get('currentclub') );
	    
	    return $club;
	}
	
	public function getCurrentRole() {
		if (!$this->isAuthenticated()) return '';

		return $this->session->get('currentrole');		
	}
	
	public function getUserRoles() {
		if (!$this->isAuthenticated()) return array();

		// json	=> {'admin':true, 'roles': [{'role': 'administrador', 'club': 'CAT999', 'nom': 'FECDAS' }, ...] }
		return json_decode($this->session->get('userroles'));
		/*
		$roles = array();
		foreach (explode(";", $this->session->get('userroles')) as $role) $roles[$role] = $role;
		return $roles;*/		
	}
	
	public function updateUserRoles($user) {
	    if ($user == null || $user->getBaseRole() == null || $user->getBaseRole()->getRole() == '' || $user->getBaseClub() == null) return; 	// No existeix o no té cap role o no té club associat
	    $this->session->set('userroles', $user->getRolesJSON());
	}
	
	public function getUserRolesArray() {	// Clau + valor per dades del select
		if (!$this->isAuthenticated()) return array();

		// json	=> {'admin':true, 'roles': [{'role': 'administrador', 'club': 'CAT999', 'nom': 'FECDAS' }, ...] }
		$roles = $this->getUserRoles();
		$rolesArray = array();
		foreach ($roles->roles as $userClubRole) {
			
		    $club = $userClubRole->club;
		    $nomclub = $userClubRole->nom;
		    if ($roles->admin) {
		          $currentClub = $this->getCurrentClub();
		          $club = $currentClub->getCodi();
		          $nomclub =  $currentClub->getNom();
		    }
		    
		    $key = $this->getUserRoleKey($userClubRole->role, $club);
		    if (!isset($rolesArray[ $key ])) {
    		    $text = mb_strtoupper($userClubRole->role);
    		    if ($userClubRole->role != BaseController::ROLE_FEDERAT) $text .= "<br/><span class='title-comment'>".mb_strtoupper($nomclub)."</span>";
    			$rolesArray[ $key ] = $text;
		    }
		}
		
		asort($rolesArray);
		
		return $rolesArray;
	}
	
	public function getUserRoleKey($role = '', $club = '') {	// Clau per dades del select
		if (!$this->isAuthenticated()) return '';

		if ($role == '') $role = $this->session->get('currentrole');
		
		if ($role == BaseController::ROLE_FEDERAT) return $role;  // Federat no s'associa a un club concret
		
		if ($club == '') $club = $this->session->get('currentclub');
		
		return $role.';'.$club;
	}
	
	public function getCurrentClubRole() {
		if (!$this->isAuthenticated()) return '';

		return $this->session->get('currentclub');
	}
	
	public function getCurrentRemoteAddr() {
		if (!$this->isAuthenticated()) return '';

		return $this->session->get('remote_addr');		
	}
	
	public function getCurrentHTTPAgent() {
		if (!$this->isAuthenticated()) return '';

		return $this->session->get('http_agent');		
	}
	
	public function getCurrentEnquestaActiva() {		
		if (!$this->isAuthenticated() || !$this->session->has('enquesta')) return '';

		return $this->session->get('enquesta');		
	}
	
	public function getCurrentEnquestaPendent() {	
		if (!$this->isAuthenticated() || !$this->session->has('enquestapendent')) return '';

		return $this->session->get('enquestapendent');		
	}
	
	public function setCurrentClubRole( $club, $role ) {
		if (!$this->isAuthenticated() || $role == '') return;
		// json	=> {'admin':true, 'roles': [{'role': 'administrador', 'club': 'CAT999', 'nom': 'FECDAS' }, ...] }
		$roles = $this->getUserRoles();

		foreach ($roles->roles as $userClubRole) {
			if ($roles->admin) {
			    // Només valida role. Pot canviar a qualsevol club 	
				if ($userClubRole->role == $role) {
				    $this->session->set('currentclub', $club);
					$this->session->set('currentrole', $role);
					return;
				}
			} else {
			    if (($userClubRole->club == $club && $userClubRole->role == $role) ||
			        ($role == BaseController::ROLE_FEDERAT && $userClubRole->role == $role) ||
			        ($role == BaseController::ROLE_INSTRUCTOR && $userClubRole->role == $role)) {
					// Valida que sigui un role permès
					$this->session->set('currentclub', $club);
					$this->session->set('currentrole', $role);
					return;
				}
			}
		}
	}
}