<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FecdasBundle\Entity\EntityTitulacio;


class FormTitulacio extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$titulacio = $event->getData();
			$metapersona = null;
			$editable = true;	
			/* Check we're looking at the right data/form */
			if ($titulacio instanceof EntityTitulacio) {
				$metapersona = $titulacio->getMetapersona();
				$curs = $titulacio->getCurs();
				if ($curs != null && !$curs->editable()) {
					// Curs en procés
					$editable = false;	
				}
			}
			
			$form->add('metapersona', 'hidden', array(
				'mapped'	=> false,
				'data'		=> $metapersona!=null?$metapersona->getId():0
			));	

			$form->add('auxnom', 'text', array(
				'mapped'	=> false,
				'data'		=> $metapersona!=null?$metapersona->getNomcognoms():'',
				'disabled'	=> true
			));	
			$form->add('auxdni', 'text', array(
				'mapped'	=> false,
				'data'		=> $metapersona!=null?$metapersona->getDni():'',
				'disabled'	=> true
			));	
			
			$form->add('num', 'text', array(
				'required'  => false, 
				'attr' =>	array('readonly' => !$editable)
			));	
			
			$form->add('fotoupld', 'file', array(
				'mapped' => false, 
				'disabled'	=> !$editable,
				'attr' => array('accept' => 'image/*')
			));
				
			$form->add('certificatupld', 'file', array(
				'mapped' => false,
				'disabled'	=> !$editable, 
				'attr' => array('accept' => 'pdf/*')
			));
			
		});
		
		$builder->add('id', 'hidden');
		
			
				
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityTitulacio'));
	}
		
	public function getName()
	{
		return 'alumne';
	}

}
