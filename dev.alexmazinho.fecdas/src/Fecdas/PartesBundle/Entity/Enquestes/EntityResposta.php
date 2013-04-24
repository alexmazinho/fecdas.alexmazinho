<?php
namespace Fecdas\PartesBundle\Entity\Enquestes;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="e_respostes")
 * 
 * @author alex
 *
 */
class EntityResposta {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	

	/**
	 * @ORM\ManyToOne(targetEntity="EntityRealitzacio")
	 * @ORM\JoinColumn(name="realitzacio", referencedColumnName="id")
	 */
	protected $realitzacio;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityPregunta")
	 * @ORM\JoinColumn(name="pregunta", referencedColumnName="id")
	 */
	protected $pregunta;
	
	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $repostatxt;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $repostabool;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $repostarang;
	
	
	public function __construct($user, $enquesta) {
		$this->repostabool = true;
	}
}