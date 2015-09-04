<?php
namespace FecdasBundle\Entity;
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
	 * @ORM\OneToOne(targetEntity="EntityComanda", mappedBy="factura")
	 **/
	protected $comanda;	// FK taula m_comandes
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datapagament;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datamodificacio;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $dataanulacio;
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $idanulacio;
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $comandaoriginal; // Sense relació, pot haver-hi moltes

	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->id = 0;
		$this->dataentrada = new \DateTime();
	
		// Hack per permetre múltiples constructors
		$a = func_get_args();
		$i = func_num_args();
	
		if ($i > 1 && method_exists($this,$f='__constructParams')) {
			call_user_func_array(array($this,$f),$a);
		}
	}
	
	
	public function __constructParams($datafactura, $num, $import = 0, $concepte = '') {
	
		$this->datafactura = $datafactura;
		$this->num = $num;
		$this->import = $import;
		$this->concepte = $concepte;
	}
	

	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}
	
	/**
	 * Factura format amb any  XXXXX/20XX
	 *
	 * @return string
	 */
	public function getNumFactura() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->datafactura->format("Y");
	}
	
	/**
	 * Factura format curt amb any  XXXXX/XX
	 *
	 * @return string
	 */
	public function getNumFacturaCurt() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->datafactura->format("y");
	}
	
	public function esBaixa()
	{
		return $this->dataanulacio != null;
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
	public function getDatafactura() {
		return $this->datafactura;
	}

	/**
	 * @param datetime $datafactura
	 */
	public function setDatafactura($datafactura) {
		$this->datafactura = $datafactura;
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
	 * @return comanda
	 */
	public function getComanda() {
		return $this->comanda;
	}
	
	/**
	 * @param \FecdasBundle\Entity\EntityComanda $comanda
	 */
	public function setComanda(\FecdasBundle\Entity\EntityComanda $comanda = null) {
		$this->comanda = $comanda;
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
	 * Set datamodificacio
	 *
	 * @param \DateTime $datamodificacio
	 */
	public function setDatamodificacio($datamodificacio)
	{
		$this->datamodificacio = $datamodificacio;
	}
	
	/**
	 * Get datamodificacio
	 *
	 * @return \DateTime
	 */
	public function getDatamodificacio()
	{
		return $this->datamodificacio;
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
	 * @return integer
	 */
	public function getIdanulacio() {
		return $this->idanulacio;
	}
	
	/**
	 * @param integer $id
	 */
	public function setIdanulacio($idanulacio) {
		$this->id = $idanulacio;
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
