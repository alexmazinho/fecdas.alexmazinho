<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_preus")
 * 
 * @author alex
 *
 */
class EntityPreu {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $id;	 
	
	/* codi + anypreu ==> unique */
	/**
	 * @ORM\ManyToOne(targetEntity="EntityProducte", inversedBy="preus")
	 * @ORM\JoinColumn(name="producte", referencedColumnName="id")
	 */
	protected $producte;  // FK m_productes
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $anypreu;
	
	/**
	 * @ORM\Column(type="decimal", precision=6, scale=2)
	 */
	protected $preu;

	/**
	 * @ORM\Column(type="decimal", precision=6, scale=2, nullable=true)
	 */
	protected $iva;
	
	/**
	 * Set id
	 *
	 * @param integer $id
	 * @return EntityPreu
	 */
	public function setId($id)
	{
		$this->id = $id;
		
		return $this;
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
     * Set anypreu
     *
     * @param integer $anypreu
     * @return EntityPreu
     */
    public function setAnypreu($anypreu)
    {
    	$this->anypreu = $anypreu;
    	
    	return $this;
    }
    
    /**
     * Get anypreu
     *
     * @return integer
     */
    public function getAnypreu()
    {
    	return $this->anypreu;
    }
    
    
    /**
     * Set preu
     *
     * @param decimal $preu
     * @return EntityPreu
     */
    public function setPreu($preu)
    {
        $this->preu = $preu;
        
        return $this;
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
  
    /**
     * Set producte
     *
     * @param FecdasBundle\Entity\EntityProducte $producte
     * @return EntityPreu
     */
    public function setProducte(\FecdasBundle\Entity\EntityProducte $producte = null)
    {
    	$this->producte = $producte;
    	
    	return $this;
    }
    
    /**
     * Get producte
     *
     * @return FecdasBundle\Entity\EntityProducte
     */
    public function getProducte()
    {
    	return $this->producte;
    }
    
    /**
     * Set iva
     *
     * @param string $iva
     * @return EntityPreu
     */
    public function setIva($iva)
    {
    	$this->iva = $iva;
    
    	return $this;
    }
    
    /**
     * Get iva
     *
     * @return string
     */
    public function getIva()
    {
    	return $this->iva;
    }
}