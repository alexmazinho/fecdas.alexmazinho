<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Enviaments a comptabilitat
 * 
 * @ORM\Entity
 * @ORM\Table(name="m_comptabilitat")
 * 
 * @author alex
 *
 */
class EntityComptabilitat {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentaris; // En cas de baixa per exemple
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataenviament;
	
	/**
	 * @ORM\Column(type="text")
	 */
	protected $fitxer; 
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $factures;
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $rebuts;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datadesde;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datafins;
	
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		
		$this->dataenviament = new \DateTime();
		$this->factures = 0;
		$this->rebuts = 0;
	
		// Hack per permetre múltiples constructors
		$a = func_get_args();
		$i = func_num_args();
	
		if ($i > 0 && method_exists($this,$f='__constructParams')) {
			call_user_func_array(array($this,$f),$a);
		}
	}
	
	
	public function __constructParams($fitxer = '', $datadesde = null, $datafins = null) {
		if ($datadesde == null) $datadesde = new \DateTime();
		if ($datafins == null) $datafins = new \DateTime();
		
		$this->datadesde = $datadesde;
		$this->datafins = $datafins;
		$this->fitxer = $fitxer;
	}
	
	public function esBaixa()
	{
		return $this->databaixa != null;
	}
	
	/**
	 * Get total apunts
	 *
	 * @return integer
	 */
	public function getApunts()
	{
		return $this->factures + $this->rebuts;
	}
	
	/**
	 * Get Info comanda
	 *
	 * @return string
	 */
	public function getInfoComptabilitat()
	{
		return $this->dataenviament->format("d/m/Y H:m:s");
	}
	
	/**
	 * Get Text comanda
	 *
	 * @return string
	 */
	public function getTextComptabilitat()
	{
		$text = " ". $this->getInfoComptabilitat();	
		$text .= "  (des de ".($this->datadesde == null?'--':$this->datadesde->format("d/m/Y"));
		$text .= " fins ".($this->datafins == null?'':$this->datafins->format("d/m/Y")). ". ".$this->factures." factures, ".$this->rebuts." rebuts)";
		return $text;
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
     * Set comentaris
     *
     * @param string $comentaris
     * @return EntityComptabilitat
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
     * Set dataenviament
     *
     * @param \DateTime $dataenviament
     * @return EntityComptabilitat
     */
    public function setDataenviament($dataenviament)
    {
        $this->dataenviament = $dataenviament;

        return $this;
    }

    /**
     * Set fitxer
     *
     * @param string $fitxer
     * @return EntityComptabilitat
     */
    public function setFitxer($fitxer)
    {
    	$this->fitxer = $fitxer;
    
    	return $this;
    }
    
    /**
     * Get fitxer
     *
     * @return string
     */
    public function getFitxer()
    {
    	return $this->fitxer;
    }
    
    
    /**
     * Get dataenviament
     *
     * @return \DateTime 
     */
    public function getDataenviament()
    {
        return $this->dataenviament;
    }

    /**
     * Set datadesde
     *
     * @param \DateTime $datadesde
     * @return EntityComptabilitat
     */
    public function setDatadesde($datadesde)
    {
        $this->datadesde = $datadesde;

        return $this;
    }

    /**
     * Get datadesde
     *
     * @return \DateTime 
     */
    public function getDatadesde()
    {
        return $this->datadesde;
    }

    /**
     * Set datafins
     *
     * @param \DateTime $datafins
     * @return EntityComptabilitat
     */
    public function setDatafins($datafins)
    {
    	$this->datafins = $datafins;
    
    	return $this;
    }
    
    /**
     * Get datafins
     *
     * @return \DateTime
     */
    public function getDatafins()
    {
    	return $this->datafins;
    }
    
    /**
     * Set databaixa
     *
     * @param \DateTime $databaixa
     * @return EntityComptabilitat
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
     * Get factures
     *
     * @return integer
     */
    public function getFactures()
    {
        return $this->factures;
    }
    
    /**
     * Set factures
     *
     * @param integer $factures
     */
    public function setFactures($factures)
    {
    	$this->factures = $factures;
    }
    
    /**
     * Get rebuts
     *
     * @return integer
     */
    public function getRebuts()
    {
    	return $this->rebuts;
    }
    
    /**
     * Set rebuts
     *
     * @param integer
     */
    public function setRebuts($rebuts)
    {
    	$this->rebuts = $rebuts;
    }
}
