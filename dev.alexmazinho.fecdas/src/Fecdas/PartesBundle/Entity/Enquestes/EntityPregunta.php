<?php
namespace Fecdas\PartesBundle\Entity\Enquestes;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="e_preguntes")
 * 
 * @author alex
 *
 */
class EntityPregunta {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	

	/**
	 * @ORM\Column(type="string", length=4)
	 */
	protected $tipus;
	/* OPEN: Oberta, BOOL: Si/NO, RANG: Max Rang: 1 ... 5 */
	
	/**
	 * @ORM\Column(type="text")
	 */
	protected $enunciat;

	/**
	 * @ORM\Column(type="integer")
	 */
	protected $ordre;

	public function __construct($tipus, $enunciat, $ordre) {
		$this->tipus = $tipus;
		$this->enunciat = $enunciat;
		$this->ordre = $ordre;
	}
}