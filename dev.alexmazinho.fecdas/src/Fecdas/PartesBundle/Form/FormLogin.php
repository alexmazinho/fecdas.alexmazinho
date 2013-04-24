<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class FormLogin extends AbstractType {

	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder->add('user', 'email');
		$builder->add('pwd', 'password');
	}
	
	public function getName()
	{
		return 'login';
	}
	
	
}
