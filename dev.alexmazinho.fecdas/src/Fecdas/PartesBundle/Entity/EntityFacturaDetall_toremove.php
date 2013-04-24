<?php
namespace Fecdas\PartesBundle\Entity;

/**
 * @author alex
 *
 */
class EntityFacturaDetall {
	
	protected $codisortida;
	
	protected $descripcio;

	protected $quantitat;
	
	protected $preuunitat;
	
	protected $preusiva;
	
	protected $iva;
	
	protected $totaldetall;

	public function __construct($codisortida, $descripcio) {
		$this->codisortida = $codisortida;
		$this->descripcio = $descripcio;
		
		//$this->llicencies = new \Doctrine\Common\Collections\ArrayCollection();
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
    	return $this->simbol . " - " . $this->categoria;
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
     * Set preu
     *
     * @param decimal $preu
     */
    public function setPreu($preu)
    {
        $this->preu = $preu;
    }

    /**
     * Get preu
     *
     * @return decimal 
     */
    public function getPreu()
    {
        return $this->preu;
    }
}