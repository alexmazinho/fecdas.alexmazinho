<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_comandadetalls")
 * 
 * @author alex
 *
 */
class EntityComandaDetall {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="EntityComanda")
	 * @ORM\JoinColumn(name="comanda", referencedColumnName="id")
	 */
	protected $comanda;	// FK taula m_comandes
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityProducte")
	 * @ORM\JoinColumn(name="producte", referencedColumnName="id")
	 */
	protected $producte;

	/**
	 * @ORM\Column(type="decimal", precision=6, scale=2)
	 */
	protected $unitats;

	/**
	 * @ORM\Column(type="decimal", precision=4, scale=2)
	 */
	protected $descomptedetall;
	
	/**
	 * @ORM\Column(type="text")
	 */
	protected $anotacions;  // Comentaris a la comanda
	
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
	
	public function __construct() {
		
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
     * Set unitats
     *
     * @param string $unitats
     * @return EntityComandaDetall
     */
    public function setUnitats($unitats)
    {
        $this->unitats = $unitats;

        return $this;
    }

    /**
     * Get unitats
     *
     * @return string 
     */
    public function getUnitats()
    {
        return $this->unitats;
    }

    /**
     * Set descomptedetall
     *
     * @param string $descomptedetall
     * @return EntityComandaDetall
     */
    public function setDescomptedetall($descomptedetall)
    {
        $this->descomptedetall = $descomptedetall;

        return $this;
    }

    /**
     * Get descomptedetall
     *
     * @return string 
     */
    public function getDescomptedetall()
    {
        return $this->descomptedetall;
    }

    /**
     * Set anotacions
     *
     * @param string $anotacions
     * @return EntityComandaDetall
     */
    public function setAnotacions($anotacions)
    {
        $this->anotacions = $anotacions;

        return $this;
    }

    /**
     * Get anotacions
     *
     * @return string 
     */
    public function getAnotacions()
    {
        return $this->anotacions;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return EntityComandaDetall
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
     * @return EntityComandaDetall
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
     * @return EntityComandaDetall
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
     * Set comanda
     *
     * @param \FecdasBundle\Entity\EntityComanda $comanda
     * @return EntityComandaDetall
     */
    public function setComanda(\FecdasBundle\Entity\EntityComanda $comanda = null)
    {
        $this->comanda = $comanda;

        return $this;
    }

    /**
     * Get comanda
     *
     * @return \FecdasBundle\Entity\EntityComanda 
     */
    public function getComanda()
    {
        return $this->comanda;
    }

    /**
     * Set producte
     *
     * @param \FecdasBundle\Entity\EntityProducte $producte
     * @return EntityComandaDetall
     */
    public function setProducte(\FecdasBundle\Entity\EntityProducte $producte = null)
    {
        $this->producte = $producte;

        return $this;
    }

    /**
     * Get producte
     *
     * @return \FecdasBundle\Entity\EntityProducte 
     */
    public function getProducte()
    {
        return $this->producte;
    }
}
