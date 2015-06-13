<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_productes", uniqueConstraints={@ORM\UniqueConstraint(name="codi_idx", columns={"codi"})})
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
	 * @ORM\Column(type="string", length=100)
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
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $stock;
	
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
	
	public function __construct() {
		$this->stockable = false;
		$this->preus = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->getId()."-".$this->codi;
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
     * Set stock
     *
     * @param integer $stock
     * @return EntityProducte
     */
    public function setStock($stock)
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * Get stock
     *
     * @return integer 
     */
    public function getStock()
    {
        return $this->stock;
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

    
    /**
     * Get
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getPreus()
    {
    	return $this->preus;
    }
    
    /**
     * Get preu any
     *
     * @return decimal
     */
    public function getPreuAny($any)
    {
    	foreach($this->preus as $preu) {
    		if ($preu->getAnypreu() == $any) return $preu->getpreu();
    	}
    	/* Never shhould happen */
    	return 0;
    }


    /**
     * Add preu
     *
     * @param \FecdasBundle\Entity\EntityPreu $preu
     * @return EntityProducte
     */
    public function addPreus(\FecdasBundle\Entity\EntityPreu $preu)
    {
    	$preu->setComanda($this);
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
    }
}
