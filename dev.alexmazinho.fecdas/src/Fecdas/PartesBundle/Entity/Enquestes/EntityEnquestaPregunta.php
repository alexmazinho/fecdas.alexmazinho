<?php
namespace Fecdas\PartesBundle\Entity\Enquestes;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="e_enquestes_preguntes")
 * 
 * @author alex
 *
 */
class EntityEnquestaPregunta {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	

	/**
     * @ORM\ManyToOne(targetEntity="EntityEnquesta", inversedBy="preguntes")
     * @ORM\JoinColumn(name="enquesta", referencedColumnName="id")
     */
	protected $enquesta;
	
	/**
     * @ORM\ManyToOne(targetEntity="EntityPregunta")
     * @ORM\JoinColumn(name="pregunta", referencedColumnName="id")
     */
	protected $pregunta;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $ordre;

	public function __construct($enquesta, $pregunta, $ordre) {
		$this->enquesta = $enquesta;
		$this->pregunta = $pregunta;
		$this->ordre = $ordre;
	}

	public function __toString() {
		$tostring = ((is_null($this->id))?'null':$this->id) . '<br/>';
		$tostring .= ((is_null($this->ordre))?'null':$this->ordre) . '<br/>';
		$tostring .= ((is_null($this->enquesta))?'Enquesta nula':'Enquesta no nula') . '<br/>';
		$tostring .= $this->pregunta->__toString();
		
		return $tostring;
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
     * Set ordre
     *
     * @param integer $ordre
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;
    }

    /**
     * Get ordre
     *
     * @return integer 
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * Set enquesta
     *
     * @param Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta $enquesta
     */
    public function setEnquesta(\Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta $enquesta = null)
    {
        $this->enquesta = $enquesta;
    }

    /**
     * Get enquesta
     *
     * @return Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta 
     */
    public function getEnquesta()
    {
        return $this->enquesta;
    }

    /**
     * Set pregunta
     *
     * @param Fecdas\PartesBundle\Entity\Enquestes\EntityPregunta $pregunta
     */
    public function setPregunta(\Fecdas\PartesBundle\Entity\Enquestes\EntityPregunta $pregunta)
    {
        $this->pregunta = $pregunta;
    }

    /**
     * Get pregunta
     *
     * @return Fecdas\PartesBundle\Entity\Enquestes\EntityPregunta 
     */
    public function getPregunta()
    {
        return $this->pregunta;
    }
}