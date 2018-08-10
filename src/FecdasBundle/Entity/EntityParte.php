<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;


/**
 * @ORM\Entity
 * @ORM\Table(name="m_partes")
 * 
 * @author alex
 *
 */
class EntityParte extends EntityComanda {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $id;	

	/**
	 * @ORM\ManyToOne(targetEntity="EntityParteType")
	 * @ORM\JoinColumn(name="tipus", referencedColumnName="id")
	 */
	protected $tipus;	// FK taula m_tipuspartes
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $numrelacio;   // =================================================> PER ESBORRAR

	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub")
	 * @ORM\JoinColumn(name="clubparte", referencedColumnName="codi", nullable=false)
	 */
	protected $clubparte;	// FK taula m_clubs => pot ser NULL només informar en cas que club comanda sigui diferent
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataalta;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentari;  // Comentaris del pagament o del parte en general
	
	/**
	 * @ORM\Column(type="string", length=38, nullable=true)
	 */
	protected $idparte_access;		// =================================================> PER ESBORRAR

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $web;			// =================================================> PER ESBORRAR

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $renovat;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $pendent;

	/**
	 * @ORM\OneToMany(targetEntity="EntityLlicencia", mappedBy="parte")
	 */
	protected $llicencies;	// Owning side of the relationship
	
	
	public function __construct() {
		$this->web = true;
		$this->renovat = false;
		$this->pendent = false;
		$this->llicencies = new \Doctrine\Common\Collections\ArrayCollection();
		
		// Hack per permetre múltiples constructors
		parent::__construct(); // Sense paràmetres
		$a = func_get_args();
		$i = func_num_args();
		
		if ($i > 1 && method_exists($this,$f='__constructParams')) {
			call_user_func_array(array($this,$f),$a);
		}
		
		// Primer es crida al constructor de comanda
		$this->clubparte = $this->club; // Club de la comanda per defecte
	}
	

	public function cloneLlicencies($parteoriginal, $currentDate) {
		// Get current collection
		$llicencies = $parteoriginal->getLlicenciesSortedByName();
	
		$this->llicencies = new \Doctrine\Common\Collections\ArrayCollection();
		
		foreach ($llicencies as $llicencia_iter) {
			if (!$llicencia_iter->esBaixa()) {

				$cloneLlicencia = clone $llicencia_iter;
				/* Init camps */
				$cloneLlicencia->setDatamodificacio($currentDate);
				$cloneLlicencia->setDatacaducitat($this->getDataCaducitat());
				
				$cloneLlicencia->setImpresa(false);
				$cloneLlicencia->setDataimpressio(null);
				$cloneLlicencia->setMailenviat(false);
				$cloneLlicencia->setDatamail(null);				
				
				$this->llicencies->add($cloneLlicencia);
				$cloneLlicencia->setParte($this);
			}
		}
	}
	
	public function esParte()
	{
		return true;
	}
	
	/**
	 * Reescriptura
	 */
	protected function updateClubSaldoTipusComanda($import) {
		$this->club->setTotalllicencies($this->club->getTotalllicencies() + $import);
	}
	
	public function baixa()
	{
		parent::baixa(); // Baixa detalls comanda
		
		// Baixa llicències
		foreach ($this->llicencies as $llicencia) {
			if (!$llicencia->esBaixa()) {
				$llicencia->setDatabaixa(new \DateTime());
				$llicencia->setDatamodificacio(new \DateTime());
			}
		}
		$this->datamodificacio = new \DateTime();
		$this->databaixa = new \DateTime();
	}
	
	
	/**
	 * Get si és baixa
	 *
	 * @return boolean
	 */
	public function esBaixa()
	{
		return $this->databaixa != null;
	}
	
	/**
	 * Get si és sense càrrec
	 *
	 * @return boolean
	 */
	public function esSenseCarrec()
	{
	    return $this->tipus->getAdmin();
	}

	/**
	 * Get html total parte si no és de despeses o és admin
	 * Pels partes de despeses es mostra el text "0€ (sense cost)"
	 *
	 * @return double
	 */
	public function getTotalParte( $admin )
	{
	    if ($this->esSenseCarrec() && !$admin) return "0€<br/><span class='title-comment'>(sense cost)</span>";
	    
	    // Mostrar total comanda
	    $html = ""; 
	    if ($this->isFacturaModificada()) $html .= "<strike>".number_format($this->getTotalDetalls(),2, ',', '.')."€</strike><br/>";
	    $html .= number_format($this->getTotalComanda(),2, ',', '.')."€";
	    
	    return $html;
	}
	
	
	/**
	 * Es pot imprimir plàstic?
	 *
	 * @return boolean
	 */
	public function perImprimir()
	{
		/*if ($this->tipus->esLlicenciaDia()) return false;
		
		return $this->tipus->getTemplate() != '';*/
		
		return $this->tipus->getImprimible();
	}
	
	/**
	 * Parte imprès ?
	 *
	 * @return boolean
	 */
	public function getImpres()
	{
		// Pendent d'imprimir si alguna llicència pendent imprimir
		foreach ($this->llicencies as $llicencia) {
			if (!$llicencia->esBaixa() && !$llicencia->getImpresa()) return false;   
		}	
		return true;
	}
	
	/**
	 * Es pot enviar per mail al federat?
	 *
	 * @return boolean
	 */
	public function perEnviarFederat()
	{
		return $this->tipus->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_1 || 
				$this->tipus->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_2 ||
				$this->tipus->getTemplate() == BaseController::TEMPLATE_ESCOLAR ||
				$this->tipus->getTemplate() == BaseController::TEMPLATE_ESCOLAR_SUBMARINISME ||
				$this->tipus->getTemplate() == BaseController::TEMPLATE_GENERAL;
	}
	
	/**
	 * Get curs
	 *
	 * @return string
	 */
	public function getCurs()
	{
		$any = $this->getDataalta()->format('Y');
		$anyCaduca = $this->getDatacaducitat()->format('Y');
		
		if ($any == $anyCaduca) $any--;
		$anyCaduca = substr($anyCaduca."", 2);
		
		//return ($any == $anyCaduca?($anyCaduca-1).'-'.($anyCaduca):$any.'-'.($any + 1));
		return $any.'-'.$anyCaduca;
	}
	
	/**
	 * Get periode
	 *
	 * @return string
	 */
	public function getPeriode()
	{
	    $strTipus = "";
	    if ($this->getNumLlicencies() > 1) $strTipus .= $this->isAsseguranca()?" assegurances: ":" llicències: ";
	    else $strTipus .= $this->isAsseguranca()?" assegurança: ":" llicència: ";
	    
	    if ($this->getTipus()->esLlicenciaDia()) return "Data".$strTipus.$this->getDataCaducitat()->format('d/m/Y');
	    
	    return "Vigència".$strTipus.$this->getDataalta()->format('d/m/Y')." a ".$this->getDataCaducitat()->format('d/m/Y');
    }
	
	/**
	 * Get prefix albarà duplicats. Sobreescriptura
	 *
	 * @return string
	 */
	public function getPrefixAlbara()
	{
		return BaseController::PREFIX_ALBARA_LLICENCIES;
	}
	
	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getOrigenPagament()
	{
		return BaseController::PAGAMENT_LLICENCIES;	
	}			
	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getDescripcioPagament()
	{
		return 'Pagament a FECDAS, llista d\'assegurats del club ' . $this->club->getNom() . 
				' en data ' . $this->getDataalta()->format('d/m/Y');	
	}			

	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getBackURLPagament()
	{
		return 'FecdasBundle_partes'; 	
	}			

	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getBackTextPagament()
	{
		return 'Llistat de llicències'; 	
	}			
	/**
	 * Get dades TPV. Sobreescriptura
	 */
	public function getMenuActiuPagament()
	{
		return 'menu-parte';	
	}			
	
	/**
	 * Sobreescrit per afegir els 10 primers noms per als prodectes/llicències del parte
	 *
	 * @return string
	 */
	public function getDetallsAcumulats($baixes = false)
	{
		$acumulades = parent::getDetallsAcumulats( $baixes );	

 	  	/*foreach ($this->llicencies as $llicencia) {
    		if (!$llicencia->esBaixa() || $baixes == true) {
    			$producte = $llicencia->getCategoria()->getProducte();		
    			if (isset($acumulades[$producte->getCodi()]) &&
					isset($acumulades[$producte->getCodi()]['extra'])) {
    				$acumulades[$producte->getCodi()]['extra'][] = $llicencia->getPersona()->getNomCognoms();  
				} else {
					error_log('revisar llicencies parte i detall comandes id '.$this->id);
					$acumulades[$producte->getCodi()] = array(
							'total' => 1,	
							'totalbaixa' => 0, 
							'preuunitat' => $producte->getPreu($this->dataalta->format('Y')),
							'ivaunitat' => $producte->getIvaAny($this->dataalta->format('Y')), 
							'import' => $producte->getPreu($this->dataalta->format('Y')),
							'producte' => $producte->getDescripcio(),
							'extra'		=> array($llicencia->getPersona()->getNomCognoms()),
							'abreviatura' => $producte->getAbreviatura(), 
							'descompte' => 0, 'codi' => $producte->getCodi(),
					);
				}
				
			}
    	}*/

    	foreach ($this->llicencies as $llicencia) {
    		if (!$llicencia->esBaixa() || $baixes == true) {
    			$producte = $llicencia->getCategoria()->getProducte();

    			if (isset($acumulades[$producte->getId()]) &&
					isset($acumulades[$producte->getId()]['extra'])) {
    				$acumulades[$producte->getId()]['extra'][] = $llicencia->getPersona()->getNomCognoms();  
				} else {
					error_log('revisar llicencies parte i detall comandes id '.$this->id);
					$acumulades[$producte->getId()] = array(
							'total' => 1,	
							'totalbaixa' => 0, 
							'preuunitat' => $producte->getPreuAny($this->dataalta->format('Y')),
							'ivaunitat' => $producte->getIvaAny($this->dataalta->format('Y')), 
							'import' => $producte->getPreuAny($this->dataalta->format('Y')),
							'producte' => $producte->getDescripcio(),
							'extra'		=> array($llicencia->getPersona()->getNomCognoms()),
							'abreviatura' => $producte->getAbreviatura(), 
							'descompte' => 0, 
							'codi' => $producte->getCodi(),
							'id' => $producte->getId()
					);
				}
				
			}
    	}

    	return $acumulades;
	}
	
	
    /**
     * Get any
     *
     * @return string
     */
    public function getAny()
    {
    	return $this->dataalta->format('Y');
    }

    /**
     * Is a current year Parte
     *  
     * @return boolean
     */
    public function isCurrentYear() {
    	return (date("Y", $this->dataalta->getTimestamp()) == date("Y"));
    }
    
    /**
     * Obtenir llicència amb id
     *
     * @param $id
     * @return $llicencia|NULL
     */
    public function getLlicenciaById($id)
    {
    	foreach ($this->llicencies as $llicencia) {
    		if (!$llicencia->esBaixa() && $llicencia->getId() == $id) return $llicencia;
    	}
    
    	return null;
    }
    
    /**
     * Obtenir llista de llicències ordenades per nom de l'assegurat i filtrades
     *
     * @return array
     */
    public function getLlicenciesSortedByName($filtre = '')
    {
    	$arr = array();
    	foreach ($this->llicencies as $llicencia) {
    		if (!$llicencia->esBaixa()) {

    			if ($filtre == '' || 
    				($filtre != '' && 
    				 strpos( mb_strtolower ($llicencia->getPersona()->getNomCognoms()),  mb_strtolower($filtre)) !== false)) $arr[] = $llicencia;
			}
    	}
    	 
    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getPersona()->getCognoms() < $b->getPersona()->getCognoms())? -1:1;;
    	});
    		return $arr;
    }
    
    
    /**
     * Obté número de llicències (actives)
     *
     * @return integer
     */
    public function getNumLlicencies()
    {
    	
    	// Només si no estan donades de baixa
    	$count = 0;
    	foreach($this->llicencies as $llicencia_iter) {
    		//$llicencia_iter->setPersonaSelect($llicencia_iter->getPersona());
    		if (!$llicencia_iter->esBaixa()) $count++;
    	}
    	return $count;
    }
    
    /**
     * Obté número d'aficionats (llicències actives)
     *
     * @return integer
     */
    public function getNumAficionats() {
    	return $this->getNumLlicenciesCategoria('A');
    }
    
    /**
     * Obté número de tècnics (llicències actives)
     *
     * @return integer
     */
    public function getNumTecnics() {
    	return $this->getNumLlicenciesCategoria('T');
    }
    
	/**
     * Obté número de llicències per enviar
     *
     * @return integer
     */
    public function getNumEnviar()
    {
    	
    	// Només si no estan donades de baixa
    	$count = 0;
    	foreach($this->llicencies as $llicencia_iter) {
    		if (!$llicencia_iter->esBaixa() && $llicencia_iter->getEnviarllicencia() == true) $count++;
    	}
    	return $count;
    }
	
	/**
     * Obté número de llicències impreses
     *
     * @return integer
     */
    public function getNumImpreses()
    {
    	
    	// Només si no estan donades de baixa
    	$count = 0;
    	foreach($this->llicencies as $llicencia_iter) {
    		if (!$llicencia_iter->esBaixa() && $llicencia_iter->getImpresa() == true) $count++;
    	}
    	return $count;
    }
	
	/**
     * Obté número de llicències enviades per mail
     *
     * @return integer
     */
    public function getNumEnviadesMail()
    {
    	
    	// Només si no estan donades de baixa
    	$count = 0;
    	foreach($this->llicencies as $llicencia_iter) {
    		if (!$llicencia_iter->esBaixa() && $llicencia_iter->getMailenviat() == true) $count++;
    	}
    	return $count;
    }
	
    /**
     * Obté número de infantils (llicències actives)
     *
     * @return integer
     */
    public function getNumInfantils() {
    	return $this->getNumLlicenciesCategoria('I');
    }

    public function getComentariDefault()
    {
    	$numA = $this->getNumAficionats();
    	$numT = $this->getNumTecnics();
    	$numI = $this->getNumInfantils();
    	
    	//$text = 'Comanda llicències: ';
    	$text = ($numA > 0)?$numA.'xA ':''; 
    	$text .= ($numT > 0)?$numT.'xT ':'';
    	$text .= ($numI > 0)?$numI.'xI ':'';
    	return $text;
    }
    
    public function getNumLlicenciesCategoria($simbol)
    {
    	// Només si no estan donades de baixa
    	$count = 0;
    	foreach($this->llicencies as $llicencia_iter) {
    		if (!$llicencia_iter->esBaixa() and 
    			$llicencia_iter->getCategoria()->getSimbol() == $simbol) $count++;
    	}
    	return $count;
    }
    
    public function getPreuTotalNet() {
    	// Retorna el preu total sense IVA de totes les llicències actives del Parte
    	$preu = 0;
    	foreach ($this->getLlicencies() as $llicencia_iter) {
    		if (!$llicencia_iter->esBaixa()) {
    			$preu += $llicencia_iter->getCategoria()->getPreuAny($this->getAny());
    		}
    	}
    	return $preu;
    }
    
	public function getPreuTotal($iva = false) {
		// Retorna el preu total de totes les llicències actives del Parte. Es pot demanar amb IVA
    	$factor = 1;
    	if ($iva == true) {
    		$iva = ($this->getTipus()!=null?$this->getTipus()->getIVA():0);
    		$factor = ($iva/100) + 1;
		}
    	return $this->getPreuTotalNet() * $factor;
    }
    
    public function getDataCaducitat() {

    	if ($this->dataalta == null) {
    		$this->setDataalta(new \DateTime());
    	}
    	$datacaducitat = new \DateTime($this->dataalta->format("Y-m-d"));
    	
    	if ($this->getTipus() == null) throw new \Exception("Tipus de llicència erroni, contacti amb la Federació");
    	
    	
        if ($this->getTipus()->getEs365()) {
        		    
            $final = $this->getTipus()->getFinal();
            if ($final == null) throw new \Exception("Error en el tipus de llicència, contacti amb la Federació");
        		    
            if (is_numeric($this->getTipus()->getFinal())) {
                // final indica nombre de dies vigència
                if ($final > 0) {
        	        $interval = "P".$final."D";
          		        
        	        $datacaducitat->add(new \DateInterval($interval)); // Add $final dies
                }
            } else {
        	    // final indica mes-dia de l'any actual finalització
                if ($datacaducitat->format("m-d") > $final) $currentYear = $datacaducitat->format("Y") + 1;
                else $currentYear = $datacaducitat->format("Y");
                $datacaducitat = \DateTime::createFromFormat("Y-m-d", $currentYear."-".$final);
            }
        } else {
        	/* Anuals caduquen a 31/12*/
        	$datacaducitat = \DateTime::createFromFormat("Y-m-d", $datacaducitat->format("Y") . "-12-31");
        }
		return $datacaducitat;
    }
    
    public function getNumActivitat($activitat)
    {
    	// Només si no estan donades de baixa
    	$count = 0;
    	foreach($this->llicencies as $llicencia_iter) {
    		if (!$llicencia_iter->esBaixa()) {
    			switch ($activitat) {
    				case 'pesca':
    					if ($llicencia_iter->getPesca() == true) $count++;
    					break;
    				case 'escafandrisme':
    					if ($llicencia_iter->getEscafandrisme() == true) $count++;
    					break;
    				case 'natacio':
    					if ($llicencia_iter->getNatacio() == true) $count++;
    					break;
    				case 'orientacio':
    					if ($llicencia_iter->getOrientacio() == true) $count++;
    					break;
    				case 'biologia':
    					if ($llicencia_iter->getBiologia() == true) $count++;
    					break;
    				case 'fotocine':
    					if ($llicencia_iter->getFotocine() == true) $count++;
    					break;
    				case 'hockey':
    					if ($llicencia_iter->getHockey() == true) $count++;
    					break;
   					case 'fotosubapnea':
   						if ($llicencia_iter->getFotosubapnea() == true) $count++;
   						break;
					case 'videosub':
						if ($llicencia_iter->getVideosub() == true) $count++;
						break;
					case 'apnea':
						if ($llicencia_iter->getApnea() == true) $count++;
						break;
					case 'rugbi':
						if ($llicencia_iter->getRugbi() == true) $count++;
						break;
					case 'besportiu':
						if ($llicencia_iter->getBesportiu() == true) $count++;
						break;
					case 'bampolles':
						if ($llicencia_iter->getBampolles() == true) $count++;
						break;
    			}
    		}
    	}
    	return $count;
    }
    
    public function hasIVA() {
    	if ($this->tipus->getIva() > 0) return true;
    	return false;
    }
    
    public function isAsseguranca() {
    	// Per indicar si cal mostrar les estadístiques pantalla llicència parte
    	/*if ($this->tipus->getId() == 2 || $this->tipus->getId() == 8 ||
    		$this->tipus->getId() == 9 || $this->tipus->getId() == 10 ||
    		$this->tipus->getId() == 11) return true;
    	return false;*/
    	return $this->tipus->getAsseguranca();
    }

	public function allowRemoveLlicencia($admin = false) {
   	
		if ($admin == true) return true;
		
		return false;
		/*
		$current = new \DateTime();
    	$interval = $current->diff($this->getDataalta());

		// Màxim 1 mes de marge per esborrar llicències		
		if ($interval->format('%r%a') < -30) return false; 
		
		return true;*/
    }

    public function allowRenovar() {
    	
    	if ($this->pendent == true) return false; // Pendents no s'han de renovar
    	// Només renoven alguns tipus de parte
    	/*if ($this->tipus->getId() == 1 || $this->tipus->getId() == 2 ||
    		$this->tipus->getId() == 4 || $this->tipus->getId() == 7 ||
    		$this->tipus->getId() == 8 || $this->tipus->getId() == 10 ||
			$this->tipus->getId() == 13) {*/

		if ($this->tipus->getRenovable()) {
			    
    		/* Si falta menys d'un més per caducar o ja han caducat */
    		$current = new \DateTime();
    		$interval = $current->diff($this->getDataCaducitat());
    		
			if ($interval->format('%r%a') <= 30 && $interval->format('%r%a') > -180) return true; // %r signo %a dias Menys d'un mes per caducar menys de 6 mesos que ha caducat
			
    		//if ($this->getAny() == (date("Y")-1) and $interval->format('%r%m') <= 1) return true; // Menys d'un mes
    		else return false;
    	}
    	return false;
    }

    /**
     * Allow edit. Permetre modificar / Afegir llicències
	 * Fins 20 minuts després d'iniciar-la. Vigilar que no s'enviïn a comptabilitat mentre estiguin en aquests 20 minuts
     *
     * @return boolean
     */
    public function isAllowEdit()
    {
		if ($this->comandaPagada() == true) return false; 		
		
		$datamaxedicio = clone $this->dataentrada;
		$datamaxedicio->add(BaseController::getIntervalConsolidacio()); // Add 20 minutes
		
		return $datamaxedicio->format('Y-m-d H:i:s') >= date('Y-m-d H:i:s');
		
    	/*return (boolean) $this->comandaPagada() == false && 
    			$this->dataalta->format('Y-m-d') >= $currentdate->format('Y-m-d');*/
    }
	
    /**
     * Pendent Sincronitzar
     *
     * @return boolean
     */
    public function isPendentSincronitzar()
    {
    	if ($this->esBaixa()) return false; // Baixes no cal sincronitzar
    	if ($this->pendent == true) return false; // Pendents no s'han de sincronitzar
    	if ($this->idparte_access == null) return true;
    	if ($this->idparte_access != null and $this->datamodificacio != null) return true;
    	
    	return false;
    }
    
    /**
     * Comprova si el parte és vigent
     *
     * @return boolean
     */
    public function isVigent() {
    	if ($this->pendent == true) return false;
    	
    	$currentdate = new \DateTime();
    	
    	/*if ($this->tipus->getId() == 11) {
    		if ($this->dataalta->format("Y-m-d") == $currentdate->format("Y-m-d")) return true;
    		else return false;
    	}*/
    	/*
    	// Normal 31/12  	dataalta >= 01/01/current year 
    	$inianual = \DateTime::createFromFormat('Y-m-d H:i:s', date("Y") . "-01-01 00:00:00");
    	// 365	dataalta >= avui / (current year - 1) 
    	$ini365 = \DateTime::createFromFormat('Y-m-d H:i:s', (date("Y") - 1) . "-" . date("m") . "-" . date("d") . "  00:00:00");
    	return  ($this->tipus->getEs365() == 0 and $this->dataalta >= $inianual) or
    		($this->tipus->getEs365() == 1 and $this->dataalta >= $ini365);*/
    	   
    	return ( $this->dataalta->format('Y-m-d') <= $currentdate->format('Y-m-d') 
    			&& $currentdate->format('Y-m-d') <= $this->getDataCaducitat()->format("Y-m-d"));
    }
    
    
    /**
     * Comprova si ha finalitzat la vigència del parte
     *
     * @return boolean
     */
    public function isPassat() {
    	$currentdate = new \DateTime();
    	return ($currentdate->format('Y-m-d') > $this->getDataCaducitat()->format("Y-m-d"));
    }
    
   
    /**
     * Missatge llista de partes
     *
     * @return string
     */
    public function getInfoLlistat( $br = PHP_EOL, $llista = false ) {
    	// Missatge que es mostra a la llista de partes
    	//$textInfo = parent::getInfoLlistat();

		$textInfo = $this->comentaris.$br.($this->tipus != null?$this->tipus->getDescripcio():'');
		
    	if (trim($textInfo) != '') $textInfo .= $br;
		
    	if ($this->esBaixa()) return $textInfo.'Llista anul·lada';
    	
    	if ($this->pendent) return $textInfo.'Pendent confirmació pagament';

    	if ($this->isVigent() == false && $this->isPassat() == false) return $textInfo.'Aquesta llista encara no està vigent';
    	
		if (!$this->comandaConsolidada()) return $textInfo.'Factura pendent';

		if ($this->isPassat() == true) return $textInfo.'Validesa de les llicències finalitzada';

    	return $textInfo;
    }
    
    /**
     * Missatges tramitació de partes
     *
     * @return string
     */
    public function getInfoParte() {
    	// Missatges que es mostra a la tramitació de partes
    	if ($this->pendent) return "*Aquesta tramitació tindrà validesa quan es confirmi el seu pagament";
    
    	return "";
    }
    
    /**
     * Comprova si el club pot imprimir les llicències 
     * Clubs DIFE amb impressio   --> sempre
     * Clubs IMME --> llicències pagades i web
     * Clubs NOTR --> Mai
     *
     * @return boolean
     */
    public function allowPrintLlicencia()
    {
    	if ($this->web == false) return false;  // No web no permet imprimir
    	
    	if ($this->comandaPagada()) return true; // Comanda pagada
    	
    	if (!$this->club->pendentPagament() && $this->club->getImpressio()) return true; // DIFE amb impressio sempre
    	
    	if (!$this->club->potTramitar()) return false; // Clubs no tramitació
    	
    	if ($this->club->pendentPagament() && !$this->pendent) return true;   // IMMEDIAT si el parte no està pendent és que tenia saldo quan es va pagar 
    	
    	return false;     // DIFE sense impressió
    }
 
 	/**
	 * Get datapreu. Reescriptura
	 *
	 * @return \Datetime
	 */
	public function getDatapreu()
	{
		return $this->dataalta;
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
     * Set numrelacio
     *
     * @param integer $numrelacio
     */
    public function setNumrelacio($numrelacio)
    {
        $this->numrelacio = $numrelacio;
    }

    /**
     * Get numrelacio
     *
     * @return integer 
     */
    public function getNumrelacio()
    {
        return $this->numrelacio;
    }
    
    /**
     * Set clubparte
     *
     * @param EntityClub $club
     */
    public function setClubparte(EntityClub $clubparte = null)
    {
        $this->clubparte = $clubparte;
    }
    
    /**
     * Get clubparte. Get club del parte o si és null de la comanda
     *
     * @return EntityClub
     */
    public function getClubparte()
    {
        return $this->clubparte;
    }

    /**
     * Set dataalta
     *
     * @param \Datetime $dataalta
     */
    public function setDataalta($dataalta)
    {
        $this->dataalta = $dataalta;
    }

    /**
     * Get dataalta
     *
     * @return \Datetime
     */
    public function getDataalta()
    {
        return $this->dataalta;
    }

    /**
     * Set comentari
     *
     * @param string $comentari
     */
    public function setComentari($comentari)
    {
    	$this->comentari = $comentari;
    }
    
    /**
     * Get comentari
     *
     * @return string
     */
    public function getComentari()
    {
    	return $this->comentari;
    }
 
    /**
     * Set idparte_access
     *
     * @param string $idparteAccess
     */
    public function setIdparteAccess($idparteAccess)
    {
        $this->idparte_access = $idparteAccess;
    }

    /**
     * Get idparte_access
     *
     * @return string 
     */
    public function getIdparteAccess()
    {
        return $this->idparte_access;
    }

    /**
     * Set tipus
     *
     * @param EntityParteType $tipus
     */
    public function setTipus(EntityParteType $tipus)
    {
        $this->tipus = $tipus;
    }

    /**
     * Get tipus
     *
     * @return EntityParteType 
     */
    public function getTipus()
    {
        return $this->tipus;
    }

    /**
     * Set web
     *
     * @param boolean $web
     */
    public function setWeb($web)
    {
    	$this->web = $web;
    }
    
    /**
     * Get web
     *
     * @return boolean
     */
    public function getWeb()
    {
    	return $this->web;
    }

    /**
     * Set renovat
     *
     * @param boolean $renovat
     */
    public function setRenovat($renovat)
    {
    	$this->renovat = $renovat;
    }
    
    /**
     * Get renovat
     *
     * @return boolean
     */
    public function getRenovat()
    {
    	return $this->renovat;
    }

    /**
     * Set pendent
     *
     * @param boolean $pendent
     */
    public function setPendent($pendent)
    {
    	$this->pendent = $pendent;
    }
    
    /**
     * Get pendent
     *
     * @return boolean
     */
    public function getPendent()
    {
    	return $this->pendent;
    }
    
    /**
     * Add llicencia
     *
     * @param EntityLlicencia $llicencia
     */
    public function addLlicencia(EntityLlicencia $llicencia)
    {
    	$llicencia->setParte($this);
    	$this->llicencies->add($llicencia);
    }

    /**
     * Remove llicencia
     *
     * @param EntityLlicencia $llicencia
     */
    public function removeLlicencia(EntityLlicencia $llicencia)
    {
    	$llicencia->setParte();
    	$this->llicencies->removeElement($llicencia);
    }
    
    /**
     * Get llicencies
     *
     * @return \Doctrine\Common\Collections\ArrayCollection 
     */
    public function getLlicencies()
    {
    	return $this->llicencies;
    }

    /*
    
    /**
     * Set llicencies
     *
     * @param $llicencies \Doctrine\Common\Collections\ArrayCollection
     * /
	
    public function setLlicencies(ArrayCollection $llicencies)
    {
    	$this->llicencies = $llicencies;
    }*/
    
    /**
     * Add llicencies
     *
     * @param EntityLlicencia $llicencies
     * @return EntityParte
     */
    public function addLlicency(EntityLlicencia $llicencies)
    {
        $this->llicencies[] = $llicencies;

        return $this;
    }

    /**
     * Remove llicencies
     *
     * @param EntityLlicencia $llicencies
     */
    public function removeLlicency(EntityLlicencia $llicencies)
    {
        $this->llicencies->removeElement($llicencies);
    }
}
