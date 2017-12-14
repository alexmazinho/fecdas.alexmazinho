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
	 * @ORM\Column(type="text", nullable=true)
	 * @Assert\NotBlank()
	 */
	protected $mail;
	
	/**
	 * @ORM\Column(type="string", length=150, nullable=true)
	 */
	protected $web;
	
	/**
	 * @ORM\Column(type="string", length=20)
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
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	protected $addrcomarca;
	
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
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	protected $addrcomarcacorreu;

	/**
	 * @ORM\Column(type="string", length=20, nullable=true)
	 */
	protected $addrprovinciacorreu;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $activat;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityUserClub", mappedBy="club")
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
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $exercici; // Comptable, validesa dels imports següents

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
	 * @ORM\Column(type="datetime")
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
	 * @ORM\Column(type="string", length=10, nullable=true)
	 */
	protected $registre;  // Num registre	
	
	/**
	 * 
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $carrecs;  // json	
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $comptabilitat;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityCurs", mappedBy="club")
	 */
	protected $cursos;	// Owning side of the relationship

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $notes;      // Camp de notes per als administradors
	

	public function __construct() {
		$this->activat = true;
		$this->impressio = false;
		$this->comptabilitat = false;
		$this->limitcredit = 0;
		$this->romanent = 0;
		$this->totalpagaments = 0;
		$this->totalllicencies = 0;
		$this->totalduplicats = 0;
		$this->totalaltres = 0;
		$this->ajustsubvencions = 0;
		$this->dataalta = new \DateTime();
		$this->exercici = $this->dataalta->format('Y');
		$this->usuaris = new \Doctrine\Common\Collections\ArrayCollection();
		$this->comandes = new \Doctrine\Common\Collections\ArrayCollection();
		$this->ingresos = new \Doctrine\Common\Collections\ArrayCollection();
		$this->cursos = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tipusparte = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function __toString() {
		return $this->codi;
	}
	
	
	/**
     * Es Federacio
     *
     * @return boolean 
     */
    public function esFederacio()
    {
        return $this->codi == BaseController::CODI_FECDAS;
    }
	
	/**
     * Add user, role, metadata
     *
     * @param EntityUser $user
     */
    public function addUsuariRole($user, $role)
    {
    	$userClubRole = new EntityUserClub($this, $user, $role);
    	
    	$user->addClub($userClubRole);
    	$this->usuaris->add($userClubRole);
		
		return $userClubRole;
    }
    
	
	/**
	 * Get partes
	 *
	 * @return \Doctrine\Common\Collections\ArrayCollection
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
     * Get mails
     *
     * @return array 
     */
    public function getMails()
    {
    	return explode(";", trim($this->mail));
    }
	
	
	/**
     * Get roles diferents
     *
     * @return 
     */
    public function getRolsDistinct($baixes = false)
    {
    	$roles = BaseController::getRoles( $this->esFederacio() || $this->codi == BaseController::CODI_CLUBTEST);
		
		return $roles;
	}
	
	/**
     * Get usuaris diferents
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getUsuarisDistinct($baixes = false)
    {
    	$roles = $this->getRolsDistinct();
		
    	$usuarisArray = array();
    	foreach ($this->usuaris as $userClubRole) {

    		if ($baixes || (!$baixes && !$userClubRole->anulat())) {
	    			
	    		$userClub = $userClubRole->getUsuari();
				$role = $userClubRole->getRole();
				if ($baixes || (!$baixes && !$userClub->anulat())) {
					
					if (!isset($usuarisArray[$userClub->getId()])) {
						$usuarisArray[$userClub->getId()] = array('userclub' => $userClub, 'rols' => array_map(function ($n) { return null; }, $roles) ); // Crea array objectes amb claus els valors de $roles i valor null
					}
					$usuarisArray[$userClub->getId()]['rols'][$role] = $userClubRole;
				}
			}
    	}
	
		return $usuarisArray;
    }
    
	/**
	 * Dades del club any actual. Opcionalment comprova errors
	 *
	 * @return array
	 */
	
	public function getDadesCurrent($errors = false, $update = false, $current = 0)
	{
		if ($current == 0) $current = date('Y');	
			
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
		
		$totalfactures = 0;
		$totalanulacions = 0;
		$correccioanulacions = 0;  // Anul·lacions facturades any current comandes anys anteriors
		
		if ($errors == true) {
			/* Afegir errors de configuració */
			if (count($this->tipusparte) == 0) $dades['errors'][] = "Aquest club no té cap tipus de parte activat per tramitar</br>";
			if (count($this->usuaris) == 0) $dades['errors'][] = "Aquest club no té cap usuari activat per tramitar</br>";
		}
		
		foreach($this->comandes as $comanda) {
			if ($comanda->esBaixa() == false && $comanda->comandaConsolidada() == true && $comanda->getAny() >= ($current -1)) { // Limitar comandes any actual i anterior
				
				$factura = $comanda->getFactura();
						
				if ($factura == null) {
					if ($comanda->getAny() == $current) $dades['errors'][] = "(Comanda sense factura) Comanda: ".$comanda->getNumComanda();
				} else {
					if ($factura->getAny() == $current) {
						// Comandes facturades current any
						$ncomandes++;
						$importComanda = $comanda->getTotalComanda();
						
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
						
						if ($factura->getImport() == 0) 
								$dades['errors'][] = "(Factura import 0,00 €) Factura:".$factura->getNumFactura()." (Comanda: ".$comanda->getNumComanda().")";
								
						$totalfactures += $factura->getImport();
						
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
				
				foreach ($comanda->getFacturesanulacions() as $anulacio) {
					if ($anulacio->getAny() == $current) {
							
						if ($anulacio->getImport() == 0) $dades['errors'][] = "(Anul·lació import 0,00 €) Factura:".$anulacio->getNumFactura()." (Comanda: ".$comanda->getNumComanda().")";
						else {
							$totalanulacions += $anulacio->getImport();
						
							if ($comanda->getAny() < $current) $correccioanulacions += $anulacio->getImport();
						}
					}
				}
			}
			if ($comanda->esBaixa() == true && $comanda->comandaConsolidada() == true && $comanda->getAny() >= ($current -1)) {
				$factura = $comanda->getFactura();
				if ($factura != null) {
					$importComanda = $comanda->getTotalComanda();
					
					if ( abs($importComanda) > 0.01) 
								$dades['errors'][] = "(Factura BAIXA import comanda incorrecte != 0 => ".$importComanda.") Factura:".$factura->getNumFactura()." (Comanda: ".$comanda->getNumComanda().")";	
						
					$importAnulacio = 0; 	
					foreach ($comanda->getFacturesanulacions() as $anulacio) {
						//if ($anulacio->getAny() == $current) {
							$importAnulacio += $anulacio->getImport();
							
							if ($anulacio->getAny() == $current && $comanda->getAny() < $current) {
								$totalanulacions += $anulacio->getImport();	
								$correccioanulacions += $anulacio->getImport();
							}
						//}
					}
					if ( abs($factura->getImport() + $importAnulacio) > 0.01) 
								$dades['errors'][] = "(Factura BAIXA import anul·lacio incorrecte".abs($factura->getImport() - $importComanda).") Factura:".$factura->getNumFactura()." (Comanda: ".$comanda->getNumComanda().")";
					
				}
				
			}
		}
	
		$totalpagaments += $this->getTotalIngresos($current); // Ingresos no associat a comandes
		
		$dades['comandes'] = $ncomandes;
		$dades['partes'] = $npartes;
		$dades['duplicats'] = $nduplicats;
		$dades['altres'] = $naltres;
		$dades['pagatsweb'] = $npagatsweb;
		$dades['pagatsmanual'] = $npagatsmanual;
		$dades['pagats'] = $npagatsweb + $npagatsmanual;
		$dades['llicencies'] = $nllicencies;
		
		$dades['pagaments'] = $totalpagaments;  // Total suma import rebuts any en curs
		$dades['import'] = $totalimport + $correccioanulacions;  // Total suma preu comandes any en curs
		$dades['importpartes'] = $totalimportpartes; // Total suma preu partes any en curs
		$dades['importduplicats'] = $totalimportduplicats; // Total suma preu duplicats any en curs
		$dades['importaltres'] = $totalimportaltres; // Total suma preu altres any en curs
		$dades['correccioanulacions'] = $correccioanulacions; // Anul·lacions facturades any current comandes anys anteriors
		$dades['saldocalculat'] = $totalpagaments + $this->ajustsubvencions + $this->romanent - $totalimport + $correccioanulacions;
		
		$dades['importfactures'] 	= $totalfactures;
		$dades['importanulacions'] 	= $totalanulacions;
		 
		 
		$saldoDif = abs($dades['saldocalculat'] - $this->getSaldo()); 
		 
		if ($saldoDif > 0.01 && $update == true) {
			$this->totalpagaments = $totalpagaments;
			$this->totalllicencies = $totalimportpartes;
			$this->totalduplicats = $totalimportduplicats;
			$this->totalaltres = $totalimportaltres;
			
			$saldoDif = 0;
		} 
		 
		/* Els totals poden incloure partes de l'any següent i el saldo desquadra 
		 * 
		 * if ($errors == true){
			// Saldos no quadren
			if ($saldoDif > 0.01) {
				$dades['errors'][] = "(Saldo calculat <> saldo club) Diferència: ".$saldoDif." (".$dades['saldocalculat']." <> ".$this->getSaldo().")";
			}
		}*/
		 
		return $dades;
	}
	
	
	/**
	 * Dades del club any següent. Només poden ser partes tramitats a final d'any pel següent
	 *
	 * @return array
	 */
	
	/*public function getDadesAnySeguent($current = 0)
	{
		if ($current == 0) $current = date('Y');	
		
		$dades = array('errors' => array());
		
		$ncomandes = 0;
		$totalpagaments = 0;
		$totalimport = 0;
		$totalfactures = 0;
		$totalanulacions = 0;
		
		foreach($this->comandes as $comanda) {
			if ($comanda->esBaixa() == false && $comanda->getAny() > $current) {
				$ncomandes++;
				$importComanda = $comanda->getTotalComanda();
				
				$totalimport += $importComanda;
				
				if ($comanda->comandaConsolidada() == true) {
					$factura = $comanda->getFactura();
					
					if ($factura == null) $dades['errors'][] = "(Comanda sense factura) Comanda: ".$comanda->getNumComanda();
					else {
						if ($factura->getImport() == 0) 
							$dades['errors'][] = "(Factura import 0,00 €) Factura:".$factura->getNumFactura()." (Comanda: ".$comanda->getNumComanda().")";
						
						if ($factura->getAny() > $current) $totalfactures += $factura->getImport();
					}
					
					foreach ($comanda->getFacturesanulacions() as $anulacio) {
						if ($anulacio->getAny() > $current) {
							if ($anulacio->getImport() == 0) 
								$dades['errors'][] = "(Anul·lació import 0,00 €) Factura:".$anulacio->getNumFactura()." (Comanda: ".$comanda->getNumComanda().")";
							
							$totalanulacions += $anulacio->getImport();
						}
					}
				}
			}
		}

		$totalpagaments += $this->getTotalIngresos( $current + 1 ); // Ingresos futurs
		
		$dades['comandes'] 			= $ncomandes;
		$dades['pagaments'] 		= $totalpagaments; 
		$dades['importcomandes'] 	= $totalimport;  
		$dades['importfactures'] 	= $totalfactures;
		$dades['importanulacions'] 	= $totalanulacions;
		
		return $dades;
	}*/
	
	/**
	 * Retorna el saldo del club amb les dades del gestor
	 *
	 * @return string
	 */
	public function getSaldo() {
		return round($this->totalpagaments + $this->ajustsubvencions + $this->romanent - $this->totalllicencies - $this->totalduplicats - $this->totalaltres, 2);
	}
	
	/**
	 * Retorna l'import dels ingresos no associats a cap comanda
	 *
	 * @return string
	 */
	public function getTotalIngresos($current = 0)
	{
		if ($current == 0) $current = date('Y');	
		
		$totalimport = 0;
		foreach($this->ingresos as $ingres) {
			if ($ingres->getAny() == $current) $totalimport += $ingres->getImport();
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
	    if (!$this->potTramitar()) return "*Per poder fer tràmits en aquest sistema, cal que us poseu en contacte amb la FECDAS";
	    if ($this->pendentPagament()) return "*Les tramitacions tindran validesa quan es confirmi el seu pagament";
	    
		return "";
	}
	
	/**
	 * Indica si el club pot tramitar llicències
	 *
	 * @return boolean
	 */
	public function potTramitar() {
		return $this->estat != null && $this->estat->getCodi() != BaseController::CLUB_SENSE_TRAMITACIO;
	}
	
	/**
	 * Indica si els partes del club queden pendents de pagament
	 *
	 * @return boolean
	 */
	public function pendentPagament() {
		return $this->estat == null || $this->estat->getCodi() != BaseController::CLUB_PAGAMENT_DIFERIT;
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
        $this->nom = substr($nom,0,100);
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
     * @param EntityClubType $tipus
     */
    public function setTipus(EntityClubType $tipus)
    {
        $this->tipus = $tipus;
    }

    /**
     * Get tipus
     *
     * @return EntityClubType 
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
        $this->cif = substr($cif,0,20);
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
        $this->web = substr($web,0,150);
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
        $this->addradreca =  substr($addradreca,0,75);
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
        $this->addrpob = substr($addrpob,0,35);
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
        $this->addrcp = substr($addrcp,0,5);
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
     * Set addrcomarca
     *
     * @param string $addrcomarca
     */
    public function setAddrcomarca($addrcomarca)
    {
        $this->addrcomarca = substr($addrcomarca,0,50);
    }
	
    /**
     * Get addrcomarca
     *
     * @return string
     */
    public function getAddrcomarca()
    {
    	return $this->addrcomarca;
    }
    
    /**
     * Set addrprovincia
     *
     * @param string $addrprovincia
     */
    public function setAddrprovincia($addrprovincia)
    {
        $this->addrprovincia = substr($addrprovincia,0,20);
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
        $this->addradrecacorreu = substr($addradrecacorreu,0,75);
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
        $this->addrpobcorreu = substr($addrpobcorreu,0,35);
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
        $this->addrcpcorreu = substr($addrcpcorreu,0,5);
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
     * Set addrcomarcacorreu
     *
     * @param string $addrcomarcacorreu
     */
    public function setAddrcomarcacorreu($addrcomarcacorreu)
    {
        $this->addrcomarcacorreu = substr($addrcomarcacorreu,0,50);
    }
	
    /**
     * Get addrcomarcacorreu
     *
     * @return string
     */
    public function getAddrcomarcacorreu()
    {
    	return $this->addrcomarcacorreu;
    }
    
    /**
     * Set addrprovinciacorreu
     *
     * @param string $addrprovincia
     */
    public function setAddrprovinciacorreu($addrprovinciacorreu)
    {
        $this->addrprovinciacorreu = substr($addrprovinciacorreu,0,20);
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
     * Get comandes
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getComandes()
    {
    	return $this->comandes;
    }
    
    /**
     * Add comanda
     *
     * @param EntityComanda $comanda
     */
    public function addEntityComanda(EntityComanda $comanda)
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
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getIngresos()
    {
    	return $this->ingresos;
    }
    
    /**
     * Add ingrés
     *
     * @param EntityRebut $ingres
     */
    public function addEntityRebut(EntityRebut $ingres)
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
     * Get tipusparte
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getTipusparte()
    {
    	return $this->tipusparte;
    }
    
    /**
     * Add tipusparte
     *
     * @param EntityParteType $tipusparte
     */
    public function addTipusparte(EntityParteType $tipusparte)
    {
    	$this->tipusparte->add($tipusparte);
    }
    
    /**
     * Remove tipusparte
     *
     * @param EntityParteType $tipusparte
     */
    public function removeTipusparte(EntityParteType $tipusparte)
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
     * @param EntityClubEstat $estat
     */
    public function setEstat(EntityClubEstat $estat)
    {
    	$this->estat = $estat;
    }
    
    /**
     * Get estat
     *
     * @return EntityClubEstat
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
     * @param string $limitcredit
     */
    public function setLimitcredit($limitcredit)
    {
    	$this->limitcredit = $limitcredit;
    }
    
    /**
     * Get limitcredit
     *
     * @return string
     */
    public function getLimitcredit()
    {
    	return $this->limitcredit;
    }
    
    /**
     * Set limitnotificacio
     *
     * @param \DateTime $limitnotificacio
     */
    public function setLimitnotificacio($limitnotificacio)
    {
    	$this->limitnotificacio = $limitnotificacio;
    }
    
    /**
     * Get limitnotificacio
     *
     * @return \DateTime
     */
    public function getLimitnotificacio()
    {
    	return $this->limitnotificacio;
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
     * Set dataalta
     *
     * @param \DateTime $dataalta
     */
    public function setDataalta($dataalta)
    {
        $this->dataalta = $dataalta;
    }

    /**
     * Get dataalta
     *
     * @return \DateTime 
     */
    public function getDataalta()
    {
        return $this->dataalta;
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
	
	/**
     * Set datacreacio
     *
     * @param \DateTime $datacreacio
     */
    public function setDatacreacio($datacreacio)
    {
        $this->datacreacio = $datacreacio;
    }

    /**
     * Get datacreacio
     *
     * @return \DateTime 
     */
    public function getDatacreacio()
    {
        return $this->datacreacio;
    }
	
	/**
     * Set datajunta
     *
     * @param \DateTime $datajunta
     */
    public function setDatajunta($datajunta)
    {
        $this->datajunta = $datajunta;
    }

    /**
     * Get datajunta
     *
     * @return \DateTime 
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
    public function setCarrecs($carrecs)
    {
        $this->carrecs = $carrecs;
    }

    /**
     * Get carrecs
     *
     * @return string 
     */
    public function getCarrecs()
    {
        return $this->carrecs;
    }

    /**
     * Set notes
     *
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }
    
    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }
    
	/**
     * Set comptabilitat
     *
     * @param boolean $comptabilitat
     */
    public function setComptabilitat($comptabilitat)
    {
    	$this->comptabilitat = $comptabilitat;
    }
    
    /**
     * Get comptabilitat
     *
     * @return boolean
     */
    public function getComptabilitat()
    {
    	return $this->comptabilitat;
    }

	/**
     * Get usuaris
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getUsuaris()
    {
    	return $this->usuaris;	// userClubRoles
    }
    
    public function setUsuaris(\Doctrine\Common\Collections\ArrayCollection $usuaris)
    {
    	$this->usuaris = $usuaris;
    	foreach ($usuaris as $userClubRole) {
    		$userClubRole->setClub($this);
    	}
    }

    /**
     * Add comandes
     *
     * @param EntityComanda $comandes
     * @return EntityClub
     */
    public function addComande(EntityComanda $comandes)
    {
        $this->comandes[] = $comandes;

        return $this;
    }

    /**
     * Remove comandes
     *
     * @param EntityComanda $comandes
     */
    public function removeComande(EntityComanda $comandes)
    {
        $this->comandes->removeElement($comandes);
    }
	
	/**
     * Get cursos
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getCursos()
    {
    	return $this->cursos;
    }
    
	/**
     * Add cursos
     *
     * @param EntityCurs $curs
     */
    public function addCursos(EntityCurs $curs)
    {
        $this->cursos->add($curs);
    }
	
}
