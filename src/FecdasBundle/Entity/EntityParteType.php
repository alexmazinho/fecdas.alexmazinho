<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_tipusparte") 
 * 
 * @author alex
 *
 */
class EntityParteType {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $id;	// 1 ... 11
	
	/**
	 * @ORM\Column(type="string", length=50)
	 */
	protected $codi;
	
	/**
	 * @ORM\Column(type="string", length=50)
	 */
	protected $descripcio;
	
	/**
	 * @ORM\Column(type="string", length=100)
	 */
	protected $titol;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $es365;

	/**
	 * @ORM\Column(type="string", length=5)
	 */
	protected $inici;
	
	/**
	 * @ORM\Column(type="string", length=5)
	 */
	protected $final;
	
	/**
	 * @ORM\Column(type="decimal", precision=4, scale=2)
	 */
	protected $iva;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $actiu;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $admin;
	
	/**
	 * @ORM\Column(type="string", length=100)
	 */
	protected $polissa;
	
	/**
	 * @ORM\Column(type="string", length=2)
	 */
	protected $template;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityCategoria", mappedBy="tipusparte")
	 */
	protected $categories;
	
	public function __construct() {
		$this->categories = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->descripcio;
	}

	/**
	 * validar preus any
	 *
	 * @return boolean
	 */
	public function validarPreusAny($any)
	{
		foreach ($this->categories as $categoria) {
			if ($categoria->getPreuAny($any) == 0) return false;
		}
		return true;
	}
	
	
	/**
	 * és llicència dia
	 *
	 * @return boolean
	 */
	public function esLlicenciaDia()
	{
		return $this->id == BaseController::ID_LLICENCIES_DIA;
	}

	
	/**
	 * és llicència col·laboradors FECDAS A compte de despeses 659.0002 de FECDAS. 
	 * Tots els productes de les diferents categories han d'anar al compte de despeses 
	 *
	 * @return boolean
	 */
	public function esLlicenciaDespeses()
	{
	    foreach ($this->categories as $categoria) {
	        if (!$categoria->esProducteDespeses()) return false;
	    }
	    return true;
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
     * Set codi
     *
     * @param string $codi
     */
    public function setCodi($codi)
    {
        $this->codi = $codi;
    }

    /**
     * Get codi
     *
     * @return string 
     */
    public function getCodi()
    {
        return $this->codi;
    }

    /**
     * Set descripcio
     *
     * @param string $descripcio
     */
    public function setDescripcio($descripcio)
    {
        $this->descripcio = $descripcio;
    }

    /**
     * Get descripcio
     *
     * @return string 
     */
    public function getDescripcio()
    {
        return $this->descripcio;
    }

    /**
     * Set titol
     *
     * @param string $titol
     */
    public function setTitol($titol)
    {
    	$this->titol = $titol;
    }
    
    /**
     * Get titol
     *
     * @return string
     */
    public function getTitol()
    {
    	return $this->titol;
    }
    
    /**
     * Set es365
     *
     * @param boolean $es365
     */
    public function setEs365($es365)
    {
    	$this->es365 = $es365;
    }
    
    /**
     * Get es365
     *
     * @return boolean
     */
    public function getEs365()
    {
    	return $this->es365;
    }
    
    /**
     * Set iva
     *
     * @param decimal $iva
     */
    public function setIva($iva)
    {
    	$this->iva = $iva;
    }
    
    /**
     * Get iva
     *
     * @return decimal
     */
    public function getIva()
    {
    	return $this->iva;
    }
    
    /**
     * Set inici
     *
     * @param string $inici
     */
    public function setInici($inici)
    {
    	$this->inici = $inici;
    }
    
    /**
     * Get inici
     *
     * @return string
     */
    public function getInici()
    {
    	return $this->inici;
    }
    
    /**
     * Set final
     *
     * @param string $final
     */
    public function setFinal($final)
    {
    	$this->final = $final;
    }
    
    /**
     * Get final
     *
     * @return string
     */
    public function getFinal()
    {
    	return $this->final;
    }
    
    /**
     * Set actiu
     *
     * @param boolean $actiu
     */
    public function setActiu($actiu)
    {
    	$this->actiu = $actiu;
    }
    
    /**
     * Get actiu
     *
     * @return boolean
     */
    public function getActiu()
    {
    	return $this->actiu;
    }
    
    /**
     * Set admin
     *
     * @param boolean $admin
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;
    }
    
    /**
     * Get admin
     *
     * @return boolean
     */
    public function getAdmin()
    {
        return $this->admin;
    }
    
	/**
     * Set polissa
     *
     * @param string $polissa
     */
    public function setPolissa($polissa)
    {
        $this->polissa = $polissa;
    }

    /**
     * Get polissa
     *
     * @return string 
     */
    public function getPolissa()
    {
        return $this->polissa;
    }
	
	/**
     * Set template
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Get template
     *
     * @return string 
     */
    public function getTemplate()
    {
        return $this->template;
    }
	
    /**
     * Add categories
     *
     * @param \FecdasBundle\Entity\EntityCategoria $categories
     */
    public function addEntityCategoria(\FecdasBundle\Entity\EntityCategoria $categories)
    {
        $this->categories[] = $categories;
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCategories()
    {
        return $this->categories;
    }
}