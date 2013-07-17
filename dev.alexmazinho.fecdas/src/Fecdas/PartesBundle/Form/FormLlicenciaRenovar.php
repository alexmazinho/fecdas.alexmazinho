<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\FormBuilder;
use Fecdas\PartesBundle\Entity\EntityPersona;

class FormLlicenciaRenovar extends FormLlicencia {

	public function buildForm(FormBuilder $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->add('cloneid', 'hidden', array('property_path'  => false,));
		
		$builder->remove('persona');  // impedir editar persona
		
		$builder->add('personashow', 'text', array(
				'read_only' => true,
				'property_path' => false,
		));
	}
	
	public function getName()
	{
		return 'llicencia_renovar';
	}

}
