<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class FormLlicenciaRenovar extends FormLlicencia {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->add('cloneid', 'hidden', array('mapped'  => false,));
		
		$builder->remove('persona');  // impedir editar persona
		
		$builder->add('personashow', 'text', array(
				'read_only' => true,
				'mapped' => false,
		));
	}
	
	public function getName()
	{
		return 'llicencia_renovar';
	}

}
