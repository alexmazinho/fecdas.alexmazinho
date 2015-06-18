<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_comandes")
 * 
 * @author alex
 *
 */
class EntityComanda {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $num;
	
	/**
	 * @ORM\Column(type="text")
	 */
	protected $comentaris;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityClub")
	 * @ORM\JoinColumn(name="club", referencedColumnName="codi")
	 */
	protected $club;	// FK taula m_clubs
	
	/**
	 * @ORM\OneToOne(targetEntity="EntityFactura", inversedBy="comanda")
	 * @ORM\JoinColumn(name="factura", referencedColumnName="id")
	 **/
	protected $factura;	// FK taula m_factures

	/**
	 * @ORM\ManyToOne(targetEntity="EntityComptabilitat")
	 * @ORM\JoinColumn(name="comptabilitat", referencedColumnName="id")
	 */
	protected $comptabilitat;	// FK taula m_comptabilitat => Enviament programa compta
	
	/**
	 * @ORM\OneToOne(targetEntity="EntityRebut", inversedBy="comanda")
	 * @ORM\JoinColumn(name="rebut", referencedColumnName="id")
	 **/
	protected $rebut;	// FK taula m_rebuts
	
	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2)
	 */
	protected $total;  // Es pot editar preu
	
	/**
	 * @ORM\Column(type="decimal", precision=4, scale=2)
	 */
	protected $descomptetotal;  
	
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
	 * @ORM\OneToMany(targetEntity="EntityComandaDetall", mappedBy="comanda")
	 */
	protected $detalls;

	public function __construct() {
		
		$this->detalls = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/**
	 * Comanda format amb any  XXXXX/20XX
	 *
	 * @return string
	 */
	public function getNumComanda() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->dataentrada->format("Y");
	}
	
	/**
	 * Get Info comanda
	 *
	 * @return string
	 */
	public function getInfoComanda()
	{
		return $this->getNumComanda().", dia ".$this->dataentrada->format("d/m/Y").
			", club ".$this->getClub()->getNom().". Total: ".number_format($this->total, 2, ',', '.');
	}

	/**
	 * Get total suma dels detalls
	 *
	 * @return double
	 */
	public function getTotalDetalls()
	{
		$total = 0;
		foreach ($this->detalls as $d) $total += $d->getTotal();
		return $total;
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

    /**
     * @param integer $num
     * @return EntityComanda
     */
    public function setNum($num) {
    	$this->num = $num;
    	
    	return $this;
    }
    
    /**
     * @return integer
     */
    public function getNum() {
    	return $this->num;
    }
    
    /**
     * Set comentaris
     *
     * @param string $comentaris
     * @return EntityComanda
     */
    public function setComentaris($comentaris)
    {
        $this->comentaris = $comentaris;

        return $this;
    }

    /**
     * Get comentaris
     *
     * @return string 
     */
    public function getComentaris()
    {
        return $this->comentaris;
    }

    /**
     * Set total
     *
     * @param string $total
     * @return EntityComanda
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return string 
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set descomptetotal
     *
     * @param string $descomptetotal
     * @return EntityComanda
     */
    public function setDescomptetotal($descomptetotal)
    {
        $this->descomptetotal = $descomptetotal;

        return $this;
    }

    /**
     * Get descomptetotal
     *
     * @return string 
     */
    public function getDescomptetotal()
    {
        return $this->descomptetotal;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return EntityComanda
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;

        return $this;
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
     * @return EntityComanda
     */
    public function setDatamodificacio($datamodificacio)
    {
        $this->datamodificacio = $datamodificacio;

        return $this;
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
     * @return EntityComanda
     */
    public function setDatabaixa($databaixa)
    {
        $this->databaixa = $databaixa;

        return $this;
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
     * @param \FecdasBundle\Entity\EntityClub $club
     * @return EntityComanda
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
     * Set factura
     *
     * @param \FecdasBundle\Entity\EntityFactura $factura
     * @return EntityComanda
     */
    public function setFactura(\FecdasBundle\Entity\EntityFactura $factura = null)
    {
        $this->factura = $factura;

        return $this;
    }

    /**
     * Get factura
     *
     * @return \FecdasBundle\Entity\EntityFactura 
     */
    public function getFactura()
    {
        return $this->factura;
    }

    /**
     * Set rebut
     *
     * @param \FecdasBundle\Entity\EntityRebut $rebut
     * @return EntityComanda
     */
    public function setRebut(\FecdasBundle\Entity\EntityRebut $rebut = null)
    {
        $this->rebut = $rebut;

        return $this;
    }

    /**
     * Get rebut
     *
     * @return \FecdasBundle\Entity\EntityRebut 
     */
    public function getRebut()
    {
        return $this->rebut;
    }

    /**
     * Add detall
     *
     * @param \FecdasBundle\Entity\EntityComandaDetall $detall
     * @return EntityComanda
     */
    public function addDetall(\FecdasBundle\Entity\EntityComandaDetall $detall) 
    {
    	
    	$detall->setComanda($this);
    	$this->detalls->add($detall);
        //$this->detalls[] = $detalls;

        return $this;
    }

    /**
     * Get detalls
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDetalls()
    {
        return $this->detalls;
    }

    /**
     * Remove detalls
     *
     * @param \FecdasBundle\Entity\EntityComandaDetall $detalls
     */
    public function removeDetall(\FecdasBundle\Entity\EntityComandaDetall $detalls)
    {
        $this->detalls->removeElement($detalls);
    }

    /**
     * Set comptabilitat
     *
     * @param \FecdasBundle\Entity\EntityComptabilitat $comptabilitat
     * @return EntityComanda
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
}
