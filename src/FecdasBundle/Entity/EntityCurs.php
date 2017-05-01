<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_cursos")
 * 
 * @author alex
 *
 */
class EntityCurs {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub", inversedBy="cursos")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi", nullable=true)
	 */
	protected $club;	// FK taula m_clubs
	
	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	protected $clubhistoric;  // Clubs que ja no existeixen
	
	/**
	 * @ORM\Column(type="string", length=20)
	 */
	protected $num;	  // El crea la FEDAS, el número es pot crear en un any diferent de l'inici o la finalització
	
	/**
	 * @ORM\Column(type="date")
	 */
	protected $datadesde;

	/**
	 * @ORM\Column(type="date")
	 */
	protected $datafins;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityTitol")
	 * @ORM\JoinColumn(name="titol", referencedColumnName="id")
	 */
	protected $titol;	// FK taula m_titols 

	/**
	 * @ORM\OneToMany(targetEntity="EntityTitulacio", mappedBy="curs" )
	 */
	protected $participants;	// FK taula m_titulacions
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityDocencia", mappedBy="curs" )
	 */
	protected $docents;	// FK taula m_docencies

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $validat;  // Pel club
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $finalitzat;   // Per FECDAS indica nums
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datamodificacio;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;

	/**
	 * Constructor
	 */
	public function __construct($titol = null, $datadesde = null, $datafins = null, $club = null, $clubhistoric = '')
	{
	
		$this->id = 0;
		$this->num = "Pendent";  // pendent
		$this->titol = $titol;
		$this->club = $club;
		if ($club == null) $this->clubhistoric = $clubhistoric;
		$this->dataentrada = new \DateTime();
		$this->datamodificacio = new \DateTime();
		
		$this->datadesde = $datadesde == null?new \DateTime():$datadesde;
		$this->datafins = $datafins == null?new \DateTime():$datafins;
		
		$this->validat = false;
		$this->finalitzat = false;
		
		$this->docents = new \Doctrine\Common\Collections\ArrayCollection();
		$this->participants = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	/**
	 * Retorna número acta format ANY_INICI/NUM
	 * 
	 * @return string
	 */
	public function getNumActa() {
		return $this->num;
	}
	
	
	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}
	
	/**
	 * @param integer $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set club
	 *
	 * @param \FecdasBundle\Entity\EntityClub $club
	 */
	public function setClub(\FecdasBundle\Entity\EntityClub $club = null)
	{
		$this->club = $club;
	}
	
	/**
	 * Get club
	 *
	 * @return \FecdasBundle\Entity\EntityClub
	 */
	public function getClub()
	{
		return $this->club;
	}

	/**
     * Set clubhistoric
     *
     * @param string $clubhistoric
     */
    public function setClubhistoric($clubhistoric)
    {
        $this->clubhistoric = $clubhistoric;
    }

    /**
     * Get clubhistoric
     *
     * @return string 
     */
    public function getClubhistoric()
    {
        return $this->clubhistoric;
    }

	/**
	 * @return string
	 */
	public function getNum() {
		return $this->num;
	}
	
	/**
	 * @param string $num
	 */
	public function setNum($num) {
		$this->num = $num;
	}

	/**
	 * @param datetime $datadesde
	 */
	public function setDatadesde($datadesde) {
		$this->datadesde = $datadesde;
	}
	
	/**
	 * @return datetime
	 */
	public function getDatadesde() {
		return $this->datadesde;
	}

	/**
	 * @param datetime $datafins
	 */
	public function setDatafins($datafins) {
		$this->datafins = $datafins;
	}
	
	/**
	 * @return datetime
	 */
	public function getDatafins() {
		return $this->datafins;
	}
	
	/**
	 * Set titol
	 *
	 * @param \FecdasBundle\Entity\EntityTitol $titol
	 */
	public function setTitol(\FecdasBundle\Entity\Entitytitol $titol = null)
	{
		$this->titol = $titol;
	}
	
	/**
	 * Get titol
	 *
	 * @return \FecdasBundle\Entity\EntityTitol
	 */
	public function getTitol()
	{
		return $this->titol;
	}
	
	/**
     * Add docents
     *
     * @param FecdasBundle\Entity\EntityDocencia $docent
     */
    public function addDocents(\FecdasBundle\Entity\EntityDocencia $docent)
    {
        $this->docents->add($docent);
    }

    /**
     * Get docents
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getDocents()
    {
        return $this->docents;
    }
	
	/**
     * Add participants
     *
     * @param FecdasBundle\Entity\EntityTitulacio $participants
     */
    public function addParticipants(\FecdasBundle\Entity\EntityTitulacio $participant)
    {
        $this->participants->add($participant);
    }

    /**
     * Get participants
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getParticipants()
    {
        return $this->participants;
    }
	
	/**
     * Set validat
     *
     * @param boolean $validat
     */
    public function setValidat($validat)
    {
    	$this->validat = $validat;
    }
    
    /**
     * Get validat
     *
     * @return boolean
     */
    public function getValidat()
    {
    	return $this->validat;
    }
    
    /**
     * Set finalitzat
     *
     * @param boolean $finalitzat
     */
    public function setFinalitzat($finalitzat)
    {
    	$this->finalitzat = $finalitzat;
    }
    
    /**
     * Get finalitzat
     *
     * @return boolean
     */
    public function getFinalitzat()
    {
    	return $this->finalitzat;
    }
	
	/**
     * Set dataentrada
     *
     * @param datetime $dataentrada
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;
    }

    /**
     * Get dataentrada
     *
     * @return datetime 
     */
    public function getDataentrada()
    {
        return $this->dataentrada;
    }

    /**
     * Set datamodificacio
     *
     * @param datetime $datamodificacio
     */
    public function setDatamodificacio($datamodificacio)
    {
        $this->datamodificacio = $datamodificacio;
    }

    /**
     * Get datamodificacio
     *
     * @return datetime 
     */
    public function getDatamodificacio()
    {
        return $this->datamodificacio;
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
