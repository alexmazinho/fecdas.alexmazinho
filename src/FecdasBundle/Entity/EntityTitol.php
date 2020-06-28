<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="m_titols")
 *
 * @author alex
 *
 */

//@ORM\Table(name="m_titols", uniqueConstraints={@ORM\UniqueConstraint(name="codi_idx", columns={"codi"})})
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
	 * @ORM\Column(type="string", length=100)
	 */
	protected $titol;

	/**
	 * @ORM\Column(type="string", length=2, nullable=false)
	 */
	protected $tipus;   //  BU – Títol bussejador, TE – Títol tècnic/Instructor, ES - Especialitat, 'CO' - Competició

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
	protected $curs;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $online;

	public function __construct() {
		$this->curs = true;
		$this->online = true;
		
		$this->requeriments = new \Doctrine\Common\Collections\ArrayCollection();
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getCodi() . " " . $this->getTitol();
	}

	/**
	 * Get persona info. as csv data
	 *
	 * @return string
	 */
	public function csvRow($i = 0)
	{
	    return array(
	        $i,
	        $this->codi,
	        $this->titol,
	        $this->organisme,
	        '',
	        '',
	        '',
	        ''
	    );
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
     * Es instructor
     *
     * @return boolean 
     */
    public function esInstructor()
    {
        return $this->tipus == BaseController::TIPUS_TITOL_TECNIC;
    }

    /**
     * Es comissari, jutge, tècnic ...
     *
     * @return boolean
     */
    public function esCompeticio()
    {
        return $this->tipus == BaseController::TIPUS_COMPETICIO;
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
	
	public function getRequerimentByNum($num)
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
	 * @return EntityCarnet
	 */
	public function getCarnet() {
		return $this->carnet;
	}

	/**
	 * @param EntityCarnet $carnet
	 */
	public function setCarnet(EntityCarnet $carnet) {
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
     * @param EntityRequeriment $requeriments
     */
    public function addRequeriments(EntityRequeriment $requeriments)
    {
        $this->requeriments->add($requeriments);
    }

    /**
     * Get requeriments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRequeriments()
    {
        return $this->requeriments;
    }

	/**
	 * @return boolean
	 */
	public function getCurs() {
		return $this->curs;
	}

	/**
	 * @param boolean $curs
	 */
	public function setCurs($curs) {
		$this->curs = $curs;
	}
	
	/**
	 * @return boolean
	 */
	public function getOnline() {
	    return $this->online;
	}
	
	/**
	 * @param boolean $online
	 */
	public function setOnline($online) {
	    $this->online = $online;
	}

	public static function getTitolsSortedBy(&$titols, $sort = 'id', $direction = 'asc')
	{
	    usort($titols, function($a, $b) use ($sort, $direction) {
	        if ($a === $b) {
	            return 0;
	        }
	        $true = $direction == 'asc'? 1:-1;
	        $false = $true * -1;
	        $result = 0;
	        switch ($sort) {
	            case 'codi':
	                $result = ($a->getCodi() > $b->getCodi())? $true:$false;
	                break;
	            case 'titol':
	                $result = ($a->getTitol() > $b->getTitol())? $true:$false;
	                break;
	            case 'organisme':
	                if ($a->getOrganisme() != $b->getOrganisme())
	                   $result = ($a->getOrganisme() > $b->getOrganisme())? $true:$false;
	                else 
	                   $result = ($a->getTitol() > $b->getTitol())? 1:-1;
	                break;
	            default:
	                $result = ($a->getId() > $b->getId())? $true:$false;
	                break;
	        }
	        
	        return $result;
	    });
	}
}
