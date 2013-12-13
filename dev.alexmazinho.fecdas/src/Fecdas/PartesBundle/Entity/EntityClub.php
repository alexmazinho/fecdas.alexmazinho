<?php
namespace Fecdas\PartesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_clubs")
 * 
 * @author alex
 *
 */
class EntityClub {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="string", length=6)
	 */
	protected $codi;	// fedeclub, CATXXX
	
	/**
	 * @ORM\Column(type="string", length=30)
	 * @Assert\NotBlank()
	 */
	protected $nom;
	
	/**
     * @ORM\ManyToOne(targetEntity="EntityClubType")
     * @ORM\JoinColumn(name="tipus", referencedColumnName="id")
     */
	protected $tipus;	// FK taula m_tipusclub

	/**
	 * @ORM\Column(type="integer", length=20, nullable=true)
	 */
	protected $telefon;

	/**
	 * @ORM\Column(type="integer", length=20, nullable=true)
	 */
	protected $fax;

	/**
	 * @ORM\Column(type="integer", length=20, nullable=true)
	 */
	protected $mobil;
	
	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 * @Assert\NotBlank()
	 */
	protected $mail;
	
	/**
	 * @ORM\Column(type="string", length=150, nullable=true)
	 */
	protected $web;
	
	/**
	 * @ORM\Column(type="string", length=12)
	 * @Assert\NotBlank()
	 */
	protected $cif;

	/**
	 * @ORM\Column(type="string", length=75, nullable=true)
	 */
	protected $addradreca;

	/**
	 * @ORM\Column(type="string", length=35, nullable=true)
	 */
	protected $addrpob;
	
	/**
	 * @ORM\Column(type="string", length=5, nullable=true)
	 */
	protected $addrcp;
	
	/**
	 * @ORM\Column(type="string", length=20, nullable=true)
	 */
	protected $addrprovincia;

	/**
	 * @ORM\Column(type="string", length=75, nullable=true)
	 */
	protected $addradrecacorreu;
	
	/**
	 * @ORM\Column(type="string", length=35, nullable=true)
	 */
	protected $addrpobcorreu;
	
	/**
	 * @ORM\Column(type="string", length=5, nullable=true)
	 */
	protected $addrcpcorreu;
	
	/**
	 * @ORM\Column(type="string", length=20, nullable=true)
	 */
	protected $addrprovinciacorreu;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $activat;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityUser", mappedBy="club")
	 */
	protected $usuaris;	// Owning side of the relationship
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityParte", mappedBy="club")
	 */
	protected $partes;	// Owning side of the relationship
	
	/**
	 * @ORM\ManyToMany(targetEntity="EntityParteType")
	 * @ORM\JoinTable(name="m_clubs_tipusparte",
	 *      joinColumns={@ORM\JoinColumn(name="club", referencedColumnName="codi")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="tipus", referencedColumnName="id")}
	 *      )
	 */
	private $tipusparte;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityClubEstat")
	 * @ORM\JoinColumn(name="estat", referencedColumnName="codi")
	 */
	protected $estat;	// FK taula m_clubestats
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $impressio;
	
	/**
	 * @ORM\Column(type="decimal", precision=6, scale=2)
	 */
	protected $limitcredit;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $limitnotificacio;

	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $romanent;

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
	protected $totalkits;
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $totalaltres;
	
	public function __construct() {
		$this->activat = true;
		$this->usuaris = new \Doctrine\Common\Collections\ArrayCollection();
		$this->partes = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tipusparte = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->codi;
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
     * Set nom
     *
     * @param string $nom
     */
    public function setNom($nom)
    {
        $this->nom = $nom;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set tipus
     *
     * @param Fecdas\PartesBundle\Entity\EntityClubType $tipus
     */
    public function setTipus(\Fecdas\PartesBundle\Entity\EntityClubType $tipus)
    {
        $this->tipus = $tipus;
    }

    /**
     * Get tipus
     *
     * @return Fecdas\PartesBundle\Entity\EntityClubType 
     */
    public function getTipus()
    {
        return $this->tipus;
    }
    
    /**
     * Get informació club en llistes desplegables
     *
     * @return string
     */
    public function getLlistaText()
    {
    	return $this->codi . "-" . $this->nom;
    }

    public function _toString()
    {
    	return $this->codi . "-" . $this->nom;
    }
    
    
    /**
     * Set telefon
     *
     * @param integer $telefon
     */
    public function setTelefon($telefon)
    {
        $this->telefon = $telefon;
    }

    /**
     * Get telefon
     *
     * @return integer 
     */
    public function getTelefon()
    {
        return $this->telefon;
    }

    /**
     * Set fax
     *
     * @param integer $fax
     */
    public function setFax($fax)
    {
    	$this->fax = $fax;
    }
    
    /**
     * Get fax
     *
     * @return integer
     */
    public function getFax()
    {
    	return $this->fax;
    }

    /**
     * Set mobil
     *
     * @param integer $mobil
     */
    public function setMobil($mobil)
    {
    	$this->mobil = $mobil;
    }
    
    /**
     * Get mobil
     *
     * @return integer
     */
    public function getMobil()
    {
    	return $this->mobil;
    }
    
    /**
     * Set cif
     *
     * @param string $cif
     */
    public function setCif($cif)
    {
        $this->cif = $cif;
    }

    /**
     * Get cif
     *
     * @return string 
     */
    public function getCif()
    {
        return $this->cif;
    }

    /**
     * Set mail
     *
     * @param string $mail
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
    }

    /**
     * Get mail
     *
     * @return string 
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set web
     *
     * @param string $web
     */
    public function setWeb($web)
    {
        $this->web = $web;
    }

    /**
     * Get web
     *
     * @return string 
     */
    public function getWeb()
    {
        return $this->web;
    }

    /**
     * Set addradreca
     *
     * @param string $addradreca
     */
    public function setAddradreca($addradreca)
    {
        $this->addradreca = $addradreca;
    }

    /**
     * Get addradreca
     *
     * @return string 
     */
    public function getAddradreca()
    {
        return $this->addradreca;
    }

    /**
     * Set addrpob
     *
     * @param string $addrpob
     */
    public function setAddrpob($addrpob)
    {
        $this->addrpob = $addrpob;
    }

    /**
     * Get addrpob
     *
     * @return string 
     */
    public function getAddrpob()
    {
        return $this->addrpob;
    }

    /**
     * Set addrcp
     *
     * @param string $addrcp
     */
    public function setAddrcp($addrcp)
    {
        $this->addrcp = $addrcp;
    }

    /**
     * Get addrcp
     *
     * @return string 
     */
    public function getAddrcp()
    {
        return $this->addrcp;
    }

    /**
     * Set addrprovincia
     *
     * @param string $addrprovincia
     */
    public function setAddrprovincia($addrprovincia)
    {
        $this->addrprovincia = $addrprovincia;
    }

    /**
     * Get addrprovincia
     *
     * @return string 
     */
    public function getAddrprovincia()
    {
        return $this->addrprovincia;
    }
    
    /**
     * Set addradrecacorreu
     *
     * @param string $addradrecacorreu
     */
    public function setAddradrecacorreu($addradrecacorreu)
    {
    	$this->addradrecacorreu = $addradrecacorreu;
    }
    
    /**
     * Get addradrecacorreu
     *
     * @return string
     */
    public function getAddradrecacorreu()
    {
    	return $this->addradrecacorreu;
    }
    
    /**
     * Set addrpobcorreu
     *
     * @param string $addrpobcorreu
     */
    public function setAddrpobcorreu($addrpobcorreu)
    {
    	$this->addrpobcorreu = $addrpobcorreu;
    }
    
    /**
     * Get addrpobcorreu
     *
     * @return string
     */
    public function getAddrpobcorreu()
    {
    	return $this->addrpobcorreu;
    }
    
    /**
     * Set addrcpcorreu
     *
     * @param string $addrcpcorreu
     */
    public function setAddrcpcorreu($addrcpcorreu)
    {
    	$this->addrcpcorreu = $addrcpcorreu;
    }
    
    /**
     * Get addrcpcorreu
     *
     * @return string
     */
    public function getAddrcpcorreu()
    {
    	return $this->addrcpcorreu;
    }
    
    /**
     * Set addrprovinciacorreu
     *
     * @param string $addrprovincia
     */
    public function setAddrprovinciacorreu($addrprovinciacorreu)
    {
    	$this->addrprovinciacorreu = $addrprovinciacorreu;
    }
    
    /**
     * Get addrprovinciacorreu
     *
     * @return string
     */
    public function getAddrprovinciacorreu()
    {
    	return $this->addrprovinciacorreu;
    }
    
    /**
     * Set activat
     *
     * @param boolean $activat
     */
    public function setActivat($activat)
    {
    	$this->activat = $activat;
    }
    
    /**
     * Get activat
     *
     * @return boolean
     */
    public function getActivat()
    {
    	return $this->activat;
    }
    
    /**
     * Get usuaris
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getUsuaris()
    {
    	return $this->usuaris;
    }
    
    /**
     * Add user
     *
     * @param Fecdas\PartesBundle\Entity\EntityUser $user
     */
    public function addEntityUser(\Fecdas\PartesBundle\Entity\EntityUser $user)
    {
    	$user->setClub($this);
    	$this->usuaris->add($user);
    }
    
    
    public function setUsuaris(\Doctrine\Common\Collections\ArrayCollection $usuaris)
    {
    	$this->usuaris = $usuaris;
    	foreach ($usuaris as $usuari) {
    		$usuari->setClub($this);
    	}
    }

    /**
     * Get partes
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getPartes()
    {
    	return $this->partes;
    }
    
    /**
     * Add parte
     *
     * @param Fecdas\PartesBundle\Entity\EntityParte $parte
     */
    /*
    public function addEntityParte(\Fecdas\PartesBundle\Entity\EntityParte $parte)
    {
    	$parte->setClub($this);
    	$this->partes->add($parte);
    }*/
    
    
    public function setPartes(\Doctrine\Common\Collections\ArrayCollection $partes)
    {
    	$this->partes = $partes;
    	foreach ($partes as $parte) {
    		$parte->setClub($this);
    	}
    }
    
    
    /**
     * Get tipusparte
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getTipusparte()
    {
    	return $this->tipusparte;
    }
    
    /**
     * Add tipusparte
     *
     * @param Fecdas\PartesBundle\Entity\EntityParteType $tipusparte
     */
    public function addTipusparte(\Fecdas\PartesBundle\Entity\EntityParteType $tipusparte)
    {
    	$this->tipusparte->add($tipusparte);
    }
    
    
    public function setTipusparte(\Doctrine\Common\Collections\ArrayCollection $tipusparte)
    {
    	$this->tipusparte = $tipusparte;
    }
    
    
    /**
     * Set estat
     *
     * @param Fecdas\PartesBundle\Entity\EntityClubEstat $estat
     */
    public function setEstat(\Fecdas\PartesBundle\Entity\EntityClubEstat $estat)
    {
    	$this->estat = $estat;
    }
    
    /**
     * Get estat
     *
     * @return Fecdas\PartesBundle\Entity\EntityClubEstat
     */
    public function getEstat()
    {
    	return $this->estat;
    }
    
    
    /**
     * Set impressio
     *
     * @param boolean $impressio
     */
    public function setImpressio($impressio)
    {
    	$this->impressio = $impressio;
    }
    
    /**
     * Get impressio
     *
     * @return boolean
     */
    public function getImpressio()
    {
    	return $this->impressio;
    }
    
    /**
     * Set limitcredit
     *
     * @param decimal $limitcredit
     */
    public function setLimitcredit($limitcredit)
    {
    	$this->limitcredit = $limitcredit;
    }
    
    /**
     * Get limitcredit
     *
     * @return decimal
     */
    public function getLimitcredit()
    {
    	return $this->limitcredit;
    }
    
    /**
     * Set limitnotificacio
     *
     * @param datetime $limitnotificacio
     */
    public function setLimitnotificacio($limitnotificacio)
    {
    	$this->limitnotificacio = $limitnotificacio;
    }
    
    /**
     * Get limitnotificacio
     *
     * @return datetime
     */
    public function getLimitnotificacio()
    {
    	return $this->limitnotificacio;
    }
    
    /**
     * Set romanent
     *
     * @param decimal $romanent
     */
    public function setRomanent($romanent)
    {
    	$this->romanent = $romanent;
    }
    
    /**
     * Get romanent
     *
     * @return decimal
     */
    public function getRomanent()
    {
    	return $this->romanent;
    }
    
    /**
     * Set totalpagaments
     *
     * @param decimal $totalpagaments
     */
    public function setTotalpagaments($totalpagaments)
    {
    	$this->totalpagaments = $totalpagaments;
    }
    
    /**
     * Get totalpagaments
     *
     * @return decimal
     */
    public function getTotalpagaments()
    {
    	return $this->totalpagaments;
    }
    
    /**
     * Set totalllicencies
     *
     * @param decimal $totalllicencies
     */
    public function setTotalllicencies($totalllicencies)
    {
    	$this->totalllicencies = $totalllicencies;
    }
    
    /**
     * Get totalllicencies
     *
     * @return decimal
     */
    public function getTotalllicencies()
    {
    	return $this->totalllicencies;
    }

    /**
     * Set totalkits
     *
     * @param decimal $totalkits
     */
    public function setTotalkits($totalkits)
    {
    	$this->totalkits = $totalkits;
    }
    
    /**
     * Get totalkits
     *
     * @return decimal
     */
    public function getTotalkits()
    {
    	return $this->totalkits;
    }
    
    /**
     * Set totalaltres
     *
     * @param decimal $totalaltres
     */
    public function setTotalaltres($totalaltres)
    {
    	$this->totalaltres = $totalaltres;
    }
    
    /**
     * Get totalaltres
     *
     * @return decimal
     */
    public function getTotalaltres()
    {
    	return $this->totalaltres;
    }
    
    /**
     * Dades del club any actual. Opcionalment comprova errors
     *
     * @return array
     */
    
    public function getDadesCurrent($errors = false)
    {
    	$dades = array();
    	$npartes = 0;
    	$npartespagatsweb = 0;
    	$nllicencies = 0;
    	$nimport = 0;
    	$nimportweb = 0;
    	$dades['err_facturadata'] = array();
    	$dades['err_facturanum'] = array();
    	$dades['err_sincro'] = array();
    	$dades['err_imports'] = array();
    	$dades['err_config'] = '';
    	foreach($this->partes as $c => $parte_iter) {
    		if ($parte_iter->getDatabaixa() == null && $parte_iter->isCurrentYear()) {
    			$npartes++;
    			$nllicencies +=  $parte_iter->getNumLlicencies();
    			$nimport += $parte_iter->getPreuTotalIVA();
    			if ($parte_iter->isPagat() && 
    				$parte_iter->getEstatpagament() == "TPV OK" &&
    				$parte_iter->getImportPagament() != null) {
    				$nimportweb += $parte_iter->getImportPagament();
    				$npartespagatsweb++;
    			}
    			if ($errors){ 
    				// Varis, validacions imports i dades pagaments
    				// Error si datapagament / estatpagament / dadespagament / importpagament algun no informat
    				// Error si import calculat és null
    				// Error si no coincideix import calculat del parte i import pagament
    				if (($parte_iter->getDatapagament() != null ||
    					$parte_iter->getEstatpagament() != null ||
    					$parte_iter->getDadespagament() != null ||
    					$parte_iter->getImportpagament() != null) && 
    					($parte_iter->getDatapagament() == null || 
    					$parte_iter->getEstatpagament() == null || 
    					$parte_iter->getDadespagament() == null || 
    					$parte_iter->getImportpagament() == null)) {
    					$dades['err_imports'][] = "(falten dades pagament) " . $parte_iter->getId() . " - " . $parte_iter->getDataalta()->format('d/m/Y');
    				}
    				
    				if ($parte_iter->getImportparte() == null) 
    					$dades['err_imports'][] = "(import incorrecte) " . $parte_iter->getId() . " - " . $parte_iter->getDataalta()->format('d/m/Y');
    				else {
	    				if ($parte_iter->getImportpagament() != null && $parte_iter->getImportpagament() != $parte_iter->getImportparte()) 
	    					$dades['err_imports'][] = "(imports no coincidents) " . $parte_iter->getId() . " - " . $parte_iter->getDataalta()->format('d/m/Y');
    				}
    				// Només si tenen més d'una setmana
    				$weekAgo = new \DateTime(date("Y-m-d", strtotime("-1 week")));
    				if ($parte_iter->getDataentrada() < $weekAgo) {
    					// No sincronitzats. Afegir relacio
    					if ($parte_iter->getIdparteAccess() == null) $dades['err_sincro'][] = $parte_iter->getDataalta()->format('d/m/Y');
    					else {
		    				// 	Sense dades de facturació
	    					if ($parte_iter->getDatafacturacio() == null) $dades['err_facturadata'][] = $parte_iter->getNumrelacio();
	    					if ($parte_iter->getNumfactura() == null) $dades['err_facturanum'][] = $parte_iter->getNumrelacio();
    					}
    				}
    			}
    		}
    	}
    	if ($errors) {
    		/* Afegir errors de configuració */
    		if (count($this->tipusparte) == 0) $dades['err_config'] .= "Aquest club no té cap tipus de parte activat per tramitar</br>";
    		if (count($this->usuaris) == 0) $dades['err_config'] .= "Aquest club no té cap usuari activat per tramitar</br>";
    	}
    	$dades['partes'] = $npartes;
    	$dades['pagats'] = $npartespagatsweb;
    	$dades['llicencies'] = $nllicencies;
    	$dades['import'] = $nimport;
    	$dades['importweb'] = $nimportweb;
    	$dades['deutegestor'] = $this->getDeutegestor();
    	return $dades;
    }
    
    /**
     * Retorna el deute del club
     *
     * @return decimal
     */
    public function getDeutegestor() {
    	return round($this->romanent + $this->totalllicencies + $this->totalkits + $this->totalaltres - $this->totalpagaments, 2);
    }
    
    /**
     * Missatge llista de partes
     *
     * @return string
     */
    public function getInfoLlistat() {
    	if ($this->estat->getCodi() == 'IMME') return "*Les tramitacions tindran validesa quan es confirmi el seu pagament";
    	if ($this->estat->getCodi() == 'NOTR') return "*Per poder fer tràmits en aquest sistema, cal que us poseu en contacte amb la FECDAS";
    	return "";
    }
    
    /**
     * Indica si el club pot tramitar llicències
     *
     * @return boolean
     */
    public function potTramitar() {
    	return $this->estat->getCodi() != 'NOTR';
    }
    
    /**
     * Indica si els partes del club queden pendents de pagament 
     *
     * @return boolean
     */
    public function pendentPagament() {
    	return $this->estat->getCodi() != 'DIFE';
    }
    
    /**
     * Indica si cal controlar el crèdit del club 
     *
     * @return boolean
     */
    public function controlCredit() {
    	return $this->estat->getCodi() == 'DIFE';
    }
    
}