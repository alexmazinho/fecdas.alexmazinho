<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_metapersones",indexes={@ORM\Index(name="dni_idx", columns={"dni"})})
 * 
 * @author alex
 *
 */
class EntityMetaPersona {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * 
	 * @ORM\Column(type="string", length=20)
	 */
	protected $dni;

	/**
	 * @ORM\OneToMany(targetEntity="EntityTitulacio", mappedBy="persona")
	 */
	protected $titulacions;

	/**
     * @ORM\ManyToMany(targetEntity="EntityTitol")
     * @ORM\JoinTable(name="m_titulacionsexternes",
     *      joinColumns={@ORM\JoinColumn(name="persona", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="titol", referencedColumnName="id")}
     *      )
     */
	protected $altrestitulacions;

	/**
	 * @ORM\OneToMany(targetEntity="EntityDocencia", mappedBy="docent")
	 */
	protected $docencies;

	/**
	 * @ORM\OneToMany(targetEntity="EntityPersona", mappedBy="metapersona")
	 */
	protected $persones;	

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;

	public function __construct($dni = '') {
		$this->id = 0;
		$this->dni = $dni;
		$this->setDataentrada(new \DateTime());
		$this->persones = new \Doctrine\Common\Collections\ArrayCollection();
		
		$this->titulacions = new \Doctrine\Common\Collections\ArrayCollection();
		$this->altrestitulacions = new \Doctrine\Common\Collections\ArrayCollection();
		
		$this->docencies = new \Doctrine\Common\Collections\ArrayCollection();
	}

	public function getPersonesSortedById($baixes = false)
    {
    	if (count($this->persones) == 1) return array($this->persones[0]);		
    	
		$arr = array();
    	foreach ($this->persones as $persona) {
    		if (!$persona->esBaixa() || $baixes == true) {
				$arr[] = $persona;
			}
    	}

    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getId() > $b->getId())? 1:-1;
    	});
    	return $arr;
    }

    public function getPersonaClub($club = null)
    {
    	if ($club == null) return null;
		
    	foreach ($this->persones as $persona) {
    		if (!$persona->esBaixa() && $persona->checkClub($club)) return $persona;
    	}

    	return null;
    }
	
	public function getUltimesDadesPersonals()
    {
    	if (count($this->persones) == 1) return $this->persones[0];
		
    	$persones = $this->getPersonesSortedById();
		
		return count($persones)==0?null:$persones[0];
    }

	/**
     * Totes les llicències de totes les persones del grup
	 * 
     * @return FecdasBundle\Entity\EntityLlicencia
     */
    public function getLlicencies() {
    	if (count($this->persones) == 1) return $this->persones[0]->getLlicencies();
		
		$llicencies = array();
    	foreach ($this->persones as $persona) {
			$llicencies = array_merge($llicencies, $persona->getLlicencies()->toArray());
		}
    	return new \Doctrine\Common\Collections\ArrayCollection( $llicencies );
    }
	
	public function getLlicenciesSortedByDate($baixes = false, $desde = null, $fins = null)
    {
    	/* Ordenades de última a primera */
    	return EntityPersona::getLlicenciesSortedByDateStatic($this->getLlicencies(), $baixes, $desde, $fins);
    }

    public function getLlicenciaVigent() {
    	return EntityPersona::getLlicenciaVigentStatic($this->getLlicencies());
    }

	public function getLastLlicencia($desde = null, $fins = null) {
    	return EntityPersona::getLastLlicenciaStatic($this->getLlicencies(), $desde, $fins);
    }
	
	public function getInfoHistorialLlicencies($admin = false, $desde = '', $fins = '') {
    	return EntityPersona::getInfoHistorialLlicenciesStatic($this->getLlicencies(), $admin, $desde, $fins);
	}
	
	/**
     * Info historial llicències llista dadespersonals
     *
     * @return string
     */
    public function getInfoHistorialTitulacions() {
    	$historial = array();
    	
		foreach ($this->titulacions as $titulacio) {
			$codi = $titulacio->getTitol()->getCodi();
			if (!in_array($codi, $historial) && $titulacio->consolidada()) $historial[] = $codi; 
		}
		
		foreach ($this->altrestitulacions as $titolextern) {
			$codi = $titolextern->getCodi();
			if (!in_array($codi, $historial)) $historial[] = $codi; 
		}
		
		if (count($historial) == 0) return '';
		
		return implode(", ", $historial); 
    }


	public function getTitulacionsSortedByDate($baixes = false)
    {
    	/* Ordenades de última a primera */
    	$arr = array();
    	foreach ($this->titulacions as $titulacio) {
    		if (!$titulacio->anulada() || $baixes == true) {
				$arr[] = $titulacio;
			}
    	}

    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getDatasuperacio() > $b->getDatasuperacio())? -1:1;;
    	});
    	return $arr;
    }

	public function teTitulacions() {
		return count($this->getTitulacionsSortedByDate(false));
	}

	public function getDocenciesSortedByDate($baixes = false)
    {
    	/* Ordenades de per curs */
    	$arr = array();
    	foreach ($this->docencies as $docencia) {
    		if (!$docencia->anulada() || $baixes == true) {
				$arr[] = $docencia;
			}
    	}

    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getCurs()->getNum() > $b->getCurs()->getNum())? -1:1;;
    	});
    	return $arr;
    }

	public function teDocencies() {
		return count($this->getDocenciesSortedByDate(false));
	}
	
	/**
     * Get nom i cognoms "COGNOMS, nom
     *
     * @return string
     */
    public function getCognomsNom()
    {
    	/*$noms = array();
    	foreach ($this->persones as $persona) {
			if (!isset($noms[$persona->getCognomsNom()])) $noms[$persona->getCognomsNom()] = $persona->getCognomsNom().", ".$persona->getClub()->getNom();	
			else $noms[$persona->getCognomsNom()] .= ", ".$persona->getClub()->getNom();
		}
			
    	return implode(PHP_EOL, $noms);*/
    	$personaMesNova = $this->getUltimesDadesPersonals();
    	return $personaMesNova==null?'':$personaMesNova->getCognomsNom();
    }
	
	/**
     * Get nom i cognoms "Nom Cognoms
     *
     * @return string
     */
    public function getNomCognoms()
    {
    	$personaMesNova = $this->getUltimesDadesPersonals();
    	return $personaMesNova==null?'':$personaMesNova->getNomCognoms();
    }

	/**
     * Get datanaixement
     *
     * @return date 
     */
    public function getDatanaixement()
    {
        $personaMesNova = $this->getUltimesDadesPersonals();
    	return $personaMesNova==null?'':$personaMesNova->getDatanaixement();
    }


	/**
     * Get mail or mails: mail 1; mail 2; ...
     *
     * @return text 
     */
    public function getMails()
    {
    	$mails = array();
		foreach ($this->getPersonesSortedById() as $persona) {
			$mails = array_merge(explode(";", $persona->getMail()));
		}
        return $mails;
    }


	public function __toString() {
		return $this->id;
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
     * Add titulacions
     *
     * @param FecdasBundle\Entity\EntityTitulacio $titulacio
     */
    public function addTitulacions(\FecdasBundle\Entity\EntityTitulacio $titulacio)
    {
        $this->titulacions->add($titulacio);
    }

    /**
     * Get titulacions
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getTitulacions()
    {
        return $this->titulacions;
    }
	
	/**
     * Add altrestitulacions
     *
     * @param FecdasBundle\Entity\EntityTitol $titolextern
     */
    public function addAltrestitulacions(\FecdasBundle\Entity\EntityTitol $titolextern)
    {
        $this->altrestitulacions->add($titolextern);
    }

    /**
     * Get altrestitulacions
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getAltrestitulacions()
    {
        return $this->altrestitulacions;
    }
	
	
	/**
     * Add docencies
     *
     * @param FecdasBundle\Entity\EntityDocencia $docencia
     */
    public function addDocencies(\FecdasBundle\Entity\EntityDocencia $docencia)
    {
        $this->docencies->add($docencia);
    }

    /**
     * Get docencies
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getDocencies()
    {
        return $this->docencies;
    }

	/**
     * Add persones
     *
     * @param FecdasBundle\Entity\EntityPersona $persona
     */
    public function addPersones(\FecdasBundle\Entity\EntityPersona $persona)
    {
        $this->persones->add($persona);
    }

    /**
     * Get persones
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getPersones()
    {
        return $this->persones;
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
}