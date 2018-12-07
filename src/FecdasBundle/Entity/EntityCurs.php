<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_cursos")
 * 
 * @author alex
 *
 */
class EntityCurs {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub", inversedBy="cursos")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi", nullable=true)
	 */
	protected $club;	// FK taula m_clubs
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityUser")
	 * @ORM\JoinColumn(name="editor", referencedColumnName="id", nullable=true)
	 */
	protected $editor;	// FK taula m_users
	
	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	protected $clubhistoric;  // Clubs que ja no existeixen
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $num;	  

	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	protected $numfedas;	// El crea la FEDAS, el número es pot crear en un any diferent de l'inici o la finalització
		
	/**
	 * @ORM\Column(type="date")
	 */
	protected $datadesde;

	/**
	 * @ORM\Column(type="date")
	 */
	protected $datafins;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityTitol")
	 * @ORM\JoinColumn(name="titol", referencedColumnName="id")
	 */
	protected $titol;	// FK taula m_titols 

	/**
	 * @ORM\OneToOne(targetEntity="EntityStock", inversedBy="curs")
	 * @ORM\JoinColumn(name="stock", referencedColumnName="id")
	 */
	protected $stock;	// FK taula m_stock
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityTitulacio", mappedBy="curs" )
	 */
	protected $participants;	// FK taula m_titulacions
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityDocencia", mappedBy="curs" )
	 */
	protected $docents;	// FK taula m_docencies

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $editable;  // Pel director
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $validat;  // Pel club
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $finalitzat;   // Per FECDAS indica nums
	
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
	 * Constructor
	 */
	public function __construct($editor, $num, $datadesde = null, $datafins = null, $club = null, $clubhistoric = '', $titol = null)
	{
	
		$this->id = 0;
		$this->editor = $editor;
		$this->num = $num;
		$this->numfedas = null;  // pendent
		$this->titol = $titol;
		$this->stock = null;
		$this->club = $club;
		if ($club == null) $this->clubhistoric = $clubhistoric;
		$this->dataentrada = new \DateTime();
		$this->datamodificacio = new \DateTime();
		
		$this->datadesde = $datadesde == null?new \DateTime():$datadesde;
		$this->datafins = $datafins == null?new \DateTime():$datafins;
		
		$this->editable = true;
		$this->validat = false;
		$this->finalitzat = false;
		
		$this->docents = new \Doctrine\Common\Collections\ArrayCollection();
		$this->participants = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function esNou()
	{
		return ($this->id == 0);
	}
	
	/**
	 * Retorna número acta format XXXXX/20XX
	 * 
	 * @return string
	 */
	public function getNumActa() {
	    return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->dataentrada->format("Y");
	}
	
	/**
     * get kit si és necessari o null en cas contrari 
     *
     * @return boolean 
     */
    public function getKit()
    {
        if ($this->titol == null) return null;	
		
        return $this->titol->esKitNecessari()?$this->titol->getKit():null;
    }
	
	/**
	 * Retorna curs anul·lat?
	 * @return boolean
	 */
	public function anulat() {
		return $this->databaixa != null;
	}
	
	/**
	 * Retorna curs finalitzat?  Federació l'ha tancat
	 * @return boolean
	 */
	public function finalitzat() {
		return $this->finalitzat;
	}
	
	/**
	 * Retorna curs validat?  El club ha validat les dades
	 * @return boolean
	 */
	public function validat() {
		return $this->validat;
	}
	
	/**
	 * Retorna curs tancat? El tècnic ha omplert totes les dades i cal validació del club
	 * @return boolean
	 */
	public function tancat() {
		return !$this->anulat() && !$this->editable && !$this->validat && !$this->finalitzat;
	}
	
	/**
	 * Retorna curs editable? El tècnic ha omplert algunes dades i l'ha desat, encara s'hi poden introduïr d'altres
	 * @return boolean
	 */
	public function editable() {
		return $this->editable;
	}
	
	/**
	 * Retorna es un curs històric?
	 * @return boolean
	 */
	public function historic() {
		return $this->club == null;
	}
	
	/**
	 * Retorna estat: Pendent validació del club, Enviat a la federació, Finalitzat 
	 * @return boolean
	 */
	public function getEstat() {
		
		if ($this->anulat()) return '';	
			
		if ($this->finalitzat) return 'Finalitzat';
		
		if ($this->validat) return 'Enviat a la federació';
		
		if ($this->editable) return 'Pendent de validar pel club';
		
		return 'En procés...';
	}
	public function getEstatColor() {
			
		if ($this->finalitzat || $this->anulat()) return '';
		
		if ($this->validat) return 'green';
		
		return 'red';
	}
	
	/**
	 * Retorna Nom del club
	 * @return string
	 */
	public function getClubInfo() {
		return $this->club != null?$this->club->getNom():$this->clubhistoric;
	}
	
	/**
	 * Retorna participants as string
	 * @return string
	 */
	public function getAlumnes($admin = false) {
			
		$arr = array();	
		
		$participacions = $this->getParticipantsSortedByCognomsNom();
		
		if (count($participacions) > 5) return count($participacions)." participants";
		
		foreach ($participacions as $titulacio) {
			$metapersona = $titulacio->getMetapersona();
			$persona = $metapersona->getPersona($this->club);
			
			if ($admin || $persona != null) $arr[] = $metapersona->getDni()." - ".$metapersona->getNomCognoms();
			else $arr[] = $metapersona->getDni();
		}
		
		return implode(PHP_EOL, $arr);
	}
	
	/**
	 * Retorna docents as string
	 * @return string
	 */
	public function getEquipDocent() {
			
		$arr = array();
		$i = 0;
		
		$docencies = $this->getDocentsByRoleSortedByCognomsNom();
		
		if (count($docencies) == 0) return "Sense dades de l'equip docent";
		
		foreach ($docencies as $docencia) {
			if ($i > 5) return implode(PHP_EOL, $arr);
			
			$metapersona = $docencia->getMetadocent();
			
			$arr[] = $docencia->getRol()." - ".$metapersona->getNomCognoms();
			$i++;
		}
		
		return implode(PHP_EOL, $arr);
	}
	
	public function getDocentsByRoleSortedByCognomsNom($role = '', $baixes = false)
    {
    	/* Ordenades per rol director, co-director, instructor, colaborador => cognoms nom*/
    	$arr = array();
    	foreach ($this->docents as $docencia) {
    		if ((!$docencia->anulada() || $baixes == true) && 
    			($role == '' || strtolower($docencia->getRol()) == strtolower($role))) $arr[] = $docencia;
    	}
		
    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
			if (strtolower($a->getRol()) == strtolower($b->getRol())) return ($a->getMetadocent()->getCognomsNom() > $b->getMetadocent()->getCognomsNom())? 1:-1;
			
			// Rols diferents
			if ($a->getRol() == BaseController::DOCENT_DIRECTOR) return 1;
			if ($b->getRol() == BaseController::DOCENT_DIRECTOR) return -1;
			if ($a->getRol() == BaseController::DOCENT_CODIRECTOR) return 1;
			if ($b->getRol() == BaseController::DOCENT_CODIRECTOR) return -1;
			if ($a->getRol() == BaseController::DOCENT_INSTRUCTOR) return 1;
			return -1;
    		
    	});
    	return $arr;
    }
	
	public function getDocenciesIds($role = '')
    {
		$arr = array();
		foreach ($this->getDocentsByRoleSortedByCognomsNom($role) as $docencia) $arr[] = $docencia->getId();
			
		return $arr;		
	}
	
	public function getDocenciaById($id)
    {
    	foreach ($this->docents as $docencia) {
    		if ($id == $docencia->getId()) return $docencia;
    	}
		return null;
    }
	
	/*
	 * Exclou director i subdirector, per obtenir aquest cal executar getDirector() o getCodirector()
	 */ 
	public function getDocenciaByMetaId($meta)
    {
    	foreach ($this->docents as $docencia) {
    		if (!$docencia->anulada() && !$docencia->esDirector() && !$docencia->esCodirector() && 
    			$docencia->getMetadocent() != null && 
    			$meta == $docencia->getMetadocent()->getId()) return $docencia;
    	}
		return null;
    }
	
	
	public function getDirector()
    {
    	$director = $this->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_DIRECTOR);
		if (count($director) > 1) return null;
    	return array_shift($director);
    }

	public function getCodirector()
    {
    	$codirector = $this->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_CODIRECTOR);
		if (count($codirector) > 1) return null;
    	return array_shift($codirector);
    }
	
	public function getParticipantsSortedByCognomsNom($baixes = false)
    {
    	/* Ordenades de primer a últim */
    	$arr = array();
    	foreach ($this->participants as $titulacio) {
    		if (!$titulacio->anulada() || $baixes == true) $arr[] = $titulacio;
    	}

    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getMetapersona()->getCognomsNom() > $b->getMetapersona()->getCognomsNom())? 1:-1;
    	});
    	return $arr;
    }
	
	public function getParticipantById($id)
    {
    	foreach ($this->participants as $titulacio) {
    		if ($id == $titulacio->getId()) return $titulacio;
    	}
		return null;
    }
	
	
	public function getParticipantByMetaId($meta)
    {
    	foreach ($this->participants as $titulacio) {
    		if (!$titulacio->anulada() && 
    			$titulacio->getMetapersona() != null && 
    			$meta == $titulacio->getMetapersona()->getId()) return $titulacio;
    	}
		return null;
    }
	
	public function getParticipantsIds()
    {
		$participants = $this->getParticipantsSortedByCognomsNom();
		$arr = array();
		foreach ($participants as $participant) $arr[] = $participant->getId();
			
		return $arr;		
	}
	
	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}
	
	/**
	 * @param integer $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set club
	 *
	 * @param EntityClub $club
	 */
	public function setClub(EntityClub $club = null)
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
	 * Set editor
	 *
	 * @param EntityUser $editor
	 */
	public function setEditor(EntityUser $editor = null)
	{
		$this->editor = $editor;
	}
	
	/**
	 * Get editor
	 *
	 * @return EntityUser
	 */
	public function getEditor()
	{
		return $this->editor;
	}

	/**
     * Set clubhistoric
     *
     * @param string $clubhistoric
     */
    public function setClubhistoric($clubhistoric)
    {
        $this->clubhistoric = $clubhistoric;
    }

    /**
     * Get clubhistoric
     *
     * @return string 
     */
    public function getClubhistoric()
    {
        return $this->clubhistoric;
    }

	/**
	 * @return string
	 */
	public function getNum() {
		return $this->num;
	}
	
	/**
	 * @param string $num
	 */
	public function setNum($num) {
		$this->num = $num;
	}

	/**
	 * @return string
	 */
	public function getNumfedas() {
	    return $this->numfedas;
	}
	
	/**
	 * @param string $numfedas
	 */
	public function setNumfedas($numfedas) {
	    $this->numfedas = $numfedas;
	}
	
	/**
	 * @param \DateTime $datadesde
	 */
	public function setDatadesde($datadesde) {
		$this->datadesde = $datadesde;
	}
	
	/**
	 * @return \DateTime
	 */
	public function getDatadesde() {
		return $this->datadesde;
	}

	/**
	 * @param \DateTime $datafins
	 */
	public function setDatafins($datafins) {
		$this->datafins = $datafins;
	}
	
	/**
	 * @return \DateTime
	 */
	public function getDatafins() {
		return $this->datafins;
	}
	
	/**
	 * Set titol
	 *
	 * @param EntityTitol $titol
	 */
	public function setTitol(EntityTitol $titol = null)
	{
		$this->titol = $titol;
	}
	
	/**
	 * Get titol
	 *
	 * @return EntityTitol
	 */
	public function getTitol()
	{
		return $this->titol;
	}
	
	/**
	 * Set stock
	 *
	 * @param EntityStock $stock
	 */
	public function setStock(EntityStock $stock = null)
	{
	    $this->stock = $stock;
	}
	
	/**
	 * Get stock
	 *
	 * @return EntityStock
	 */
	public function getStock()
	{
	    return $this->stock;
	}
	
	
	/**
     * Add docencia
     *
     * @param EntityDocencia $docencia
     */
    public function addDocencia(EntityDocencia $docencia)
    {
        $this->docents->add($docencia);
    }

	/**
     * Remove docencia
     *
     * @param EntityDocencia $docencia
     */
    public function removeDocencia(EntityDocencia $docencia)
    {
        $this->docents->removeElement($docencia);
    }

    /**
     * Get docencies
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDocencies()
    {
        return $this->docencies;
    }
	
	/**
     * Add participant
     *
     * @param EntityTitulacio $participants
     */
    public function addParticipant(EntityTitulacio $participant)
    {
        $this->participants->add($participant);
    }

	/**
     * Remove participant
     *
     * @param EntityTitulacio $participants
     */
    public function removeParticipant(EntityTitulacio $participant)
    {
        $this->participants->removeElement($participant);
    }

    /**
     * Get participants
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getParticipants()
    {
        return $this->participants;
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
     * Set editable
     *
     * @param boolean $editable
     */
    public function setEditable($editable)
    {
    	$this->editable = $editable;
    }
    
    /**
     * Get editable
     *
     * @return boolean
     */
    public function getEditable()
    {
    	return $this->editable;
    }
	
    /**
     * Set finalitzat
     *
     * @param boolean $finalitzat
     */
    public function setFinalitzat($finalitzat)
    {
    	$this->finalitzat = $finalitzat;
    }
    
    /**
     * Get finalitzat
     *
     * @return boolean
     */
    public function getFinalitzat()
    {
    	return $this->finalitzat;
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
	
}
