<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(name="m_llicencies")
 * 
 * @author alex
 *
 */
class EntityLlicencia {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	

	/**
	 * @Assert\NotNull(message = "Cal seleccionar una persona")
	 * @ORM\ManyToOne(targetEntity="EntityPersona", inversedBy="llicencies")
	 * @ORM\JoinColumn(name="persona", referencedColumnName="id")
	 */
	protected $persona;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityParte", inversedBy="llicencies")
	 * @ORM\JoinColumn(name="parte", referencedColumnName="id")
	 */
	protected $parte;  // Inverse side relationship
	
	/**
	 * @ORM\Column(type="string", length=38, nullable=true)
	 */
	protected $idparte_access;
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $idpartedetall_access;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityCategoria")
	 * @ORM\JoinColumn(name="categoria", referencedColumnName="id")
	 */
	protected $categoria;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $pesca;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $escafandrisme;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $natacio;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $orientacio;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $biologia;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $fotocine;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $hockey;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $fotosubapnea;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $videosub;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $apnea;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $rugbi;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $besportiu;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $bampolles;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $nocmas;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $fusell;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $enviarllicencia;
	
	/**
	 * @ORM\Column(type="date")
	 */
	protected $datacaducitat; 
	
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

	public function __construct($currentDate) {
		$this->id = 0;
		$this->setDataentrada($currentDate);
		$this->pesca = false;
		$this->escafandrisme = false;
		$this->natacio = false;
		$this->orientacio = false;
		$this->biologia = false;
		$this->fotocine = false;
		$this->hockey = false;
		$this->fotosubapnea = false;
		$this->videosub = false;
		$this->apnea = false;
		$this->rugbi = false;
		$this->besportiu = false;
		$this->bampolles = false;
		$this->nocmas = false;
		$this->fusell = false;
		$this->enviarllicencia = true;
	}
	
	public function __toString() {
		return (string) $this->id;
	}
	
	public function __clone() {
		$this->id = 0;
		$this->dataentrada = new \DateTime();
		$this->datamodificacio = null;
		$this->idparte_access = null;
		$this->idpartedetall_access = null;
	}
	
	public function esNova()
	{
		return ($this->id == 0);
	}
	
	/**
	 * Get id
	 *
	 * @return boolean
	 */
	public function esBaixa()
	{
		return $this->databaixa != null;
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
     * Set idparte_access
     *
     * @param string $idparteAccess
     */
    public function setIdparteAccess($idparteAccess)
    {
        $this->idparte_access = $idparteAccess;
    }

    /**
     * Get idparte_access
     *
     * @return string 
     */
    public function getIdparteAccess()
    {
        return $this->idparte_access;
    }

    /**
     * Set idpartedetall_access
     *
     * @param integer $idpartedetall_access
     */
    public function setIdpartedetall_access($idpartedetall_access)
    {
    	$this->idpartedetall_access = $idpartedetall_access;
    }
    
    /**
     * Get idpartedetall_access
     *
     * @return integer
     */
    public function getIdpartedetall_access()
    {
    	return $this->idpartedetall_access;
    }
    
    /**
     * Set persona
     *
     * @param FecdasBundle\Entity\EntityPersona $persona
     */
    public function setPersona(\FecdasBundle\Entity\EntityPersona $persona)
    {
        $this->persona = $persona;
    }

    /**
     * Get persona
     *
     * @return FecdasBundle\Entity\EntityPersona 
     */
    public function getPersona()
    {
        return $this->persona;
    }

    /**
     * Set parte
     *
     * @param FecdasBundle\Entity\EntityParte $parte
     */
    public function setParte(\FecdasBundle\Entity\EntityParte $parte = null)
    {
        $this->parte = $parte;
    }

    /**
     * Get parte
     *
     * @return FecdasBundle\Entity\EntityParte 
     */
    public function getParte()
    {
        return $this->parte;
    }

    /**
     * Set categoria
     *
     * @param FecdasBundle\Entity\EntityCategoria $categoria
     */
    public function setCategoria(\FecdasBundle\Entity\EntityCategoria $categoria)
    {
        $this->categoria = $categoria;
    }

    /**
     * Get categoria
     *
     * @return FecdasBundle\Entity\EntityCategoria 
     */
    public function getCategoria()
    {
        return $this->categoria;
    }

    
    /**
     * Set pesca
     *
     * @param boolean $pesca
     */
    public function setPesca($pesca)
    {
        $this->pesca = $pesca;
    }

    /**
     * Get pesca
     *
     * @return boolean 
     */
    public function getPesca()
    {
        return $this->pesca;
    }

    /**
     * Set escafandrisme
     *
     * @param boolean $escafandrisme
     */
    public function setEscafandrisme($escafandrisme)
    {
        $this->escafandrisme = $escafandrisme;
    }

    /**
     * Get escafandrisme
     *
     * @return boolean 
     */
    public function getEscafandrisme()
    {
        return $this->escafandrisme;
    }

    /**
     * Set natacio
     *
     * @param boolean $natacio
     */
    public function setNatacio($natacio)
    {
        $this->natacio = $natacio;
    }

    /**
     * Get natacio
     *
     * @return boolean 
     */
    public function getNatacio()
    {
        return $this->natacio;
    }

    /**
     * Set orientacio
     *
     * @param boolean $orientacio
     */
    public function setOrientacio($orientacio)
    {
        $this->orientacio = $orientacio;
    }

    /**
     * Get orientacio
     *
     * @return boolean 
     */
    public function getOrientacio()
    {
        return $this->orientacio;
    }

    /**
     * Set biologia
     *
     * @param boolean $biologia
     */
    public function setBiologia($biologia)
    {
        $this->biologia = $biologia;
    }

    /**
     * Get biologia
     *
     * @return boolean 
     */
    public function getBiologia()
    {
        return $this->biologia;
    }

    /**
     * Set fotocine
     *
     * @param boolean $fotocine
     */
    public function setFotocine($fotocine)
    {
        $this->fotocine = $fotocine;
    }

    /**
     * Get fotocine
     *
     * @return boolean 
     */
    public function getFotocine()
    {
        return $this->fotocine;
    }

    /**
     * Set hockey
     *
     * @param boolean $hockey
     */
    public function setHockey($hockey)
    {
        $this->hockey = $hockey;
    }

    /**
     * Get hockey
     *
     * @return boolean 
     */
    public function getHockey()
    {
        return $this->hockey;
    }

    /**
     * Set fotosubapnea
     *
     * @param boolean $fotosubapnea
     */
    public function setFotosubapnea($fotosubapnea)
    {
        $this->fotosubapnea = $fotosubapnea;
    }

    /**
     * Get fotosubapnea
     *
     * @return boolean 
     */
    public function getFotosubapnea()
    {
        return $this->fotosubapnea;
    }

    /**
     * Set videosub
     *
     * @param boolean $videosub
     */
    public function setVideosub($videosub)
    {
        $this->videosub = $videosub;
    }

    /**
     * Get videosub
     *
     * @return boolean 
     */
    public function getVideosub()
    {
        return $this->videosub;
    }

    /**
     * Set apnea
     *
     * @param boolean $apnea
     */
    public function setApnea($apnea)
    {
        $this->apnea = $apnea;
    }

    /**
     * Get apnea
     *
     * @return boolean 
     */
    public function getApnea()
    {
        return $this->apnea;
    }

    /**
     * Set rugbi
     *
     * @param boolean $rugbi
     */
    public function setRugbi($rugbi)
    {
    	$this->rugbi = $rugbi;
    }
    
    /**
     * Get rugbi
     *
     * @return boolean
     */
    public function getRugbi()
    {
    	return $this->rugbi;
    }
    
    /**
     * Set besportiu
     *
     * @param boolean $besportiu
     */
    public function setBesportiu($besportiu)
    {
    	$this->besportiu = $besportiu;
    }
    
    /**
     * Get besportiu
     *
     * @return boolean
     */
    public function getBesportiu()
    {
    	return $this->besportiu;
    }
        
    /**
     * Set bampolles
     *
     * @param boolean $bampolles
     */
    public function setBampolles($bampolles)
    {
    	$this->bampolles = $bampolles;
    }
    
    /**
     * Get bampolles
     *
     * @return boolean
     */
    public function getBampolles()
    {
    	return $this->bampolles;
    }
    
    /**
     * Set nocmas
     *
     * @param boolean $nocmas
     */
    public function setNocmas($nocmas)
    {
        $this->nocmas = $nocmas;
    }

    /**
     * Get nocmas
     *
     * @return boolean 
     */
    public function getNocmas()
    {
        return $this->nocmas;
    }

    /**
     * Set fusell
     *
     * @param boolean $fusell
     */
    public function setFusell($fusell)
    {
        $this->fusell = $fusell;
    }

    /**
     * Get fusell
     *
     * @return boolean
     */
    public function getFusell()
    {
    	return $this->fusell;
    }
    
    /**
     * Set enviarllicencia
     *
     * @param boolean $enviarllicencia
     */
    public function setEnviarllicencia($enviarllicencia)
    {
    	$this->enviarllicencia = $enviarllicencia;
    }
    
    /**
     * Get enviarllicencia
     *
     * @return boolean
     */
    public function getEnviarllicencia()
    {
    	return $this->enviarllicencia;
    }
    
    /**
     * Set datacaducitat
     *
     * @param date $datacaducitat
     */
    public function setDatacaducitat($datacaducitat)
    {
    	$this->datacaducitat = $datacaducitat;
    }
    
    /**
     * Get datacaducitat
     *
     * @return date
     */
    public function getDatacaducitat()
    {
    	return $this->datacaducitat;
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

    /**
     * Set idpartedetall_access
     *
     * @param integer $idpartedetallAccess
     */
    public function setIdpartedetallAccess($idpartedetallAccess)
    {
        $this->idpartedetall_access = $idpartedetallAccess;
    }

    /**
     * Get idpartedetall_access
     *
     * @return integer 
     */
    public function getIdpartedetallAccess()
    {
        return $this->idpartedetall_access;
    }
    
    /**
     * Get activitats per lletres
     */
    public function getActivitats() 
    {
    	$activitats = "";
    	if ($this->apnea == true) $activitats .= "A ";
    	else $activitats .= "- ";
    	if ($this->escafandrisme == true) $activitats .= " E ";
    	else $activitats .= " - ";
    	if ($this->fotosubapnea == true) $activitats .= " FA ";
    	else $activitats .= " - ";
    	if ($this->fotocine == true) $activitats .= " FS ";
    	else $activitats .= " - ";
    	if ($this->hockey == true) $activitats .= " HS ";
    	else $activitats .= " - ";
    	if ($this->natacio == true) $activitats .= " N ";
    	else $activitats .= " - ";
    	if ($this->biologia == true) $activitats .= " B ";
    	else $activitats .= " - ";
    	if ($this->orientacio == true) $activitats .= " O ";
    	else $activitats .= " - ";
    	if ($this->pesca == true) $activitats .= " P ";
    	else $activitats .= " - ";
    	if ($this->videosub == true) $activitats .= " VS ";
    	else $activitats .= " - ";
    	if ($this->rugbi == true) $activitats .= " RG ";
    	else $activitats .= " - ";
    	if ($this->besportiu == true) $activitats .= " BP ";
    	else $activitats .= " - ";
    	if ($this->bampolles == true) $activitats .= " BA ";
    	else $activitats .= " - ";
    	/*if ($this->nocmas == true) $activitats .= "M";
    	else $activitats .= "-";
    	if ($this->fusell == true) $activitats .= "F";
    	else $activitats .= "-";*/
    	 
    	return $activitats;
    }
    
    /**
     * Get llicencia vàlida
     */
    public function isValida()
    {
    	return ($this->getDatabaixa() == null && $this->getParte() != null && $this->getParte()->getPendent() == false);
    }

	/**
     * Get llicencia baixa
     */
    public function isBaixa()
    {
    	return ($this->getDatabaixa() != null || $this->getParte() == null || ($this->getParte() != null && $this->getParte()->getDatabaixa() != null));
    }
	
    /**
     * Get llicencia vigent
     */
    public function isVigent()
    {
    	return ($this->isValida() == true && $this->getParte()->isVigent() == true);
    }
    
}
