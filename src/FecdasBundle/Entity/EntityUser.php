<?php
namespace FecdasBundle\Entity;

use FecdasBundle\Controller\BaseController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity
 * @ORM\Table(name="m_users")
 * 
 * @author alex
 *
 */
class EntityUser {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	
	
	/**
	 * @ORM\Column(name="usuari",type="string", length=50, unique=true, nullable=false)
	 * @Assert\Email()
	 */
	protected $user;	// Mail del club

	/**
	 * @ORM\Column(type="string", length=40, nullable=false)
	 */
	protected $pwd;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $newsletter;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityUserClub", mappedBy="usuari")
	 */
	protected $clubs;	// Owning side of the relationship
	
	/**
	 * @ORM\OneToOne(targetEntity="EntityMetaPersona", inversedBy="usuari")
	 * @ORM\JoinColumn(name="metapersona", referencedColumnName="id")
	 */
	protected $metapersona;			// Un usuari pot estar associat a una metapersona (federat o instructor)
	
	/**
	 * @ORM\Column(type="string", length=40, nullable=true)
	 */
	protected $recoverytoken;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $recoveryexpiration;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $lastaccess;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;
	
	public function __construct( $user = null, $pwd = null, $newsletter = true, $metapersona = null ) {
		$this->id = 0;
		$this->user = $user;
		$this->pwd 	= $pwd;
		$this->newsletter 	= $newsletter;
		$this->metapersona = $metapersona;
		$this->clubs = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->user;
	}
	
	/**
     * està anul·lat?
     *
     * @return boolean
     */
    public function anulat()
    {
    	return $this->databaixa != null;
    }
	
	/**
     * has Admin?
     *
     * @return boolean
     */
    public function isAdmin()
    {
    	return count($this->getRolesAdmin()) > 0; 	
    }
	
    /**
     * has club?
     *
     * @return boolean
     */
    public function isClub()
    {
        return $this->getRoleClub() != null;
    }
    
    /**
     * has instructor?
     *
     * @return boolean
     */
    public function isInstructor()
    {
        return count($this->getRolesInstructor()) > 0;
    }
    
    /**
     * has federat?
     *
     * @return boolean
     */
    public function isFederat()
    {
        return $this->getRoleFederat() != null;
    }
    
	/**
     * Get JSON  roles 
     *
     * @return string
     */
    public function getRolesJSON()
    {
    	$roles = array('admin' => $this->isAdmin(), 'roles' => array());
			
    	foreach ($this->clubs as $userClubRole) {
    		if (!$userClubRole->anulat()) {
    			$roles['roles'][] = array('role' => $userClubRole->getRole(), 
    									  'club' => $userClubRole->getClub()->getCodi(),
										  'nom'  => $userClubRole->getClub()->getNom());
			}
		}
    	return json_encode($roles);
    }
	
	/**
     * Get roles club
     *
     * @return array
     */
    public function getRolesClubs($club)
    {
    	if ($club == null) return $this->clubs;
		
		$roles = array();
		foreach ($this->clubs as $userClubRole) {
    		if (!$userClubRole->anulat() && $userClubRole->getClub() === $club) $roles[] = $userClubRole;
		}
    	return $roles;
    }
	
    /**
     * Get clubs with role
     *
     * @return array
     */
    public function getClubsRole($role)
    {
        $clubs = array();
        if ($role == null || $role == "") return $clubs;
        
        foreach ($this->clubs as $userClubRole) {
            if (!$userClubRole->anulat() && $userClubRole->getRole() == $role) $clubs[] = $userClubRole->getClub();
        }
        return $clubs;
    }
    
    /**
     * Has role club
     *
     * @return boolean
     */
    public function hasRoleClub($club, $role)
    {
        foreach ($this->clubs as $userClubRole) {
            if ($userClubRole->getClub() === $club && $userClubRole->getRole() === $role) return true;
        }
        return false;
    }
    
    /**
     * Activar rol usuari a tots els clubs. Només federat
     *
     */
    public function activarUsuariRole($metapersona, $role)
    {
        if ($role != BaseController::ROLE_FEDERAT) return;
        $this->databaixa = null;
        $this->metapersona = $metapersona;
        
        foreach ($this->clubs as $userClubRole) $userClubRole->activarRole($role);
    }
    
    /**
     * Desactivar rol usuari. Si no queda cap rol actiu, baixa d'usuari
     *
     */
    public function desactivarUsuariRole($role)
    {
        foreach ($this->clubs as $userClubRole) $userClubRole->desactivarRole($role);
        
        if ($this->getBaseRole() == null) $this->databaixa = new \DateTime();
        
    }
    
    public function baixaUsuari()
    {
        $this->databaixa = new \DateTime();
        foreach ($this->clubs as $userClubRole) $userClubRole->desactivarRole();
        
        $this->setMetapersona(null);
    }
    
    /**
     * Get main role
     *
     * @return EntityUserClub 
     */
    public function getBaseRole()
    {
    	// Revisa rols per ordre preferència: Admin, Club, Instructor, Federat (Pendent)
        $roles = $this->getRolesAdmin();
        if (count($roles) == 0) $roles = $this->getRoleClub()==null?array():array($this->getRoleClub());
		if (count($roles) == 0) $roles = $this->getRolesInstructor();
		if (count($roles) == 0) $roles = $this->getRoleFederat()==null?array():array($this->getRoleFederat());
		
		return count($roles) == 0?null:$roles[0];
    }
	
    /**
     * Roles from this user need active license 
     *
     * @return boolean
     */
    public function roleNeedLlicencia()
    {
        // Necessita llicència si no és Admin ni Club
        return !$this->isAdmin() && !$this->isClub();
    }
    
    
    /**
     * Usuari pendent d'omplir dades personals. Rol Federat + metapersona == null
     *
     * @return boolean
     */
    public function isPendentDadesPersonals()
    {
        return $this->isFederat() && $this->metapersona == null;
    }
    
	/**
     * Get club
     *
     * @return EntityClub 
     */
    public function getBaseClub()
    {
    	// Revisa rols per ordre preferència: Admin, Club, Instructor, Federat (Pendent)
    	$role = $this->getBaseRole();
		if ($role != null) return $role->getClub();
		return null;
    }
	
	/**
     * Get club
     *
     * @return EntityUSerClub 
     */
    public function getRolesAdmin()
    {
        $admins = array();
        foreach ($this->clubs as $userClubRole) {
            if (!$userClubRole->anulat() && $userClubRole->isAdmin()) $admins[] = $userClubRole;
        }
    	
        return $admins;
    }
	
	/**
     * Get club, usuari només pot tenir un role club
     *
     * @return EntityUSerClub 
     */
    public function getRoleClub()
    {
        foreach ($this->clubs as $userClubRole) if (!$userClubRole->anulat() && $userClubRole->isRoleClub()) return $userClubRole;
    	
    	return null;
    }
	
	/**
     * Get club
     *
     * @return EntityUSerClub 
     */
    public function getRolesInstructor()
    {
        $instructors = array();
        foreach ($this->clubs as $userClubRole) {
            if (!$userClubRole->anulat() && $userClubRole->isRoleInstructor()) $instructors[] =$userClubRole;
        }
    	
        return $instructors;
    }
	
	/**
     * Get club
     *
     * @return EntityUSerClub 
     */
    public function getRoleFederat()
    {
        foreach ($this->clubs as $userClubRole) if ($userClubRole->isRoleFederat()) return $userClubRole;
    	
    	return null;
    }
	
	/**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
    	return $this->id;
    }

    // Set Id not autogenerated
    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
    	$this->id = $id;
    }
	
	/**
     * Set user
     *
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return string 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set pwd
     *
     * @param string $pwd
     */
    public function setPwd($pwd)
    {
        $this->pwd = $pwd;
    }

    /**
     * Get pwd
     *
     * @return string 
     */
    public function getPwd()
    {
        return $this->pwd;
    }
	
    /**
     * Get newsletter
     *
     * @return boolean
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }
    
    /**
     * Set newsletter
     * Set metapersona newsletter si no és null
     *
     * @param boolean $newsletter
     */
    public function setNewsletter($newsletter)
    {
        $this->newsletter = $newsletter;
        if ($this->metapersona != null) $this->metapersona->setNewsletter($newsletter);
    }
    
    
	/**
     * Get clubs
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getClubs()
    {
    	return $this->clubs;	// userClubRoles
    }
    
	/**
     * Add user club
     *
     * @param EntityUserClub $club
     */
    public function addClub(EntityUserClub $userClub)
    {
    	$this->clubs->add($userClub);
    }
	
	    
    public function setClubs(\Doctrine\Common\Collections\ArrayCollection $clubs)
    {
    	$this->clubs = $clubs;
    	foreach ($clubs as $userClubRole) {
    		$userClubRole->setUsuari($this);
    	}
    }
	
    /**
     * Get metapersona
     *
     * @return EntityMetaPersona 
     */
    public function getMetapersona()
    {
        return $this->metapersona;
    }	
	
	/**
     * Set metapersona
     *
     * @param EntityMetaPersona $metapersona
     */
    public function setMetapersona(EntityMetaPersona $metapersona = null)
    {
        if ($metapersona == null && $this->metapersona != null) $this->metapersona->setUsuari(null);
        
        $this->metapersona = $metapersona;
    }

    
    /**
     * Set recoverytoken
     *
     * @param boolean $recoverytoken
     */
    public function setRecoverytoken($recoverytoken)
    {
    	$this->recoverytoken = $recoverytoken;
    }
    
    /**
     * Get recoverytoken
     *
     * @return boolean
     */
    public function getRecoverytoken()
    {
    	return $this->recoverytoken;
    }
    
    /**
     * Set recoveryexpiration
     *
     * @param boolean $recoveryexpiration
     */
    public function setRecoveryexpiration($recoveryexpiration)
    {
    	$this->recoveryexpiration = $recoveryexpiration;
    }
    
    /**
     * Get recoveryexpiration
     *
     * @return boolean
     */
    public function getRecoveryexpiration()
    {
    	return $this->recoveryexpiration;
    }
    
    /**
     * Set lastaccess
     *
     * @param \DateTime $lastaccess
     */
    public function setLastaccess($lastaccess)
    {
    	$this->lastaccess = $lastaccess;
    }
    
    /**
     * Get lastaccess
     *
     * @return \DateTime
     */
    public function getLastaccess()
    {
    	return $this->lastaccess;
    }
    
    /**
     * Set databaixa
     *
     * @param \DateTime $databaixa
     */

    public function setDatabaixa($databaixa)
    {
    	$this->databaixa = $databaixa;
    }
    
    /**
     * Get databaixa
     *
     * @return \DateTime
     */
    public function getDatabaixa()
    {
    	return $this->databaixa;
    }
}