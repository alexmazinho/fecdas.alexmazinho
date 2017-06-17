<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_titols", uniqueConstraints={@ORM\UniqueConstraint(name="codi_idx", columns={"codi"})})
 * 
 * @author alex
 *
 */
class EntityTitol {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityCarnet", inversedBy="titols")
	 * @ORM\JoinColumn(name="carnet", referencedColumnName="id")
	 */
	protected $carnet; // FK taula m_carnets

	/**
	 * @ORM\Column(type="string", length=10)
	 */
	protected $codi;

	/**
	 * @ORM\Column(type="string", length=20, nullable=true)
	 */
	protected $prefix;
	
	/**
	 * @ORM\Column(type="string", length=50)
	 */
	protected $titol;

	/**
	 * @ORM\Column(type="string", length=2, nullable=false)
	 */
	protected $tipus;   //  BU – Títol bussejador, TE – Títol tècnic, ES - Especialitat 

	/**
	 * @ORM\ManyToOne(targetEntity="EntityProducte")
	 * @ORM\JoinColumn(name="kit", referencedColumnName="id")
	 **/
	protected $kit;

	/**
	 * @ORM\Column(type="string", length=30, nullable=false)
	 */
	protected $organisme;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityTitol")
	 * @ORM\JoinColumn(name="equivalentcmas", referencedColumnName="id")
	 */
	protected $equivalentcmas; // FK taula m_titols  self referencing

	/**
	 * @ORM\OneToMany(targetEntity="EntityRequeriment", mappedBy="titol" )
	 */
	protected $requeriments;	// FK taula m_requeriments
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $actiu;

	public function __construct() {
		$this->actiu = true;
		
		$this->requeriments = new \Doctrine\Common\Collections\ArrayCollection();
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getCodi() . " " . $this->getTitol();
	}


	/**
     * Es CMAS
     *
     * @return boolean 
     */
    public function esCMAS()
    {
        return $this->organisme == BaseController::ORGANISME_CMAS;
    }

	/**
	 * Get informació títol en llistes desplegables
	 *
	 * @return string
	 */
	public function getLlistaText()
	{
		return ($this->esCMAS()?"":$this->getOrganisme())." ".$this->getCodi()." - ".$this->getTitol();
	}
	
	
	/**
     * kit necessari ? Si kit != null cal un kit per cada alumne dún curs d'aquest títol 
     *
     * @return boolean 
     */
    public function esKitNecessari()
    {
        return $this->kit != null;
    }
	
	
	public function getRequerimentsSortedByContextCategoria($context = '', $actius = true)
    {
    	/* Ordenats per context i categoria */
    	$arr = array();
    	foreach ($this->requeriments as $requeriment) {
    		$tipus = $requeriment->getRequeriment();
    		if ($tipus != null && 
    			($requeriment->actiu() || $actius == false) && 
    			($context == '' || strtolower($tipus->getContext()) == strtolower($context))) $arr[] = $requeriment;
    	}
		
    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
			$tipusA = $a->getRequeriment();
			$tipusB = $b->getRequeriment();
			
			return $tipusA->getId() - $tipusB->getId();
			/*
			if (strtolower($tipusA->getContext()) === strtolower($tipusB->getContext())) {
				if (strtolower($tipusA->getCategoria()) === strtolower($tipusB->getCategoria())) return $tipusB->getId() - $tipusA->getId();
				return strcmp($tipusA->getCategoria(), $tipusB->getCategoria());
			}
			return strcmp($tipusA->getContext(), $tipusB->getContext());*/
    	});
    	return $arr;
    }
	
	public function getRequerimentByTipus($num)
    {
    	foreach ($this->requeriments as $requeriment) {
    		$tipus = $requeriment->getRequeriment();
    		if ($num == $tipus->getId()) return $requeriment;
    	}
		return null;
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
	 * @return FecdasBundle\Entity\EntityCarnet
	 */
	public function getCarnet() {
		return $this->carnet;
	}

	/**
	 * @param FecdasBundle\Entity\EntityCarnet $carnet
	 */
	public function setCarnet(\FecdasBundle\Entity\EntityCarnet $carnet) {
		$this->carnet = $carnet;
	}

	/**
	 * @return string
	 */
	public function getCodi() {
		return $this->codi;
	}

	/**
	 * @param string $codi
	 */
	public function setCodi($codi) {
		$this->codi = $codi;
	}

	/**
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @param string $prefix
	 */
	public function setPrefix($prefix) {
		$this->prefix = $prefix;
	}

	/**
	 * @return string
	 */
	public function getTitol() {
		return $this->titol;
	}

	/**
	 * @param string $titol
	 */
	public function setTitol($titol) {
		$this->titol = $titol;
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
	 * Set kit
	 *
	 * @param \FecdasBundle\Entity\EntityProducte $kit
	 */
	public function setKit(\FecdasBundle\Entity\EntityProducte $kit = null)
	{
		$this->kit = $kit;
	}
	
	/**
	 * Get kit
	 *
	 * @return \FecdasBundle\Entity\EntityProducte
	 */
	public function getKit()
	{
		return $this->kit;
	}

	/**
     * Set organisme
     *
     * @param string $organisme
     */
    public function setOrganisme($organisme)
    {
        $this->organisme = $organisme;
    }

    /**
     * Get organisme
     *
     * @return string 
     */
    public function getOrganisme()
    {
        return $this->organisme;
    }

	/**
	 * Set equivalentcmas
	 *
	 * @param \FecdasBundle\Entity\EntityTitol $equivalentcmas
	 */
	public function setEquivalentcmas(\FecdasBundle\Entity\EntityTitol $equivalentcmas = null)
	{
		$this->equivalentcmas = $equivalentcmas;
	}
	
	/**
	 * Get equivalentcmas
	 *
	 * @return \FecdasBundle\Entity\EntityTitol
	 */
	public function getEquivalentcmas()
	{
		return $this->equivalentcmas;
	}


	/**
     * Add requeriments
     *
     * @param FecdasBundle\Entity\EntityRequeriment $requeriments
     */
    public function addRequeriments(\FecdasBundle\Entity\EntityRequeriment $requeriments)
    {
        $this->requeriments->add($requeriments);
    }

    /**
     * Get requeriments
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getRequeriments()
    {
        return $this->requeriments;
    }

	/**
	 * @return boolean
	 */
	public function getActiu() {
		return $this->actiu;
	}

	/**
	 * @param boolean $actiu
	 */
	public function setActiu($actiu) {
		$this->actiu = $actiu;
	}

}
