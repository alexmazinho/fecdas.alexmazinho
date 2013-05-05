<?php
namespace Fecdas\PartesBundle\Entity\Enquestes;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true) 
 * @ORM\Table(name="e_preguntes")
 * 
 * @author alex
 *
 */
class EntityPregunta {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	

	/**
	 * @ORM\Column(type="string", length=4)
	 */
	protected $tipus;
	/* OPEN: Oberta, BOOL: Si/NO, RANG: Max Rang: 1 ... 5 */
	
	/**
	 * @ORM\Column(type="text")
	 */
	protected $enunciat;

	public function __construct($tipus, $enunciat) {
		$this->tipus = $tipus;
		$this->enunciat = $enunciat;
	}

	public function __toString() {
		$tostring = ((is_null($this->id))?'null':$this->id) . '<br/>';
		$tostring .= ((is_null($this->tipus))?'null':$this->tipus) . '<br/>';
		$tostring .= ((is_null($this->enunciat))?'null':$this->enunciat) . '<br/>';
				
		return $tostring;
	}
	
	/*
	if ($enquesta->getDatainici() == null) echo "nuulll";
	else echo $enquesta->getDatainici()->format('Y-m-d');*/
	
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

    /**
     * Set enunciat
     *
     * @param text $enunciat
     */
    public function setEnunciat($enunciat)
    {
        $this->enunciat = $enunciat;
    }

    /**
     * Get enunciat
     *
     * @return text 
     */
    public function getEnunciat()
    {
        return $this->enunciat;
    }
}