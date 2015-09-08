<?php
namespace FecdasBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="m_factures")
 * 
 * @author alex
 *
 */
class EntityFactura {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datafactura;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $num;

	/**
	 * @ORM\Column(type="decimal", precision=8, scale=2)
	 */
	protected $import;

	/**
	 * @ORM\Column(type="text")
	 */
	protected $concepte;

	/**
	 * @ORM\OneToOne(targetEntity="EntityComanda", mappedBy="factura")
	 **/
	protected $comanda;	
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityComanda", inversedBy="facturesanulacions" )
	 * @ORM\JoinColumn(name="comandaanulacio", referencedColumnName="id")
	 */
	protected $comandaanulacio; 
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datapagament;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityComptabilitat")
	 * @ORM\JoinColumn(name="comptabilitat", referencedColumnName="id")
	 */
	protected $comptabilitat;	// FK taula m_comptabilitat => Enviament programa compta
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->id = 0;
		$this->dataentrada = new \DateTime();
	
		// Hack per permetre múltiples constructors
		$a = func_get_args();
		$i = func_num_args();
	
		if ($i > 1 && method_exists($this,$f='__constructParams')){
			call_user_func_array(array($this,$f),$a);
		}
	}
	
	public function __constructParams($datafactura, $num, $comanda = null, $import = 0, $concepte = '') {
	
		$this->datafactura = $datafactura;
		$this->num = $num;
		$this->import = $import;
		$this->concepte = $concepte;
		$this->comanda = $comanda;
	}

	public function __toString() {
		return $this->getId() . "-" . $this->getNum();
	}
	
	public function esAnulacio()
	{
		return $this->comandaanulacio != null;
	}
	
	public function infoToolTip($admin)
	{
		$toolTip = 'Factura '.($this->esAnulacio()?'anul·lació':'');
		if ($admin == true && $this->comptabilitat != null) $toolTip .= '. Comptabilitat '.$this->comptabilitat->getDataenviament()->format('d/m/Y'); 
		return $toolTip;
	}
	
	/**
	 * Factura format amb any  XXXXX/20XX
	 *
	 * @return string
	 */
	public function getNumFactura() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->datafactura->format("Y");
	}
	
	/**
	 * Factura format curt amb any  XXXXX/XX
	 *
	 * @return string
	 */
	public function getNumFacturaCurt() {
		return str_pad($this->num, 5,"0", STR_PAD_LEFT) . "/".$this->datafactura->format("y");
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
	 * @return datetime
	 */
	public function getDatafactura() {
		return $this->datafactura;
	}

	/**
	 * @param datetime $datafactura
	 */
	public function setDatafactura($datafactura) {
		$this->datafactura = $datafactura;
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
	 * @return text
	 */
	public function getConcepte() {
		return $this->concepte;
	}

	/**
	 * @param text $concepte
	 */
	public function setConcepte($concepte) {
		$this->concepte = $concepte;
	}

	/**
	 * @return comanda
	 */
	public function getComanda() {
		return $this->comanda;
	}
	
	/**
	 * @param \FecdasBundle\Entity\EntityComanda $comanda
	 */
	public function setComanda(\FecdasBundle\Entity\EntityComanda $comanda = null) {
		$this->comanda = $comanda;
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
	public function setComandaanulacio(\FecdasBundle\Entity\EntityComanda $comandaanulacio = null) {
		$this->comandaanulacio = $comandaanulacio;
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
	
}
