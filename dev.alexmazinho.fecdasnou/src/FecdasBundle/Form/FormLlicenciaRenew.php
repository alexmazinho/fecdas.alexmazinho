<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormLlicenciaRenew extends FormLlicencia {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->add('renovar', 'checkbox', array(
				'required'  => false,
				'label' => '',
				'mapped' => false,
		));
		
		$builder->get('renovar')->setData( true );
	}
	
	public function getName()
	{
		return 'llicencia_renew';
	}

}
