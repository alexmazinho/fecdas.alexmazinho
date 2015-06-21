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
class EntityDuplicat extends EntityComanda {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub") 
	 * @ORM\JoinColumn(name="clubdel", referencedColumnName="codi")
	 */
	protected $clubdel; // FK taula m_clubs

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
	protected $databaixadel;

	/**
	 * @ORM\OneToOne(targetEntity="EntityImatge")
	 * @ORM\JoinColumn(name="foto", referencedColumnName="id")
	 */
	protected $foto;
	
	
	/*
	 * @ORM\OneToOne(targetEntity="EntityComanda", inversedBy="parte")
	 * @ORM\JoinColumn(name="comanda", referencedColumnName="id")
	 *
	protected $comanda;*/
	
	/*
	 * @ORM\OneToOne(targetEntity="EntityComanda", mappedBy="duplicat")
	 * @ORM\JoinColumn(name="comanda", referencedColumnName="id")
	 *
	protected $comanda;*/
	
	
	public function __construct() {
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getClub();
	}

	public function esBaixa()
	{
		return $this->databaixadel != null;
	}
	
	
	/**
	 * Get prefix albarà duplicats. Sobreescriptura
	 *
	 * @return string
	 */
	public function getPrefixAlbara()
	{
		return BaseController::PREFIX_ALBARA_DUPLICATS;
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
	public function getClubdel() {
		return $this->clubdel;
	}

	/**
	 * @param FecdasBundle\Entity\EntityClub $clubdel
	 */
	public function setClubdel(\FecdasBundle\Entity\EntityClub $clubdel) {
		$this->clubdel = $clubdel;
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
	public function getDatabaixadel() {
		return $this->databaixadel;
	}

	/**
	 * @param datetime $databaixadel
	 */
	public function setDatabaixadel($databaixadel) {
		$this->databaixadel = $databaixadel;
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
		 
		if ($this->databaixadel != null) return "Petició anul·lada " . $this->databaixadel->format("d/m/Y");
		
		if ($this->getRebut() != null) $textInfo .= "Petició pagada.";
			
		if ($this->getFactura() != null) $textInfo .= "Factura " . $this->comanda->getFactura()->getNumFactura(). " - " .$this->comanda->getFactura()->getDatafactura()->format("d/m/Y")."." ;
			
		if ($this->observacions != null) $textInfo .= $this->observacions;
		
		return $textInfo;
	}
	
	
}
