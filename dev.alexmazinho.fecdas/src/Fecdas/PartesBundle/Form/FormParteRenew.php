<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\FormBuilder;

class FormParteRenew extends FormParte {

	public function buildForm(FormBuilder $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->add('cloneid', 'hidden', array('property_path'  => false,));
		
		$builder->add('llicencies', 'collection', array('type' => new FormLlicenciaRenew($this->options)));
	}
	
	public function getName()
	{
		return 'parte_renew';
	}

}
