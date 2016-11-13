<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_persones",indexes={@ORM\Index(name="dni_idx", columns={"dni"})})
 * 
 * @author alex
 *
 */
class EntityPersona {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=20)
	 */
	protected $nom;

	/**
	 * @ORM\Column(type="string", length=30)
	 */
	protected $cognoms;
	
	/* Aquesta validació pot donar problemes
	 * @Assert\Type(type="numeric", message="El dni {{ value }} no es un valor vàlid")*/
	/**
	 * 
	 * @ORM\Column(type="string", length=20)
	 */
	protected $dni;
	
	/**
	 * @ORM\Column(type="date")
	 */
	protected $datanaixement;	
	
	/**
	 * @ORM\Column(type="string", length=1)
	 */
	protected $sexe;		// 'H' o 'D'
	
	/**
     * @Assert\Type(type="numeric", message="El telèfon1 {{ value }} no es un valor vàlid")
     * @ORM\Column(type="integer", nullable=true)
	 */
	protected $telefon1;
	
	/**
	 * @Assert\Type(type="numeric", message="El telèfon2 {{ value }} no es un valor vàlid") 
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $telefon2;
	
	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	protected $mail;
	
	/**
	 * @ORM\Column(type="string", length=75, nullable=true)
	 */
	protected $addradreca;

	/**
	 * @ORM\Column(type="string", length=25, nullable=true)
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
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	protected $addrcomarca;
	
	/**
	 * @ORM\Column(type="string", length=3, nullable=true)
	 */
	protected $addrnacionalitat;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;	// FK taula m_clubs
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datamodificacio;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $validat;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $web;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityLlicencia", mappedBy="persona")
	 */
	protected $llicencies;

	public function __construct($currentDate) {
		$this->id = 0;
		$this->setDataentrada($currentDate);
		$this->web = true;
		$this->validat = false;
		$this->llicencies = new \Doctrine\Common\Collections\ArrayCollection();
	}

	public function __toString() {
		return $this->getLlistaText();
	}

	public static function csvHeader() {
		return array (  'dni', 
				 		'nom', 
				 		'cognoms', 
				 		'naixement', 
				 		'edat',	
				 		'sexe', 
				 		'telefon1', 
				 		'telefon2', 
				 		'mail',	
				 		'adreca', 
				 		'poblacio', 
				 		'cp', 
				 		'comarca',
				 		'provincia', 
				 		'pais' );
	}
	
	/**
     * Get persona info. as csv data 
     *
     * @return string
     */
    public function csvRow()
    {
    	return array (	$this->getDni(), 
				 		$this->getNom(), 
				 		$this->getCognoms(), 
				 		$this->getDatanaixement()->format('d/m/Y'), 
				 		$this->getEdat(),	
				 		$this->getSexe(), 
				 		$this->getTelefon1(), 
				 		$this->getTelefon2(), 
				 		$this->getMail(),	
				 		$this->getAddradreca(), 
				 		$this->getAddrpob(), 
				 		$this->getAddrcp(), 
				 		$this->getAddrcomarca(),
				 		$this->getAddrprovincia(), 
				 		$this->getAddrnacionalitat() );
    }
	
    /**
     * Get nom i cognoms "COGNOMS, nom
     *
     * @return string
     */
    public function getCognomsNom()
    {
    	return strtoupper($this->cognoms) . ', ' . $this->nom;
    }

    /**
     * Get nom i cognoms "Nom Cognom1 Cognom2
     *
     * @return string
     */
    public function getNomCognoms()
    {
    	//$fc = mb_strtoupper(mb_substr($str, 0, 1));
    	//return $fc.mb_substr($str, 1);
		return $this->nom.' '.mb_strtoupper($this->cognoms, 'UTF-8');
    }
    
    /**
     * Get adreça completa
     *
     * @return string
     */
    public function getAdrecaCompleta()
    {
    	$strAdreca = "";
    	if ($this->addradreca != null) $strAdreca .= $this->addradreca . ".";
    	if ($this->addrcp != null) $strAdreca .= $this->addrcp . " ";
    	if ($this->addrpob != null) $strAdreca .= $this->addrpob . " ";
    	if ($this->addrprovincia != null) $strAdreca .= "(". $this->addrprovincia . ") ";
    	
    	return  $strAdreca;
    }
    
    public function getLlicenciesSortedByDate($baixes = false, $desde = null, $fins = null)
    {
    	/* Ordenades de última a primera */
    	$arr = array();
    	foreach ($this->llicencies as $llicencia) {
    		if ($llicencia->isValida() || $baixes == true) {
    			$parte = $llicencia->getParte();
				
				if ($parte != null && 
					($desde == null || $desde->format('Y-m-d') <= $parte->getDataalta()->format('Y-m-d') ) && 
					($fins == null  || $fins->format('Y-m-d') >= $parte->getDataalta()->format('Y-m-d') )  ) $arr[] = $llicencia;
			}
    	}

    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getParte()->getDatacaducitat("getLlicenciesSortedByDate") > $b->getParte()->getDatacaducitat("getLlicenciesSortedByDate"))? -1:1;;
    	});
    	return $arr;
    }

    /**
     * 
     * @return FecdasBundle\Entity\EntityLlicencia
     */
    public function getLlicenciaVigent() {
    	foreach ($this->llicencies as $llicencia) {
    		if ($llicencia->isVigent() == true) return $llicencia;
    	} 
    	return null;
    }
    
    public function getLastLlicencia($desde = null, $fins = null) {
    	$llicenciesOrdenades = $this->getLlicenciesSortedByDate(false, $desde, $fins);
    	
    	foreach ($llicenciesOrdenades as $llicencia) return $llicencia;
    	
    	return null;
    }
    
    /**
     * Missatges llista assegurats
     *
     * @return string
     */
    public function getInfoAssegurats($admin = false, $desde = null, $fins = null) {
    	$txtClub = "";
    	if ($admin) $txtClub = "(".$this->club->getNom().") ";  
    	
		if ($desde != null || $fins != null) {
			$llicenciaLast = $this->getLastLlicencia($desde, $fins);
	    	if ($llicenciaLast != null && $llicenciaLast->getParte() != null )  {
	    		$parte = $llicenciaLast->getParte();
    			if ($fins != null && $fins->format('Y-m-d') >= $parte->getDataalta()->format('Y-m-d')) return $txtClub . $llicenciaLast->getCategoria()->getDescripcio() . " fins al " . $parte->getDatacaducitat()->format('d/m/Y');
				return $txtClub . "Darrera llicència finalitzada en data " . $parte->getDatacaducitat()->format('d/m/Y');	
			}
			
			return $txtClub . "Persona sense llicències en aquestes dates";		
		}
		
		$llicenciaVigent = $this->getLlicenciaVigent(); 
    	if ($llicenciaVigent != null && $llicenciaVigent->getParte() != null)
    		return  $txtClub . $llicenciaVigent->getCategoria()->getDescripcio() . " fins al " . $llicenciaVigent->getParte()->getDatacaducitat()->format('d/m/Y');
    		
    	$llicenciaLast = $this->getLastLlicencia();
    	if ($llicenciaLast != null && $llicenciaLast->getParte() != null )  
    		return $txtClub . "Darrera llicència finalitzada en data " . $llicenciaLast->getParte()->getDatacaducitat()->format('d/m/Y');
    	
    	return $txtClub . "Persona sense historial de llicències";
    }

	/**
     * És estranger?
     *
     * @return boolean
     */
	public function esEstranger() {
		$dniCheck = $this->dni;
		/* Tractament fills sense dni, prefix M o P + el dni del progenitor */
		if ( substr ($dniCheck, 0, 1) == 'P' || substr ($dniCheck, 0, 1) == 'M' ) $dniCheck = substr ($dniCheck, 1,  strlen($dniCheck) - 1);
						
		return BaseController::esDNIvalid($dniCheck) != true;
	}

	/**
     * Get edat
     *
     * @return integer 
     */
    public function getEdat()
    {
        $current = new \DateTime();
    	$interval = $current->diff($this->datanaixement);	
			
        return $interval->format('%y');
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
     * Set cognoms
     *
     * @param string $cognoms
     */
    public function setCognoms($cognoms)
    {
        $this->cognoms = $cognoms;
    }

    /**
     * Get cognoms
     *
     * @return string 
     */
    public function getCognoms()
    {
        return $this->cognoms;
    }

    /**
     * Set dni
     *
     * @param string $dni
     */
    public function setDni($dni)
    {
        $this->dni = $dni;
    }

    /**
     * Get dni
     *
     * @return string 
     */
    public function getDni()
    {
        return $this->dni;
    }

    /**
     * Set datanaixement
     *
     * @param date $datanaixement
     */
    public function setDatanaixement($datanaixement)
    {
        $this->datanaixement = $datanaixement;
    }

    /**
     * Get datanaixement
     *
     * @return date 
     */
    public function getDatanaixement()
    {
        return $this->datanaixement;
    }

    /**
     * Set sexe
     *
     * @param string $sexe
     */
    public function setSexe($sexe)
    {
        $this->sexe = $sexe;
    }

    /**
     * Get sexe
     *
     * @return string 
     */
    public function getSexe()
    {
        return $this->sexe;
    }

    /**
     * Set telefon1
     *
     * @param integer $telefon1
     */
    public function setTelefon1($telefon1)
    {
        $this->telefon1 = $telefon1;
    }

    /**
     * Get telefon1
     *
     * @return integer 
     */
    public function getTelefon1()
    {
        return $this->telefon1;
    }

    /**
     * Set telefon2
     *
     * @param integer $telefon2
     */
    public function setTelefon2($telefon2)
    {
        $this->telefon2 = $telefon2;
    }

    /**
     * Get telefon2
     *
     * @return integer 
     */
    public function getTelefon2()
    {
        return $this->telefon2;
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
     * Get mail or mails: mail 1; mail 2; ...
     *
     * @return string 
     */
    public function getMail()
    {
        return $this->mail;
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
     * Set addrcomarca
     *
     * @param string $addrcomarca
     */
    public function setAddrcomarca($addrcomarca)
    {
        $this->addrcomarca = $addrcomarca;
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
     * Set addrnacionalitat
     *
     * @param string $addrnacionalitat
     */
    public function setAddrnacionalitat($addrnacionalitat)
    {
        $this->addrnacionalitat = $addrnacionalitat;
    }

    /**
     * Get addrnacionalitat
     *
     * @return string 
     */
    public function getAddrnacionalitat()
    {
        return $this->addrnacionalitat;
    }

    /**
     * Add llicencies
     *
     * @param FecdasBundle\Entity\EntityLlicencia $llicencies
     */
    public function addLlicencia(\FecdasBundle\Entity\EntityLlicencia $llicencies)
    {
        $this->llicencies->add($llicencies);
    }

    /**
     * Get llicencies
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getLlicencies()
    {
        return $this->llicencies;
    }
    
    /**
     * Get informació club en llistes desplegables
     *
     * @return string
     */
    public function getLlistaText()
    {
    	return $this->cognoms . ", " . $this->nom;
    }

    /**
     * Set dataentrada
     *
     * @param datetime $dataentrada
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;
    }

    /**
     * Get dataentrada
     *
     * @return datetime 
     */
    public function getDataentrada()
    {
        return $this->dataentrada;
    }

    /**
     * Set datamodificacio
     *
     * @param datetime $datamodificacio
     */
    public function setDatamodificacio($datamodificacio)
    {
        $this->datamodificacio = $datamodificacio;
    }

    /**
     * Get datamodificacio
     *
     * @return datetime 
     */
    public function getDatamodificacio()
    {
        return $this->datamodificacio;
    }

    /**
     * Set databaixa
     *
     * @param datetime $databaixa
     */
    public function setDatabaixa($databaixa)
    {
    	$this->databaixa = $databaixa;
    }
    
    /**
     * Get databaixa
     *
     * @return datetime
     */
    public function getDatabaixa()
    {
    	return $this->databaixa;
    }

    /**
     * Set club
     *
     * @param FecdasBundle\Entity\EntityClub $club
     */
    public function setClub(\FecdasBundle\Entity\EntityClub $club)
    {
        $this->club = $club;
    }

    /**
     * Get club
     *
     * @return FecdasBundle\Entity\EntityClub 
     */
    public function getClub()
    {
        return $this->club;
    }
    
    /**
     * Set validat
     *
     * @param boolean $validat
     */
    public function setValidat($validat)
    {
    	$this->validat = $validat;
    }
    
    /**
     * Get validat
     *
     * @return boolean
     */
    public function getValidat()
    {
    	return $this->validat;
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
    
}