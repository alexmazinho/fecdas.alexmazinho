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
	 * @ORM\ManyToOne(targetEntity="EntityClub")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;	// FK taula m_clubs
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityComptabilitat")
	 * @ORM\JoinColumn(name="comptabilitat", referencedColumnName="id")
	 */
	protected $comptabilitat;	// FK taula m_comptabilitat => Enviament programa compta
	
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
	 * @ORM\Column(type="datetime")
	 */
	protected $datamodificacio;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $dataanulacio;

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
		$this->datamodificacio = new \DateTime();
		
		// Hack per permetre múltiples constructors
		$a = func_get_args();
		$i = func_num_args();
	
		if ($i > 1 && method_exists($this,$f='__constructParams')) {
			call_user_func_array(array($this,$f),$a);
		}
	}
	
	
	public function __constructParams($datapagament, $tipusPagament, $num, $comanda = null, $club = null, $import = 0) {
	
		$this->datapagament = $datapagament;
		$this->tipuspagament = $tipusPagament;
		$this->num = $num;
		$this->comanda = $comanda;
		if ($comanda == null) { // Ingrés no  associat a cap comanda
			$this->club = $club;
			$this->import = $import;
			$this->comentari = "Ingrés a compte del club ".($this->club!=null?$this->club->getNom():'');
		} else {  // Pagament d'una comanda
			$this->club = $comanda->getClub();
			$this->import = $this->comanda->getTotalDetalls();
			$this->comentari = "Rebut comanda ".$this->comanda->getNumComanda()." ".$this->comanda->getTipusComanda();

			$this->comanda->setRebut($this); 
		}
	}
	
	
	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}

		
	public function esBaixa()
	{
		return $this->dataanulacio != null;
	}
	
	public function getEstat()
	{
		return $this->dataanulacio != null?'anul·lat':'';
	}
	
	/**
	 * Get concepte rebut/ingrés
	 *
	 * @return string
	 */
	public function getConcepteRebutLlarg()
	{
		if ($this->comanda == null) return $this->getNumRebut()." INGRES ".$this->getClub()->getNom();  // Ingrés a compte
			
		return "REBUT ".$this->getNumRebut()." - ".$this->comanda->getConcepteComanda(); 	
	}
	
	/**
	 * Get concepte rebut/ingrés
	 *
	 * @return string
	 */
	public function getConcepteRebutCurt()
	{
		if ($this->comanda == null) return "REBUT: ".$this->getNumRebut();  // Ingrés a compte
			
		if ($this->comanda->getFactura() == null) return "COMANDA: ".$this->comanda->getNumComanda();
		
		return "FACTURA: ".$this->comanda->getFactura()->getNumFactura();
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
	 * Rebut format curt amb any  XXXXX/XX
	 *
	 * @return string
	 */
	public function getNumRebutCurt() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->datapagament->format("y");
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
	 * Set club
	 *
	 * @param \FecdasBundle\Entity\EntityClub $club
	 * @return EntityRebut
	 */
	public function setClub(\FecdasBundle\Entity\EntityClub $club = null)
	{
		$this->club = $club;
	
		return $this;
	}
	
	/**
	 * Get club
	 *
	 * @return \FecdasBundle\Entity\EntityClub
	 */
	public function getClub()
	{
		return $this->club;
	}
	
	
	/**
	 * Set comptabilitat
	 *
	 * @param \FecdasBundle\Entity\EntityComptabilitat $comptabilitat
	 * @return EntityRebut
	 */
	public function setComptabilitat(\FecdasBundle\Entity\EntityComptabilitat $comptabilitat = null)
	{
		$this->comptabilitat = $comptabilitat;
	
		return $this;
	}
	
	/**
	 * Get comptabilitat
	 *
	 * @return \FecdasBundle\Entity\EntityComptabilitat
	 */
	public function getComptabilitat()
	{
		return $this->comptabilitat;
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
