<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;


class FormRebut extends AbstractType {

	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$rebut = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($rebut instanceof EntityRebut) {
				
				$form->add('numrebut', 'text', array(
						'required' => true,
						'read_only' => true,
						'mapped'	=> false,
						'data'		=> $rebut->getNumRebut()
				));
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('num', 'hidden');
		
		$builder->add('datapagament', 'date', array(
				'read_only' => true,
				'widget' => 'single_text',
				'input' => 'date',
				'empty_value' => false,
				'format' => 'dd/MM/yyyy',
		));
		
		$builder->add ( 'import', 'number', array (
				'required' => true,
				'scale' => 2,
				'mapped' => false
		));
		
		$builder->add('dadespagament', 'text', array(
				'required' => false,
		));
		
		$builder->add('comentaris', 'textarea', array(
				'required' => false,
		));
		
		
		$builder->add('comanda', 'entity', array(
				'class' => 'FecdasBundle:EntityComanda',
				'choice_label' => 'InfoComanda',
				'empty_value' => '',
				'required'  => false,
				'read_only' => true,
		));
		
		$builder->add('dataanulacio', 'date', array(
				'read_only' => true,
				'widget' => 'single_text',
				'input' => 'date',
				'empty_value' => false,
				'format' => 'dd/MM/yyyy',
		));
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityRebut'));
	}
		
	public function getName()
	{
		return 'rebut';
	}

}
