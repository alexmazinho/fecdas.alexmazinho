<?php
namespace Fecdas\PartesBundle\Entity\Enquestes;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="e_enquestes")
 * 
 * @author alex
 *
 */
class EntityEnquesta {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataalta;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $datainici;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datafinal;
	
	/**
	 * @ORM\ManyToMany(targetEntity="EntityResposta")
	 * @ORM\JoinTable(name="e_enquestes_respostes",
	 *      joinColumns={@ORM\JoinColumn(name="enquesta", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="resposta", referencedColumnName="id")}
	 *      )
	 */
	protected $preguntes;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityRealitzacio", mappedBy="enquesta")
	 */
	protected $realitzacions;	// Owning side of the relationship
	
	public function __construct($currentDate) {
		$this->setDataalta($currentDate);
		$this->preguntes = new \Doctrine\Common\Collections\ArrayCollection();
		$this->realitzacions = new \Doctrine\Common\Collections\ArrayCollection();
	}
}