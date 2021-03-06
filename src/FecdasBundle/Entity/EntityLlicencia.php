<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use FecdasBundle\Controller\BaseController;

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
	protected $imprimir;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $impresa;
	
	/**
	 * @ORM\Column(type="date", nullable=true)
	 */
	protected $dataimpressio; 
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $mailenviat;
	
	/**
	 * @ORM\Column(type="date", nullable=true)
	 */
	protected $datamail; 
	
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
		$this->imprimir = false;
		$this->impresa = false;
		$this->mailenviat = false;
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
	
	public static function csvHeader() {
	    return array( '#', 'Club', 'Des de', 'Fins', 'Categoria', 'Descripció' );
	}
	
	/**
	 * Get persona info. as csv data
	 *
	 * @return string
	 */
	public function csvRow($i = 0)
	{
	    $descripcio = $this->categoria->getDescripcio();
	    $parte = $this->getParte();
	    if ($parte != null && $parte->comandaUsuari() && !$parte->comandaPagada()) {
	        $descripcio = '(Pendent fins confirmar el pagament)';
	    }
	    
	    return array( 
	        $i, 
	        $this->parte->getClub()->getNom(), 
	        $this->parte->getDataalta()->format("d/m/Y"),
	        $this->datacaducitat->format("d/m/Y"),
	        $this->categoria->getCategoria(),
	        $descripcio,
	    );
	}
	
	public function esNova()
	{
		return ($this->id == 0);
	}
	
	/**
     * Get llicencia vàlida
     */
    public function isValida()
    {
    	return ($this->getDatabaixa() == null && $this->getParte() != null && !$this->getParte()->getPendent());
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
    	return ($this->isValida() && $this->getParte()->isVigent());
    }
	
	/**
	 * Get llicència baixa
	 *
	 * @return boolean
	 */
	public function esBaixa()
	{
		return $this->databaixa != null;
	}

	/**
	 * Get llicència tècnic?
	 *
	 * @return boolean
	 */
	public function esTecnic()
	{
		return $this->categoria->getSimbol() == BaseController::SIMBOL_TECNIC;
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
     * @param EntityPersona $persona
     */
    public function setPersona(EntityPersona $persona)
    {
        $this->persona = $persona;
    }

    /**
     * Get persona
     *
     * @return EntityPersona 
     */
    public function getPersona()
    {
        return $this->persona;
    }

    /**
     * Set parte
     *
     * @param EntityParte $parte
     */
    public function setParte(EntityParte $parte = null)
    {
        $this->parte = $parte;
    }

    /**
     * Get parte
     *
     * @return EntityParte 
     */
    public function getParte()
    {
        return $this->parte;
    }

    /**
     * Set categoria
     *
     * @param EntityCategoria $categoria
     */
    public function setCategoria(EntityCategoria $categoria)
    {
        $this->categoria = $categoria;
    }

    /**
     * Get categoria
     *
     * @return EntityCategoria 
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
     * Set imprimir
     *
     * @param boolean $imprimir
     */
    public function setImprimir($imprimir)
    {
        $this->imprimir = $imprimir;
    }
    
    /**
     * Get imprimir
     *
     * @return boolean
     */
    public function getImprimir()
    {
        return $this->imprimir;
    }
    
    /**
     * Set datacaducitat
     *
     * @param \DateTime $datacaducitat
     */
    public function setDatacaducitat($datacaducitat)
    {
    	$this->datacaducitat = $datacaducitat;
    }
    
    /**
     * Get datacaducitat
     *
     * @return \DateTime
     */
    public function getDatacaducitat()
    {
    	return $this->datacaducitat;
    }
    
	/**
     * Set impresa
     *
     * @param boolean $impresa
     */
    public function setImpresa($impresa)
    {
    	$this->impresa = $impresa;
    }
    
    /**
     * Get impresa
     *
     * @return boolean
     */
    public function getImpresa()
    {
    	return $this->impresa;
    }

	/**
     * Set dataimpressio
     *
     * @param \DateTime $dataimpressio
     */
    public function setDataimpressio($dataimpressio)
    {
    	$this->dataimpressio = $dataimpressio;
    }
    
    /**
     * Get dataimpressio
     *
     * @return \DateTime
     */
    public function getDataimpressio()
    {
    	return $this->dataimpressio;
    }
	
	/**
     * Set mailenviat
     *
     * @param boolean $mailenviat
     */
    public function setMailenviat($mailenviat)
    {
    	$this->mailenviat = $mailenviat;
    }
    
    /**
     * Get mailenviat
     *
     * @return boolean
     */
    public function getMailenviat()
    {
    	return $this->mailenviat;
    }
	
	/**
     * Set datamail
     *
     * @param \DateTime $datamail
     */
    public function setDatamail($datamail)
    {
    	$this->datamail = $datamail;
    }
    
    /**
     * Get datamail
     *
     * @return \DateTime
     */
    public function getDatamail()
    {
    	return $this->datamail;
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
    
    public static function getLlicenciesSortedBy(&$llicencies, $sort = 'id', $direction = 'asc')
    {
        usort($llicencies, function($a, $b) use ($sort, $direction) {
            if ($a === $b) {
                return 0;
            }
            $true = $direction == 'asc'? 1:-1;
            $false = $true * -1;
            $result = 0;
            switch ($sort) {
                case 'dataalta':
                    $result = ($a->getParte()->getDataalta() > $b->getParte()->getDataalta())? $true:$false;
                    break;
                case 'datacaducitat':
                    $result = ($a->getParte()->getDatacaducitat() > $b->getParte()->getDatacaducitat())? $true:$false;
                    break;
                case 'club':
                    $result = ($a->getParte()->getClub()->getNom() > $b->getParte()->getClub()->getNom())? $true:$false;
                    break;
                case 'categoria':
                    $result = ($a->getCategoria()->getCategoria() > $b->getCategoria()->getCategoria())? $true:$false;
                    break;
                case 'categoria.descripcio':
                    $result = ($a->getCategoria()->getDescripcio() > $b->getCategoria()->getDescripcio())? $true:$false;
                    break;
                default:
                    $result = ($a->getId() > $b->getId())? $true:$false;
                    break;
            }
            
            return $result;
        });
    }
}
