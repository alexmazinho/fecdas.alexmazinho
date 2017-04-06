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
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $preuunitat;

	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $stock;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityFactura")
	 * @ORM\JoinColumn(name="factura", referencedColumnName="id", nullable=true)
	 */
	protected $factura;	// FK taula m_comandes
	
	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentaris;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $databaixa;
	

	public function __construct($producte = null, $unitats = 0, $comentaris = '', $dataregistre = null, $tipus = BaseController::REGISTRE_STOCK_ENTRADA, $factura = null ) {
		$this->id = 0;
		$this->producte = $producte;
		$this->dataregistre = ($dataregistre != null?$dataregistre:new \DateTime('today'));
		$this->tipus = $tipus;
		$this->unitats = $unitats;
		$this->preuunitat = ($producte!=null?$producte->getPreuAny($this->dataregistre->format('Y')):0);
		$this->factura = $factura;
		$this->comentaris = $comentaris;
		$this->dataentrada = new \DateTime('now');
		
	}
	
	public function __toString() {
		return $this->id.$this->producte->getDescripcio().$this->dataregistre->format('Y-m-d H:i');
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
	 * @return decimal
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
     * @param \FecdasBundle\Entity\EntityProducte $producte
     */
    public function setProducte(\FecdasBundle\Entity\EntityProducte $producte = null)
    {
        $this->producte = $producte;
    }

    /**
     * Get producte
     *
     * @return \FecdasBundle\Entity\EntityProducte 
     */
    public function getProducte()
    {
        return $this->producte;
    }
	
	/**
     * Set dataregistre
     *
     * @param date $dataregistre
     */
    public function setDataregistre($dataregistre)
    {
        $this->dataregistre = $dataregistre;
    }

    /**
     * Get dataregistre
     *
     * @return date 
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
     * @param decimal $preuunitat
     */
    public function setPreuunitat($preuunitat)
    {
    	$this->preuunitat = $preuunitat;
    }
    
    /**
     * Get preuunitat 
     *
     * @return decimal
     */
    public function getPreuunitat()
    {
    	return $this->preuunitat;
    }
	
	/**
     * Set stock
     *
     * @param integer $stock
     */
    public function setStock($stock)
    {
        $this->stock = $stock;
    }

    /**
     * Get stock
     *
     * @return integer 
     */
    public function getStock()
    {
        return $this->stock;
    }
	
	
	/**
     * Set factura
     *
     * @param \FecdasBundle\Entity\EntityFactura $factura
     */
    public function setFactura(\FecdasBundle\Entity\EntityFactura $factura = null)
    {
        $this->factura = $factura;
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
     * Set comentaris
     *
     * @param text $comentaris
     */
    public function setComentaris($comentaris)
    {
        $this->comentaris = $comentaris;
    }

    /**
     * Get comentaris
     *
     * @return text 
     */
    public function getComentaris()
    {
        return $this->comentaris;
    }

	/**
     * Set dataentrada
     *
     * @param date $dataentrada
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;
    }

    /**
     * Get dataentrada
     *
     * @return date 
     */
    public function getDataentrada()
    {
        return $this->dataentrada;
    }
		
	/**
     * Set databaixa
     *
     * @param date $databaixa
     */
    public function setDatabaixa($databaixa)
    {
        $this->databaixa = $databaixa;
    }

    /**
     * Get databaixa
     *
     * @return date
     */
    public function getDatabaixa()
    {
        return $this->databaixa;
    }	
		
}
