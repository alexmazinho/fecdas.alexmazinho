<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use FecdasBundle\Controller\BaseController;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_rebuts")
 * 
 * @author alex
 *
 */
class EntityRebut {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datapagament;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $num;
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $import;

	/**
	 * @ORM\OneToMany(targetEntity="EntityComanda", mappedBy="rebut" )
	 */
	protected $comandes;	// FK taula m_comandes
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityComanda", inversedBy="rebutsanulacions")
	 * @ORM\JoinColumn(name="comandaanulacio", referencedColumnName="id")
	 */
	protected $comandaanulacio;	// FK taula m_comandes 
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub", inversedBy="ingresos")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;	// FK taula m_clubs
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityComptabilitat")
	 * @ORM\JoinColumn(name="comptabilitat", referencedColumnName="id")
	 */
	protected $comptabilitat;	// FK taula m_comptabilitat => Enviament programa compta
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $tipuspagament;  // Llista
	
	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	protected $dadespagament;  // Del TPV per exemple

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentari;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $enviat;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datamodificacio;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
	
		$this->id = 0;
		$this->dataentrada = new \DateTime();
		$this->datamodificacio = new \DateTime();
		
		// Hack per permetre múltiples constructors
		$a = func_get_args();
		$i = func_num_args();
	
		if ($i > 1 && method_exists($this,$f='__constructParams')) {
			call_user_func_array(array($this,$f),$a);
		}
	}
	
	
	public function __constructParams($datapagament, $tipusPagament, $num, $comanda = null, $club = null, $import = 0) {
	
		$this->datapagament = $datapagament;
		
		$this->tipuspagament = $tipusPagament;
		$this->num = $num;
		$this->comandes = new \Doctrine\Common\Collections\ArrayCollection();
		$this->enviat = false;
		if ($comanda == null) { // Ingrés no  associat a cap comanda
			$this->club = $club;
			$this->import = $import;
			$this->comentari = "Ingrés a compte del club ".($this->club!=null?$this->club->getNom():'');
		} else {  // Pagament d'una comanda
			//$this->club = ($club != null?$club:$comanda->getClub());
			$this->club = $comanda->getClub();
			$this->import = ($import != 0?$import:$comanda->getTotalComanda()); // Total a partir de les dades de la Factura per si està modificada

			if ($this->import < 0) {
				if (abs($this->import) >= $comanda->getTotalComanda()) $this->comentari = "Rebut anul·lació comanda ".$comanda->getNumComanda();
				else $this->comentari = "Rebut anul·lació parcial comanda ".$comanda->getNumComanda();
					
				$this->setComandaanulacio($comanda);
				$comanda->addrebutsanulacions($this);
			} else {
				$this->comentari = "Rebut comanda ".$comanda->getNumComanda()." ".$comanda->getTipusComanda();
				$this->addComanda($comanda);
				$comanda->setRebut($this);
			} 
		}
		if ($this->club != null) {
			$this->updateClubPagaments($this->import);
			$this->club->addEntityRebut($this);
		} 
	}
	
	/**
	 * Actualitza pagaments club. Si el pagament es de l'any anterior modifica romanent
	 * 
	 */
	public function updateClubPagaments($import) {
			
		if ($this->club == null) return false;
		
		//if ($this->datapagament != null && $this->datapagament->format('Y') < date('Y')) $this->club->setRomanent($this->club->getRomanent() + $import);
		if ($this->datapagament != null && $this->datapagament->format('Y') < $this->club->getExercici()) $this->club->setRomanent($this->club->getRomanent() + $import);
		else $this->club->setTotalpagaments($this->club->getTotalpagaments() + $import);
		
		return true; 
	}
	
	
	
	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}
	
	/**
     * Get any
     *
     * @return string
     */
    public function getAny()
    {
    	return $this->datapagament->format('Y');
    }

	/**
     * Is a current year comanda
     *  
     * @return boolean
     */
    public function isCurrentYear() {
    	return (date("Y", $this->datapagament->getTimestamp()) == date("Y"));
    }
    
    /**
     * Es pot esborrar?
     *
     * @return boolean
     */
    public function esborrable() {
        return $this->isCurrentYear() && $this->getTipuspagament() != BaseController::TIPUS_PAGAMENT_TPV;
    }

	/**
	 * Get concepte rebut/ingrés
	 *
	 * @return string
	 */
	public function getConcepteRebutLlarg()
	{
		if ($this->esAnulacio()) return 'REBUT ANUL·LACIO: '.$this->getNumRebut().'. FACTURA ANUL·LACIO: '.$this->comandaanulacio->getFactura()->getNumFactura();	
			
		$concepte = 'REBUT: '.$this->getNumRebut().'.';
			
		if (count($this->comandes) == 0) return $concepte.' INGRES '.$this->getClub()->getNom();  // Ingrés a compte
		
		if ($this->getNumFactures() == 1) {
			$comanda = $this->comandes[0];
			return $concepte.' FACTURA: '.$comanda->getFactura()->getNumFactura();	
		}
		return $concepte. ' FACTURES: '.$this->getLlistaNumsFactures();
	}
	
	/**
	 * Get concepte rebut/ingrés
	 *
	 * @return string
	 */
	public function getConcepteRebutCurt()
	{
		if ($this->esAnulacio()) return 'Fra. ANUL·LACIO: '.$this->comandaanulacio->getFactura()->getNumFactura();		
			
		//if (count($this->comandes) == 0) return "A compte ".$this->getNumRebut();  // Ingrés a compte
		if (count($this->comandes) == 0) return $this->comentari;  // Ingrés a compte
		
		if ($this->getNumFactures() == 1) {
			$comanda = $this->comandes[0];
			
			return 'Fra. '.$comanda->getFactura()->getNumFactura();	
		}
		return 'Fra/s. '.$this->getLlistaNumsFactures(true);
	}

	/**
	 * Get array nums totes comandes
	 *
	 * @return array
	 */
	public function getArrayNumsComandes()
	{
		if ($this->esAnulacio()) return array ( $this->comandaanulacio->getNum() => array ( 'num' => $this->comandaanulacio->getNumComanda(), 'import' => $this->comandaanulacio->getTotalComanda() ));
			
		$nums = array();
		foreach ($this->comandes as $comanda) {
			$nums[ $comanda->getNum() ] = array ( 'num' => $comanda->getNumComanda(), 'import' => $comanda->getTotalComanda() );	
		}
		return $nums;
	}

	/**
	 * Get nums factures totes comandes
	 *
	 * @return string
	 */
	public function getLlistaNumsFactures($curt = false)
	{
		if ($this->esAnulacio()) return $this->comandaanulacio->getFactura()->getNumFactura();
			
		$concepte = '';
		foreach ($this->comandes as $comanda) {
			$concepte .= $comanda->getLlistaNumsFactures($curt).($curt == true?'-':', '); 	
		}
		return ($curt == true?substr($concepte, 0, -1):substr($concepte, 0, -2));
		
	}

	/**
	 * Get # factures 
	 *
	 * @return string
	 */
	public function getNumFactures()
	{
		if ($this->esAnulacio()) return 1;
			
		$total = 0;
		foreach ($this->comandes as $comanda) {
			$total += $comanda->getNumFactures(); 	
		}
		
		return $total;		
	}
	/**
	 * Rebut format amb any  XXXXX/20XX
	 *
	 * @return string
	 */
	public function getNumRebut() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->datapagament->format("Y");
	}
	
	/**
	 * Rebut format curt amb any  XXXXX/XX
	 *
	 * @return string
	 */
	public function getNumRebutCurt() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->datapagament->format("y");
	}
	
	public function esAnulacio()
	{
		return $this->comandaanulacio != null;
	}

	public function esIngres()
	{
		return $this->comandaanulacio == null && count($this->comandes) == 0;
	}

	public function estaComptabilitzat()
	{
		return $this->comptabilitat != null;
	}
	
	
	public function infoToolTip($admin)
	{
		$toolTip = 'Rebut '.($this->esAnulacio()?'anul·lació':'');
		$toolTip .= ' - '.number_format($this->import, 2, ',', '.');
		if ($admin == true && $this->comptabilitat != null) $toolTip .= '. Enviat a comptabilitat '.$this->comptabilitat->getDataenviament()->format('d/m/Y'); 
		return $toolTip;
	}
	
	/* Diferència entre suma comandes i import rebut */
	public function getRomanent() {
		$romanent = $this->getImport();
		foreach ($this->comandes as $comanda) {
			$romanent -= $comanda->getTotalComanda();
		}
		return $romanent;		
	}
	
	/**
	 * @return integer
	 */
	public function getId() {
		return $this->id;
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
	public function getNum() {
		return $this->num;
	}
	
	/**
	 * @param integer $num
	 */
	public function setNum($num) {
		$this->num = $num;
	}
	
	/**
	 * @return datetime
	 */
	public function getDatapagament() {
		return $this->datapagament;
	}

	/**
	 * @param datetime $datapagament
	 */
	public function setDatapagament($datapagament) {
		$this->datapagament = $datapagament;
	}

	/**
	 * @return decimal
	 */
	public function getImport() {
		return $this->import;
	}

	/**
	 * @param decimal $import
	 */
	public function setImport($import) {
		$this->import = $import;
	}

	/**
	 * @return comandaanulacio
	 */
	public function getComandaanulacio() {
		return $this->comandaanulacio;
	}
	
	/**
	 * @param \FecdasBundle\Entity\EntityComanda $comandaanulacio
	 */
	public function setComandaanulacio(\FecdasBundle\Entity\EntityComanda $comandaanulacio) {
		$this->comandaanulacio = $comandaanulacio;
	}

	/**
     * Add comandes
     *
     * @param EntityComanda $comandes
     * @return EntityRebut
     */
    public function addComanda(EntityComanda $comanda) 
    {
    	$this->comandes->add($comanda);

        return $this;
    }

    /**
     * Get comandes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getComandes()
    {
        return $this->comandes;
    }

    /**
     * Remove comandes
     *
     * @param EntityComanda $comandes
     */
    public function removeComanda(EntityComanda $comanda)
    {
        $this->comandes->removeElement($comanda);
    }

    /**
     * Remove all comandes
     *
     */
    public function resetComandes()
    {
    	$this->comandes = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set comandes
     *
     * @param \Doctrine\Common\Collections\Collection $comandes
     */
    public function setComandes(\Doctrine\Common\Collections\ArrayCollection $comandes)
    {
    	$this->comandes = $comandes;
    }  


	/**
	 * Set club
	 *
	 * @param \FecdasBundle\Entity\EntityClub $club
	 * @return EntityRebut
	 */
	public function setClub(\FecdasBundle\Entity\EntityClub $club = null)
	{
		$this->club = $club;
	
		return $this;
	}
	
	/**
	 * Get club
	 *
	 * @return \FecdasBundle\Entity\EntityClub
	 */
	public function getClub()
	{
		return $this->club;
	}
	
	
	/**
	 * Set comptabilitat
	 *
	 * @param \FecdasBundle\Entity\EntityComptabilitat $comptabilitat
	 * @return EntityRebut
	 */
	public function setComptabilitat(\FecdasBundle\Entity\EntityComptabilitat $comptabilitat = null)
	{
		$this->comptabilitat = $comptabilitat;
	
		return $this;
	}
	
	/**
	 * Get comptabilitat
	 *
	 * @return \FecdasBundle\Entity\EntityComptabilitat
	 */
	public function getComptabilitat()
	{
		return $this->comptabilitat;
	}
	
	/**
	 * @return integer
	 */
	public function getTipuspagament() {
		return $this->tipuspagament;
	}
	
	/**
	 * @param integer $tipuspagament
	 */
	public function setTipuspagament($tipuspagament) {
		$this->tipuspagament = $tipuspagament;
	}
	
	
	/**
	 * @return string
	 */
	public function getDadespagament() {
		return $this->dadespagament;
	}

	/**
	 * @param string $dadespagament
	 */
	public function setDadespagament($dadespagament) {
		$this->dadespagament = substr($dadespagament,0,30);
	}

	/**
	 * @return text
	 */
	public function getComentari() {
		return $this->comentari;
	}

	/**
	 * @param text $comentari
	 */
	public function setComentari($comentari) {
		$this->comentari = $comentari;
	}

	/**
     * Set enviat
     *
     * @param boolean $enviat
     */
    public function setEnviat($enviat)
    {
    	$this->enviat = $enviat;
    }
    
    /**
     * Get enviat
     *
     * @return boolean
     */
    public function getEnviat()
    {
    	return $this->enviat;
    }
	

	/**
	 * @return datetime
	 */
	public function getDataentrada() {
		return $this->dataentrada;
	}
	
	/**
	 * @param datetime $dataentrada
	 */
	public function setDataentrada($dataentrada) {
		$this->dataentrada = $dataentrada;
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
	
}
