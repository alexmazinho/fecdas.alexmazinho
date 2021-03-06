<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_requeriments")
 * 
 * @author alex
 *
 */
class EntityRequeriment {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityTitol", inversedBy="requeriments")
	 * @ORM\JoinColumn(name="titol", referencedColumnName="id")
	 */
	protected $titol; // FK taula m_titols
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityRequerimentTipus")
	 * @ORM\JoinColumn(name="requeriment", referencedColumnName="id")
	 */
	protected $requeriment; // FK taula m_tipusrequeriments

	/**
	 * @ORM\Column(type="string", length=100)
	 */
	protected $valor;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $actiu;


	public function __construct() {
		$this->actiu = true;
	}

	public function __toString() {
		return $this->getId();
	}

	/**
	 * @return string
	 */
	public function getText() {
		return $this->requeriment!=null?trim($this->requeriment->getDescripcio()):'';
	}

	/**
	 * @return boolean
	 */
	public function actiu() {
		return $this->actiu;
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
	 * @return EntityTitol
	 */
	public function getTitol() {
		return $this->titol;
	}

	/**
	 * @param EntityTitol $titol
	 */
	public function setTitol(EntityTitol $titol) {
		$this->titol = $titol;
	}

	/**
	 * @return EntityRequeriment
	 */
	public function getRequeriment() {
		return $this->requeriment;
	}

	/**
	 * @param EntityRequeriment $requeriment
	 */
	public function setRequeriment(EntityRequeriment $requeriment) {
		$this->requeriment = $requeriment;
	}

	/**
	 * @return string
	 */
	public function getValor() {
		return $this->valor;
	}

	/**
	 * @return boolean
	 */
	public function getActiu() {
		return $this->actiu;
	}

	/**
	 * @param boolean $actiu
	 */
	public function setActiu($actiu) {
		$this->actiu = $actiu;
	}

}
