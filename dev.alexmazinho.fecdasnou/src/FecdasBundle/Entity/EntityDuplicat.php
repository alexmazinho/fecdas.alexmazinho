<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_duplicats")
 * 
 * @author alex
 *
 */
class EntityDuplicat {
	const PREFIX_ALBARA_DUPLICATS = 'D';
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub") 
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club; // FK taula m_clubs

	/**
	 * @ORM\ManyToOne(targetEntity="EntityPersona")
	 * @ORM\JoinColumn(name="persona", referencedColumnName="id")
	 */
	protected $persona;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityCarnet")
	 * @ORM\JoinColumn(name="carnet", referencedColumnName="id")
	 */
	protected $carnet;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityTitol")
	 * @ORM\JoinColumn(name="titol", referencedColumnName="id")
	 */
	protected $titol;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datapeticio;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $observacions;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $dataimpressio;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;

	/**
	 * @ORM\OneToOne(targetEntity="EntityPagament")
	 * @ORM\JoinColumn(name="pagament", referencedColumnName="id")
	 */
	protected $pagament;

	/**
	 * @ORM\OneToOne(targetEntity="EntityFactura")
	 * @ORM\JoinColumn(name="factura", referencedColumnName="id")
	 */
	protected $factura;

	/**
	 * @ORM\OneToOne(targetEntity="EntityImatge")
	 * @ORM\JoinColumn(name="foto", referencedColumnName="id")
	 */
	protected $foto;
	
	public function __construct() {
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getClub();
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
	 * @return FecdasBundle\Entity\EntityClub
	 */
	public function getClub() {
		return $this->club;
	}

	/**
	 * @param FecdasBundle\Entity\EntityClub $club
	 */
	public function setClub(\FecdasBundle\Entity\EntityClub $club) {
		$this->club = $club;
	}

	/**
	 * @return FecdasBundle\Entity\EntityPersona
	 */
	public function getPersona() {
		return $this->persona;
	}

	/**
	 * @param FecdasBundle\Entity\EntityPersona $persona
	 */
	public function setPersona(\FecdasBundle\Entity\EntityPersona $persona) {
		$this->persona = $persona;
	}

	/**
	 * @return FecdasBundle\Entity\EntityCarnet
	 */
	public function getCarnet() {
		return $this->carnet;
	}

	/**
	 * @param FecdasBundle\Entity\EntityCarnet $carnet
	 */
	public function setCarnet(\FecdasBundle\Entity\EntityCarnet $carnet) {
		$this->carnet = $carnet;
	}

	/**
	 * @return FecdasBundle\Entity\EntityTitol
	 */
	public function getTitol() {
		return $this->titol;
	}

	/**
	 * @param FecdasBundle\Entity\EntityTitol $titol
	 */
	public function setTitol(\FecdasBundle\Entity\EntityTitol $titol) {
		$this->titol = $titol;
	}

	/**
	 * @return datetime
	 */
	public function getDatapeticio() {
		return $this->datapeticio;
	}

	/**
	 * @param datetime $datapeticio
	 */
	public function setDatapeticio($datapeticio) {
		$this->datapeticio = $datapeticio;
	}

	/**
	 * @return text
	 */
	public function getObservacions() {
		return $this->observacions;
	}

	/**
	 * @param text $observacions
	 */
	public function setObservacions($observacions) {
		$this->observacions = $observacions;
	}
	
	/**
	 * @return datetime
	 */
	public function getDataimpressio() {
		return $this->dataimpressio;
	}

	/**
	 * @param datetime $dataimpressio
	 */
	public function setDataimpressio($dataimpressio) {
		$this->dataimpressio = $dataimpressio;
	}

	/**
	 * @return datetime
	 */
	public function getDatabaixa() {
		return $this->databaixa;
	}

	/**
	 * @param datetime $databaixa
	 */
	public function setDatabaixa($databaixa) {
		$this->databaixa = $databaixa;
	}

	/**
	 * Get pagament
	 * 
	 * @return FecdasBundle\Entity\EntityPagament
	 */
	public function getPagament() {
		return $this->pagament;
	}

	/**
	 * @param FecdasBundle\Entity\EntityPagament $pagament
	 * @return EntityPagament
	 */
	public function setPagament(\FecdasBundle\Entity\EntityPagament $pagament) {
		$this->pagament = $pagament;
	}

	/**
	 * Get factura
	 * 
	 * @return FecdasBundle\Entity\EntityFactura
	 */
	public function getFactura() {
		return $this->factura;
	}

	/**
	 * @param FecdasBundle\Entity\EntityFactura $factura
	 * @return EntityFactura
	 */
	public function setFactura(\FecdasBundle\Entity\EntityFactura $factura = null) {
		$this->factura = $factura;
	}

	/**
	 * Set foto
	 *
	 * @param FecdasBundle\Entity\EntityImatge $imatge
	 * @return EntityImatge
	 */
	public function setFoto(\FecdasBundle\Entity\EntityImatge $foto = null)
	{
		$this->foto = $foto;
	}
	
	/**
	 * Get foto
	 *
	 * @return FecdasBundle\Entity\EntityImatge
	 */
	public function getFoto()
	{
		return $this->foto;
	}
	
	
	/**
	 * Array amb el detall de la factura de la petició de duplicat
	 *
	 * @return string
	 */
	public function getDetallFactura() {
		$detallfactura = array();
		$iva = 0;
		$codi = $this->getCarnet()->getCodisortida();
		$preu = $this->getCarnet()->getPreu();
		$detallfactura[$codi]['codi'] = $codi;
		$detallfactura[$codi]['desc'] = $this->getTextCarnet(false).". <b>".$this->getPersona()->getCognomsNom()."</b>";
		$detallfactura[$codi]['quant'] = 1;
		$detallfactura[$codi]['preuunitat'] = $preu;
		$detallfactura[$codi]['preusiva'] = $preu;
		$detallfactura[$codi]['iva'] = $preu*$iva/100;
		$detallfactura[$codi]['totaldetall'] = $detallfactura[$codi]['preusiva'] + $detallfactura[$codi]['iva'];
		
		return $detallfactura;
	}
	
	/**
	 * @return string
	 */
	public function getTextCarnet($preu = true) {
		$strCarnet = $this->carnet->getTipus();
		if ($this->titol != null) $strCarnet .= ". " . $this->getTitol()->getTitol();
		if ($preu == true) $strCarnet .= " (" . number_format($this->carnet->getPreu(), 2, ',', '') . " €)";
		
		return $strCarnet;
	}
	
	/**
	 * Missatge llista de duplicats
	 *
	 * @return string
	 */
	public function getInfoLlistat() {
		// Missatge que es mostra a la llista de duplicats
		$textInfo = "";
		 
		if ($this->databaixa != null) return "Petició anul·lada " . $this->databaixa->format("d/m/Y");
		
		if ($this->pagament != null) $textInfo .= "Petició pagada.";
		
		if ($this->factura != null) $textInfo .= "Factura " . $this->getFactura()->getNumFactura(). " - " .$this->factura->getDatafactura()->format("d/m/Y")."." ;
	
		if ($this->observacions != null) $textInfo .= $this->observacions;
		
		return $textInfo;
	}
	
	/**
	 * Get num albarà PREFIX + id
	 *
	 * @return integer
	 */
	public function getNumAlbara()
	{
		return self::PREFIX_ALBARA_DUPLICATS.str_pad($this->getId(),6,'0',STR_PAD_LEFT);
	}
}
