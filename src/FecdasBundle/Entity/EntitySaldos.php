<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_saldos")
 * 
 * @author alex
 *
 */
class EntitySaldos {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub", inversedBy="comandes")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;	// FK taula m_saldos
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $entrades;  	// rebuts des de darrer registre
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $sortides;	// factures des de darrer registre
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $romanent;

	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $exercici; // Comptable

	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $totalpagaments;
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $totalllicencies;
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $totalduplicats;
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $totalaltres;

	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $ajustsubvencions;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentaris;
	
	/**
	 * @ORM\Column(type="date")
	 */
	protected $dataregistre;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	

	public function __construct($club = null, $dataregistre = null ) {
		$this->id = 0;
		$this->club = $club;
		$this->entrades = 0;
		$this->sortides = 0;
		$this->romanent = $club != null?$club->getRomanent():0;
		$this->totalpagaments = $club != null?$club->getTotalpagaments():0;
		$this->totalllicencies = $club != null?$club->getTotalllicencies():0;
		$this->totalduplicats = $club != null?$club->getTotalduplicats():0;
		$this->totalaltres = $club != null?$club->getTotalaltres():0;
		$this->ajustsubvencions = $club != null?$club->getAjustsubvencions():0;
		$this->comentaris = '';
		$this->dataregistre = ($dataregistre != null?$dataregistre:new \DateTime('today'));
		$this->exercici = $club != null?$club->getExercici():($dataregistre != null?$this->dataregistre->format('Y'): (new \DateTime('today'))->format('Y'));
		$this->dataentrada = new \DateTime('now');
		
	}
	
	public function __toString() {
		return $this->id.$this->club->getCodi().$this->dataentrada->format('Y-m-d H:i');
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
	
	
	/**
     * Set club
     *
     * @param \FecdasBundle\Entity\EntityClub $club
     */
    public function setClub(\FecdasBundle\Entity\EntityClub $club = null)
    {
        $this->club = $club;
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
     * Set entrades (comptables, segons data rebut i data factura)
     *
     * @param string $entrades
     */
    public function setEntrades($entrades)
    {
    	$this->entrades = $entrades;
    }
    
    /**
     * Get entrades (comptables, segons data rebut i data factura)
     *
     * @return string
     */
    public function getEntrades()
    {
    	return $this->entrades;
    }
	
	/**
     * Set sortides (comptables, segons data rebut i data factura)
     *
     * @param string $sortides
     */
    public function setSortides($sortides)
    {
    	$this->sortides = $sortides;
    }
    
    /**
     * Get sortides (comptables, segons data rebut i data factura)
     *
     * @return string
     */
    public function getSortides()
    {
    	return $this->sortides;
    }
	
	/**
     * Set exercici
     *
     * @param integer $exercici
     */
    public function setExercici($exercici)
    {
    	$this->exercici = $exercici;
    }
    
    /**
     * Get exercici
     *
     * @return integer
     */
    public function getExercici()
    {
    	return $this->exercici;
    }
	
	
    /**
     * Set romanent
     *
     * @param string $romanent
     */
    public function setRomanent($romanent)
    {
    	$this->romanent = $romanent;
    }
    
    /**
     * Get romanent
     *
     * @return string
     */
    public function getRomanent()
    {
    	return $this->romanent;
    }
    
    /**
     * Set totalpagaments
     *
     * @param string $totalpagaments
     */
    public function setTotalpagaments($totalpagaments)
    {
    	$this->totalpagaments = $totalpagaments;
    }
    
    /**
     * Get totalpagaments
     *
     * @return string
     */
    public function getTotalpagaments()
    {
    	return $this->totalpagaments;
    }
    
    /**
     * Set totalllicencies
     *
     * @param string $totalllicencies
     */
    public function setTotalllicencies($totalllicencies)
    {
    	$this->totalllicencies = $totalllicencies;
    }
    
    /**
     * Get totalllicencies
     *
     * @return string
     */
    public function getTotalllicencies()
    {
    	return $this->totalllicencies;
    }

    /**
     * Set totalduplicats
     *
     * @param string $totalduplicats
     */
    public function setTotalduplicats($totalduplicats)
    {
    	$this->totalduplicats = $totalduplicats;
    }
    
    /**
     * Get totalduplicats
     *
     * @return string
     */
    public function getTotalduplicats()
    {
    	return $this->totalduplicats;
    }
    
    /**
     * Set totalaltres
     *
     * @param string $totalaltres
     */
    public function setTotalaltres($totalaltres)
    {
    	$this->totalaltres = $totalaltres;
    }
    
    /**
     * Get totalaltres
     *
     * @return string
     */
    public function getTotalaltres()
    {
    	return $this->totalaltres;
    }
    
    /**
     * Set ajustsubvencions
     *
     * @param string $ajustsubvencions
     */
    public function setAjustsubvencions($ajustsubvencions)
    {
    	$this->ajustsubvencions = $ajustsubvencions;
    }
    
    /**
     * Get ajustsubvencions
     *
     * @return string
     */
    public function getAjustsubvencions()
    {
    	return $this->ajustsubvencions;
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
		
}
