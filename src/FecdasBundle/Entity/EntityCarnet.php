<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
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
	 * @ORM\OneToOne(targetEntity="EntityProducte")
	 * @ORM\JoinColumn(name="producte", referencedColumnName="id")
	 **/
	protected $producte;
	
	/*
	 * @ORM\Column(type="string", length=40)
	 */
	/*protected $tipus; <== Passa a ser la descripció del producte */ 

	/*
	 * @ORM\Column(type="integer")
	 */
	/*protected $codisortida;*/

	/*
	 * @ORM\Column(type="decimal", precision=6, scale=2)
	 */
	/*protected $preu;*/

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
		return $this->getCodisortida() == 7090000;
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
	 * Set producte
	 *
	 * @param \FecdasBundle\Entity\EntityProducte $producte
	 * @return EntityCarnet
	 */
	public function setProducte(\FecdasBundle\Entity\EntityProducte $producte = null)
	{
		$this->producte = $producte;
	
		return $this;
	}
	
	/**
	 * Get producte
	 *
	 * @return \FecdasBundle\Entity\EntityProducte
	 */
	public function getProducte()
	{
		return $this->producte;
	}
	
	
	/**
	 * @return string
	 */
	public function getTipus() {
		//return $this->tipus;
		
		if ($this->producte == null) return '';
		return $this->producte->getDescripcio();
	}

	/**
	 * @param string $tipus
	 */
	/*public function setTipus($tipus) {
		$this->tipus = $tipus;
	}*/

	/**
	 * @return integer
	 */
	public function getCodisortida() {
		//return $this->codisortida;
		if ($this->producte == null) return 0;
		return $this->producte->getCodi();
	}

	/**
	 * @param integer $codisortida
	 */
	/*public function setCodisortida($codisortida) {
		$this->codisortida = $codisortida;
	}*/

	/**
	 * @return decimal
	 */
	public function getPreu() {
		//return $this->preu;
		
		if ($this->producte == null) return 0;
		return $this->producte->getPreuAny(Date('Y'));
	}

	/**
	 * @param decimal $preu
	 */
	/*public function setPreu($preu) {
		$this->preu = $preu;
	}*/

	/**
	 * @return boolean
	 */
	public function getFoto() {
		return $this->foto;
	}

	/**
	 * @param boolean $foto
	 * @return EntityCarnet
	 */
	public function setFoto($foto) {
		$this->foto = $foto;
		
		return $this;
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
	 * @param FecdasBundle\Entity\EntityLlicencia $titols
	 */
	public function addEntityTitol(\FecdasBundle\Entity\EntityTitol $titol)
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
