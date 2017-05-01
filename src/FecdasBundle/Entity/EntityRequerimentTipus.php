<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_tipusrequeriments")
 * 
 * @author alex
 *
 */
class EntityRequerimentTipus {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=10)
	 */
	protected $context;		// alumne, horari, immersions, docent, ratio

	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	protected $categoria;		// grup dins un context, variable
	
	/**
	 * @ORM\Column(type="string", length=255)
	 */
	protected $descripcio;


	public function __toString() {
		return $this->getId() . "-" . $this->getCodi() . " " . $this->getTitol();
	}

	/**
     * És context alumnes?
     *
     * @return boolean 
     */
    public function aplicaAlumnes()
    {
        return $this->context == BaseController::CONTEXT_REQUERIMENT_ALUMNES;
    }

	/**
     * És context docents?
     *
     * @return boolean 
     */
    public function aplicaDocents()
    {
        return $this->context == BaseController::CONTEXT_REQUERIMENT_DOCENTS;
    }

	/**
	 * @param integer $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param string $context
	 */
	public function setContext($context) {
		$this->context = $context;
	}
		
	/**
	 * @return string
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * @param string $categoria
	 */
	public function setCategoria($categoria) {
		$this->categoria = $categoria;
	}
		
	/**
	 * @return string
	 */
	public function getCategoria() {
		return $this->categoria;
	}

	/**
	 * @param string $descripcio
	 */
	public function setDescripcio($descripcio) {
		$this->descripcio = $descripcio;
	}
		
	/**
	 * @return string
	 */
	public function getDescripcio() {
		return $this->descripcio;
	}
}
