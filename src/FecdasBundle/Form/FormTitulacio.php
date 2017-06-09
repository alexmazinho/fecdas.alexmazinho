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
			$persona = null;

			/* Check we're looking at the right data/form */
			if ($titulacio instanceof EntityTitulacio) {
				$persona = $titulacio->getPersona();
			} else {
				$titulacio = null;
			}
			
			
			$form->add('auxpersona', 'hidden', array(
				'mapped'	=> false,
				'data'		=> $persona!=null?$persona->getId():''
			));	

			$form->add('auxnom', 'text', array(
				'mapped'	=> false,
				'data'		=> $persona!=null?$persona->getNomcognoms():'',
				'attr'		=>	array('readonly' => true)
			));	
			$form->add('auxdni', 'text', array(
				'mapped'	=> false,
				'data'		=> $persona!=null?$persona->getDni():''
			));	
			$form->add('datanaixement', 'text',	array(
				'mapped'	=> false,
				'data'		=> $persona!=null&&$persona->getDatanaixement()!=null?$persona->getDatanaixement()->format('d/m/Y'):'',
				'attr'		=>	array('readonly' => true)
			)); 

			$telf  = $persona!=null&&$persona->getTelefon1()!=null?$persona->getTelefon1():'';
			$telf .= $persona!=null&&$persona->getTelefon2()!=null&&$telf=''?$persona->getTelefon2():'';
			$form->add('telefon', 'text', array(
				'mapped'	=> false,
				'data'		=> $telf,
				'attr'		=>	array('readonly' => true)
			));	

			$form->add('poblacio', 'text', array(
   	   			'mapped'	=> false,
				'data'		=> $persona!=null?$persona->getAddrpob():'',
				'attr'		=>	array('readonly' => true)
			));
				
			$form->add('nacionalitat', 'text', array(
   	   			'mapped'	=> false,
				'data'		=> $persona!=null?$persona->getAddrnacionalitat():'',
				'attr'		=>	array('readonly' => true)
			));
				
			$form->add('mail', 'text', array(
				'mapped'	=> false,
				'data'		=> $persona!=null?$persona->getNomcognoms():'',
				'attr'		=>	array('readonly' => true)
			));	
			
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('fotoupld', 'file', array('mapped' => false, 'attr' => array('accept' => 'image/*')));
				
		$builder->add('certificatupld', 'file', array('mapped' => false, 'attr' => array('accept' => 'pdf/*')));	
				
		$builder->add('num', 'text', array('required'  => false));	
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
