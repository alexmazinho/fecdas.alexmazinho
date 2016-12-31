<?php
namespace FecdasBundle\Form;

use FecdasBundle\Entity\EntityLlicencia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;


class FormLlicenciaSortida extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. 
			// Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$llicencia = $event->getData();
			
			/* Check we're looking at the right data/form */
			if ($llicencia instanceof EntityLlicencia) {
				$persona = $llicencia->getPersona();
					
				$form->add('personaid', 'hidden', array(
					'mapped' 	=> false,
					'data'		=> $persona->getId()
				));

				$form->add('nom', 'text', array(
					'read_only' => true,
					'mapped' 	=> false,
					'data'		=> $persona->getNomCognoms()
				));

				$form->add('dni', 'text', array(
					'read_only' => true,
					'mapped' 	=> false,
					'data'		=> $persona->getDni()
				));
				
				$form->add('mail', 'text', array(
					'read_only' => true,
					'mapped' 	=> false,
					'data'		=> $persona->getMail()
				));
			}
		});

		$builder->add('id', 'hidden');
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityLlicencia'));
	}
	
	public function getName()
	{
		return 'llicencia_sortida';
	}

}
