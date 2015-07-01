<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;
/* 
*/

/**
 * @ORM\Entity 
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="tipus", type="string", length=1)
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
	 * @ORM\Column(type="text")
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
	 * @ORM\ManyToOne(targetEntity="EntityComptabilitat")
	 * @ORM\JoinColumn(name="comptabilitat", referencedColumnName="id")
	 */
	protected $comptabilitat;	// FK taula m_comptabilitat => Enviament programa compta
	
	/**
	 * @ORM\OneToOne(targetEntity="EntityRebut", inversedBy="comanda")
	 * @ORM\JoinColumn(name="rebut", referencedColumnName="id")
	 **/
	protected $rebut;	// FK taula m_rebuts
	
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
		
		/*if ($this->esParte()) {
		 $this->comentaris = 'Llista '.$parte->getId().'-'.$this->comentaris;
		 $this->parte = $parte;
		 $this->parte->setComanda($this);
		 }
		
		 if ($this->esDuplicat()) {
		 $this->comentaris = 'Petició '.$duplicat->getId().'/'.$duplicat->getDatapeticio()->format('Y-m-d').'-'.$this->comentaris;
		 $this->duplicat = $duplicat;
		 $this->duplicat->setComanda($this);
		 }*/
	}
	
	public function estBaixa()
	{
		return $this->databaixa != null;
	}
	
	public function comandaPagada()
	{
		return $this->rebut != null && !$this->rebut->esBaixa();
	}
	
	/**
	 * Comanda format amb any  XXXXX/20XX
	 *
	 * @return string
	 */
	public function getNumComanda() {
		return $this->getPrefixAlbara().str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->dataentrada->format("Y");
	}
	
	
	/**
	 * Comanda format curt amb any  XXXXX/XX
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
		
		if ($this->comentaris == null || $this->comentaris == "") $this->comentaris = $comentari;
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
		return $this->databaixa != null?'baixa':'';
	}
	
	/**
	 * Num factura o res
	 *
	 * @return string
	 */
	public function getNumFactura() {
		return ($this->factura != null?$this->factura->getNumFactura():"");
	}
	
	/**
	 * Num rebut o res
	 *
	 * @return string
	 */
	public function getNumRebut() {
		return ($this->rebut != null?$this->rebut->getNumRebut():"");
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
	public function getDetallsAcumulats()
	{
		$acumulades = array();
		
		foreach ($this->detalls as $d) {
			if (!$d->esBaixa() && $d->getProducte() != null) {
				
				$codi = $d->getProducte()->getCodi();
				
				if (isset($acumulades[$codi])) {
					$acumulades[$codi]['total']++;
					$acumulades[$codi]['import'] += $d->getTotal();
				}
				else {
					$acumulades[$codi] = array(
						'total' => 1, 
						'import' => $d->getTotal(),
						'producte' => $d->getProducte()->getDescripcio());
				}
			}
		}
		return $acumulades;
	}
	
	/**
	 * Get concepte comanda (Factura / Rebut)
	 *
	 * @return string
	 */
	public function getConcepteComanda()
	{
		
		if ($this->factura == null) return "COMANDA: ".$this->getNumComanda()." ".$this->getTipusComanda();
		
		return $this->factura->getConcepte();
		
	}
	
	/**
	 * Get concepte comanda curt
	 *
	 * @return string
	 */
	public function getConcepteComandaCurt()
	{
	
		if ($this->factura == null) return "COMANDA: ".$this->getNumComanda();
	
		return "FACTURA: ".$this->factura->getNumFactura();
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
	
	public function esBaixa()
	{
		return $this->databaixa != null;
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
     * Set comptabilitat
     *
     * @param \FecdasBundle\Entity\EntityComptabilitat $comptabilitat
     * @return EntityComanda
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
}
