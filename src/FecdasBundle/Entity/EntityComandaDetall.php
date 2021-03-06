<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_comandadetalls")
 * 
 * @author alex
 *
 */
class EntityComandaDetall {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityComanda", inversedBy="detalls")
	 * @ORM\JoinColumn(name="comanda", referencedColumnName="id")
	 */
	protected $comanda;	// FK taula m_comandes
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityProducte")
	 * @ORM\JoinColumn(name="producte", referencedColumnName="id")
	 */
	protected $producte; // FK taula m_productes

	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $unitats;
	
	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $unitatsbaixa;

	/**
	 * @ORM\Column(type="decimal", precision=8, scale=2)
	 */
	protected $preuunitat;  // Preu aplicat en el moment de fer la comanda

	/**
	 * @ORM\Column(type="decimal", precision=6, scale=2, nullable=true)
	 */
	protected $ivaunitat;  // IVA aplicat en el moment de fer la comanda

	/**
	 * @ORM\Column(type="decimal", precision=4, scale=2)
	 */
	protected $descomptedetall;
	
	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $anotacions;  // Comentaris a la comanda
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datamodificacio;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->id = 0;
		$this->unitats = 1;
		$this->unitatsbaixa = 0;
		$this->preuunitat = 0;
		$this->ivaunitat = 0;
		$this->dataentrada = new \DateTime();
	
		// Hack per permetre múltiples constructors
		$a = func_get_args();
		$i = func_num_args();
	
		if ($i > 1 && method_exists($this,$f='__constructParams')) {
			call_user_func_array(array($this,$f),$a);
		}	
	}
	
	
	public function __constructParams($comanda, $producte = null, $unitats = 1, $descomptedetall = 0, $anotacions = '') {
	
		$this->comanda 	= $comanda;
		$this->producte = $producte;
		$this->unitats =  $unitats;
		
		if ($producte != null) {
			$anypreu = ($comanda != null?$comanda->getDatapreu()->format('Y'):date('Y'));
			$this->preuunitat = $producte->getPreuAny($anypreu);
			$this->ivaunitat = $producte->getIvaAny($anypreu);   
		} 
		  
		//$this->preuunitat = ($producte != null?$producte->getCurrentPreu():0);
		//$this->ivaunitat = ($producte != null?$producte->getCurrentIva():0);
		$this->descomptedetall = $descomptedetall;
		$this->anotacions = $anotacions;
	}
	
	
	/**
	 * Get total net
	 *
	 * @return double
	 */
	public function getTotalNet() {
	    return BaseController::getImportNetDetall($this->getDetallsArray());
	}
	/*public function getTotalNet()
	{
		if ($this->producte == null) return 0;	

		if ($this->esBaixa()) return 0; 
		//$preu 	= $this->producte->getCurrentPreu();
		//$iva 	= $this->producte->getCurrentIva();
		
		$total = $this->unitats;
	
		return $this->preuunitat * $total * (1 - $this->descomptedetall);
	}*/
	
	/**
	 * Get total
	 *
	 * @return double
	 */
	/*public function getTotal($baixes = false)
	{
		return $this->getTotalNet($baixes)*(1 + $this->ivaunitat);	
	}*/

	/**
	 * Get iva
	 *
	 * @return double
	 */
	/*public function getIva($baixes = false)
	{
	    return $this->getTotalNet($baixes)*$this->ivaunitat;
	}*/
	
	/**
	 * Get detalls 
	 *
	 * @return array
	 */
	public function getDetallsArray()
	{
		//$total = $this->unitats;
		//if ($baixes == true) $total += $this->unitatsbaixa;
		return array('total' => $this->unitats,
					'totalbaixa' => $this->unitatsbaixa,	 
					'preuunitat' => $this->preuunitat,
					'ivaunitat' => $this->ivaunitat,
					//'import' => round($this->getTotal($baixes), 2),
					//'producte' => mb_convert_encoding($this->producte->getDescripcio(), 'UTF-8'),
					'producte' => $this->producte->getDescripcio(),
					'extra'		=> '',
					'abreviatura' => $this->producte->getAbreviatura(),
					'descompte' => $this->descomptedetall,
					'codi' => $this->producte->getCodi(),
					'id' => $this->producte->getId()
		);

	}

	/**
	 * Get id
	 *
	 * @return boolean
	 */
	public function esBaixa()
	{
		//return $this->getUnitats() <= 0;
		return $this->getUnitats() == 0;
	}
	
	/**
	 * És transport?
	 *
	 * @return boolean
	 */
	public function esTransport()
	{
	    return $this->producte->getCodi() == BaseController::PRODUCTE_CORREUS;
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
     * Set unitats
     *
     * @param integer $unitats
     * @return EntityComandaDetall
     */
    public function setUnitats($unitats)
    {
        $this->unitats = $unitats;

        return $this;
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
     * Set unitatsbaixa
     *
     * @param integer $unitatsbaixa
     * @return EntityComandaDetall
     */
    public function setUnitatsbaixa($unitatsbaixa)
    {
        $this->unitatsbaixa = $unitatsbaixa;

        return $this;
    }

    /**
     * Get unitatsbaixa
     *
     * @return integer 
     */
    public function getUnitatsbaixa()
    {
        return $this->unitatsbaixa;
    }
	
    /**
     * Set preuunitat
     *
     * @param string $preuunitat
     * @return EntityComandaDetall
     */
    public function setPreuunitat($preuunitat)
    {
    	$this->preuunitat = $preuunitat;
    
    	return $this;
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
     * Set ivaunitat
     *
     * @param string $ivaunitat
     * @return EntityComandaDetall
     */
    public function setIvaunitat($ivaunitat)
    {
    	$this->ivaunitat = $ivaunitat;
    
    	return $this;
    }
    
    /**
     * Get ivaunitat
     *
     * @return string
     */
    public function getIvaunitat()
    {
    	return $this->ivaunitat;
    }
    
    /**
     * Set descomptedetall
     *
     * @param string $descomptedetall
     * @return EntityComandaDetall
     */
    public function setDescomptedetall($descomptedetall)
    {
        $this->descomptedetall = $descomptedetall;

        return $this;
    }

    /**
     * Get descomptedetall
     *
     * @return string 
     */
    public function getDescomptedetall()
    {
        return $this->descomptedetall;
    }

    /**
     * Set anotacions
     *
     * @param string $anotacions
     * @return EntityComandaDetall
     */
    public function setAnotacions($anotacions)
    {
        $this->anotacions = $anotacions;

        return $this;
    }

    /**
     * Get anotacions
     *
     * @return string 
     */
    public function getAnotacions()
    {
        return $this->anotacions;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return EntityComandaDetall
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
     * @return EntityComandaDetall
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
     * Set comanda
     *
     * @param \FecdasBundle\Entity\EntityComanda $comanda
     * @return EntityComandaDetall
     */
    public function setComanda(\FecdasBundle\Entity\EntityComanda $comanda = null)
    {
        $this->comanda = $comanda;

        return $this;
    }

    /**
     * Get comanda
     *
     * @return \FecdasBundle\Entity\EntityComanda 
     */
    public function getComanda()
    {
        return $this->comanda;
    }

    /**
     * Set producte
     *
     * @param \FecdasBundle\Entity\EntityProducte $producte
     * @return EntityComandaDetall
     */
    public function setProducte(\FecdasBundle\Entity\EntityProducte $producte = null)
    {
        $this->producte = $producte;

        return $this;
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
	
}
