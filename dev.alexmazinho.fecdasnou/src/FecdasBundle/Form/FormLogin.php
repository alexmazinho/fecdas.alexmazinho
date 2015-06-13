<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormLogin extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('user', 'email');
		$builder->add('pwd', 'password');
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityUser'));
	}
	
	public function getName()
	{
		return 'login';
	}
	
	
}
