<?php
namespace Fecdas\PartesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_clubs")
 * 
 * @author alex
 *
 */
class EntityClub {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="string", length=6)
	 */
	protected $codi;	// fedeclub, CATXXX
	
	/**
	 * @ORM\Column(type="string", length=30)
	 * @Assert\NotBlank()
	 */
	protected $nom;
	
	/**
     * @ORM\ManyToOne(targetEntity="EntityClubType")
     * @ORM\JoinColumn(name="tipus", referencedColumnName="id")
     */
	protected $tipus;	// FK taula m_tipusclub

	/**
	 * @ORM\Column(type="integer", length=20, nullable=true)
	 */
	protected $telefon;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 * @Assert\NotBlank()
	 */
	protected $mail;
	
	/**
	 * @ORM\Column(type="string", length=150, nullable=true)
	 */
	protected $web;
	
	/**
	 * @ORM\Column(type="string", length=12)
	 * @Assert\NotBlank()
	 */
	protected $cif;

	/**
	 * @ORM\Column(type="string", length=75, nullable=true)
	 */
	protected $addradreca;

	/**
	 * @ORM\Column(type="string", length=35, nullable=true)
	 */
	protected $addrpob;
	
	/**
	 * @ORM\Column(type="string", length=5, nullable=true)
	 */
	protected $addrcp;
	
	/**
	 * @ORM\Column(type="string", length=20, nullable=true)
	 */
	protected $addrprovincia;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $decathlon;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $tecnocampus;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $activat;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityUser", mappedBy="club")
	 */
	protected $usuaris;	// Owning side of the relationship
	
	public function __construct() {
		$this->activat = true;
		$this->decathlon = false;
		$this->tecnocampus = false;
		$this->usuaris = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->codi;
	}
	
	
    /**
     * Set codi
     *
     * @param string $codi
     */
    public function setCodi($codi)
    {
        $this->codi = $codi;
    }

    /**
     * Get codi
     *
     * @return string 
     */
    public function getCodi()
    {
        return $this->codi;
    }

    /**
     * Set nom
     *
     * @param string $nom
     */
    public function setNom($nom)
    {
        $this->nom = $nom;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set tipus
     *
     * @param Fecdas\PartesBundle\Entity\EntityClubType $tipus
     */
    public function setTipus(\Fecdas\PartesBundle\Entity\EntityClubType $tipus)
    {
        $this->tipus = $tipus;
    }

    /**
     * Get tipus
     *
     * @return Fecdas\PartesBundle\Entity\EntityClubType 
     */
    public function getTipus()
    {
        return $this->tipus;
    }
    
    /**
     * Get informació club en llistes desplegables
     *
     * @return string
     */
    public function getLlistaText()
    {
    	return $this->codi . "-" . $this->nom;
    }

    public function _toString()
    {
    	return $this->codi . "-" . $this->nom;
    }
    
    
    /**
     * Set telefon
     *
     * @param integer $telefon
     */
    public function setTelefon($telefon)
    {
        $this->telefon = $telefon;
    }

    /**
     * Get telefon
     *
     * @return integer 
     */
    public function getTelefon()
    {
        return $this->telefon;
    }

    /**
     * Set cif
     *
     * @param string $cif
     */
    public function setCif($cif)
    {
        $this->cif = $cif;
    }

    /**
     * Get cif
     *
     * @return string 
     */
    public function getCif()
    {
        return $this->cif;
    }

    /**
     * Set mail
     *
     * @param string $mail
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
    }

    /**
     * Get mail
     *
     * @return string 
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set web
     *
     * @param string $web
     */
    public function setWeb($web)
    {
        $this->web = $web;
    }

    /**
     * Get web
     *
     * @return string 
     */
    public function getWeb()
    {
        return $this->web;
    }

    /**
     * Set addradreca
     *
     * @param string $addradreca
     */
    public function setAddradreca($addradreca)
    {
        $this->addradreca = $addradreca;
    }

    /**
     * Get addradreca
     *
     * @return string 
     */
    public function getAddradreca()
    {
        return $this->addradreca;
    }

    /**
     * Set addrpob
     *
     * @param string $addrpob
     */
    public function setAddrpob($addrpob)
    {
        $this->addrpob = $addrpob;
    }

    /**
     * Get addrpob
     *
     * @return string 
     */
    public function getAddrpob()
    {
        return $this->addrpob;
    }

    /**
     * Set addrcp
     *
     * @param string $addrcp
     */
    public function setAddrcp($addrcp)
    {
        $this->addrcp = $addrcp;
    }

    /**
     * Get addrcp
     *
     * @return string 
     */
    public function getAddrcp()
    {
        return $this->addrcp;
    }

    /**
     * Set addrprovincia
     *
     * @param string $addrprovincia
     */
    public function setAddrprovincia($addrprovincia)
    {
        $this->addrprovincia = $addrprovincia;
    }

    /**
     * Get addrprovincia
     *
     * @return string 
     */
    public function getAddrprovincia()
    {
        return $this->addrprovincia;
    }
    
    /**
     * Set decathlon
     *
     * @param boolean $decathlon
     */
    public function setDecathlon($decathlon)
    {
    	$this->decathlon = $decathlon;
    }
    
    /**
     * Get decathlon
     *
     * @return boolean
     */
    public function getDecathlon()
    {
    	return $this->decathlon;
    }
    
    /**
     * Set tecnocampus
     *
     * @param boolean $tecnocampus
     */
    public function setTecnocampus($tecnocampus)
    {
    	$this->tecnocampus = $tecnocampus;
    }
    
    /**
     * Get tecnocampus
     *
     * @return boolean
     */
    public function getTecnocampus()
    {
    	return $this->tecnocampus;
    }
    
    /**
     * Set activat
     *
     * @param boolean $activat
     */
    public function setActivat($activat)
    {
    	$this->activat = $activat;
    }
    
    /**
     * Get activat
     *
     * @return boolean
     */
    public function getActivat()
    {
    	return $this->activat;
    }
    
    /**
     * Get usuaris
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getUsuaris()
    {
    	return $this->usuaris;
    }
    
    /**
     * Add user
     *
     * @param Fecdas\PartesBundle\Entity\EntityUser $user
     */
    public function addEntityUser(\Fecdas\PartesBundle\Entity\EntityUser $user)
    {
    	$user->setClub($this);
    	$this->usuaris->add($user);
    }
    
    
    public function setUsuaris(\Doctrine\Common\Collections\ArrayCollection $usuaris)
    {
    	$this->usuaris = $usuaris;
    	foreach ($usuaris as $usuari) {
    		$usuari->setClub($this);
    	}
    }
    
}