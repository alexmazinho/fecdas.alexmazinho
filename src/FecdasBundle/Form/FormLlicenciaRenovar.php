<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class FormLlicenciaRenovar extends FormLlicencia {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->add('cloneid', 'hidden', array('mapped'  => false,));
		
		$builder->remove('persona');  // impedir editar persona
		
		$builder->add('personashow', 'text', array(
				'mapped' => false,
				'attr'			=>	array('readonly' => true)
		));
	}
	
	public function getName()
	{
		return 'llicencia_renovar';
	}

}
