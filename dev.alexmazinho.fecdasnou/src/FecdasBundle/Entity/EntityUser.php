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
	 * @ORM\Column(name="usuari",type="string", length=50)
	 * @Assert\NotBlank()
	 * @Assert\Email()
	 */
	protected $user;	// Mail del club

	/**
	 * @ORM\Column(type="string", length=40)
	 * @Assert\NotBlank()
	 */
	protected $pwd;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub", inversedBy="usuaris")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;  // FK m_clubs
	
	/**
	 * @ORM\Column(type="string", length=5)
	 */
	protected $role;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $forceupdate;
	
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
	
	public function __construct() {
		$this->forceupdate = true;
	}
	
	public function __toString() {
		return $this->user;
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
     * Set club
     *
     * @param FecdasBundle\Entity\EntityClub $club
     */
    public function setClub(\FecdasBundle\Entity\EntityClub $club = null)
    {
        $this->club = $club;
    }

    /**
     * Get club
     *
     * @return FecdasBundle\Entity\EntityClub 
     */
    public function getClub()
    {
        return $this->club;
    }
    
    /**
     * Set role
     *
     * @param string $role
     */
    public function setRole($role)
    {
    	$this->role = $role;
    }
    
    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
    	return $this->role;
    }
    
    /**
     * Set forceupdate
     *
     * @param boolean $forceupdate
     */
    public function setForceupdate($forceupdate)
    {
    	$this->forceupdate = $forceupdate;
    }
    
    /**
     * Get forceupdate
     *
     * @return boolean
     */
    public function getForceupdate()
    {
    	return $this->forceupdate;
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
     * @param datetime $lastaccess
     */
    public function setLastaccess($lastaccess)
    {
    	$this->lastaccess = $lastaccess;
    }
    
    /**
     * Get lastaccess
     *
     * @return datetime
     */
    public function getLastaccess()
    {
    	return $this->lastaccess;
    }
    
    /**
     * Set databaixa
     *
     * @param datetime $databaixa
     */

    public function setDatabaixa($databaixa)
    {
    	$this->databaixa = $databaixa;
    }
    
    /**
     * Get databaixa
     *
     * @return datetime
     */
    public function getDatabaixa()
    {
    	return $this->databaixa;
    }
}