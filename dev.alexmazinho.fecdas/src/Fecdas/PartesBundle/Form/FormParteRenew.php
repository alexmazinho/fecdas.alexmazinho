<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormParteRenew extends FormParte {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->add('cloneid', 'hidden', array('mapped'  => false,));
		
		$builder->add('llicencies', 'collection', array('type' => new FormLlicenciaRenew($this->options)));
	}
	
	public function getName()
	{
		return 'parte_renew';
	}

}
