<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormLogin extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('user', 'email');
		$builder->add('pwd', 'password');
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
	    $resolver->setDefaults(array(
	        'data_class' => 'FecdasBundle\Entity\EntityUser'
	    ));
	}
	
	public function getName()
	{
		return 'login';
	}
	
	
}
