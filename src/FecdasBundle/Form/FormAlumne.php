<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FecdasBundle\Entity\EntityPersona;


class FormAlumne extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$persona = $event->getData();

			//error_log("ERERTEREEEEEEEEEEEEEEEEEEEEEEEEEEEEEE".get_class($persona).get_class($form));
			
			/* Check we're looking at the right data/form */
			if ($persona instanceof EntityPersona) {
				$editable = false;
					
				$form->add('nomcognoms', 'text', array(
					'mapped'	=> false,
					'data'		=> $persona->getNomcognoms(),
					'attr'		=>	array('readonly' => true)
				));	


				$form->add('datanaixement', 'text',	array(
					'data'		=> $persona->getDatanaixement()!=null?$persona->getDatanaixement()->format('d/m/Y'):'',
					'attr'		=>	array('readonly' => true)
				)); 

				$telf  = $persona->getTelefon1()!=null?$persona->getTelefon1():'';
				$telf .= $persona->getTelefon2()!=null&&$telf=''?$persona->getTelefon2():'';
				$form->add('telefon', 'text', array(
					'mapped'	=> false,
					'data'		=> $telf,
					'attr'		=>	array('readonly' => true)
				));	

				$form->add('poblacio', 'text', array(
    	   			'mapped'	=> false,
					'data'		=> $persona->getAddrpob(),
					'attr'		=>	array('readonly' => true)
				));
				
				$form->add('nacionalitat', 'text', array(
    	   			'mapped'	=> false,
					'data'		=> $persona->getAddrnacionalitat(),
					'attr'		=>	array('readonly' => true)
				));
				
				$form->add('fotoupld', 'file', array('mapped' => false, 'attr' => array('accept' => 'image/*')));
				
				$form->add('certificatupld', 'file', array('mapped' => false, 'attr' => array('accept' => 'pdf/*')));			
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('dni', 'text', array());
		
		$builder->add('mail', 'text', array(
			'attr'		=>	array('readonly' => true)
		));	
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityPersona'));
	}
		
	public function getName()
	{
		return 'alumne';
	}

}
