<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_titulacions")
 * 
 * @author alex
 *
 */
class EntityTitulacio {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityMetaPersona", inversedBy="titulacions")
	 * @ORM\JoinColumn(name="metapersona", referencedColumnName="id")
	 */
	protected $metapersona;	// FK taula m_metapersones
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityCurs", inversedBy="participants")
	 * @ORM\JoinColumn(name="curs", referencedColumnName="id")
	 */
	protected $curs;	// FK taula m_cursos
	
	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	protected $num;	 // El crea la FEDAS, el número es pot crear en un any diferent de l'inici o la finalització
	
	/**
	 * @ORM\Column(type="date", nullable=true)
	 */
	protected $datasuperacio;

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
	public function __construct($metapersona = null, $curs = null)
	{
		$this->id = 0;
		$this->metapersona = $metapersona;
		$this->curs = $curs;
		$this->num = "Pendent";  // pendent
		$this->dataentrada = new \DateTime();
		$this->datamodificacio = new \DateTime();

	}
	
	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}
	
	/**
	 * @return titol del curs
	 */
	public function getTitol() {
		return $this->curs->getTitol();
	}
	
	/**
	 * @return Nom del club
	 */
	public function getClub() {
		return $this->curs->getClubInfo();
	}
	
	/**
	 * @return pertany al club?
	 */
	public function checkClub($club) {
		if ($this->curs->getClub() == null) return false;
		return $this->curs->getClub()->getCodi() == $club->getCodi();
	}
	
	/**
	 * @return titulacio anul·lada?
	 */
	public function anulada() {
		return $this->databaixa != null;
	}
	
	/**
	 * @return baixa titulacio
	 */
	public function baixa() {
		$this->setDatamodificacio(new \DateTime('now'));
		$this->setDatabaixa(new \DateTime('now'));
	}
	
	/**
	 * @return titulacio consolidada?
	 */
	public function consolidada() {
		return !$this->anulada() && $this->datasuperacio != null;
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
	 * Set metapersona
	 *
	 * @param \FecdasBundle\Entity\EntityMetaPersona $metapersona
	 */
	public function setMetapersona(\FecdasBundle\Entity\EntityMetaPersona $metapersona = null)
	{
		$this->metapersona = $metapersona;
	}
	
	/**
	 * Get metapersona
	 *
	 * @return \FecdasBundle\Entity\EntityMetaPersona
	 */
	public function getMetapersona()
	{
		return $this->metapersona;
	}


	/**
	 * Set curs
	 *
	 * @param \FecdasBundle\Entity\EntityCurs $curs
	 */
	public function setClub(\FecdasBundle\Entity\EntityCurs $curs = null)
	{
		$this->curs = $curs;
	}
	
	/**
	 * Get curs
	 *
	 * @return \FecdasBundle\Entity\EntityCurs
	 */
	public function getCurs()
	{
		return $this->curs;
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
	 * @param datetime $datasuperacio
	 */
	public function setDatasuperacio($datasuperacio) {
		$this->datasuperacio = $datasuperacio;
	}
	
	/**
	 * @return datetime
	 */
	public function getDatasuperacio() {
		return $this->datasuperacio;
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
