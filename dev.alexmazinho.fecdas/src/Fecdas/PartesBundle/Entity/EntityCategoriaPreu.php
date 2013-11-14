<?php
namespace Fecdas\PartesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_categoriespreus")
 * 
 * @author alex
 *
 */
class EntityCategoriaPreu {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $id;	 
	
	/* codisortida + anypreu ==> unique */
	/**
	 * @ORM\ManyToOne(targetEntity="EntityCategoria", inversedBy="preus")
	 * @ORM\JoinColumn(name="categoria", referencedColumnName="codisortida")
	 */
	protected $categoria;  // FK m_categories
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $anypreu;
	
	/**
	 * @ORM\Column(type="decimal", precision=6, scale=2)
	 */
	protected $preu;

	
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
     * Set anypreu
     *
     * @param integer $anypreu
     */
    public function setAnypreu($anypreu)
    {
    	$this->anypreu = $anypreu;
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
  
    /**
     * Set categoria
     *
     * @param Fecdas\PartesBundle\Entity\EntityCategoria $categoria
     */
    public function setCategoria(\Fecdas\PartesBundle\Entity\EntityCategoria $categoria = null)
    {
    	$this->categoria = $categoria;
    }
    
    /**
     * Get categoria
     *
     * @return Fecdas\PartesBundle\Entity\EntityCategoria
     */
    public function getCategoria()
    {
    	return $this->categoria;
    }
}