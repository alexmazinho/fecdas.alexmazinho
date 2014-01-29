<?php
namespace Fecdas\PartesBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_factures")
 * 
 * @author alex
 *
 */
class EntityFactura {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datafactura;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $num;

	/**
	 * @ORM\Column(type="decimal", precision=8, scale=2)
	 */
	protected $import;

	/**
	 * @ORM\Column(type="text")
	 */
	protected $concepte;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $dataanulacio;

	public function __construct($currentDate) {
		$this->setDataentrada($currentDate);
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
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
	public function getDatafacturacio() {
		return $this->datafacturacio;
	}

	/**
	 * @param datetime $datafacturacio
	 */
	public function setDatafacturacio($datafacturacio) {
		$this->datafacturacio = $datafacturacio;
	}

	/**
	 * @return integer
	 */
	public function getNum() {
		return $this->num;
	}

	/**
	 * @param integer $num
	 */
	public function setNum($num) {
		$this->num = $num;
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
	 * @return text
	 */
	public function getConcepte() {
		return $this->concepte;
	}

	/**
	 * @param text $concepte
	 */
	public function setConcepte($concepte) {
		$this->concepte = $concepte;
	}

	/**
	 * @return datetime
	 */
	public function getDataentrada() {
		return $this->dataentrada;
	}
	
	/**
	 * @param datetime $dataentrada
	 */
	public function setDataentrada($dataentrada) {
		$this->dataentrada = $dataentrada;
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
