<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormPayment extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('preu', 'hidden');
		$builder->add('numordre', 'hidden');
		$builder->add('codi', 'hidden');
		$builder->add('terminal', 'hidden');
		$builder->add('moneda', 'hidden');
		$builder->add('tipusTx', 'hidden');
		$builder->add('urlmerchant', 'hidden');
		$builder->add('paymethods', 'hidden');
		$builder->add('lang', 'hidden');
		$builder->add('desc', 'hidden');
		$builder->add('titular', 'hidden');
		$builder->add('fecdas', 'hidden');
		$builder->add('dades', 'hidden');
		$builder->add('params', 'hidden');
		$builder->add('signatura', 'hidden');
		$builder->add('version', 'hidden');
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityPayment'));
	}	
	
	public function getName()
	{
		return 'payment';
	}
	
}
