<?php
namespace FecdasBundle\Entity\Enquestes;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="e_respostes")
 * 
 * @author alex
 *
 */
class EntityResposta {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	

	/**
	 * @ORM\ManyToOne(targetEntity="EntityRealitzacio", inversedBy="respostes")
	 * @ORM\JoinColumn(name="realitzacio", referencedColumnName="id")
	 */
	protected $realitzacio;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityPregunta")
	 * @ORM\JoinColumn(name="pregunta", referencedColumnName="id")
	 */
	protected $pregunta;
	
	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $respostatxt;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $respostabool;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $respostarang;
	
	
	public function __construct($realitzacio, $pregunta) {
		$this->realitzacio = $realitzacio;
		$this->pregunta = $pregunta;
		$this->respostabool = true;
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
     * Set respostatxt
     *
     * @param text $respostatxt
     */
    public function setRespostatxt($respostatxt)
    {
        $this->respostatxt = $respostatxt;
    }

    /**
     * Get respostatxt
     *
     * @return text 
     */
    public function getRespostatxt()
    {
        return $this->respostatxt;
    }

    /**
     * Set respostabool
     *
     * @param boolean $respostabool
     */
    public function setRespostabool($respostabool)
    {
        $this->respostabool = $respostabool;
    }

    /**
     * Get respostabool
     *
     * @return boolean 
     */
    public function getRespostabool()
    {
        return $this->respostabool;
    }

    /**
     * Set respostarang
     *
     * @param integer $respostarang
     */
    public function setRespostarang($respostarang)
    {
        $this->respostarang = $respostarang;
    }

    /**
     * Get respostarang
     *
     * @return integer 
     */
    public function getRespostarang()
    {
        return $this->respostarang;
    }

    /**
     * Set realitzacio
     *
     * @param FecdasBundle\Entity\Enquestes\EntityRealitzacio $realitzacio
     */
    public function setRealitzacio(\FecdasBundle\Entity\Enquestes\EntityRealitzacio $realitzacio = null)
    {
        $this->realitzacio = $realitzacio;
    }

    /**
     * Get realitzacio
     *
     * @return FecdasBundle\Entity\Enquestes\EntityRealitzacio 
     */
    public function getRealitzacio()
    {
        return $this->realitzacio;
    }

    /**
     * Set pregunta
     *
     * @param FecdasBundle\Entity\Enquestes\EntityPregunta $pregunta
     */
    public function setPregunta(\FecdasBundle\Entity\Enquestes\EntityPregunta $pregunta)
    {
        $this->pregunta = $pregunta;
    }

    /**
     * Get pregunta
     *
     * @return FecdasBundle\Entity\Enquestes\EntityPregunta 
     */
    public function getPregunta()
    {
        return $this->pregunta;
    }
}