<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;


/**
 * @ORM\Entity
 * @ORM\Table(name="m_productes")
 * 
 * @author alex
 *
 */
class EntityProducte {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $codi;

	/**
	 * @ORM\Column(type="string", length=3)
	 */
	protected $abreviatura;
	
	/**
	 * @ORM\Column(type="text")
	 */
	protected $descripcio;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $tipus;  // 1 - llicencies, 2 - duplicats, 3 - kits, 4 - merchandising , 5 - altres
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityPreu", mappedBy="producte")
	 */
	protected $preus;
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $minim;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $stockable;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $limitnotifica;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $transport;
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $pes;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $visible; // visible pels clubs?
	
	/**
	 * @ORM\OneToOne(targetEntity="EntityCategoria", mappedBy="producte")
	 **/
	protected $categoria; // categoria del producte si és llicència pot ser null

	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $departament;
	
	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $subdepartament;
			
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datamodificacio;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;
	
	public function __construct() {
	    $this->tipus = BaseController::TIPUS_PRODUCTE_KITS;
		$this->stockable = true;
		$this->transport = true;
		$this->dataentrada = new \DateTime();
        $this->departament = BaseController::INDEX_DPT_INGRESOS_KITS;
        $this->subdepartament = BaseController::INDEX_SUBDPT_INGRESOS_KITS_MERCHANDISING;
		$this->preus = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->getId()."".$this->getCodi();
	}
	
	public static function csvHeader() {
	    
	    return array (
	        'num',
	        'codi',
	        'abrev.',
	        'descripció',
	        'tipus',
	        'any preu',
	        'preu',
	        'iva',
	        'transport',
	        'stock',
	        'mínim',
	        'avís',
	        'pes',
	        'visible',
	        'departament',
	        'subdepartament',
	        'baixa'
	    );
	}
	
	/**
	 * Get curs info. as csv data
	 *
	 * @return string
	 */
	public function csvRow($i = 0)
	{
	    return array (
	        $i,
	        $this->getCodi(),
	        $this->getAbreviatura(),
	        $this->getDescripcio(),
	        $this->getTipusText(),
	        $this->getCurrentAny(),
	        number_format($this->getCurrentPreu(), 2, ',', '.'),
	        number_format($this->getCurrentIva(), 2, ',', '.'),
	        $this->getTransport()?"Si":"No",
	        $this->getStockable()?"Si":"No",
	        $this->getMinim(),
	        $this->getLimitnotifica(),
	        $this->getPes(),
	        $this->getVisible()?"Si":"No",
	        $this->getDepartament(),
	        $this->getSubdepartament(),
	        $this->esBaixa()?$this->getDatabaixa()->format("d/m/Y"):""
	    );
	}
	
	
	
	public function esBaixa()
	{
		return $this->databaixa != null;
	}
    
    public function esNou()
    {
        return $this->id == 0;
    }
    
    /**
     * Get descripcio
     *
     * @return string
     */
    public function getAbreviaturaDescripcio()
    {
        return $this->abreviatura." ".$this->descripcio;
    }
    
    /**
     * Es el tipus stockable
     *
     * @return boolean
     */
    public function esStockable()
    {
        return  self::esTipusStockable($this->tipus);
    }
    
    public static function esTipusStockable($tipus)
    {
        return  $tipus == BaseController::TIPUS_PRODUCTE_KITS ||
                $tipus == BaseController::TIPUS_PRODUCTE_MERCHA ||
                $tipus == BaseController::TIPUS_PRODUCTE_MATERIAL;
    }
    
    
	public function getEstat()
	{
		return $this->databaixa != null?'baixa':'';
	}
	
	/**
	 * Get preus persist
	 *
	 * @return string
	 */
	public function getPreusConsolidated()
	{
		$preusconsolidated = array(); 
		foreach($this->preus as $preu) {
			if ($preu->getId() > 0) $preusconsolidated[] = $preu;
		}
		 
		return $preusconsolidated;
	}
	
	/**
     * és Kit?
     *
     * @return boolean 
     */
    public function esKit()
    {
        return $this->tipus == BaseController::TIPUS_PRODUCTE_KITS;
    }
	
    /**
     * Get
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getPreus()
    {
        return $this->preus;
    }
    
    /**
     * Get preu (objecte) any
     *
     * @return string
     */
    public function getPreu($any)
    {
        foreach($this->preus as $preu) {
            if ($preu->getAnypreu() == $any) return $preu;
        }
        
        return null;
    }
    
    /**
     * Get preu (objecte) aplicable a any
     * preu any actual si existeix o
     * darrer preu anys anteriors
     *
     * @return string
     */
    public function getPreuAplicable($any)
    {
        // Ordenar per any
        if (count($this->preus) == 0) return null;
        
        $preus = $this->preus->toArray();
        
        usort($preus, function($a, $b) {
            if ($a === $b) {
                return 0;
            }
            // Descendent primer darrer any
            return ($a->getAnypreu() > $b->getAnypreu())? -1:1;
        });

        if (date('d') >= BaseController::INICI_TRAMITACIO_ANUAL_DIA &&
            date('m') >= BaseController::INICI_TRAMITACIO_ANUAL_MES) $any++;
            
        foreach($preus as $preu) {
            if ($preu->getAnypreu() <= $any) return $preu;
        }
            
        return null; // No trobat cap aplicable
    }
    
    /**
     * Get preu any actual
     *
     * @return string
     */
    public function getCurrentPreu()
    {
        return $this->getPreuAny(date('Y'));
    }
    
    /**
     * Get any preu any actual
     *
     * @return string
     */
    public function getCurrentAny()
    {
        $preu = $this->getPreuAplicable(date('Y'));
        //$preu = $this->getPreu($any);
        if ($preu == null) return '--';
        return $preu->getAnypreu();
    }
    
    /**
     * Get preu any
     *
     * @return string
     */
    public function getPreuAny($any)
    {
        $preu = $this->getPreuAplicable($any);
        //$preu = $this->getPreu($any);
        if ($preu == null) return 0;
        return $preu->getPreu();
    }
    
    
    /**
     * Get iva any actual
     *
     * @return string
     */
    public function getCurrentIva()
    {
        return $this->getIvaAny(Date('Y'));
    }
    
    /**
     * Get iva any
     *
     * @return string
     */
    public function getIvaAny($any)
    {
        $preu = $this->getPreuAplicable($any);
        //$preu = $this->getPreu($any);
        if ($preu == null || $preu->getIva() == null) return 0;
        return $preu->getIva();
    }
    
    /**
     * Is disponible
     *
     * @return boolean
     */
    public function disponible()
    {
        return true;
    }
    
    /**
     * Add preu
     *
     * @param \FecdasBundle\Entity\EntityPreu $preu
     * @return EntityProducte
     */
    public function addPreus(\FecdasBundle\Entity\EntityPreu $preu)
    {
        $preu->setProducte($this);
        $this->preus->add($preu);
        //$this->preus[] = $preu;
        
        return $this;
    }
    
    /**
     * Remove preu
     *
     * @param \FecdasBundle\Entity\EntityPreu $preu
     */
    public function removePreus(\FecdasBundle\Entity\EntityPreu $preu)
    {
        $this->preus->removeElement($preu);
        $preu->setProducte(null);
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
     * Set codi
     *
     * @param integer $codi
     * @return EntityProducte
     */
    public function setCodi($codi)
    {
        $this->codi = $codi;

        return $this;
    }

    /**
     * Get codi
     *
     * @return integer 
     */
    public function getCodi()
    {
        return $this->codi;
    }

    /**
     * Set abreviatura
     *
     * @param string $abreviatura
     * @return EntityProducte
     */
    public function setAbreviatura($abreviatura)
    {
        $this->abreviatura = $abreviatura;

        return $this;
    }

    /**
     * Get abreviatura
     *
     * @return string 
     */
    public function getAbreviatura()
    {
        return $this->abreviatura;
    }

    /**
     * Set descripcio
     *
     * @param string $descripcio
     * @return EntityProducte
     */
    public function setDescripcio($descripcio)
    {
        $this->descripcio = $descripcio;

        return $this;
    }

    /**
     * Get descripcio
     *
     * @return string 
     */
    public function getDescripcio()
    {
        return $this->descripcio;
    }

    /**
     * Set tipus
     *
     * @param integer $tipus
     * @return EntityProducte
     */
    public function setTipus($tipus)
    {
        $this->tipus = $tipus;

        return $this;
    }

    /**
     * Get tipus
     *
     * @return integer 
     */
    public function getTipus()
    {
        return $this->tipus;
    }
    
    /**
     * Get tipus text
     *
     * @return integer
     */
    public function getTipusText()
    {
    	return BaseController::getTipusProducte($this->tipus);
    }

    /**
     * Set minim
     *
     * @param integer $minim
     * @return EntityProducte
     */
    public function setMinim($minim)
    {
        $this->minim = $minim;

        return $this;
    }

    /**
     * Get minim
     *
     * @return integer 
     */
    public function getMinim()
    {
        return $this->minim;
    }

    /**
     * Set stockable
     *
     * @param boolean $stockable
     * @return EntityProducte
     */
    public function setStockable($stockable)
    {
        $this->stockable = $stockable;

        return $this;
    }

    /**
     * Get stockable
     *
     * @return boolean 
     */
    public function getStockable()
    {
        return $this->stockable;
    }

    /**
     * Set limitnotifica
     *
     * @param integer $limitnotifica
     * @return EntityProducte
     */
    public function setLimitnotifica($limitnotifica)
    {
        $this->limitnotifica = $limitnotifica;

        return $this;
    }

    /**
     * Get limitnotifica
     *
     * @return integer 
     */
    public function getLimitnotifica()
    {
        return $this->limitnotifica;
    }

    /**
     * Set transport
     *
     * @param boolean $transport
     * @return EntityProducte
     */
    public function setTransport($transport)
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * Get transport
     *
     * @return boolean 
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Set pes
     *
     * @param integer $pes
     * @return EntityProducte
     */
    public function setPes($pes)
    {
        $this->pes = $pes;

        return $this;
    }

    /**
     * Get pes
     *
     * @return integer 
     */
    public function getPes()
    {
        return $this->pes;
    }

	/**
     * Set visible
     *
     * @param boolean $visible
     */
    public function setVisible($visible)
    {
    	$this->visible = $visible;
    }
    
    /**
     * Get visible
     *
     * @return boolean
     */
    public function getVisible()
    {
    	return $this->visible;
    }

	/**
	 * Set categoria
	 *
	 * @param \FecdasBundle\Entity\EntityCategoria $categoria
	 * @return EntityCategoria
	 */
	public function setCategoria(\FecdasBundle\Entity\EntityCategoria $categoria = null)
	{
		$this->categoria = $categoria;
	
		return $this;
	}
	
	/**
	 * Get categoria
	 *
	 * @return \FecdasBundle\Entity\EntityCategoria
	 */
	public function getCategoria()
	{
		return $this->categoria;
	}


    /**
     * Set departament
     *
     * @param integer $departament
     * @return EntityProducte
     */
    public function setDepartament($departament)
    {
        $this->departament = $departament;

        return $this;
    }

    /**
     * Get departament
     *
     * @return integer 
     */
    public function getDepartament()
    {
        return $this->departament;
    }

    /**
     * Set subdepartament
     *
     * @param integer $subdepartament
     * @return EntityProducte
     */
    public function setSubdepartament($subdepartament)
    {
        $this->subdepartament = $subdepartament;

        return $this;
    }

    /**
     * Get subdepartament
     *
     * @return integer 
     */
    public function getSubdepartament()
    {
        return $this->subdepartament;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return EntityProducte
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;

        return $this;
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
     * @return EntityProducte
     */
    public function setDatamodificacio($datamodificacio)
    {
        $this->datamodificacio = $datamodificacio;

        return $this;
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
     * @return EntityProducte
     */
    public function setDatabaixa($databaixa)
    {
        $this->databaixa = $databaixa;

        return $this;
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
