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
	 * @ORM\Column(type="boolean")
	 */
	protected $impres;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityLlicencia", mappedBy="parte")
	 */
	protected $llicencies;	// Owning side of the relationship
	
	
	public function __construct() {
		$this->web = true;
		$this->renovat = false;
		$this->impres = false;
		$this->pendent = false;
		$this->llicencies = new \Doctrine\Common\Collections\ArrayCollection();
		
		// Hack per permetre múltiples constructors
		parent::__construct(); // Sense paràmetres
		
		$a = func_get_args();
		$i = func_num_args();
		
		if ($i > 1 && method_exists($this,$f='__constructParams')) {
			call_user_func_array(array($this,$f),$a);
		}
	}
	
	public function __toString() {
		return $this->getId();
	}
	
	public function cloneLlicencies($parteoriginal, $currentDate) {
		// Get current collection
		$llicencies = $partearenovar->getLlicencies();
	
		$this->llicencies = new \Doctrine\Common\Collections\ArrayCollection();
		
		foreach ($llicencies as $llicencia_iter) {
			if (!$llicencia_iter->esBaixa()) {
				$cloneLlicencia = clone $llicencia_iter;
				
				/* Init camps */
				$cloneLlicencia->setDatacaducitat($this->getDataCaducitat("cloneLlicencies"));
				
				$this->llicencies->add($cloneLlicencia);
				$cloneLlicencia->setParte($this);
			}
		}
	}
	
	public function esParte()
	{
		return true;
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
	 * Get id
	 *
	 * @return boolean
	 */
	public function esBaixa()
	{
		return $this->databaixa != null;
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
     * Get any
     *
     * @return string
     */
    public function getAny()
    {
    	return date("Y", $this->dataalta->getTimestamp());
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
     * Obtenir llista de llicències ordenades per nom de l'assegurat
     *
     * @return array
     */
    public function getLlicenciesSortedByName()
    {
    	$arr = array();
    	foreach ($this->llicencies as $llicencia) {
    		if (!$llicencia->esBaixa()) $arr[] = $llicencia;
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
    	
    	$text = 'Comanda llicències: ';
    	$text .= ($numA > 0)?$numA.'xA ':''; 
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
    
    public function getPreuTotalIVA() {
    	// Retorna el preu total amb IVA de totes les llicències actives del Parte
    	$iva = ($this->getTipus()!=null?$this->getTipus()->getIVA():0);
    	$factor = ($iva/100) + 1;
    	return $this->getPreuTotalNet() * $factor;
    }
    
    public function getDataCaducitat($source = null) {
    	//$datacaducitat = clone $this->getDataalta(); // Important treballar amb còpies no amb referències
    	//$datacaducitat = clone $this->dataalta;

    	if ($this->dataalta == null) {
    		// mime type to display message in HTML
    		$headers = "From: webadmin@fecdasgestio.cat\r\n";
    		$headers .= "MIME-Version: 1.0\r\n";
    		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    		/* Error punyetero. Debug */
    		if ($source == null) {
	    		error_log("getDataCaducitat amb dataalta null (Origen desconegut)", 1, "alexmazinho@gmail.com",$headers);
    		} else {
    			error_log("getDataCaducitat amb dataalta null (".$source.")", 1, "alexmazinho@gmail.com",$headers);
    		}
    		$this->setDataalta(new \DateTime());
    	}
    	
    	$datacaducitat = new \DateTime($this->dataalta->format("Y-m-d"));
    	if ($this->getTipus() != null) {
    	if ($this->getTipus()->getId() != 11) { // No un dia
    		if ($this->getTipus()->getEs365() == true) {
    			/* Competició. En 365 datafinal indica data de caducitat */
    			if ($this->getTipus()->getFinal() != null) {
    				// Si dataalta > datafinal  --> any següent, sinó any dataalta 
    				if ($datacaducitat->format("m-d") > $this->getTipus()->getFinal()) $currentYear = $datacaducitat->format("Y") + 1; 
    				else $currentYear = $datacaducitat->format("Y");
    				$datacaducitat = \DateTime::createFromFormat("Y-m-d", $currentYear."-".$this->getTipus()->getFinal());
    			} else {
    				$datacaducitat->add(new \DateInterval('P364D')); // Add 364 dies
    			}
    		} else {
    			/* Anuals caduquen a 31/12*/
    			$datacaducitat = \DateTime::createFromFormat("Y-m-d", $datacaducitat->format("Y") . "-12-31");
    		}
    	}
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
    	if ($this->tipus->getId() == 2 || $this->tipus->getId() == 8 ||
    		$this->tipus->getId() == 9 || $this->tipus->getId() == 10 ||
    		$this->tipus->getId() == 11) return true;
    	return false;
    }

    public function allowRenovar() {
    	
    	if ($this->pendent == true) return false; // Pendents no s'han de renovar
    	// Només renoven alguns tipus de parte
    	if ($this->tipus->getId() == 1 || $this->tipus->getId() == 2 ||
    			$this->tipus->getId() == 4 || $this->tipus->getId() == 7 ||
    			$this->tipus->getId() == 10) {

    		/* Si falta menys d'un més per caducar o ja han caducat */
    		$current = new \DateTime();
    		$interval = $current->diff($this->getDataCaducitat("allowRenovar"));
    		
    		if ($this->getAny() == (date("Y")-1) and $interval->format('%r%m') <= 1) return true; // Menys d'un mes
    		else return false;
    	}
    	return false;
    }

    /**
     * Allow edit
     *
     * @return boolean
     */
    public function isAllowEdit()
    {
    	$currentdate = new \DateTime();
    	
    	return (boolean) $this->comandaPagada() == false && 
    			$this->dataalta->format('Y-m-d') >= $currentdate->format('Y-m-d');
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
    	   
    	/* Dataalta <= avui and avui <= Caducitat */
    	error_log('id=>'.$this->id.' avui => '.$currentdate->format('Y-m-d'));
    	error_log($this->dataalta->format('Y-m-d') .'=>'.$this->getDataCaducitat("isVigent")->format("Y-m-d"));
    	
    	
    	return ( $this->dataalta->format('Y-m-d') <= $currentdate->format('Y-m-d') 
    			&& $currentdate->format('Y-m-d') <= $this->getDataCaducitat("isVigent")->format("Y-m-d"));
    }
    
    
    /**
     * Comprova si ha finalitzat la vigència del parte
     *
     * @return boolean
     */
    public function isPassat() {
    	$currentdate = new \DateTime();
    	return ($currentdate->format('Y-m-d') > $this->getDataCaducitat("isPassat")->format("Y-m-d"));
    }
    
   
    /**
     * Missatge llista de partes
     *
     * @return string
     */
    public function getInfoLlistat() {
    	// Missatge que es mostra a la llista de partes
    	$textInfo = "";
    	
    	if ($this->esBaixa()) return "Llista anulada";
    	
    	if ($this->pendent) return "Pendent confirmació pagament";

    	if ($this->isVigent() == false) return "Aquesta llista encara no està vigent";
    	
    	if ($this->isPassat() == true) $textInfo .= "Validesa de les llicències finalitzada";
    	 
    	if ($this->getNumFactura() != null && $this->getDatafactura() != null) {
    		$textInfo .= $textInfo == ""?"":"<br/>";
    		$textInfo .= "Fra. ". $this->getNumFactura();
    		$textInfo .= " - ". $this->getDatafactura()->format("d/m/y");
    	} else {
    		if ($this->getAny() >= 2013) $textInfo .= "Llicències vigents (Factura pendent)";
    	}
    	
    	if ($this->getNumRebut() != null && $this->getDatapagament() != null) {
    		$textInfo .= $textInfo == ""?"":"<br/>";
    		$textInfo .= "Rebut. ". $this->getNumRebut();
    		$textInfo .= " - ". $this->getDatapagament()->format("d/m/y");
    	}
    	
    	//if ($this->comandaPagada() == true && $this->getRebut()->getTipuspagament() == BaseController::TIPUS_PAGAMENT_TPV) $textInfo .=  ". Pagament on-line";

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
    	
    	if ($this->club->getEstat()->getCodi() == "DIFE" and $this->club->getImpressio() == true) return true;  // DIFE amb impressio sempre
    	
    	if ($this->club->getEstat()->getCodi() == "NOTR") return false; // NOTR mai 

    	return $this->comandaPagada();  // La resta poden imprimir si està pagat
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
     * Set dataalta
     *
     * @param datetime $dataalta
     */
    public function setDataalta($dataalta)
    {
        $this->dataalta = $dataalta;
    }

    /**
     * Get dataalta
     *
     * @return datetime
     */
    public function getDataalta()
    {
        return $this->dataalta;
    }

    /**
     * Set comentari
     *
     * @param text $comentari
     */
    public function setComentari($comentari)
    {
    	$this->comentari = $comentari;
    }
    
    /**
     * Get comentari
     *
     * @return text
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
     * @param FecdasBundle\Entity\EntityParteType $tipus
     */
    public function setTipus(\FecdasBundle\Entity\EntityParteType $tipus)
    {
        $this->tipus = $tipus;
    }

    /**
     * Get tipus
     *
     * @return FecdasBundle\Entity\EntityParteType 
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
     * Set impres
     *
     * @param boolean $impres
     */
    public function setImpres($impres)
    {
    	$this->impres = $impres;
    }
    
    /**
     * Get impres
     *
     * @return boolean
     */
    public function getImpres()
    {
    	return $this->impres;
    }
    
    /**
     * Add llicencia
     *
     * @param FecdasBundle\Entity\EntityLlicencia $llicencia
     */
    public function addLlicencia(\FecdasBundle\Entity\EntityLlicencia $llicencia)
    {
    	$llicencia->setParte($this);
    	$this->llicencies->add($llicencia);
    }

    /**
     * Remove llicencia
     *
     * @param FecdasBundle\Entity\EntityLlicencia $llicencia
     */
    public function removeLlicencia(\FecdasBundle\Entity\EntityLlicencia $llicencia)
    {
    	$llicencia->setParte();
    	$this->llicencies->removeElement($llicencia);
    }
    
    /**
     * Get llicencies
     *
     * @return Doctrine\Common\Collections\ArrayCollection 
     */
    public function getLlicencies()
    {
    	return $this->llicencies;
    }

    /**
     * Set llicencies
     *
     * @param $llicencies Doctrine\Common\Collections\ArrayCollection
     */
	/*
    public function setLlicencies(ArrayCollection $llicencies)
    {
    	$this->llicencies = $llicencies;
    }*/
    
    /**
     * Add llicencies
     *
     * @param \FecdasBundle\Entity\EntityLlicencia $llicencies
     * @return EntityParte
     */
    public function addLlicency(\FecdasBundle\Entity\EntityLlicencia $llicencies)
    {
        $this->llicencies[] = $llicencies;

        return $this;
    }

    /**
     * Remove llicencies
     *
     * @param \FecdasBundle\Entity\EntityLlicencia $llicencies
     */
    public function removeLlicency(\FecdasBundle\Entity\EntityLlicencia $llicencies)
    {
        $this->llicencies->removeElement($llicencies);
    }
}
