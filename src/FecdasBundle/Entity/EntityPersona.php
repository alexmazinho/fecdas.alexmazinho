<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_persones")
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
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $mail;
	
	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $professio;
	
	/**
	 * @ORM\Column(type="string", length=75, nullable=true)
	 */
	protected $addradreca;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
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
	 * @ORM\OneToOne(targetEntity="EntityArxiu")
	 * @ORM\JoinColumn(name="foto", referencedColumnName="id")
	 */
	protected $foto;
	
	/**
	 * @ORM\OneToOne(targetEntity="EntityArxiu")
	 * @ORM\JoinColumn(name="certificat", referencedColumnName="id")
	 */
	protected $certificat;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityArxiu", mappedBy="persona")
	 */
	protected $arxius;	// Altres arxius, baixes, esborrats, obsolets
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;	// FK taula m_clubs
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityMetaPersona", inversedBy="persones")
	 * @ORM\JoinColumn(name="metapersona", referencedColumnName="id")
	 */
	protected $metapersona;	// FK taula m_metapersones
	
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

	public function __construct($metapersona = null, $club = null) {
		$this->id = 0;
		$this->setDataentrada(new \DateTime());
		$this->metapersona = $metapersona;
		$this->club = $club;
		$this->web = true;
		$this->validat = false;
		$this->llicencies = new \Doctrine\Common\Collections\ArrayCollection();
		$this->arxius = new \Doctrine\Common\Collections\ArrayCollection();
	}

	public function __toString() {
		return $this->getLlistaText();
	}

	public static function csvHeader($admin = false, $strTemps = '') {
		
		$csvHeader = array( 'num' );
		if ($admin) $csvHeader = array( 'num', 'club' );
		
		$titolLlicencies = "Llicències ".$strTemps; 
		
		return array_merge($csvHeader, array (  
						'dni', 
				 		'nom', 
				 		'cognoms', 
				 		'naixement', 
				 		'edat',	
				 		'sexe', 
				 		'telefon1', 
				 		'telefon2', 
				 		'mail',
            		    'professio',
            		    'adreca', 
				 		'poblacio', 
				 		'cp', 
				 		'comarca',
				 		'provincia', 
				 		'nac.',
				 		'titulacions',
				 		$titolLlicencies 
					));
	}
	
	/**
     * Get persona info. as csv data 
     *
     * @return string
     */
    public function csvRow($i = 0, $admin = false, $desde = '', $fins = '')
    {
    	$csvRow = array( $i );	
    	if ($admin) $csvRow = array( $i, $this->getClub()->getNom() );	
			
    	$csvRow = array_merge($csvRow,	array (	
    					$this->getDni(), 
				 		$this->getNom(), 
				 		$this->getCognoms(), 
				 		$this->getDatanaixement()->format('d/m/Y'), 
				 		$this->getEdat(),	
				 		$this->getSexe(), 
				 		$this->getTelefon1(), 
				 		$this->getTelefon2(), 
				 		$this->getMail(),	
    	                $this->getProfessio(),
				 		$this->getAddradreca(), 
				 		$this->getAddrpob(), 
				 		$this->getAddrcp(), 
				 		$this->getAddrcomarca(),
				 		$this->getAddrprovincia(), 
				 		$this->getAddrnacionalitat(),
						$this->getInfoHistorialTitulacions(),
						$this->getInfoHistorialLlicencies(FALSE, $desde, $fins)		// No adjuntar club
					 ));
		
		return $csvRow;
			
    }
	
	/**
	 * return array( self )
	 */
	public function getPersonesSortedById($baixes = false)
    {
        if (!$this->esBaixa() || $baixes == true) return array( $this ); 		
    	return array( );
    }
	
	/**
	 * titulacio baixa?
	 * @return boolean
	 */
	public function esBaixa() {
		return $this->databaixa != null;
	}
	
	/**
	 * pertany al club?
	 * @return boolean
	 */
	public function checkClub($club) {
		if ($this->club == null || $club == null || !is_object($club)) return false;
		return $this->club->getCodi() == $club->getCodi();
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
    
	public function getTelefons() {
		$tlfs = array();
		if ($this->getTelefon1() != null && $this->getTelefon1() != '') $tlfs[] = $this->getTelefon1();
		if ($this->getTelefon2() != null && $this->getTelefon2() != '') $tlfs[] = $this->getTelefon2();
		return implode(", ", $tlfs);
	}
	
	/**
	 * Get mails
	 *
	 * @return array
	 */
	public function getMails()
	{
	    $mails = array();
	    if ($this->mail == null || trim($this->mail) == "") return $mails;
	
        foreach (explode(";", $this->mail) as $mail) {
            $mails[] = trim($mail);
        }

	    return $mails;
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
	
	public static function getLlicenciesSortedByDateStatic($llicencies, $baixes = false, $desde = null, $fins = null)
    {
    	/* Ordenades de última a primera */
    	$arr = array();
    	foreach ($llicencies as $llicencia) {
    		if ($llicencia->isValida() || $baixes == true) {
    			$parte = $llicencia->getParte();
				
				if ($parte != null && 
					($desde == null || $desde->format('Y-m-d') <= $parte->getDatacaducitat()->format('Y-m-d') ) && 
					($fins == null  || $fins->format('Y-m-d') >= $parte->getDataalta()->format('Y-m-d') )  ) $arr[] = $llicencia;
			}
    	}

    	EntityLlicencia::getLlicenciesSortedBy($arr, 'datacaducitat', 'desc');
    	
    	return $arr;
    }
    
    public function getLlicenciesSortedByDate($baixes = false, $desde = null, $fins = null)
    {
    	/* Ordenades de última a primera */
    	return EntityPersona::getLlicenciesSortedByDateStatic($this->llicencies, $baixes, $desde, $fins);
    }

	/**
     * 
     * @return \FecdasBundle\Entity\EntityLlicencia
     */
    public static function getLlicenciaVigentStatic($llicencies) {
        foreach ($llicencies as $llicencia) {
    		if ($llicencia->isVigent() == true) return $llicencia;
    	} 
    	return null;
    }
	
    public function getLlicenciaVigent() {
    	return EntityPersona::getLlicenciaVigentStatic($this->llicencies);
    }
    
	public static function getLastLlicenciaStatic($llicencies, $desde = null, $fins = null) {
    	$llicenciesOrdenades = EntityPersona::getLlicenciesSortedByDateStatic($llicencies, false, $desde, $fins);
    	foreach ($llicenciesOrdenades as $llicencia) return $llicencia;
    	
    	return null;
    }
    
    public function getLastLlicencia($desde = null, $fins = null) {
    	return EntityPersona::getLastLlicenciaStatic($this->llicencies, $desde, $fins);
    }
    
    /**
     * Info historial llicències llista dadespersonals
     *
     * @return string
     */
    public static function getInfoHistorialLlicenciesStatic($llicencies, $admin = false, $desde = '', $fins = '', $club = null) {
        $txtClub = $club==null?"":"(".$club->getNom().") ";
   	  
		if ($desde != '' || $fins != '') {
			$desde = ($desde != ''?\DateTime::createFromFormat('Y-m-d', $desde):null);
			$fins = ($fins != ''?\DateTime::createFromFormat('Y-m-d', $fins):null);
			
			$llicenciaLast = EntityPersona::getLastLlicenciaStatic($llicencies, $desde, $fins);
	    	if ($llicenciaLast != null && $llicenciaLast->getParte() != null )  {
	    		$parte = $llicenciaLast->getParte();
	    		if ($admin) $txtClub = "(".$parte->getClubparte()->getNom().") ";
				
    			if ($fins != null && $fins->format('Y-m-d') >= $parte->getDataalta()->format('Y-m-d')) return $txtClub . $llicenciaLast->getCategoria()->getDescripcio() . " fins al " . $parte->getDatacaducitat()->format('d/m/Y');
				return $txtClub . "Darrera llicència finalitzada en data " . $parte->getDatacaducitat()->format('d/m/Y');	
			}
			
			return $txtClub . "Persona sense llicències en aquestes dates";		
		}

		$llicenciaVigent = EntityPersona::getLlicenciaVigentStatic($llicencies);
    	if ($llicenciaVigent != null && $llicenciaVigent->getParte() != null) {
    		$parte = $llicenciaVigent->getParte();
    		if ($admin) $txtClub = "(".$parte->getClubparte()->getNom().") ";
    		return  $txtClub . $llicenciaVigent->getCategoria()->getDescripcio() . " fins al " . $parte->getDatacaducitat()->format('d/m/Y');
		}
    		
		$llicenciaLast = EntityPersona::getLastLlicenciaStatic($llicencies);
    	if ($llicenciaLast != null && $llicenciaLast->getParte() != null ) {
    		$parte = $llicenciaLast->getParte();
    		if ($admin) $txtClub = "(".$parte->getClubparte()->getNom().") ";
    		return $txtClub . "Darrera llicència finalitzada en data " . $parte->getDatacaducitat()->format('d/m/Y');
		}
    	return $txtClub . "Persona sense historial de llicències";
    }

    public function getInfoHistorialLlicencies($admin = false, $desde = '', $fins = '') {
    	return EntityPersona::getInfoHistorialLlicenciesStatic($this->llicencies, $admin, $desde, $fins, $this->club);
	}


    /**
     * Info historial llicències llista dadespersonals
     *
     * @return string
     */
    public function getInfoHistorialTitulacions() {
		return $this->metapersona!=null?$this->metapersona->getInfoHistorialTitulacions():''; 
    }


	public function getTitulacionsSortedByDate($baixes = false)
    {
    	return $this->metapersona!=null?$this->metapersona->getTitulacionsSortedByDate($baixes):array(); 
    }

	/**
     * Get altrestitulacions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAltrestitulacions()
    {
        return $this->metapersona!=null?$this->metapersona->getAltrestitulacions():array();
    }
	
	/**
     * Get altrestitulacions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAltrestitulacionsIds()
    {
    	$altres = array();
    	foreach ($this->getAltrestitulacions() as $altre) {
			$altres[] = $altre->getId();
		}
        return $altres;
    }
	
	public function teTitulacions() {
		return $this->metapersona!=null?$this->metapersona->teTitulacions():false;
	}

	public function getDocenciesSortedByDate($baixes = false)
    {
    	return $this->metapersona!=null?$this->metapersona->getDocenciesSortedByDate($baixes):array();
	}
	
	public function teDocencies() {
		return $this->metapersona!=null?$this->metapersona->teDocencies():false;
	}
	
	/**
     * És estranger?
     *
     * @return boolean
     */
	public function esEstranger() {
		if ($this->metapersona==null) return true;
		
		//$dniCheck = $this->dni;
		$dniCheck = $this->metapersona->getDni();
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
     * Get usuari de la metapersona si existeix, en cas contrari null
     *
     * @return EntityUser
     */
    public function getUsuari()
    {
        if ($this->metapersona == null) return null;
        
        return $this->metapersona->getUsuari();
    }
    
    /**
     * Nova?
     *
     * @return boolean
     */
    public function nova()
    {
        return $this->id == 0;
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
        $this->nom = substr($nom,0,20);
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
        $this->cognoms = substr($cognoms,0,30);
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
        $this->metapersona->setDni($dni);	
    }

    /**
     * Get dni
     *
     * @return string 
     */
    public function getDni()
    {
        return $this->metapersona->getDni();
    }

    /**
     * Set datanaixement
     *
     * @param \DateTime $datanaixement
     */
    public function setDatanaixement($datanaixement)
    {
        $this->datanaixement = $datanaixement;
    }

    /**
     * Get datanaixement
     *
     * @return \DateTime 
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
     * Set professio
     *
     * @param string $professio
     */
    public function setProfessio($professio)
    {
        $this->professio = $professio;
    }
    
    /**
     * Get professio
     *
     * @return string
     */
    public function getProfessio()
    {
        return $this->professio;
    }

    /**
     * Set addradreca
     *
     * @param string $addradreca
     */
    public function setAddradreca($addradreca)
    {
        $this->addradreca = substr($addradreca,0,75);
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
        $this->addrpob = substr($addrpob,0,50);
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
     * @param EntityLlicencia $llicencies
     */
    public function addLlicencia(EntityLlicencia $llicencies)
    {
        $this->llicencies->add($llicencies);
    }

    /**
     * Get llicencies
     *
     * @return \Doctrine\Common\Collections\Collection 
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
	 * Set foto
	 *
	 * @param EntityArxiu $foto
	 * @return EntityArxiu
	 */
	public function setFoto(EntityArxiu $foto = null)
	{
		$this->foto = $foto;
	}
	
	/**
	 * Get foto
	 *
	 * @return EntityArxiu
	 */
	public function getFoto()
	{
		return $this->foto;
	}

	/**
	 * Set certificat
	 *
	 * @param EntityArxiu $certificat
	 * @return EntityArxiu
	 */
	public function setCertificat(EntityArxiu $certificat = null)
	{
		$this->certificat = $certificat;
	}
	
	/**
	 * Get certificat
	 *
	 * @return EntityArxiu
	 */
	public function getCertificat()
	{
		return $this->certificat;
	}


	/**
     * Add arxius
     *
     * @param EntityArxiu $arxiu
     */
    public function addArxius(EntityArxiu $arxiu)
    {
    	if ($arxiu != null) {
	        $this->arxius->add($arxiu);
			$arxiu->setPersona($this);
		}
    }

    /**
     * Remove arxiu
     *
     * @param EntityArxiu $arxiu
     */
    public function removeArxius(EntityArxiu $arxiu)
    {
        $this->arxius->removeElement($arxiu);
        $arxiu->setPersona(null);
    }
    
    
    /**
     * Get arxius
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getArxius()
    {
        return $this->arxius;
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

    /**
     * Set datamodificacio
     *
     * @param \DateTime $datamodificacio
     */
    public function setDatamodificacio($datamodificacio)
    {
        $this->datamodificacio = $datamodificacio;
    }

    /**
     * Get datamodificacio
     *
     * @return \DateTime 
     */
    public function getDatamodificacio()
    {
        return $this->datamodificacio;
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
     * Set club
     *
     * @param EntityClub $club
     */
    public function setClub(EntityClub $club)
    {
        $this->club = $club;
    }

    /**
     * Get club
     *
     * @return EntityClub 
     */
    public function getClub()
    {
        return $this->club;
    }
    
	/**
     * Set metapersona
     *
     * @param EntityMetaPersona $metapersona
     */
    public function setMetapersona(EntityMetaPersona $metapersona)
    {
        $this->metapersona = $metapersona;
    }

    /**
     * Get metapersona
     *
     * @return EntityMetaPersona 
     */
    public function getMetapersona()
    {
        return $this->metapersona;
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