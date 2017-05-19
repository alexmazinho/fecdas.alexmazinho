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

	public function isAuthenticated() {
		
		$request = $this->requestStack->getCurrentRequest();
		if (!$this->session->has('username') 	||
			!$this->session->has('remote_addr') ||
			($this->session->get('remote_addr') != $request->server->get('REMOTE_ADDR'))) return false;	
			
		if ($this->session->has('currentrole') && $this->session->get('currentrole') != '') return true;
		
		// Encara no disposa de role assignat Accedir BBDD i obtenir role per defecte
		$repository = self::$em->getRepository('FecdasBundle:EntityUser');
		$user = $repository->findOneByUser($this->session->get('username'));		

		if (!$user || $user->getRole() == '') return false;	// No existeix o no té cap role
		
		$this->session->set('currentrole', $user->getRole());
		$this->session->set('userroles', $user->getRoles());
		
		return true;
	}
	
	public function isCurrentAdmin() {
			
		if ($this->isAuthenticated() != true) return false;

		return $this->session->get('currentrole') == BaseController::ROLE_ADMIN;		
	}
	
	public function getCurrentRole() {
		if ($this->isAuthenticated() != true) return '';

		return $this->session->get('currentrole');		
	}
	
	public function getUserRoles() {
		if ($this->isAuthenticated() != true) return array();

		return explode(";", $this->session->get('userroles'));		
	}
	
}