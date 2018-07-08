<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_titulacions")
 * 
 * @author alex
 *
 */
class EntityTitulacio {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityMetaPersona", inversedBy="titulacions")
	 * @ORM\JoinColumn(name="metapersona", referencedColumnName="id")
	 */
	protected $metapersona;	// FK taula m_metapersones
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityCurs", inversedBy="participants")
	 * @ORM\JoinColumn(name="curs", referencedColumnName="id")
	 */
	protected $curs;	// FK taula m_cursos
	
	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	protected $num;	 // El crea la FEDAS, el número es pot crear en un any diferent de l'inici o la finalització
	
	/**
	 * @ORM\Column(type="date", nullable=true)
	 */
	protected $datasuperacio;

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
	public function __construct($metapersona = null, $curs = null)
	{
		$this->id = 0;
		$this->metapersona = $metapersona;
		$this->curs = $curs;
		$this->num = "Pendent";  // pendent
		$this->dataentrada = new \DateTime();
		$this->datamodificacio = new \DateTime();

	}
	
	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}
	
	/**
	 * Retorna titol del curs
	 * @return string
	 */
	public function getTitol() {
		return $this->curs->getTitol();
	}
	
	/**
	 * Retorna Nom del club
	 * @return string
	 */
	public function getClub() {
		return $this->curs->getClubInfo();
	}
	
	/**
	 * Retorna pertany al club?
	 * @return boolean
	 */
	public function checkClub($club) {
	    if ($this->curs->getClub() == null || $club == null) return false;
		return $this->curs->getClub()->getCodi() == $club->getCodi();
	}
	
	/**
	 * Retorna titulacio anul·lada?
	 * @return boolean
	 */
	public function anulada() {
		return $this->databaixa != null;
	}
	
	/**
	 * Retorna baixa titulacio
	 * @return boolean
	 */
	public function baixa() {
		$this->setDatamodificacio(new \DateTime('now'));
		$this->setDatabaixa(new \DateTime('now'));
	}
	
	/**
	 * Retorna titulacio consolidada?
	 * @return boolean
	 */
	public function consolidada() {
		return !$this->anulada() && $this->datasuperacio != null;
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
	 * Set metapersona
	 *
	 * @param \FecdasBundle\Entity\EntityMetaPersona $metapersona
	 */
	public function setMetapersona(\FecdasBundle\Entity\EntityMetaPersona $metapersona = null)
	{
		$this->metapersona = $metapersona;
	}
	
	/**
	 * Get metapersona
	 *
	 * @return \FecdasBundle\Entity\EntityMetaPersona
	 */
	public function getMetapersona()
	{
		return $this->metapersona;
	}


	/**
	 * Set curs
	 *
	 * @param \FecdasBundle\Entity\EntityCurs $curs
	 */
	public function setCurs(\FecdasBundle\Entity\EntityCurs $curs = null)
	{
		$this->curs = $curs;
	}
	
	/**
	 * Get curs
	 *
	 * @return \FecdasBundle\Entity\EntityCurs
	 */
	public function getCurs()
	{
		return $this->curs;
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
	public function getNum() {
		return $this->num;
	}
	
	/**
	 * @param \DateTime $datasuperacio
	 */
	public function setDatasuperacio($datasuperacio) {
		$this->datasuperacio = $datasuperacio;
	}
	
	/**
	 * @return \DateTime
	 */
	public function getDatasuperacio() {
		return $this->datasuperacio;
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
    
    public static function getTitulacionsSortedBy($titulacions, $sort = 'id', $direction = 'asc')
    {
        usort($titulacions, function($a, $b) use ($sort, $direction) {
            if ($a === $b) {
                return 0;
            }
            $true = $direction == 'asc'? 1:-1;
            $false = $true * -1;
            $result = 0;
            switch ($sort) {
                case 'club':
                    $result = ($a->getCurs()->getClub()->getNom() > $b->getCurs()->getClub()->getNom())? $true:$false;
                    break;
                case 'periode':
                    $result = ($a->getCurs()->getDatadesde() > $b->getCurs()->getDatadesde())? $true:$false;
                    break;
                case 'acta':
                    $result = ($a->getCurs()->getNumActa() > $b->getCurs()->getNumActa())? $true:$false;
                    break;
                case 'titol.codi':
                    $result = ($a->getTitol()->getCodi() > $b->getTitol()->getCodi())? $true:$false;
                    break;
                case 'titol':
                    $result = ($a->getTitol()->getTitol() > $b->getTitol()->getTitol())? $true:$false;
                    break;
                case 'datasuperacio':
                    $result = ($a->getDatasuperacio() > $b->getDatasuperacio())? $true:$false;
                    break;
                case 'num':
                    $result = ($a->getNum() > $b->getNum())? $true:$false;
                    break;
                default:
                    $result = ($a->getId() > $b->getId())? $true:$false;
                    break;
            }
            
            return $result;
        });
        return $titulacions;
    }
}
