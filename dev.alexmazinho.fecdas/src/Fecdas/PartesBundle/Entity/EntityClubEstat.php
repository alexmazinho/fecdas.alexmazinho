<?php
namespace Fecdas\PartesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_clubestats")
 * 
 * @author alex
 *
 */
class EntityClubEstat {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="string", length=4)
	 */
	protected $codi;	
	
	/**
	 * @ORM\Column(type="string", length=30)
	 */
	protected $descripcio;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $control;

	public function __toString() {
		return $this->descripcio;
	}

    /**
     * Set codi
     *
     * @param string $codi
     */
    public function setCodi($codi)
    {
        $this->codi = $codi;
    }

    /**
     * Get codi
     *
     * @return string 
     */
    public function getCodi()
    {
        return $this->codi;
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
     * Set control
     *
     * @param boolean $control
     */
    public function setControl($control)
    {
    	$this->control = $control;
    }
    
    /**
     * Get control
     *
     * @return boolean
     */
    public function getControl()
    {
    	return $this->control;
    }
}