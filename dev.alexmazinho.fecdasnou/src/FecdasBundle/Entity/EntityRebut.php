<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_rebuts")
 * 
 * @author alex
 *
 */
class EntityRebut {

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
	 * @ORM\Column(type="integer")
	 */
	protected $num;
	
	/**
	 * @ORM\Column(type="decimal", precision=8, scale=2)
	 */
	protected $import;

	/**
	 * @ORM\OneToOne(targetEntity="EntityComanda", mappedBy="rebut")
	 **/
	protected $comanda;	// FK taula m_comandes
	
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $tipuspagament;  // Llista
	
	/**
	 * @ORM\Column(type="string", length=15, nullable=true)
	 */
	protected $dadespagament;  // Del TPV per exemple

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentari;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $dataanulacio;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $comandaoriginal; // Sense relació, pot haver-hi moltes
	
	public function __construct() {
		$this->dateentrada = new \DateTime();
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}

	/**
	 * Rebut format amb any  XXXXX/20XX
	 *
	 * @return string
	 */
	public function getNumRebut() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->datapagament->format("Y");
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
	 * @return comanda
	 */
	public function getComanda() {
		return $this->comanda;
	}
	
	/**
	 * @param \FecdasBundle\Entity\EntityComanda $comanda
	 */
	public function setComanda(\FecdasBundle\Entity\EntityComanda $comanda) {
		$this->comanda = $comanda;
	}
	
	/**
	 * @return integer
	 */
	public function getTipuspagament() {
		return $this->tipuspagament;
	}
	
	/**
	 * @param integer $tipuspagament
	 */
	public function setTipuspagament($tipuspagament) {
		$this->tipuspagament = $tipuspagament;
	}
	
	
	/**
	 * @return string
	 */
	public function getDadespagament() {
		return $this->dadespagament;
	}

	/**
	 * @param string $dadespagament
	 */
	public function setDadespagament($dadespagament) {
		$this->dadespagament = $dadespagament;
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

	/**
	 * @return comandaoriginal
	 */
	public function getComandaoriginal() {
		return $this->comandaoriginal;
	}
	
	/**
	 * @param int $comandaoriginal
	 */
	public function setComandaoriginal($comandaoriginal) {
		$this->comandaoriginal = $comandaoriginal;
	}
	
}
