<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity 
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="tipuscomanda", type="string", length=1)
 * @ORM\DiscriminatorMap({"P" = "EntityParte", "D" = "EntityDuplicat", "A" = "EntityComanda"})
 * @ORM\Table(name="m_comandes")
 * 
 * @author alex
 *
 */
class EntityComanda {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $num;
	
	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentaris;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub", inversedBy="comandes")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;	// FK taula m_clubs
	
	/**
	 * @ORM\OneToOne(targetEntity="EntityFactura", inversedBy="comanda")
	 * @ORM\JoinColumn(name="factura", referencedColumnName="id")
	 **/
	protected $factura;	// FK taula m_factures

	/**
	 * @ORM\OneToMany(targetEntity="EntityFactura", mappedBy="comandaanulacio" )
	 */
	protected $facturesanulacions;	// FK taula m_factures
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityRebut", inversedBy="comandes")
	 * @ORM\JoinColumn(name="rebut", referencedColumnName="id")
	 */
	protected $rebut;	// FK taula m_rebuts

	/**
	 * @ORM\OneToMany(targetEntity="EntityRebut", mappedBy="comandaanulacio" )
	 */
	protected $rebutsanulacions;	// FK taula m_rebuts
		
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
	protected $databaixa;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityComandaDetall", mappedBy="comanda" )
	 */
	protected $detalls;
	
	/*
	 * @ORM\OneToOne(targetEntity="EntityParte", inversedBy="comanda")
	 * @ORM\JoinColumn(name="parte", referencedColumnName="id")
	 *
	protected $parte;*/
	
	/*
	 * @ORM\OneToOne(targetEntity="EntityDuplicat", inversedBy="comanda")
	 * @ORM\JoinColumn(name="duplicat", referencedColumnName="id")
	 *
	protected $duplicat;*/
	
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->id = 0;
		$this->dataentrada = new \DateTime();
		$this->detalls = new \Doctrine\Common\Collections\ArrayCollection();
		$this->facturesanulacions = new \Doctrine\Common\Collections\ArrayCollection();
		$this->rebutsanulacions = new \Doctrine\Common\Collections\ArrayCollection();
	
		// Hack per permetre múltiples constructors
		$a = func_get_args();
		$i = func_num_args();
	
		if ($i > 1 && method_exists($this,$f='__constructParams')) {
			call_user_func_array(array($this,$f),$a);
		}
	}
	
	
	public function __constructParams($num, $factura = null, $club = null, $comentaris = '') {
	
		$this->num = $num;
		$this->factura = $factura;
		$this->club = $club;
		$this->comentaris = ($comentaris==''?null:$comentaris);
		
	}
	
	public function __toString() {
		return $this->id.'';
	}
	
	
	public function esNova()
	{
		return ($this->id == 0);
	}
	
	public function esBaixa()
	{
		return $this->databaixa != null;
	}
	
	public function comandaPagada()
	{
		return $this->rebut != null;
	}

	/**
     * Allow edit. Permetre modificar / Afegir detalls. ==> Fals. Sobreescrit a partes (20 minuts)
     *
     * @return boolean
     */
    public function isAllowEdit()
    {
		return false;
    }

    public function mostrarFactura()
    {
		return ($this->comandaConsolidada() == true && $this->factura != null);
    }
	
	/**
     * Allow edit. True. Sobreescrit a partes (esperar 20 minuts)
     *
     * @return boolean
     */
    public function comandaConsolidada()
    {
		return !$this->isAllowEdit();  // Si no es permet editar es pot mostrar la factura
    }

	public function detallsEditables()
	{
		return !$this->esParte() && !$this->esDuplicat();
	}
	
	public function esParte()
	{
		return false;
	}
	
	public function esDuplicat()
	{
		return false;
	}
	
	public function esAltre()
	{
		return !$this->esDuplicat() && !$this->esParte();
	}

	/**
	 * Actualitza saldos club. Reescriptura updateClubSaldoTipusComanda
	 * 
	 */
	public function updateClubSaldos($import) {
		if ($this->club == null) return false;
		
		$this->updateClubSaldoTipusComanda($import);
		$this->club->addEntityComanda($this);
		
		return true; 
	}

	protected function updateClubSaldoTipusComanda($import) {
		$this->club->setTotalaltres($this->club->getTotalaltres() + $import);
	}

	/**
     * Is a current year comanda
     *  
     * @return boolean
     */
    public function isCurrentYear() {
    	return (date("Y", $this->dataentrada->getTimestamp()) == date("Y"));
    }

	/* Per sobreescriure als fills */
	public function baixa()
	{
		// Baixa dels detalls
		foreach ($this->getDetalls() as $detall) {
			if (!$detall->esBaixa()) {
				$detall->setDatabaixa(new \DateTime());
				$detall->setDatamodificacio(new \DateTime());
			}
		}
		$this->datamodificacio = new \DateTime();
		$this->databaixa = new \DateTime();
	}
	
	/**
	 * Comanda 
	 * format nou amb any  LXXXXX/20XX
	 *
	 * @return string
	 */
	public function getNumComanda() {
		return $this->getPrefixAlbara().str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->dataentrada->format("Y");
	}

	/**
	 * Comanda curt
	 * format nou amb any  LXXXXX/XX
	 * 
	 * @return string
	 */
	public function getNumComandaCurt() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->dataentrada->format("y");
	}
	
		
	/**
	 * Adds a comentari
	 * 
	 * @param string $comentari
	 */
	public function addComentari($comentari) {
		
		if ($this->comentaris == null || $this->comentaris == '') $this->comentaris = $comentari;
		else {
			$pos = strpos($this->comentaris, $comentari);
			if ($pos === false) $this->comentaris = $this->comentaris.PHP_EOL.$comentari;
		}
	}
	
	
	/**
	 * Get num albarà PREFIX + id => getNumComanda()
	 *
	 * @return string
	 */
	public function getNumAlbara()
	{
		return $this->getNumComanda();
	}
	
	/**
	 * Get nums factures totes 
	 *
	 * @return string
	 */
	public function getLlistaNumsFactures($curt = false, $anulacions = false)
	{
		$concepte = '';
		if ($this->comandaConsolidada() == true &&
			$this->factura != null) $concepte = ($curt == true?$this->factura->getNum().'-':$this->factura->getNumFactura().', ');
		
		if ($anulacions == true) {
			foreach ($this->facturesanulacions as $factura) {
				$concepte .= ($curt == true?$this->factura->getNum().'-':$factura->getNumFactura().', '); 	
			}
		}
		return ($curt == true?substr($concepte, 0, -1):substr($concepte, 0, -2));
		
	}

	/**
	 * Get # factures 
	 *
	 * @return string
	 */
	public function getNumFactures($anulacions = false)
	{
		$total = 0;
		if ($this->factura != null) $total++;
		
		if ($anulacions == true) $total += count($this->facturesanulacions);
		
		return $total;		
	}
	
	/**
	 * Get nums rebuts totes 
	 *
	 * @return string
	 */
	public function getLlistaNumsRebuts()
	{
		$concepte = '';
		if ($this->comandaPagada() == true) $concepte = $this->rebut->getNumRebut().', ';
		
		foreach ($this->rebutsanulacions as $rebut) {
			$concepte .= $rebut->getNumRebut().', '; 	
		}
		return substr($concepte, 0, -2);
		
	}

	/**
	 * Get # rebuts 
	 *
	 * @return string
	 */
	public function getNumRebuts()
	{
		$total = 0;
		if ($this->rebut != null) $total++;
		
		$total += count($this->rebutsanulacions);
		
		return $total;		
	}
	
	/**
	 * Get info llistat 
	 *
	 * @return string
	 */
	public function getInfoLlistat()
	{
		$info = $this->comentaris;
		if (trim($info) != '' && $this->getNumDetalls()>0) $info .= PHP_EOL;
		
		foreach ($this->detalls as $d) {
			if (!$d->esBaixa()) $info .= $d->getAnotacions().', ';	
		}
		return substr($info, 0, -2);
	}
	
	
	/**
	 * Get prefix albarà comú.
	 * A sobrecarregar pels fills
	 * 
	 * @return string
	 */
	public function getPrefixAlbara()
	{
		return BaseController::PREFIX_ALBARA_ALTRES;
	}
	
	public function getEstat()
	{
		if ($this->esBaixa() == true) return 'baixa';
		return $this->comandaPagada() != true?'pendent':'';
	}
	
	/**
	 * Get factures
	 *
	 * @return array
	 */
	public function getFactures() {
		$factures = array();
		if ($this->factura != null) $factures[] = $this->factura;
	
		return array_merge($factures, $this->facturesanulacions->toArray());
	}

	/**
	 * Get importpagament
	 *
	 * @return decimal
	 */
	public function getImportpagament()
	{
		return ($this->rebut != null?$this->rebut->getImport():0);
	}
	
	/**
	 * Get datapagament
	 *
	 * @return date
	 */
	public function getDatapagament()
	{
		return ($this->rebut != null?$this->rebut->getDatapagament():null);
	}
	
	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getOrigenPagament()
	{
		return BaseController::PAGAMENT_ALTRES;	
	}
	
	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getDescripcioPagament()
	{
		return 'Pagament a FECDAS, comanda del club ' . $this->club->getCodi() . 
				' en data ' . $this->getDataentrada()->format('d/m/Y');	
	}			

	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getBackURLPagament()
	{
		return 'FecdasBundle_comandes'; 	
	}			

	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getBackTextPagament()
	{
		return 'Llistat de comandes'; 	
	}			

	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getMenuActiuPagament()
	{
		return 'menu-comanda';	
	}			

	/**
	 * @return integer
	 */
	public function getTipuspagament() {
		return ($this->rebut != null?$this->rebut->getTipuspagament():null);
	}
	
	/**
	 * Comprova si el rebut es vàlid.
	 *
	 * @return boolean
	 */
	public function isRebutValid()
	{
		if ($this->rebut == null) return false;
		return abs($this->rebut->getImport() - $this->getTotalDetalls() <= 0.01);
	}
	
	/**
	 * Get Info comanda
	 *
	 * @return string
	 */
	public function getInfoComanda()
	{
		return $this->getNumComanda().", dia ".$this->dataentrada->format("d/m/Y").
			", club ".$this->getClub()->getNom().". Total: ".number_format($this->getTotalDetalls(), 2, ',', '.');
	}

	/**
	 * Get detall producte
	 *
	 * @return EntityComandaDetall
	 */
	public function getDetallComanda($producte)
	{
		foreach ($this->detalls as $d) {
			//if (!$d->esBaixa() && $d->getProducte()->getId() == $producte->getId()) return $d;
			if ($d->getProducte()->getId() == $producte->getId()) return $d;
		}
		return null;
	}
	
	
	/**
	 * Get tipus comanda: kits, llicències, etc... 
	 *
	 * @return string
	 */
	public function getTipusComanda()
	{
		$keystipus = array();
		foreach ($this->detalls as $d) {
			if (!$d->esBaixa() && $d->getProducte() != null) {
				$keystipus[$d->getProducte()->getTipus()] = true;
			}
		}
		$tipusTexts = array();
		foreach (array_keys($keystipus) as $tipus) {
			$tipusTexts[] = BaseController::getTipusProducte($tipus);
		}
		
		return implode(", ", $tipusTexts);
	}
	
	
	/**
	 * Get obtenir dades dels detalls agrupades i acumulades per producte: 'kits' => 3, 'llicències..' => 1, etc...
	 *
	 * @return string
	 */
	public function getDetallsAcumulats($baixes = false)
	{
		$acumulades = array();
		
		foreach ($this->detalls as $d) {
			if ( (!$d->esBaixa() || $baixes == true) && 
					$d->getProducte() != null) {
				
				$codi = $d->getProducte()->getCodi();
				
				if (isset($acumulades[$codi])) {
					$acumulades[$codi]['total'] += $d->getUnitats();
					if ($baixes == true) $acumulades[$codi]['total'] += $d->getUnitatsbaixa();
					$acumulades[$codi]['import'] += $d->getTotal($baixes);
				}
				else {
					$acumulades[$codi] = $d->getDetallsArray($baixes);
				}
			}
		}
		ksort($acumulades); // Ordenada per codi
		return $acumulades;
	}
	
	/**
	 * Get concepte comanda (Factura / Rebut)
	 *
	 * @return string
	 */
	public function getConcepteComanda()
	{
		
		return $this->getNumComanda()." ".$this->getTipusComanda();
		
	}
	
	/**
	 * Get num assentament
	 *
	 * @return string
	 */
	public function getNumAssentament()
	{
	
		if ($this->factura == null) return $this->getNumComandaCurt();
	
		return $this->factura->getNumFacturaCurt();
	}
	
	/**
	 * Get total suma dels detalls
	 *
	 * @return double
	 */
	public function getTotalDetalls()
	{
		$total = 0;
		foreach ($this->detalls as $d) $total += $d->getTotal();
		return $total;
	}

	/**
	 * Get total suma dels detalls sense IVA
	 *
	 * @return double
	 */
	public function getTotalNetDetalls()
	{
		$total = 0;
		foreach ($this->detalls as $d) $total += $d->getTotalNet();
		return $total;
	}

	/**
	 * Get total suma dels detalls sense IVA
	 *
	 * @return double
	 */
	public function getTotalIVADetalls()
	{
		return round($this->getTotalDetalls() - $this->getTotalNetDetalls(), 2); 	
	}

	/**
	 * Get total dels detalls no baixa
	 *
	 * @return double
	 */
	public function getNumDetalls()
	{
		$total = 0;
		foreach ($this->detalls as $d) if (!$d->esBaixa()) $total ++;
		return $total;
	}

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    // Set Id not autogenerated
    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
    	$this->id = $id;
    }
    
    
    
    /**
     * @param integer $num
     * @return EntityComanda
     */
    public function setNum($num) {
    	$this->num = $num;
    	
    	return $this;
    }
    
    /**
     * @return integer
     */
    public function getNum() {
    	return $this->num;
    }
    
    /**
     * Set comentaris
     *
     * @param string $comentaris
     * @return EntityComanda
     */
    public function setComentaris($comentaris)
    {
        $this->comentaris = $comentaris;

        return $this;
    }

    /**
     * Get comentaris
     *
     * @return string 
     */
    public function getComentaris()
    {
        return $this->comentaris;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return EntityComanda
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;

        return $this;
    }

    /**
     * Get dataentrada
     *
     * @return \DateTime 
     */
    public function getDataentrada()
    {
        return $this->dataentrada;
    }

    /**
     * Set datamodificacio
     *
     * @param \DateTime $datamodificacio
     * @return EntityComanda
     */
    public function setDatamodificacio($datamodificacio)
    {
        $this->datamodificacio = $datamodificacio;

        return $this;
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
     * Set databaixa
     *
     * @param \DateTime $databaixa
     * @return EntityComanda
     */
    public function setDatabaixa($databaixa)
    {
        $this->databaixa = $databaixa;

        return $this;
    }

    /**
     * Get databaixa
     *
     * @return \DateTime 
     */
    public function getDatabaixa()
    {
        return $this->databaixa;
    }

    /**
     * Set club
     *
     * @param \FecdasBundle\Entity\EntityClub $club
     * @return EntityComanda
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
     * Set factura
     *
     * @param \FecdasBundle\Entity\EntityFactura $factura
     * @return EntityComanda
     */
    public function setFactura(\FecdasBundle\Entity\EntityFactura $factura = null)
    {
    	$this->factura = $factura;

        return $this;
    }

    /**
     * Get factura
     *
     * @return \FecdasBundle\Entity\EntityFactura 
     */
    public function getFactura()
    {
        return $this->factura;
    }

    /**
     * Set rebut
     *
     * @param \FecdasBundle\Entity\EntityRebut $rebut
     * @return EntityComanda
     */
    public function setRebut(\FecdasBundle\Entity\EntityRebut $rebut = null)
    {
        $this->rebut = $rebut;

        return $this;
    }

    /**
     * Get rebut
     *
     * @return \FecdasBundle\Entity\EntityRebut 
     */
    public function getRebut()
    {
        return $this->rebut;
    }
	
	
	/**
     * Add rebutsanulacions
     *
     * @param EntityRebut $rebutsanulacions
     * @return EntityComanda
     */
    public function addrebutsanulacions(EntityRebut $rebutanulacio) 
    {
    	$this->rebutsanulacions->add($rebutanulacio);

        return $this;
    }

    /**
     * Get rebutsanulacions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRebutsanulacions()
    {
        return $this->rebutsanulacions;
    }

    /**
     * Remove rebutsanulacions
     *
     * @param EntityRebut $rebutanulacio
     */
    public function removeRebutsanulacions(EntityRebut $rebutanulacio)
    {
        $this->rebutsanulacions->removeElement($rebutanulacio);
    }

    /**
     * Remove all rebutsanulacions
     *
     */
    public function resetRebutsanulacions()
    {
    	$this->rebutsanulacions = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set rebutsanulacions
     *
     * @param \Doctrine\Common\Collections\Collection $rebutsanulacions
     */
    public function setRebutsanulacions(\Doctrine\Common\Collections\ArrayCollection $rebutsanulacions)
    {
    	$this->rebutsanulacions = $rebutsanulacions;
    }  
	

    /**
     * Add detall
     *
     * @param EntityComandaDetall $detall
     * @return EntityComanda
     */
    public function addDetall(EntityComandaDetall $detall) 
    {
    	$this->detalls->add($detall);

        return $this;
    }

    /**
     * Get detalls
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDetalls()
    {
        return $this->detalls;
    }

    /**
     * Remove detalls
     *
     * @param EntityComandaDetall $detall
     */
    public function removeDetall(EntityComandaDetall $detall)
    {
        $this->detalls->removeElement($detall);
    }

    /**
     * Remove all detalls
     *
     */
    public function resetDetalls()
    {
    	$this->detalls = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set detalls
     *
     * @param \Doctrine\Common\Collections\Collection $detalls
     */
    public function setDetalls(\Doctrine\Common\Collections\ArrayCollection $detalls)
    {
    	$this->detalls = $detalls;
    }  
	
	/**
     * Add facturesanulacions
     *
     * @param EntityFactura $facturesanulacions
     * @return EntityComanda
     */
    public function addFacturaanulacio(EntityFactura $facturaanulacio) 
    {
    	$this->facturesanulacions->add($facturaanulacio);

        return $this;
    }

    /**
     * Get facturesanulacions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFacturesanulacions()
    {
        return $this->facturesanulacions;
    }

    /**
     * Remove facturesanulacions
     *
     * @param EntityFactura $facturesanulacions
     */
    public function removeFacturaanulacio(EntityFactura $facturaanulacio)
    {
        $this->facturesanulacions->removeElement($facturaanulacio);
    }

    /**
     * Remove all facturesanulacions
     *
     */
    public function resetFacturesanulacions()
    {
    	$this->facturesanulacions = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set facturesanulacions
     *
     * @param \Doctrine\Common\Collections\Collection $facturesanulacions
     */
    public function setFacturesanulacions(\Doctrine\Common\Collections\ArrayCollection $facturesanulacions)
    {
    	$this->facturesanulacions = $facturesanulacions;
    }  
	
}
