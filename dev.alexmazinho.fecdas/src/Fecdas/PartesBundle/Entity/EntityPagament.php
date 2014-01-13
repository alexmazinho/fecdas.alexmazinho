<?php
namespace Fecdas\PartesBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_pagaments")
 * 
 * @author alex
 *
 */
class EntityPagament {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datapagament;

	/**
	 * @ORM\Column(type="string", length=15)
	 */
	protected $estat;

	/**
	 * @ORM\Column(type="decimal", precision=8, scale=2)
	 */
	protected $import;

	/**
	 * @ORM\Column(type="string", length=15, nullable=true)
	 */
	protected $dades;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentari;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $dataanulacio;

	public function __construct() {
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getEstat();
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
	 * @return datetime
	 */
	public function getDatapagament() {
		return $this->datapagament;
	}

	/**
	 * @param datetime $datapagament
	 */
	public function setDatapagament($datapagament) {
		$this->datapagament = $datapagament;
	}

	/**
	 * @return string
	 */
	public function getEstat() {
		return $this->estat;
	}

	/**
	 * @param string $estat
	 */
	public function setEstat($estat) {
		$this->estat = $estat;
	}

	/**
	 * @return decimal
	 */
	public function getImport() {
		return $this->import;
	}

	/**
	 * @param decimal $import
	 */
	public function setImport($import) {
		$this->import = $import;
	}

	/**
	 * @return string
	 */
	public function getDades() {
		return $this->dades;
	}

	/**
	 * @param string $dades
	 */
	public function setDades($dades) {
		$this->dades = $dades;
	}

	/**
	 * @return text
	 */
	public function getComentari() {
		return $this->comentari;
	}

	/**
	 * @param text $comentari
	 */
	public function setComentari($comentari) {
		$this->comentari = $comentari;
	}

	/**
	 * @return datetime
	 */
	public function getDataanulacio() {
		return $this->dataanulacio;
	}

	/**
	 * @param datetime $dataanulacio
	 */
	public function setDataanulacio($dataanulacio) {
		$this->dataanulacio = $dataanulacio;
	}

}
