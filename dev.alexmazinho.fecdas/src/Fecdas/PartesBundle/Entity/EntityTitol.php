<?php
namespace Fecdas\PartesBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_titols")
 * 
 * @author alex
 *
 */
class EntityTitol {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityCarnet")
	 * @ORM\JoinColumn(name="carnet", referencedColumnName="id")
	 */
	protected $carnet; // FK taula m_titols

	/**
	 * @ORM\Column(type="string", length=10)
	 */
	protected $codi;

	/**
	 * @ORM\Column(type="string", length=50)
	 */
	protected $titol;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $actiu;

	public function __construct() {
		$this->actiu = true;
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getCodi() . " " . $this->getTitol();
	}

	/**
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param integer $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return Fecdas\PartesBundle\Entity\EntityCarnet
	 */
	public function getCarnet() {
		return $this->carnet;
	}

	/**
	 * @param Fecdas\PartesBundle\Entity\EntityCarnet $carnet
	 */
	public function setCarnet(\Fecdas\PartesBundle\Entity\EntityCarnet $carnet) {
		$this->carnet = $carnet;
	}

	/**
	 * @return string
	 */
	public function getCodi() {
		return $this->codi;
	}

	/**
	 * @param string $codi
	 */
	public function setCodi($codi) {
		$this->codi = $codi;
	}

	/**
	 * @return string
	 */
	public function getTitol() {
		return $this->titol;
	}

	/**
	 * @param string $titol
	 */
	public function setTitol($titol) {
		$this->titol = $titol;
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

	/**
	 * Get informació títol en llistes desplegables
	 *
	 * @return string
	 */
	public function getLlistaText()
	{
		return $this->getCodi() . " - " . $this->getTitol();
	}
	
}
