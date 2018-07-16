<?php
namespace FecdasBundle\Entity;

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
	
	public function __construct( $user = null, $pwd = null, $metapersona = null ) {
		$this->id = 0;
		$this->user = $user;
		$this->pwd 	= $pwd;
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
    	return $this->getRoleAdmin() != null; 	
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
     * @return \Doctrine\Common\Collections\ArrayCollection
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
     * Activar rol usuari
     *
     */
    public function activarUsuariRole($metapersona, $role)
    {
        $this->databaixa = null;
        $this->metapersona = $metapersona;
        foreach ($this->clubs as $userClubRole) $userClubRole->activarRole($role);
    }
    
    /**
     * Get main role
     *
     * @return EntityUserClub 
     */
    public function getBaseRole()
    {
    	// Revisa rols per ordre preferència: Admin, Club, Instructor, Federat (Pendent)
        $role = $this->getRoleAdmin();
		if ($role == null) $role = $this->getRoleClub();
		if ($role == null) $role = $this->getRoleInstructor();
		if ($role == null) $role = $this->getRoleFederat();  
		
		return $role;
    }
	
    /**
     * Roles from this user need active license 
     *
     * @return boolean
     */
    public function roleNeedLlicencia()
    {
        // Necessita llicència si no és Admin ni Club
        return $this->getRoleAdmin() == null && $this->getRoleClub() == null;
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
    public function getRoleAdmin()
    {
        foreach ($this->clubs as $userClubRole) if ($userClubRole->isAdmin()) return $userClubRole;
    	
    	return null;
    }
	
	/**
     * Get club
     *
     * @return EntityUSerClub 
     */
    public function getRoleClub()
    {
        foreach ($this->clubs as $userClubRole) if ($userClubRole->isRoleClub()) return $userClubRole;
    	
    	return null;
    }
	
	/**
     * Get club
     *
     * @return EntityUSerClub 
     */
    public function getRoleInstructor()
    {
        foreach ($this->clubs as $userClubRole) if ($userClubRole->isRoleInstructor()) return $userClubRole;
    	
    	return null;
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
    public function setMetapersona(EntityMetaPersona $metapersona)
    {
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