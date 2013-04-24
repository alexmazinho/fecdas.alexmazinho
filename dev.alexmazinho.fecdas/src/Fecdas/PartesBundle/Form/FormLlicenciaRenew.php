<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\FormBuilder;

class FormLlicenciaRenew extends FormLlicencia {

	public function buildForm(FormBuilder $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->add('renovar', 'checkbox', array(
				'required'  => false,
				'label' => '',
				'property_path' => false,
		));
		
		$builder->get('renovar')->setData( true );
	}
	
	public function getName()
	{
		return 'llicencia_renew';
	}

}
