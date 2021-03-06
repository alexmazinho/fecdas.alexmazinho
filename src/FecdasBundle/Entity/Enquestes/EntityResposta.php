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
     * @param string $respostatxt
     */
    public function setRespostatxt($respostatxt)
    {
        $this->respostatxt = $respostatxt;
    }

    /**
     * Get respostatxt
     *
     * @return string 
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
     * @param EntityRealitzacio $realitzacio
     */
    public function setRealitzacio(EntityRealitzacio $realitzacio = null)
    {
        $this->realitzacio = $realitzacio;
    }

    /**
     * Get realitzacio
     *
     * @return EntityRealitzacio 
     */
    public function getRealitzacio()
    {
        return $this->realitzacio;
    }

    /**
     * Set pregunta
     *
     * @param EntityPregunta $pregunta
     */
    public function setPregunta(EntityPregunta $pregunta)
    {
        $this->pregunta = $pregunta;
    }

    /**
     * Get pregunta
     *
     * @return EntityPregunta 
     */
    public function getPregunta()
    {
        return $this->pregunta;
    }
}