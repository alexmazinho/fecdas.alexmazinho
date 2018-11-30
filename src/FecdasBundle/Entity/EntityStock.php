<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_stock")
 * 
 * @author alex
 *
 */
class EntityStock {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityProducte")
	 * @ORM\JoinColumn(name="producte", referencedColumnName="id")
	 */
	protected $producte;	// FK taula m_productes => només productes stock
	
	/**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $dataregistre;		
    
    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    protected $tipus; // 'E' entrada o 'S' sortida
	
	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $unitats;
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2, nullable=true)
	 */
	protected $preuunitat;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityFactura")
	 * @ORM\JoinColumn(name="factura", referencedColumnName="id", nullable=true)
	 */
	protected $factura;	// FK taula m_factures
	
	/**
	 * @ORM\OneToOne(targetEntity="EntityCurs", mappedBy="stock")
	 */
	protected $curs;	// FK taula m_curs
	
	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentaris;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;  // FK m_clubs
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $databaixa;
	

	public function __construct($club = null, $producte = null, $unitats = 0, $comentaris = '', $dataregistre = null, $tipus = BaseController::REGISTRE_STOCK_ENTRADA, $factura = null, $curs = null) {
		$this->id = 0;
		$this->stock = 0;
		$this->club = $club;
		$this->producte = $producte;
		$this->dataregistre = ($dataregistre != null?$dataregistre:new \DateTime('today'));
		$this->tipus = $tipus;
		$this->unitats = $unitats;
		$this->preuunitat = ($producte!=null?$producte->getPreuAny($this->dataregistre->format('Y')):0);
		$this->factura = $factura;
		$this->curs = $curs;
		$this->comentaris = $comentaris;
		$this->dataentrada = new \DateTime('now');
		
	}
	
	public function __toString() {
		return $this->id.($this->club != null?$this->club->getNom():'').($this->producte != null?$this->producte->getDescripcio():'').$this->dataregistre->format('Y-m-d H:i');
	}
	
	
	/**
     * Està anul·lat l'apunt?
     *
     * @return boolean
     */
    public function anulat()
    {
    	return $this->databaixa != null;
    }
    
    /**
     * És sortida?
     *
     * @return boolean
     */
    public function esSortida()
    {
    	return $this->tipus == BaseController::REGISTRE_STOCK_SORTIDA;
    }
    
    /**
     * És entrada?
     *
     * @return boolean
     */
    public function esEntrada()
    {
    	return $this->tipus == BaseController::REGISTRE_STOCK_ENTRADA;
    }
	
	/**
	 * Retorna el saldo del registre 
	 *
	 * @return string
	 */
	public function getSaldo() {
		return round($this->totalpagaments + $this->ajustsubvencions + $this->romanent - $this->totalllicencies - $this->totalduplicats - $this->totalaltres, 2);
	}
	
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
	
	/**
     * Set producte
     *
     * @param EntityProducte $producte
     */
    public function setProducte(EntityProducte $producte = null)
    {
        $this->producte = $producte;
    }

    /**
     * Get producte
     *
     * @return EntityProducte 
     */
    public function getProducte()
    {
        return $this->producte;
    }
	
	/**
     * Set dataregistre
     *
     * @param \DateTime $dataregistre
     */
    public function setDataregistre($dataregistre)
    {
        $this->dataregistre = $dataregistre;
    }

    /**
     * Get dataregistre
     *
     * @return \DateTime 
     */
    public function getDataregistre()
    {
        return $this->dataregistre;
    }
	
	/**
     * Set tipus
     *
     * @param string $tipus
     */
    public function setTipus($tipus)
    {
        $this->tipus = $tipus;
    }

    /**
     * Get tipus
     *
     * @return string 
     */
    public function getTipus()
    {
        return $this->tipus;
    }
	
	/**
     * Set unitats
     *
     * @param integer $unitats
     */
    public function setUnitats($unitats)
    {
        $this->unitats = $unitats;
    }

    /**
     * Get unitats
     *
     * @return integer 
     */
    public function getUnitats()
    {
        return $this->unitats;
    }
	
	/**
     * Set preuunitat 
     *
     * @param string $preuunitat
     */
    public function setPreuunitat($preuunitat)
    {
    	$this->preuunitat = $preuunitat;
    }
    
    /**
     * Get preuunitat 
     *
     * @return string
     */
    public function getPreuunitat()
    {
    	return $this->preuunitat;
    }
	
	/**
     * Set factura
     *
     * @param EntityFactura $factura
     */
    public function setFactura(EntityFactura $factura = null)
    {
        $this->factura = $factura;
    }

    /**
     * Get factura
     *
     * @return EntityFactura
     */
    public function getFactura()
    {
        return $this->factura;
    }
	
    /**
     * Set curs
     *
     * @param EntityCurs $curs
     */
    public function setCurs(EntityCurs $curs = null)
    {
        $this->curs = $curs;
    }
    
    /**
     * Get curs
     *
     * @return EntityCurs
     */
    public function getCurs()
    {
        return $this->curs;
    }
    
	/**
     * Set comentaris
     *
     * @param string $comentaris
     */
    public function setComentaris($comentaris)
    {
        $this->comentaris = $comentaris;
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
     * Set club
     *
     * @param EntityClub $club
     */
    public function setClub(EntityClub $club = null)
    {
        $this->club = $club;
    }

    /**
     * Get club
     *
     * @return EntityClub 
     */
    public function getClub()
    {
        return $this->club;
    }

	/**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;
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
     * Set databaixa
     *
     * @param \DateTime $databaixa
     */
    public function setDatabaixa($databaixa)
    {
        $this->databaixa = $databaixa;
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
		
}
