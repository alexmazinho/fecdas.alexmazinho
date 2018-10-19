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
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $import;
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $iva;

	/**
	 * @ORM\Column(type="text")
	 */
	protected $concepte;

	/**
	 * @ORM\Column(type="text")
	 */
	protected $detalls;

	/**
	 * @ORM\OneToOne(targetEntity="EntityComanda", mappedBy="factura")
	 **/
	protected $comanda;	
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityComanda", inversedBy="facturesanulacions" )
	 * @ORM\JoinColumn(name="comandaanulacio", referencedColumnName="id")
	 */
	protected $comandaanulacio; 
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityComptabilitat")
	 * @ORM\JoinColumn(name="comptabilitat", referencedColumnName="id")
	 */
	protected $comptabilitat;	// FK taula m_comptabilitat => Enviament programa compta
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $enviada;
	
	/**
	 * @ORM\Column(type="string")
	 */
	protected $numcompte;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
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
	
		if ($i > 1 && method_exists($this,$f='__constructParams')){
			call_user_func_array(array($this,$f),$a);
		}
	}
	
	public function __constructParams($datafactura, $num, $comanda = null, $import = 0, $iva = 0, $concepte = '', $detalls = array(), $numcompte = '') {

		$this->datafactura = $datafactura;
		$this->num = $num;
		$this->import = $import;
		$this->iva = $iva;
		$this->concepte = $concepte;
		//$this->detalls = json_encode($detalls, JSON_UNESCAPED_UNICODE);
		$this->detalls = json_encode($detalls);
		$this->comanda = $comanda;
		$this->enviada = false;
		$this->numcompte = $numcompte;

		if ($comanda != null) {
			if ($import == 0) $this->import = $comanda->getTotalDetalls();
			if ($iva == 0) $this->iva = $comanda->getTotalIVADetalls();
			
			if (trim($concepte) == '') $this->concepte = $comanda->getConcepteComanda();
			if ($detalls == null || count($detalls) == 0) {
				$detalls = $comanda->getDetallsAcumulats();
				//$this->detalls = json_encode($detalls, JSON_UNESCAPED_UNICODE); // Desar estat detalls a la factura
				$this->detalls = json_encode($detalls); // Desar estat detalls a la factura
			}
			$club = $comanda->getClub();
			
			//if ($datafactura->format('Y') < date('Y')) $this->comanda->updateClubRomanent($this->import);
			if ($club != null && $datafactura->format('Y') < $club->getExercici()) $this->comanda->updateClubRomanent($this->import);
			else $this->comanda->updateClubSaldos($this->import);
		}
	}
	
	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}

	public function esAnulacio()
	{
		return $this->comandaanulacio != null;
	}
	
	/**
     * Get any
     *
     * @return integer
     */
    public function getAny()
    {
    	return $this->datafactura->format('Y');
    }
	
	public function infoToolTip($admin)
	{
		$toolTip = 'Factura '.($this->esAnulacio()?'anul·lació':'');
		if ($admin == true && $this->comptabilitat != null) $toolTip .= '. Comptabilitat '.$this->comptabilitat->getDataenviament()->format('d/m/Y'); 
		return $toolTip;
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
	 * @return EntityComanda
	 */
	public function getComandaFactura() {
		if ($this->comandaanulacio == null && $this->comanda == null) return null;
		
		if ($this->esAnulacio() == true) return $this->comandaanulacio;	
		
		return $this->comanda;
	}
	
	/**
	 * Factura format curt amb any  XXXXX/XX
	 *
	 * @return string
	 */
	public function getNumFacturaCurt() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->datafactura->format("y");
	}
	
	/**
	 * @return string extra. Federats factura
	 */
	public function getConcepteExtra($max = 0) {
		$strExtra = '';
		try {
			$detallsArray = json_decode($this->detalls, true);
			
			foreach ($detallsArray as $lineafactura) {
				if (isset($lineafactura['extra']) && is_array($lineafactura['extra'])) {  // Noms persones llicències
					foreach ($lineafactura['extra'] as $extra) {
						$strExtra .= $extra.', ';
					}
					if (count($lineafactura['extra']) > 0) $strExtra = substr($strExtra, 0, -2); 
				}	
			}
		} catch (\Exception $e) {
			error_log('FECDAS GESTIO error factura detalls '.$this->id. '('.$e->getMessage().')');
			return '';
		}	
		
		if ($max > 0 && strlen($strExtra) > $max)  $strExtra = substr($strExtra, 0, $max).'...';
		
		return $strExtra;
	}
	
	public function getNumDetallsExtra() {
		$total = 0;
		try {
			$detallsArray = json_decode($this->detalls, true);
			
			foreach ($detallsArray as $lineafactura) {
				if (isset($lineafactura['extra']) && is_array($lineafactura['extra'])) {  // Noms persones llicències
					$total += count($lineafactura['extra']);
				}
			}
		} catch (\Exception $e) {
			error_log('FECDAS GESTIO error factura num detalls detalls '.$this->id. '('.$e->getMessage().')');
		}	

		return $total;
	}
	
	
	public function getPreuProducte($producte) {
	    if ($producte == null) return 0;
	    try {
	        $detallsArray = json_decode($this->detalls, true);
	        
	        foreach ($detallsArray as $k => $lineafactura) {
	            if ($k == $producte->getId() && isset($lineafactura['preuunitat'])) return $lineafactura['preuunitat'];
	        }
	    } catch (\Exception $e) {
	        error_log('FECDAS GESTIO error factura '.$this->id.' preu producte '.$producte->getId().'('.$e->getMessage().')');
	    }
	    
	    return 0;
	    
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
	 * @return \DateTime
	 */
	public function getDatafactura() {
		return $this->datafactura;
	}

	/**
	 * @param \DateTime $datafactura
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
	 * @return string
	 */
	public function getImport() {
		return $this->import;
	}

	/**
	 * @param string $import
	 */
	public function setImport($import) {
		// Update import comanda
		if ($this->comanda != null) {
			$club = $this->comanda->getClub();
			
			//if ($this->datafactura->format('Y') < date('Y')) $this->comanda->updateClubRomanent($import - $this->import);
			if ($club != null && $this->datafactura->format('Y') < $club->getExercici()) $this->comanda->updateClubRomanent($import - $this->import);
			else $this->comanda->updateClubSaldos($import - $this->import);
		}

		$this->import = $import;
	}
	
	
	/**
	 * @return string
	 */
	public function getIva() {
	    return $this->iva;
	}
	
	/**
	 * @param string $iva
	 */
	public function setIva($iva) {
	    $this->iva = $iva;
	}
	

	/**
	 * @return string
	 */
	public function getConcepte() {
		return $this->concepte;
	}

	/**
	 * @param string $concepte
	 */
	public function setConcepte($concepte) {
		$this->concepte = $concepte;
	}

	/**
	 * @return string
	 */
	public function getDetalls() {
		return $this->detalls;
	}

	/**
	 * @param string $detalls
	 */
	public function setDetalls($detalls) {
		$this->detalls = $detalls;
	}

	/**
	 * @return EntityComanda
	 */
	public function getComanda() {
		return $this->comanda;
	}
	
	/**
	 * @param EntityComanda $comanda
	 */
	public function setComanda(EntityComanda $comanda = null) {
		$this->comanda = $comanda;
	}
	
	/**
	 * @return EntityComanda
	 */
	public function getComandaanulacio() {
		return $this->comandaanulacio;
	}
	
	/**
	 * @param EntityComanda $comandaanulacio
	 */
	public function setComandaanulacio(EntityComanda $comandaanulacio = null) {
		$this->comandaanulacio = $comandaanulacio;
	}
	
    /**
     * Set comptabilitat
     *
     * @param EntityComptabilitat $comptabilitat
     * @return EntityComanda
     */
    public function setComptabilitat(EntityComptabilitat $comptabilitat = null)
    {
        $this->comptabilitat = $comptabilitat;

        return $this;
    }

    /**
     * Get comptabilitat
     *
     * @return EntityComptabilitat 
     */
    public function getComptabilitat()
    {
        return $this->comptabilitat;
    }
	
	/**
     * Set enviada
     *
     * @param boolean $enviada
     */
    public function setEnviada($enviada)
    {
    	$this->enviada = $enviada;
    }
    
    /**
     * Get enviada
     *
     * @return boolean
     */
    public function getEnviada()
    {
    	return $this->enviada;
    }
	
	/**
	 * @return string
	 */
	public function getNumcompte() {
		return $this->numcompte;
	}

	/**
	 * @param string $numcompte
	 */
	public function setNumcompte($numcompte) {
		$this->numcompte = $numcompte;
	}
	
	
	/**
	 * @return \DateTime
	 */
	public function getDataentrada() {
		return $this->dataentrada;
	}
	
	/**
	 * @param \DateTime $dataentrada
	 */
	public function setDataentrada($dataentrada) {
		$this->dataentrada = $dataentrada;
	}
	
}
