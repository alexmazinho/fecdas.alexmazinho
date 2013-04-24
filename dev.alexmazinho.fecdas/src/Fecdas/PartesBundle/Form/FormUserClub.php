<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\FormBuilder;

class FormUserClub extends FormUser {

	public function buildForm(FormBuilder $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->add('role', 'choice', array(
				'choices' => array('user'=> 'user'),
				'data' => 'user',
		));
		
		$builder->get('user')->setReadOnly(false);
	}
	
	public function getDefaultOptions(array $options)
	{
		return array(
				'data_class' => 'Fecdas\PartesBundle\Entity\EntityUser',
		);
	}
	
	public function getName()
	{
		return 'user_club';
	}

}
