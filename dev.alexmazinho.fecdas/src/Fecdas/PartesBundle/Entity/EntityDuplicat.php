<?php
namespace Fecdas\PartesBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_duplicats")
 * 
 * @author alex
 *
 */
class EntityDuplicat {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub", inversedBy="duplicats") 
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
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $pagament;

	/**
	 * @ORM\Column(type="integer", nullable=true)
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
	 * @return Fecdas\PartesBundle\Entity\EntityClub
	 */
	public function getClub() {
		return $this->club;
	}

	/**
	 * @param Fecdas\PartesBundle\Entity\EntityClub $club
	 */
	public function setClub(\Fecdas\PartesBundle\Entity\EntityClub $club) {
		$this->club = $club;
	}

	/**
	 * @return Fecdas\PartesBundle\Entity\EntityPersona
	 */
	public function getPersona() {
		return $this->persona;
	}

	/**
	 * @param Fecdas\PartesBundle\Entity\EntityPersona $persona
	 */
	public function setPersona(\Fecdas\PartesBundle\Entity\EntityPersona $persona) {
		$this->persona = $persona;
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
	 * @return Fecdas\PartesBundle\Entity\EntityTitol
	 */
	public function getTitol() {
		return $this->titol;
	}

	/**
	 * @param Fecdas\PartesBundle\Entity\EntityTitol $titol
	 */
	public function setTitol(\Fecdas\PartesBundle\Entity\EntityTitol $titol) {
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
	 * @return integer
	 */
	public function getPagament() {
		return $this->pagament;
	}

	/**
	 * @param integer $pagament
	 */
	public function setPagament($pagament) {
		$this->pagament = $pagament;
	}

	/**
	 * @return integer
	 */
	public function getFactura() {
		return $this->factura;
	}

	/**
	 * @param integer $factura
	 */
	public function setFactura($factura) {
		$this->factura = $factura;
	}

	/**
	 * Set foto
	 *
	 * @param Fecdas\PartesBundle\Entity\EntityImatge $imatge
	 * @return EntityImatge
	 */
	public function setFoto(\Fecdas\PartesBundle\Entity\EntityImatge $foto = null)
	{
		$this->foto = $foto;
	}
	
	/**
	 * Get foto
	 *
	 * @return Fecdas\PartesBundle\Entity\EntityImatge
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
		 
		if ($this->pagament != null) return "Petició pagada";
		
		if ($this->factura != null) return "Petició facturada";
	
		if ($this->observacions != null) return $this->observacions;
		
		return $textInfo;
	}
	
}
