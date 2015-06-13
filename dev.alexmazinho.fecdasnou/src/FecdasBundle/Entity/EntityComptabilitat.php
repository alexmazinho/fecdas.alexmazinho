<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Enviaments a comptabilitat
 * 
 * @ORM\Entity
 * @ORM\Table(name="m_comptabilitat")
 * 
 * @author alex
 *
 */
class EntityComptabilitat {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $comentaris; // En cas de baixa per exemple
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataenviament;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityComanda", mappedBy="comptabilitat")
	 */
	protected $comandes;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datamodificacio;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;
	
	public function __construct() {
		$this->comandes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set comentaris
     *
     * @param string $comentaris
     * @return EntityComptabilitat
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
     * Set dataenviament
     *
     * @param \DateTime $dataenviament
     * @return EntityComptabilitat
     */
    public function setDataenviament($dataenviament)
    {
        $this->dataenviament = $dataenviament;

        return $this;
    }

    /**
     * Get dataenviament
     *
     * @return \DateTime 
     */
    public function getDataenviament()
    {
        return $this->dataenviament;
    }

    /**
     * Set datamodificacio
     *
     * @param \DateTime $datamodificacio
     * @return EntityComptabilitat
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
     * @return EntityComptabilitat
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
     * Add comanda
     *
     * @param \FecdasBundle\Entity\EntityComanda $comanda
     * @return EntityComptabilitat
     */
    public function addComanda(\FecdasBundle\Entity\EntityComanda $comanda)
    {
    	$comanda->setComanda($this);
    	$this->comandes->add($comanda);
    	//$this->comandes[] = $comandes;

        return $this;
    }

    /**
     * Remove comanda
     *
     * @param \FecdasBundle\Entity\EntityComanda $comanda
     */
    public function removeComanda(\FecdasBundle\Entity\EntityComanda $comanda)
    {
        $this->comandes->removeElement($comanda);
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
}