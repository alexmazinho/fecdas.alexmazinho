<?php
namespace FecdasBundle\Entity\Enquestes;

use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Entity\EntityUser;

/**
 * @ORM\Entity
 * @ORM\Table(name="e_realitzacions")
 * 
 * @author alex
 *
 */
class EntityRealitzacio {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	

	/**
	 * @ORM\ManyToOne(targetEntity="FecdasBundle\Entity\EntityUser")
	 * @ORM\JoinColumn(name="usuari", referencedColumnName="usuari")
	 */
	protected $usuari;	// Mail del club
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityEnquesta")
	 * @ORM\JoinColumn(name="enquesta", referencedColumnName="id")
	 */
	protected $enquesta;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datadarreraccess;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityPregunta")
	 * @ORM\JoinColumn(name="darrerapregunta", referencedColumnName="id")
	 */
	protected $darrerapregunta;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datafinal;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityResposta", mappedBy="realitzacio")
	 */
	protected $respostes;	// Owning side of the relationship
	
	public function __construct($usuari, $enquesta) {
		$this->usuari = $usuari;
		$this->enquesta = $enquesta;
		$this->respostes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set datadarreraccess
     *
     * @param datetime $datadarreraccess
     */
    public function setDatadarreraccess($datadarreraccess)
    {
        $this->datadarreraccess = $datadarreraccess;
    }

    /**
     * Get datadarreraccess
     *
     * @return datetime 
     */
    public function getDatadarreraccess()
    {
        return $this->datadarreraccess;
    }

    /**
     * Set datafinal
     *
     * @param datetime $datafinal
     */
    public function setDatafinal($datafinal)
    {
        $this->datafinal = $datafinal;
    }

    /**
     * Get datafinal
     *
     * @return datetime 
     */
    public function getDatafinal()
    {
        return $this->datafinal;
    }

    /**
     * Set usuari
     *
     * @param FecdasBundle\Entity\EntityUser $usuari
     */
    public function setUsuari(\FecdasBundle\Entity\EntityUser $usuari)
    {
        $this->usuari = $usuari;
    }

    /**
     * Get usuari
     *
     * @return FecdasBundle\Entity\EntityUser 
     */
    public function getUsuari()
    {
        return $this->usuari;
    }

    /**
     * Set enquesta
     *
     * @param FecdasBundle\Entity\Enquestes\EntityEnquesta $enquesta
     */
    public function setEnquesta(\FecdasBundle\Entity\Enquestes\EntityEnquesta $enquesta)
    {
        $this->enquesta = $enquesta;
    }

    /**
     * Get enquesta
     *
     * @return FecdasBundle\Entity\Enquestes\EntityEnquesta 
     */
    public function getEnquesta()
    {
        return $this->enquesta;
    }

    /**
     * Set darrerapregunta
     *
     * @param FecdasBundle\Entity\Enquestes\EntityPregunta $darrerapregunta
     */
    public function setDarrerapregunta(\FecdasBundle\Entity\Enquestes\EntityPregunta $darrerapregunta)
    {
        $this->darrerapregunta = $darrerapregunta;
    }

    /**
     * Get darrerapregunta
     *
     * @return FecdasBundle\Entity\Enquestes\EntityPregunta 
     */
    public function getDarrerapregunta()
    {
        return $this->darrerapregunta;
    }

    /**
     * Add resposta
     *
     * @param FecdasBundle\Entity\Enquestes\EntityResposta $resposta
     */
    public function addEntityResposta(\FecdasBundle\Entity\Enquestes\EntityResposta $resposta)
    {
    	$this->respostes->add($resposta);
    }

    /**
     * Remove resposta
     *
     * @param FecdasBundle\Entity\Enquestes\EntityResposta $resposta
     */
    public function removeEntityResposta(\FecdasBundle\Entity\Enquestes\EntityResposta $resposta)
    {
    	$this->respostes->removeElement($resposta);
    }
    
    
    /**
     * Get resposta per una pregunta concreta o null
     *  
     */
    public function getResposta(\FecdasBundle\Entity\Enquestes\EntityPregunta $pregunta)
    {
    	if ($pregunta == null) return null;
    	foreach($this->respostes as $resposta) {
    		if ($resposta->getPregunta() === $pregunta) return $resposta;
    	}
    	return null;
    }
    
    /**
     * Get respostes
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getRespostes()
    {
        return $this->respostes;
    }
}