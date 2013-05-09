<?php
namespace Fecdas\PartesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_tipusparte")
 * 
 * @author alex
 *
 */
class EntityParteType {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $id;	// 1 ... 11
	
	/**
	 * @ORM\Column(type="string", length=50)
	 */
	protected $codi;
	
	/**
	 * @ORM\Column(type="string", length=50)
	 */
	protected $descripcio;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $es365;

	/**
	 * @ORM\Column(type="string", length=5)
	 */
	protected $inici;
	
	/**
	 * @ORM\Column(type="string", length=5)
	 */
	protected $final;
	
	/**
	 * @ORM\Column(type="decimal", precision=4, scale=2)
	 */
	protected $iva;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityCategoria", mappedBy="tipusparte")
	 */
	protected $categories;
	
	public function __construct() {
		$this->categories = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->descripcio;
	}

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
     * Set es365
     *
     * @param boolean $es365
     */
    public function setEs365($es365)
    {
    	$this->es365 = $es365;
    }
    
    /**
     * Get es365
     *
     * @return boolean
     */
    public function getEs365()
    {
    	return $this->es365;
    }
    
    /**
     * Set iva
     *
     * @param decimal $iva
     */
    public function setIva($iva)
    {
    	$this->iva = $iva;
    }
    
    /**
     * Get iva
     *
     * @return decimal
     */
    public function getIva()
    {
    	return $this->iva;
    }
    
    /**
     * Set inici
     *
     * @param string $inici
     */
    public function setInici($inici)
    {
    	$this->inici = $inici;
    }
    
    /**
     * Get inici
     *
     * @return string
     */
    public function getInici()
    {
    	return $this->inici;
    }
    
    /**
     * Set final
     *
     * @param string $final
     */
    public function setFinal($final)
    {
    	$this->final = $final;
    }
    
    /**
     * Get final
     *
     * @return string
     */
    public function getFinal()
    {
    	return $this->final;
    }
    
    /**
     * Add categories
     *
     * @param Fecdas\PartesBundle\Entity\EntityCategoria $categories
     */
    public function addEntityCategoria(\Fecdas\PartesBundle\Entity\EntityCategoria $categories)
    {
        $this->categories[] = $categories;
    }

    /**
     * Get categories
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getCategories()
    {
        return $this->categories;
    }
}