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
	 * @ORM\ManyToOne(targetEntity="EntityUser")
	 * @ORM\JoinColumn(name="usuari", referencedColumnName="id")
	 */
	protected $usuari;	// FK taula m_users
	
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
	    return $this->rebut != null && !$this->rebut->esBaixa();
	}

	public function comandaUsuari()
	{
	    return $this->usuari != null;
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

    public function comandaKits()
    {
		// Baixa dels detalls
		foreach ($this->getDetalls() as $detall) {
			if (!$detall->esBaixa() && $detall->getProducte()->esKit()) return true;
		}
		return false;
    }

    /**
     * conté transport?
     *
     * @return boolean
     */
    public function conteTransport()
    {
        foreach ($this->getDetalls() as $detall) {
            if (!$detall->esBaixa() && $detall->esTransport()) return true;
        }
        
        return false;
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
	 * Actualitza romanent club. Comanda facturada a l'any anterior
	 * 
	 */
	public function updateClubRomanent($import) {
		if ($this->club == null) return false;
		
		$this->club->setRomanent($this->club->getRomanent() - $import); // Resta import del romanent
		$this->club->addEntityComanda($this);
		
		return true; 
	}

	/**
     * Is a current year comanda
     *  
     * @return boolean
     */
    public function isCurrentYear() {
    	return (date("Y", $this->dataentrada->getTimestamp()) == date("Y"));
    }

	/**
     * Get any
     *
     * @return integer
     */
    public function getAny()
    {
    	return $this->dataentrada->format('Y');
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
		return $this->getPrefixAlbara().str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->dataentrada->format("y");
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
	 * Get factura anulacio nova
	 *
	 * @return EntityFactura
	 */
	public function getFacturaAnulacioNova()
	{
		foreach ($this->facturesanulacions as $factura) {
			if ($factura->getId() == 0) return $factura;
		}	
		return null;		
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
	 * Get nums rebuts totes 
	 *
	 * @return string
	 */
	public function getLlistaNumsRebuts()
	{
		$concepte = '';
		if ($this->comandaPagada()) $concepte = $this->rebut->getNumRebut().', ';
		
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
		if ($this->comandaPagada()) $total++;
		
		$total += count($this->rebutsanulacions);
		
		return $total;		
	}
	
	/**
	 * Get rebuts
	 *
	 * @return array
	 */
	public function getRebuts() {
	    $rebuts = array();
	    if ($this->rebut != null) $rebuts[] = $this->rebut;
	    
	    return array_merge($rebuts, $this->rebutsanulacions->toArray());
	}
	
	
	/**
	 * Get info llistat 
	 *
	 * @return string
	 */
	public function getInfoLlistat( $br = PHP_EOL, $llista = false )
	{
		$info = $this->comentaris;
		if (trim($info) != '' && $this->getNumDetalls()>0) $info .= $br;
		
		foreach ($this->detalls as $d) {
			if (!$d->esBaixa()) $info .= $d->getAnotacions().($llista == true?$br:', ');	
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
		return !$this->comandaPagada()?'pendent':'';
	}
	
	
	/**
	 * Get datapreu
	 *
	 * @return \DateTime
	 */
	public function getDatapreu()
	{
		return $this->dataentrada;
	}

	/**
	 * Get importpagament
	 *
	 * @return string
	 */
	public function getImportpagament()
	{
	    return ($this->comandaPagada()?$this->rebut->getImport():0);
	}
	
	/**
	 * Get datapagament
	 *
	 * @return \DateTime
	 */
	public function getDatapagament()
	{
	    return ($this->comandaPagada()?$this->rebut->getDatapagament():null);
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
	    return ($this->comandaPagada()?$this->rebut->getTipuspagament():null);
	}
	
	/**
	 * Comprova si el rebut es vàlid.
	 *
	 * @return boolean
	 */
	public function isRebutValid()
	{
	    if ($this->comandaPagada()) return false;
		return abs($this->rebut->getImport() - $this->getTotalComanda() <= 0.01);
	}
	
	/**
	 * Get Info comanda
	 *
	 * @return string
	 */
	public function getInfoComanda()
	{
		return $this->getNumComanda().", dia ".$this->dataentrada->format("d/m/Y").
			", club ".$this->getClub()->getNom().". Total: ".number_format($this->getTotalComanda(), 2, ',', '.');
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
		
		/*foreach ($this->detalls as $d) {
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
		return $acumulades;*/
		if ($this->isFacturaModificada()) {
			
			$detallsArray = json_decode($this->getFactura()->getDetalls(), false, 512);
			
			foreach ($detallsArray as $id => $d) {
					$acumulades[$id] = array('total' => $d->total,
						'totalbaixa' => $d->totalbaixa,	 
						'preuunitat' => $d->preuunitat,
						'ivaunitat' => $d->ivaunitat,
						'import' => round($d->import, 2),
						'producte' => $d->producte,
						'extra'		=> '', //$d->extra
						'abreviatura' => $d->abreviatura,
						'descompte' => $d->descompte,
						'codi' => $d->codi,
						'id' => $id);
			}
			return $acumulades;
		}
		
		foreach ($this->detalls as $d) {
			if ( (!$d->esBaixa() || $baixes == true) && 
					$d->getProducte() != null) {
				
				$id = $d->getProducte()->getId();
				if (isset($acumulades[$id])) {
					$acumulades[$id]['total'] += $d->getUnitats();
					if ($baixes == true) $acumulades[$id]['total'] += $d->getUnitatsbaixa();
					$acumulades[$id]['import'] += $d->getTotal($baixes);
				}
				else {
					$acumulades[$id] = $d->getDetallsArray($baixes);
				}
			}
		}
		
		// Ordre  codi > producte
		uasort($acumulades, function($a, $b) {
			if ($a === $b) {
				return 0;
			}
			if ($a['codi'] == $b['codi']) ($a['producte'] > $b['producte'])? -1:1;
			return ($a['codi'] > $b['codi'])? -1:1;
		});
		
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
	 * Import comanda modificat
	 *
	 * @return double
	 */
	public function isFacturaModificada() 
	{
		return abs($this->getTotalComanda() - $this->getTotalDetalls()) > 0.01 && $this->dataentrada->format('Y') >= 2015;
	}
	
	/**
	 * Get total factures
	 *
	 * @return double
	 */
	public function getTotalComanda()
	{

		if ($this->factura == null) return $this->getTotalDetalls();
		
		$total = $this->factura->getImport();
		
		foreach ($this->facturesanulacions as $factura) $total += $factura->getImport();
			
		return $total;
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
	 * Get total suma IVA dels detalls
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
     * Set club
     *
     * @param \FecdasBundle\Entity\EntityUser $usuari
     * @return EntityComanda
     */
    public function setUsuari(\FecdasBundle\Entity\EntityUser $usuari = null)
    {
        $this->usuari = $usuari;
        
        return $this;
    }
    
    /**
     * Get usuari
     *
     * @return \FecdasBundle\Entity\EntityUser
     */
    public function getUsuari()
    {
        return $this->usuari;
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
