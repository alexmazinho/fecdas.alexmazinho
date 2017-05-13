<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Entity\EntityDocencia;


class FormDocencia extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$docencia = $event->getData();
			
			/* Check we're looking at the right data/form */
			if ($docencia instanceof EntityDocencia) {
				
				$editable = false;
				$curs = $docencia->getCurs();
				if ($curs != null && !$curs->omplert()) {
					// Curs en procés
					$editable = true;	
				}
					
				$form->add('auxdocentdni', 'text', array(
					'mapped'	=> false,
					'attr'		=>	array('readonly' => !$editable)
				));	
				
				$form->add('auxdocentnom', 'text', array(
					'mapped'	=> false,
					'attr'		=>	array('readonly' => false)
				));
				
				$form->add ('hteoria', 'integer', array (
					'required' 	=> true,
					'scale' 	=> 0,
					'attr'		=>	array('readonly' => !$editable)
				));
				
				$form->add ('haula', 'integer', array (
					'required' 	=> true,
					'scale' 	=> 0,
					'attr'		=>	array('readonly' => !$editable)
				));
				
				$form->add ('hpiscina', 'integer', array (
					'required' 	=> true,
					'scale' 	=> 0,
					'attr'		=>	array('readonly' => !$editable)
				));
				
				$form->add ('hmar', 'integer', array (
					'required' 	=> true,
					'scale' 	=> 0,
					'attr'		=>	array('readonly' => !$editable)
				));
		
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('rol', 'hidden');
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityDocencia'));
	}
		
	public function getName()
	{
		return 'docencia';
	}

}
