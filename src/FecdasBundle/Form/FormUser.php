<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormUser extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('user', 'email',array(
    	    'attr'		=>	array('readonly' => true)
		));
		
		$builder->add('pwd', 'repeated', array(
    		'type' => 'password',
    		'required' => true,
    		'first_name'  => 'first',
    		'second_name' => 'second',
		));
		
		/*$builder->add('forceupdate', 'checkbox', array(
    	    'required'  => false
		));*/
		
		$builder->add('recoverytoken', 'hidden');
		$builder->add('usertoken', 'hidden', array('mapped' => false));
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityUser'));
	}
	
	public function getName()
	{
		return 'user';
	}

}
