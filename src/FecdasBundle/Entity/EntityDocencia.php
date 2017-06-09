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
	 * @ORM\JoinColumn(name="docent", referencedColumnName="id")
	 */
	protected $docent;	// FK taula m_persones
	
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
	public function __construct($docent = null, $rol = '')
	{
		$this->id = 0;
		
		$this->docent = $docent;
		$this->rol = ($rol == '' || $rol == null)?BaseController::DOCENT_INSTRUCTOR:$rol;  // pendent
		$this->hteoria = 0;
		$this->haula = 0;
		$this->hpiscina = 0;
		$this->hmar = 0;
		
		$this->dataentrada = new \DateTime();
		$this->datamodificacio = new \DateTime();

	}
	
	/**
	 * @return docència anul·lada?
	 */
	public function anulada() {
		return $this->databaixa != null;
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
	 * Set docent
	 *
	 * @param \FecdasBundle\Entity\EntityMetaPersona $docent
	 */
	public function setDocent(\FecdasBundle\Entity\EntityMetaPersona $docent = null)
	{
		$this->docent = $docent;
	}
	
	/**
	 * Get docent
	 *
	 * @return \FecdasBundle\Entity\EntityMetaPersona
	 */
	public function getDocent()
	{
		return $this->docent;
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
