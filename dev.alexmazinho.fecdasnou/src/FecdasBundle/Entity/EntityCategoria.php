<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_categories")
 * 
 * @author alex
 *
 */
class EntityCategoria {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	
	/**
	 * @ORM\OneToOne(targetEntity="EntityProducte")
	 * @ORM\JoinColumn(name="producte", referencedColumnName="id")
	 **/
	protected $producte;
	
	/**
	 * @ORM\Column(type="string", length=1, nullable=true)
	 */
	protected $simbol;
	
	/**
	 * @ORM\Column(type="string", length=15, nullable=true)
	 */
	protected $categoria;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityParteType", inversedBy="categories")
	 * @ORM\JoinColumn(name="tipusparte", referencedColumnName="id")
	 */
	protected $tipusparte;

	public function __construct() {
		//$this->preus = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->getId()."-".$this->simbol;
	}
	
	/**
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * @param integer $id
	 */
	public function setId($id) {
		$this->id = $id;
	}
	
	/**
	 * Set producte
	 *
	 * @param \FecdasBundle\Entity\EntityProducte $producte
	 * @return EntityCategoria
	 */
	public function setProducte(\FecdasBundle\Entity\EntityProducte $producte = null)
	{
		$this->producte = $producte;
	
		return $this;
	}
	
	/**
	 * Get producte
	 *
	 * @return \FecdasBundle\Entity\EntityProducte
	 */
	public function getProducte()
	{
		return $this->producte;
	}
	
    /**
     * Set codisortida
     *
     * @param integer $codisortida
     */
    /*public function setCodisortida($codisortida)
    {
        $this->codisortida = $codisortida;
    }*/

    /**
     * Get codisortida
     *
     * @return integer 
     */
    public function getCodisortida()
    {
        /*return $this->codisortida;*/
    	if ($this->producte == null) return 0; 
    	return $this->producte->getCodi();
    }

    /**
     * Set simbol
     *
     * @param string $simbol
     */
    public function setSimbol($simbol)
    {
        $this->simbol = $simbol;
    }

    /**
     * Get simbol
     *
     * @return string 
     */
    public function getSimbol()
    {
        return $this->simbol;
    }

    /**
     * Set categoria
     *
     * @param string $categoria
     */
    public function setCategoria($categoria)
    {
        $this->categoria = $categoria;
    }

    /**
     * Get categoria
     *
     * @return string 
     */
    public function getCategoria()
    {
        return $this->categoria;
    }

    /**
     * Set tipusparte
     *
     * @param FecdasBundle\Entity\EntityParteType $tipusparte
     */
    public function setTipusparte(\FecdasBundle\Entity\EntityParteType $tipusparte)
    {
        $this->tipusparte = $tipusparte;
    }

    /**
     * Get tipusparte
     *
     * @return FecdasBundle\Entity\EntityParteType 
     */
    public function getTipusparte()
    {
        return $this->tipusparte;
    }
    
    /**
     * Get informació club en llistes desplegables
     *
     * @return string
     */
    public function getLlistaText()
    {
    	return $this->getLlistaTextAny(Date('Y'));
    }

    /**
     * Get informació club en llistes desplegables any posterior
     *
     * @return string
     */
    public function getLlistaTextPost()
    {
    	return $this->getLlistaTextAny(Date('Y') + 1);
    }
    
    private function getLlistaTextAny($any)
    {
    	$preu = $this->getPreuAny($any);
    	$factor = ($this->tipusparte->getIva()/100) + 1;
    	return $this->categoria . " " . number_format($preu * $factor, 2, ',', '') . " €";
    }
        
    /**
     * Set descripcio
     *
     * @param string $descripcio
     */
    /*public function setDescripcio($descripcio)
    {
        $this->descripcio = $descripcio;
    }*/

    /**
     * Get descripcio
     *
     * @return string 
     */
    public function getDescripcio()
    {
        //return $this->descripcio;
    	if ($this->producte == null) return '';
    	return $this->producte->getDescripcio();
    }

    /**
     * Get preus
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getPreus()
    {
    	//return $this->preus;
    	
    	if ($this->producte == null) return array();
    	return $this->producte->getPreus();
    }
    
    /**
     * Get preu any
     *
     * @return decimal
     */
    public function getPreuAny($any)
    {
    	/*foreach($this->preus as $c=>$preu) {
    		if ($preu->getAnypreu() == $any) return $preu->getpreu();
    	}
    	// Never shhould happen 
    	return 0;*/
    	if ($this->producte == null) return 0;
    	return $this->producte->getPreuAny($any);
    }
}
