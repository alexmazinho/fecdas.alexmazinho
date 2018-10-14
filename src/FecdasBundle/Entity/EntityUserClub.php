<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_usersclubs")
 * 
 * @author alex
 *
 */
class EntityUserClub {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	
	
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub", inversedBy="usuaris")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;  // FK m_clubs
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityUser", inversedBy="clubs")
	 * @ORM\JoinColumn(name="usuari", referencedColumnName="id")
	 */
	protected $usuari;  // FK m_users

	/**
	 * @ORM\Column(type="string", length=20)
	 */
	protected $role;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;
	
	public function __construct( $club, $usuari, $role ) {
		$this->club = $club;	
		$this->usuari = $usuari;	
		$this->role = $role;
	}
	
	public function __toString() {
		return $this->id." ";
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
     * Activar rol
     *
     */
    public function activarRole($role = null)
    {
        if ($role == null || $this->role == $role) $this->databaixa = null;
    }
    
    /**
     * Desactivar rol
     *
     */
    public function desactivarRole($role = null)
    {
        if ($role == null || $this->role == $role) $this->databaixa = new \DateTime();
    }
    
	/**
     * is Admin?
     *
     * @return boolean
     */
    public function isAdmin()
    {
    	return !$this->anulat() && $this->role == BaseController::ROLE_ADMIN;
    }
	
	/**
     * is role Club?
     *
     * @return boolean
     */
    public function isRoleClub()
    {
    	return !$this->anulat() && $this->role == BaseController::ROLE_CLUB;
    }

	/**
     * is role Instructor?
     *
     * @return boolean
     */
    public function isRoleInstructor()
    {
    	return !$this->anulat() && $this->role == BaseController::ROLE_INSTRUCTOR;
    }

	/**
     * is role Federat?
     *
     * @return boolean
     */
    public function isRoleFederat()
    {
    	return !$this->anulat() && $this->role == BaseController::ROLE_FEDERAT;
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
     * Set club
     *
     * @param EntityClub $club
     */
    public function setClub(EntityClub $club = null)
    {
       $this->club = $club;
    }

    /**
     * Get club
     *
     * @return EntityClub 
     */
    public function getClub()
    {
       return $this->club;
    }
	
	/**
     * Set usuari
     *
     * @param string $usuari
     */
    public function setUsuari(EntityUser $usuari = null)
    {
        $this->usuari = $usuari;
    }

    /**
     * Get usuari
     *
     * @return EntityUser 
     */
    public function getUsuari()
    {
        return $this->usuari;
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