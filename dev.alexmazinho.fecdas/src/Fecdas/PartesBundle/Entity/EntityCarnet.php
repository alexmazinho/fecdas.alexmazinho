<?php
namespace Fecdas\PartesBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_carnets")
 * 
 * @author alex
 *
 */
class EntityCarnet {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=40)
	 */
	protected $tipus;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $codisortida;

	/**
	 * @ORM\Column(type="decimal", precision=6, scale=2)
	 */
	protected $preu;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $foto;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityTitol", mappedBy="carnet")
	 */
	protected $titols;

	public function __construct() {
		$this->foto = true;
		$this->titols = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/**
	 * @return boolean
	 */
	public function esLlicencia() {
		return $this->codisortida == 7090000;
	}
	
	public function __toString() {
	 return $this->getId()."-".$this->getTipus();
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
	 * @return string
	 */
	public function getTipus() {
		return $this->tipus;
	}

	/**
	 * @param string $tipus
	 */
	public function setTipus($tipus) {
		$this->tipus = $tipus;
	}

	/**
	 * @return integer
	 */
	public function getCodisortida() {
		return $this->codisortida;
	}

	/**
	 * @param integer $codisortida
	 */
	public function setCodisortida($codisortida) {
		$this->codisortida = $codisortida;
	}

	/**
	 * @return decimal
	 */
	public function getPreu() {
		return $this->preu;
	}

	/**
	 * @param decimal $preu
	 */
	public function setPreu($preu) {
		$this->preu = $preu;
	}

	/**
	 * @return boolean
	 */
	public function getFoto() {
		return $this->foto;
	}

	/**
	 * @param boolean $foto
	 */
	public function setFoto($foto) {
		$this->foto = $foto;
	}
	
	/**
	 * Get titols
	 *
	 * @return Doctrine\Common\Collections\Collection
	 */
	public function getTitols()
	{
		return $this->titols;
	}

	/**
	 * Add titol
	 *
	 * @param Fecdas\PartesBundle\Entity\EntityLlicencia $titols
	 */
	public function addEntityTitol(\Fecdas\PartesBundle\Entity\EntityTitol $titol)
	{
		$this->titols->add($titol);
	}
	
	/**
	 * Get informació carnet en llistes desplegables
	 *
	 * @return string
	 */
	public function getLlistaText()
	{
		$str = "(" . number_format($this->getPreu(), 2, ',', '') . " €)";
		return $this->getTipus() . $str; 
	}
}
