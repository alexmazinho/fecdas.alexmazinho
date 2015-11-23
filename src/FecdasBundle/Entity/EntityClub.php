<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use FecdasBundle\Controller\BaseController;

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
	 * @ORM\Column(type="string", length=100)
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
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $compte; // Comptable
	
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
	 * @ORM\OneToMany(targetEntity="EntityComanda", mappedBy="club")
	 */
	protected $comandes;	// Owning side of the relationship

	/**
	 * @ORM\OneToMany(targetEntity="EntityRebut", mappedBy="club")
	 */
	protected $ingresos;	// Owning side of the relationship
	
	/*
	 * @ORM\OneToMany(targetEntity="EntityDuplicat", mappedBy="club")
	 */
	/*protected $duplicats;*/	// Owning side of the relationship
	
	/**
	 * @ORM\ManyToMany(targetEntity="EntityParteType", cascade={"remove", "persist"})
	 * @ORM\JoinTable(name="m_clubs_tipusparte",
	 *      joinColumns={@ORM\JoinColumn(name="club", referencedColumnName="codi")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="tipus", referencedColumnName="id")}
	 *      )
	 */
	protected $tipusparte;
	
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
	 * @ORM\Column(type="datetime", nullable=true)
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
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $dataalta;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datacreacio;
		
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datajunta; // Última
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $estatuts;
	
	/**
	 * 
	 * @ORM\Column(type="string", length=10)
	 */
	protected $registre;  // Num registre	
	
	/**
	 * 
	 * @ORM\Column(type="text")
	 */
	protected $carrecs;  // json	


	public function __construct() {
		$this->activat = true;
		$this->impressio = false;
		$this->limitcredit = 0;
		$this->romanent = 0;
		$this->totalpagaments = 0;
		$this->totalllicencies = 0;
		$this->totalduplicats = 0;
		$this->totalaltres = 0;
		$this->ajustsubvencions = 0;
		$this->usuaris = new \Doctrine\Common\Collections\ArrayCollection();
		$this->comandes = new \Doctrine\Common\Collections\ArrayCollection();
		$this->ingresos = new \Doctrine\Common\Collections\ArrayCollection();
		/*$this->duplicats = new \Doctrine\Common\Collections\ArrayCollection();*/
		$this->tipusparte = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->codi;
	}
	
	/**
	 * Get partes
	 *
	 * @return Doctrine\Common\Collections\ArrayCollection
	 */
	public function getPartes()
	{
		$partes = array();
		foreach ($this->comandes as $comanda) {
			if ($comanda->esParte()) $partes[] = $comanda;
		}
		return $partes;
	}
	
	/**
	 * Dades del club any actual. Opcionalment comprova errors
	 *
	 * @return array
	 */
	
	public function getDadesCurrent($errors = false, $update = false)
	{
		$dades = array('errors' => array());
		
		$ncomandes = 0;
		$npartes = 0;
		$nduplicats = 0;
		$naltres = 0;
		$npagatsweb = 0;
		$npagatsmanual = 0;
		$nllicencies = 0;
		$totalpagaments = 0;
		$totalimport = 0;
		$totalimportpartes = 0;
		$totalimportduplicats = 0;
		$totalimportaltres = 0;
		
		if ($errors == true) {
			/* Afegir errors de configuració */
			if (count($this->tipusparte) == 0) $dades['errors'][] = "Aquest club no té cap tipus de parte activat per tramitar</br>";
			if (count($this->usuaris) == 0) $dades['errors'][] = "Aquest club no té cap usuari activat per tramitar</br>";
		}
		
		
		foreach($this->comandes as $comanda) {
			if ($comanda->esBaixa() == false && $comanda->isCurrentYear()) {
				$ncomandes++;
				$importComanda = $comanda->getTotalDetalls();
				
				$totalimport += $importComanda;
				
				if ($comanda->comandaPagada()) {
					$rebut = $comanda->getRebut();
					
					$importRebut = $rebut->getImport();
						
					/*$importRebutsAnulats = 0;
					foreach($comanda->getRebutsanulacions() as $anulacio) {
						$importRebutsAnulats += $anulacio->getImport();
					}*/
						
					if ($rebut->getTipuspagament() == BaseController::TIPUS_PAGAMENT_TPV) $npagatsweb++;
					else $npagatsmanual++;
						
					if ($errors == true){
						// Varis, validacions imports i dades pagaments
						// Error si datapagament / estatpagament / dadespagament / importpagament algun no informat
						// Error si import calculat és null
						// Error si no coincideix import calculat del parte i import pagament
							
						if ($importRebut == 0) {
							$dades['errors'][] = "(Rebut import 0,00 €) Rebut: ".$rebut->getNumRebut()." (Comanda: ".$comanda->getNumComanda().")";
						}
					}
				}
				
				if ($comanda->comandaConsolidada() == true) {
					$factura = $comanda->getFactura();
					
					if ($factura == null) $dades['errors'][] = "(Comanda sense factura) Comanda: ".$comanda->getNumComanda();
					else {
						if ($factura->getImport() == 0) 
							$dades['errors'][] = "(Factura import 0,00 €) Factura:".$factura->getNumFactura()." (Comanda: ".$comanda->getNumComanda().")";
					}
				}
				
				if ($comanda->esDuplicat() ) {
					$nduplicats = 0;
					$totalimportduplicats += $importComanda;
				}
				
				if ($comanda->esAltre() ) {
					$naltres = 0;
					$totalimportaltres += $importComanda;
				}
				
				if ($comanda->esParte() ) {
					$npartes++;
					$totalimportpartes += $importComanda;
					
					$parte = $comanda;

					$nllicencies +=  $parte->getNumLlicencies();
					
					/* Només mirar sincronitzats */
					$auxImportParte = $parte->getTotalDetalls();

					if ($errors == true){
						// Import parte i import rebuts
						if ($auxImportParte != $importComanda) 
							$dades['errors'][] = "(Parte/Comanda imports diferents) Comanda: ".$comanda->getNumComanda()." (".$auxImportParte." <> ".$importComanda.")";
					}
				}
			}
		}
		
		$totalpagaments += $this->getTotalIngresos(); // Ingresos no associat a comandes
		
		$dades['comandes'] = $ncomandes;
		$dades['partes'] = $npartes;
		$dades['duplicats'] = $nduplicats;
		$dades['altres'] = $naltres;
		$dades['pagatsweb'] = $npagatsweb;
		$dades['pagatsmanual'] = $npagatsmanual;
		$dades['pagats'] = $npagatsweb + $npagatsmanual;
		$dades['llicencies'] = $nllicencies;
		
		$dades['pagaments'] = $totalpagaments;  // Total suma import rebuts any en curs
		$dades['import'] = $totalimport;  // Total suma preu comandes any en curs
		$dades['importpartes'] = $totalimportpartes; // Total suma preu partes any en curs
		$dades['importduplicats'] = $totalimportduplicats; // Total suma preu duplicats any en curs
		$dades['importaltres'] = $totalimportaltres; // Total suma preu altres any en curs
		$dades['saldocalculat'] = $totalpagaments + $this->ajustsubvencions - $this->romanent - $totalimport;
		 
		 
		$saldoDif = abs($dades['saldocalculat'] - $this->getSaldo()); 
		 
		if ($saldoDif > 0.01 && $update == true) {
			$this->totalpagaments = $totalpagaments;
			$this->totalllicencies = $totalimportpartes;
			$this->totalduplicats = $totalimportduplicats;
			$this->totalaltres = $totalimportaltres;
			
			$saldoDif = 0;
		} 
		 
		if ($errors == true){
			// Saldos no quadren
			if ($saldoDif > 0.01) {
				$dades['errors'][] = "(Saldo calculat <> saldo club) Diferència: ".$saldoDif." (".$dades['saldocalculat']." <> ".$this->getSaldo().")";
			}
		}
		 
		return $dades;
	}
	
	/**
	 * Retorna el saldo del club amb les dades del gestor
	 *
	 * @return decimal
	 */
	public function getSaldo() {
		return round($this->totalpagaments + $this->ajustsubvencions - $this->romanent - $this->totalllicencies - $this->totalduplicats - $this->totalaltres, 2);
	}
	
	/**
	 * Retorna l'import dels ingresos no associats a cap comanda
	 *
	 * @return decimal
	 */
	public function getTotalIngresos() {
		$totalimport = 0;
		foreach($this->ingresos as $ingres) {
			if ($ingres->isCurrentYear()) $totalimport += $ingres->getImport();
		}
		 
		return round($totalimport, 2);
	}
	
	/**
	 * Dades del club des de certa data,fins una data, per un tipus
	 *
	 * @return array
	 */
	public function getDadesDesde($tipus, $desde, $fins)
	{
		if ($desde == null) $desde = \DateTime::createFromFormat('Y-m-d', date('Y') . '-01-01');
		if ($fins == null) $fins = \DateTime::createFromFormat('Y-m-d', date('Y') . '-12-31');
		
		/* Recollir estadístiques */
		$stat = array();
		$stat['ltotal'] = 0;	// Llicències total
		$stat['vigents'] = 0;	// Partes vigents
		$stat['lvigents'] = 0;	// llicències vigents
		 
		foreach($this->comandes as $comanda) {
			$parte_iter = $comanda;
			/*if ($parte_iter->esParte() && $parte_iter->getDatabaixa() == null and
			 $parte_iter->getDataalta()->format('Y-m-d') >= $desde->format('Y-m-d') and
			 $parte_iter->getDataalta()->format('Y-m-d') <= $fins->format('Y-m-d') and
			 $parte_iter->getTipus()->getId() == $tipus ) {
			 $nlic = $parte_iter->getNumLlicencies();
			 if ($nlic > 0) {
			 $stat['ltotal'] +=  $nlic;
			 if ($parte_iter->isVigent()) {
			 $stat['lvigents'] +=  $nlic;
			 $stat['vigents']++;
			 }
			 }
			 }*/
	
			if ($parte_iter->esParte() && !$parte_iter->esBaixa() &&
				$parte_iter->getDataalta()->format('Y-m-d') >= $desde->format('Y-m-d') &&
				$parte_iter->getDataalta()->format('Y-m-d') <= $fins->format('Y-m-d')) {
	
				if ( $parte_iter->getTipus() != null && 
					 ( $tipus == 0 || $parte_iter->getTipus()->getId() == $tipus )) {
					$nlic = $parte_iter->getNumLlicencies();
					if ($nlic > 0) {
						$stat['ltotal'] +=  $nlic;
						if ($parte_iter->isVigent()) {
							$stat['lvigents'] +=  $nlic;
							$stat['vigents']++;
						}
					}
				}
			}
		}
		return $stat;
	}
	
	/**
	 * Missatge llista de partes
	 *
	 * @return string
	 */
	public function getInfoLlistat() {
		if ($this->estat->getCodi() == BaseController::CLUB_PAGAMENT_IMMEDIAT) return "*Les tramitacions tindran validesa quan es confirmi el seu pagament";
		if ($this->estat->getCodi() == BaseController::CLUB_SENSE_TRAMITACIO) return "*Per poder fer tràmits en aquest sistema, cal que us poseu en contacte amb la FECDAS";
		return "";
	}
	
	/**
	 * Indica si el club pot tramitar llicències
	 *
	 * @return boolean
	 */
	public function potTramitar() {
		return $this->estat->getCodi() != BaseController::CLUB_SENSE_TRAMITACIO;
	}
	
	/**
	 * Indica si els partes del club queden pendents de pagament
	 *
	 * @return boolean
	 */
	public function pendentPagament() {
		return $this->estat->getCodi() != BaseController::CLUB_PAGAMENT_DIFERIT;
	}
	
	/**
	 * Indica si cal controlar el crèdit del club
	 *
	 * @return boolean
	 */
	public function controlCredit() {
		return $this->estat->getCodi() == BaseController::CLUB_PAGAMENT_DIFERIT;
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
     * @param FecdasBundle\Entity\EntityClubType $tipus
     */
    public function setTipus(\FecdasBundle\Entity\EntityClubType $tipus)
    {
        $this->tipus = $tipus;
    }

    /**
     * Get tipus
     *
     * @return FecdasBundle\Entity\EntityClubType 
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
     * Set compte
     *
     * @param integer $compte
     */
    public function setCompte($compte)
    {
    	$this->compte = $compte;
    }
    
    /**
     * Get compte
     *
     * @return integer
     */
    public function getCompte()
    {
    	return $this->compte;
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
     * @param FecdasBundle\Entity\EntityUser $user
     */
    public function addEntityUser(\FecdasBundle\Entity\EntityUser $user)
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
     * Get comandes
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getComandes()
    {
    	return $this->comandes;
    }
    
    /**
     * Add comanda
     *
     * @param FecdasBundle\Entity\EntityComanda $comanda
     */
    public function addEntityComanda(\FecdasBundle\Entity\EntityComanda $comanda)
    {
    	$comanda->setClub($this);
    	$this->comandes->add($comanda);
    }
    
    /**
     * Set comandes
     * 
     * @param \Doctrine\Common\Collections\ArrayCollection $comandes
     */
    public function setComandes(\Doctrine\Common\Collections\ArrayCollection $comandes)
    {
    	$this->comandes = $comandes;
    	foreach ($comandes as $comanda) {
    		$comanda->setClub($this);
    	}
    }
    
	
	
	   /**
     * Get ingresos
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getIngresos()
    {
    	return $this->ingresos;
    }
    
    /**
     * Add ingrés
     *
     * @param FecdasBundle\Entity\EntityRebut $ingres
     */
    public function addEntityRebut(\FecdasBundle\Entity\EntityRebut $ingres)
    {
    	$ingres->setClub($this);
    	$this->ingresos->add($ingres);
    }
    
    /**
     * Set ingresos
     * 
     * @param \Doctrine\Common\Collections\ArrayCollection $ingresos
     */
    public function setIngresos(\Doctrine\Common\Collections\ArrayCollection $ingresos)
    {
    	$this->ingresos = $ingresos;
    	foreach ($ingresos as $ingres) {
    		$ingres->setClub($this);
    	}
    }
    
	
	
    /**
     * Get duplicats
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    /*public function getDuplicats()
    {
    	return $this->duplicats;
    }*/
    
    /*public function setDuplicats(\Doctrine\Common\Collections\ArrayCollection $duplicats)
    {
    	$this->duplicats = $duplicats;
    	foreach ($duplicats as $duplicat) {
    		$duplicat->setClub($this);
    	}
    }*/
    
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
     * @param FecdasBundle\Entity\EntityParteType $tipusparte
     */
    public function addTipusparte(\FecdasBundle\Entity\EntityParteType $tipusparte)
    {
    	$this->tipusparte->add($tipusparte);
    }
    
    /**
     * Remove tipusparte
     *
     * @param FecdasBundle\Entity\EntityParteType $tipusparte
     */
    public function removeTipusparte(\FecdasBundle\Entity\EntityParteType $tipusparte)
    {
    	$this->tipusparte->removeElement($tipusparte);
    }
    
    
    public function setTipusparte(\Doctrine\Common\Collections\ArrayCollection $tipusparte)
    {
    	$this->tipusparte = $tipusparte;
    }
    
     
    /**
     * Set estat
     *
     * @param FecdasBundle\Entity\EntityClubEstat $estat
     */
    public function setEstat(\FecdasBundle\Entity\EntityClubEstat $estat)
    {
    	$this->estat = $estat;
    }
    
    /**
     * Get estat
     *
     * @return FecdasBundle\Entity\EntityClubEstat
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
     * Set totalduplicats
     *
     * @param decimal $totalduplicats
     */
    public function setTotalduplicats($totalduplicats)
    {
    	$this->totalduplicats = $totalduplicats;
    }
    
    /**
     * Get totalduplicats
     *
     * @return decimal
     */
    public function getTotalduplicats()
    {
    	return $this->totalduplicats;
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
     * Set ajustsubvencions
     *
     * @param decimal $ajustsubvencions
     */
    public function setAjustsubvencions($ajustsubvencions)
    {
    	$this->ajustsubvencions = $ajustsubvencions;
    }
    
    /**
     * Get ajustsubvencions
     *
     * @return decimal
     */
    public function getAjustsubvencions()
    {
    	return $this->ajustsubvencions;
    }


	/**
     * Set dataalta
     *
     * @param date $dataalta
     */
    public function setDataalta($dataalta)
    {
        $this->dataalta = $dataalta;
    }

    /**
     * Get dataalta
     *
     * @return date 
     */
    public function getDataalta()
    {
        return $this->dataalta;
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
	
	/**
     * Set datacreacio
     *
     * @param date $datacreacio
     */
    public function setDatacreacio($datacreacio)
    {
        $this->datacreacio = $datacreacio;
    }

    /**
     * Get datacreacio
     *
     * @return date 
     */
    public function getDatacreacio()
    {
        return $this->datacreacio;
    }
	
	/**
     * Set datajunta
     *
     * @param date $datajunta
     */
    public function setDatajunta($datajunta)
    {
        $this->datajunta = $datajunta;
    }

    /**
     * Get datajunta
     *
     * @return date 
     */
    public function getDatajunta()
    {
        return $this->datajunta;
    }	
	
	 /**
     * Set estatuts
     *
     * @param boolean $estatuts
     */
    public function setEstatuts($estatuts)
    {
    	$this->estatuts = $estatuts;
    }
    
    /**
     * Get estatuts
     *
     * @return boolean
     */
    public function getEstatuts()
    {
    	return $this->estatuts;
    }
	
	
	/**
     * Set registre
     *
     * @param string $registre
     */
    public function setRegistre($registre)
    {
        $this->registre = $registre;
    }

    /**
     * Get registre
     *
     * @return string 
     */
    public function getRegistre()
    {
        return $this->registre;
    }
	
	/**
     * Set carrecs
     *
     * @param string $carrecs
     */
    public function setcarrecs($carrecs)
    {
        $this->carrecs = $carrecs;
    }

    /**
     * Get carrecs
     *
     * @return string 
     */
    public function getcarrecs()
    {
        return $this->carrecs;
    }
	


    /**
     * Add usuaris
     *
     * @param \FecdasBundle\Entity\EntityUser $usuaris
     * @return EntityClub
     */
    public function addUsuari(\FecdasBundle\Entity\EntityUser $usuaris)
    {
        $this->usuaris[] = $usuaris;

        return $this;
    }

    /**
     * Remove usuaris
     *
     * @param \FecdasBundle\Entity\EntityUser $usuaris
     */
    public function removeUsuari(\FecdasBundle\Entity\EntityUser $usuaris)
    {
        $this->usuaris->removeElement($usuaris);
    }

    /**
     * Add comandes
     *
     * @param \FecdasBundle\Entity\EntityComanda $comandes
     * @return EntityClub
     */
    public function addComande(\FecdasBundle\Entity\EntityComanda $comandes)
    {
        $this->comandes[] = $comandes;

        return $this;
    }

    /**
     * Remove comandes
     *
     * @param \FecdasBundle\Entity\EntityComanda $comandes
     */
    public function removeComande(\FecdasBundle\Entity\EntityComanda $comandes)
    {
        $this->comandes->removeElement($comandes);
    }
}
