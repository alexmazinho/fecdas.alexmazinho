<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

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
	 * @ORM\OneToOne(targetEntity="EntityArxiu")
	 * @ORM\JoinColumn(name="foto", referencedColumnName="id")
	 */
	protected $foto;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $dataimpressio;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $finalitzat;
	
	public function __construct() {
		$this->datapeticio = new \DateTime();
		$this->finalitzat = false;
		
		parent::__construct();
		
		$a = func_get_args();
		$i = func_num_args();
		
		if ($i > 1 && method_exists($this,$f='__constructParams')) {
			call_user_func_array(array($this,$f),$a);
		}
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getClub()->getNom();
	}

	public function esDuplicat()
	{
		return true;
	}
	
	/**
	 * Reescriptura
	 */
	protected function updateClubSaldoTipusComanda($import) {
		$this->club->setTotalduplicats($this->club->getTotalduplicats() + $import);
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
	 * Get dades TPV. Sobreescriptura
	 */
	public function getOrigenPagament()
	{
		return BaseController::PAGAMENT_DUPLICAT;	
	}			
	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getDescripcioPagament()
	{
		return 'Pagament a FECDAS, petició de duplicat del club ' . $this->club->getCodi() . 
				' per a '. $this->getPersona()->getNomCognoms() .' en data ' . 
				$this->getDatapeticio()->format('d/m/Y');	
	}			

	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getBackURLPagament()
	{
		return 'FecdasBundle_duplicats'; 	
	}			

	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getBackTextPagament()
	{
		return 'Petició de duplicats'; 	
	}			

	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getMenuActiuPagament()
	{
		return 'menu-duplicats';	
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
	 * @return EntityPersona
	 */
	public function getPersona() {
		return $this->persona;
	}

	/**
	 * @param EntityPersona $persona
	 */
	public function setPersona(EntityPersona $persona) {
		$this->persona = $persona;
	}

	/**
	 * @return EntityCarnet
	 */
	public function getCarnet() {
		return $this->carnet;
	}

	/**
	 * @param EntityCarnet $carnet
	 */
	public function setCarnet(EntityCarnet $carnet) {
		$this->carnet = $carnet;
	}

	/**
	 * @return EntityTitol
	 */
	public function getTitol() {
		return $this->titol;
	}

	/**
	 * @param EntityTitol $titol
	 */
	public function setTitol(EntityTitol $titol) {
		$this->titol = $titol;
	}

	/**
	 * @return \DateTime
	 */
	public function getDatapeticio() {
		return $this->datapeticio;
	}

	/**
	 * @param \DateTime $datapeticio
	 */
	public function setDatapeticio($datapeticio) {
		$this->datapeticio = $datapeticio;
	}

	/**
	 * @return string
	 */
	public function getObservacions() {
		return $this->observacions;
	}

	/**
	 * @param string 
	 */
	public function setObservacions($observacions) {
		$this->observacions = $observacions;
	}
	
	/**
	 * Set foto
	 *
	 * @param EntityArxiu $imatge
	 * @return EntityArxiu
	 */
	public function setFoto(EntityArxiu $foto = null)
	{
	    $this->foto = $foto;
	}
	
	/**
	 * Get foto
	 *
	 * @return EntityArxiu
	 */
	public function getFoto()
	{
	    return $this->foto;
	}
	
	
	/**
	 * @return \DateTime
	 */
	public function getDataimpressio() {
		return $this->dataimpressio;
	}

	/**
	 * @param \DateTime $dataimpressio
	 */
	public function setDataimpressio($dataimpressio) {
		$this->dataimpressio = $dataimpressio;
	}

	/**
	 * Set finalitzat
	 *
	 * @param boolean $finalitzat
	 */
	public function setFinalitzat($finalitzat)
	{
	    $this->finalitzat = $finalitzat;
	}
	
	/**
	 * Get finalitzat
	 *
	 * @return boolean
	 */
	public function getFinalitzat()
	{
	    return $this->finalitzat;
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
	public function getInfoLlistat( $br = PHP_EOL, $llista = false ) {
		// Missatge que es mostra a la llista de duplicats
		$textInfo = parent::getInfoLlistat( $br );
		 
		if ($this->esBaixa()) return $br.'Petició anul·lada ' . $this->getDatabaixa()->format("d/m/Y");
		
		if ($this->comandaPagada() == true) $textInfo .= $br.'Petició pagada.';
			
		if ($this->observacions != null) $textInfo .= $this->observacions;
		
		return $textInfo;
	}
	
	public function getComentariDefault()
    {
    	$text = $this->getTextCarnet(false)." ".$this->getPersona()->getCognomsNom();
    	return $text;
    }
}
