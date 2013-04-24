<?php
namespace Fecdas\PartesBundle\Entity\Enquestes;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="e_realitzacions")
 * 
 * @author alex
 *
 */
class EntityRealitzacio {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	

	/**
	 * @ORM\ManyToOne(targetEntity="EntityUser")
	 * @ORM\JoinColumn(name="user", referencedColumnName="user")
	 */
	protected $user;	// Mail del club
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityEnquesta")
	 * @ORM\JoinColumn(name="enquesta", referencedColumnName="id")
	 */
	protected $enquesta;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datadarreraccess;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityPregunta")
	 * @ORM\JoinColumn(name="darrerapregunta", referencedColumnName="id")
	 */
	protected $darrerapregunta;
	
	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datafinal;
	
	/**
	 * @ORM\OneToMany(targetEntity="EntityResposta", mappedBy="enquesta")
	 */
	protected $respostes;	// Owning side of the relationship
	
	public function __construct($user, $enquesta) {
		$this->user = $user;
		$this->enquesta = $enquesta;
		$this->respostes = new \Doctrine\Common\Collections\ArrayCollection();
	}
}