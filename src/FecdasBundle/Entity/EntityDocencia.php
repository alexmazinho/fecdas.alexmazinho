<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_docencies")
 * 
 * @author alex
 *
 */
class EntityDocencia {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityMetaPersona", inversedBy="docencies")
	 * @ORM\JoinColumn(name="metadocent", referencedColumnName="id")
	 */
	protected $metadocent;	// FK taula m_metapersones
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityCurs", inversedBy="docents")
	 * @ORM\JoinColumn(name="curs", referencedColumnName="id")
	 */
	protected $curs;	// FK taula m_cursos
	
	/**
	 * @ORM\Column(type="string", length=20)
	 */
	protected $rol;	// director, co-director, instructor, colaborador
	
	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	protected $carnet;	// Num titulació instructor corresponent (EntityTitulacio -> num)	
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $hteoria;	// Hores teoria

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $haula;  // Hores aula
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $hpiscina;  // Hores piscina
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $hmar; // Hores mar
	
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
	public function __construct($metadocent = null, $curs = null, $rol = '')
	{
		$this->id = 0;
		$this->metadocent = $metadocent;
		$this->curs = $curs;
		$this->rol = ($rol == '' || $rol == null)?BaseController::DOCENT_INSTRUCTOR:$rol;  // pendent
		$this->hteoria = 0;
		$this->haula = 0;
		$this->hpiscina = 0;
		$this->hmar = 0;
		
		$this->dataentrada = new \DateTime();
		$this->datamodificacio = new \DateTime();

	}
	
	/**
	 * Retorna docència anul·lada?
	 * @return boolean
	 */
	public function anulada() {
		return $this->databaixa != null;
	}
	
	/**
	 * Retorna baixa docència
	 * @return boolean
	 */
	public function baixa() {
		$this->setDatamodificacio(new \DateTime('now'));
		$this->setDatabaixa(new \DateTime('now'));
	}
	
	/**
	 * es director?
	 * @return boolean 
	 */
	public function esDirector() {
		return $this->rol === BaseController::DOCENT_DIRECTOR;
	}
	
	/**
	 * es codirector?
	 * @return boolean 
	 */
	public function esCodirector() {
		return $this->rol === BaseController::DOCENT_CODIRECTOR;
	}
	
	/**
	 * es instructor?
	 * @return boolean
	 */
	public function esInstructor() {
	    return $this->rol === BaseController::DOCENT_INSTRUCTOR;
	}
	
	/**
	 * es docent classes teòriques?
	 * @return boolean
	 */
	public function esDocentTeoriques() {
	    return $this->hteoria > 0;
	}
	
	/**
	 * es docent classes pràctiques?
	 * @return boolean
	 */
	public function esDocentPractiques() {
	    return $this->haula + $this->hpiscina + $this->hmar > 0;
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
	 * Set metadocent
	 *
	 * @param \FecdasBundle\Entity\EntityMetaPersona $metadocent
	 */
	public function setMetadocent(\FecdasBundle\Entity\EntityMetaPersona $metadocent = null)
	{
		$this->metadocent = $metadocent;
	}
	
	/**
	 * Get metadocent
	 *
	 * @return \FecdasBundle\Entity\EntityMetaPersona
	 */
	public function getMetadocent()
	{
		return $this->metadocent;
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
	 * @param string $rol
	 */
	public function setRol($rol) {
		$this->rol = $rol;
	}

	/**
	 * @return string
	 */
	public function getRol() {
		return $this->rol;
	}
	
	/**
	 * @param string $carnet
	 */
	public function setCarnet($carnet) {
		$this->carnet = $carnet;
	}

	/**
	 * @return string
	 */
	public function getCarnet() {
		return $this->carnet;
	}

	/**
	 * @param integer $hteoria
	 */
	public function setHteoria($hteoria) {
		$this->hteoria = $hteoria;
	}

	/**
	 * @return integer
	 */
	public function getHteoria() {
		return $this->hteoria;
	}

	/**
	 * @param integer $haula
	 */
	public function setHaula($haula) {
		$this->haula = $haula;
	}

	/**
	 * @return integer
	 */
	public function getHaula() {
		return $this->haula;
	}

	/**
	 * @param integer $hpiscina
	 */
	public function setHpiscina($hpiscina) {
		$this->hpiscina = $hpiscina;
	}

	/**
	 * @return integer
	 */
	public function getHpiscina() {
		return $this->hpiscina;
	}

	/**
	 * @param integer $hmar
	 */
	public function setHmar($hmar) {
		$this->hmar = $hmar;
	}

	/**
	 * @return integer
	 */
	public function getHmar() {
		return $this->hmar;
	}

	/**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;
    }

    /**
     * Get dataentrada
     *
     * @return \DateTime 
     */
    public function getDataentrada()
    {
        return $this->dataentrada;
    }

    /**
     * Set datamodificacio
     *
     * @param \DateTime $datamodificacio
     */
    public function setDatamodificacio($datamodificacio)
    {
        $this->datamodificacio = $datamodificacio;
    }

    /**
     * Get datamodificacio
     *
     * @return \DateTime 
     */
    public function getDatamodificacio()
    {
        return $this->datamodificacio;
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
