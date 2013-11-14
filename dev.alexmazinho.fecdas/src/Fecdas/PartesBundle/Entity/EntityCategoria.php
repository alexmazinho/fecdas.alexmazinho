<?php
namespace Fecdas\PartesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_categories")
 * 
 * @author alex
 *
 */
class EntityCategoria {
	
	/**	
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $codisortida;
	
	/**
	 * @ORM\Column(type="string", length=1, nullable=true)
	 */
	protected $simbol;
	
	/**
	 * @ORM\Column(type="string", length=15, nullable=true)
	 */
	protected $categoria;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	protected $descripcio;

	/**
	 * @ORM\OneToMany(targetEntity="EntityCategoriaPreu", mappedBy="categoria")
	 */
	protected $preus;	// Owning side of the relationship
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityParteType", inversedBy="categories")
	 * @ORM\JoinColumn(name="tipusparte", referencedColumnName="id")
	 */
	protected $tipusparte;

	public function __construct() {
		$this->preus = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->codisortida;
	}
	
    /**
     * Set codisortida
     *
     * @param integer $codisortida
     */
    public function setCodisortida($codisortida)
    {
        $this->codisortida = $codisortida;
    }

    /**
     * Get codisortida
     *
     * @return integer 
     */
    public function getCodisortida()
    {
        return $this->codisortida;
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
     * @param Fecdas\PartesBundle\Entity\EntityParteType $tipusparte
     */
    public function setTipusparte(\Fecdas\PartesBundle\Entity\EntityParteType $tipusparte)
    {
        $this->tipusparte = $tipusparte;
    }

    /**
     * Get tipusparte
     *
     * @return Fecdas\PartesBundle\Entity\EntityParteType 
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
    public function setDescripcio($descripcio)
    {
        $this->descripcio = $descripcio;
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
     * Get preus
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
    	
    	foreach($this->preus as $c=>$preu) {
    		if ($preu->getAnypreu() == $any) return $preu->getpreu();
    	}
    	/* Never shhould happen */
    	return 0;
    }
}