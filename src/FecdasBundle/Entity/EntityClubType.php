<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_tipusclub")
 * 
 * @author alex
 *
 */
class EntityClubType {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $id;	// 0 ... 7
	
	/**
	 * @ORM\Column(type="string", length=50)
	 */
	protected $tipus;

	
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
     * Set tipus
     *
     * @param string $tipus
     */
    public function setTipus($tipus)
    {
        $this->tipus = $tipus;
    }

    /**
     * Get tipus
     *
     * @return string 
     */
    public function getTipus()
    {
        return $this->tipus;
    }
}