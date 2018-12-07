<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FecdasBundle\Entity\EntityTitulacio;


class FormTitulacio extends AbstractType {

	private $editable;
	
	public function __construct($editable = false)
	{
		$this->editable = $editable;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$titulacio = $event->getData();
			$metapersona = null;
			$persona = null;
		
			/* Check we're looking at the right data/form */
			if ($titulacio instanceof EntityTitulacio) {
				$metapersona = $titulacio->getMetapersona();
				$curs = $titulacio->getCurs();
				if ($curs != null && $curs->getClub() != null && $metapersona != null)  $persona = $metapersona->getPersona($curs->getClub());
				if ($curs != null && !$curs->editable()) {
					// Curs en procés
					$this->editable = false;	
				}
			}
		
			$form->add('metapersona', 'hidden', array(
				'mapped'	=> false,
				'data'		=> $metapersona!=null?$metapersona->getId():0
			));	

			$form->add('auxnom', 'text', array(
				'mapped'	=> false,
			    'data'		=> $persona!=null?$persona->getNomcognoms():'',
				'disabled'	=> true
			));	
			$form->add('auxdni', 'text', array(
				'mapped'	=> false,
				'data'		=> $metapersona!=null?$metapersona->getDni():'',
				'disabled'	=> true
			));	
			
			$form->add('auxdatasuperacio', 'text', array(
			    'mapped'	=> false,
			    'data'		=> $titulacio!=null&&$titulacio->getDatasuperacio()!=null?$titulacio->getDatasuperacio()->format('d/m/Y'):'',
			    'disabled'	=> true
			));	
			
			$form->add('num', 'text', array(
				'required'  => false, 
				'attr'      => array('readonly' => true),
			    'mapped'	=> false,
			    'data'  	=> $titulacio!=null?$titulacio->getNumTitulacio():'Pendent'
			));	
			
			$form->add('numfedas', 'text', array(
			    'required'  => false,
			    'attr'      =>	array('readonly' => true),
			    'data'  	=> $titulacio!=null?$titulacio->getNumfedas():'Pendent'
			));	
			
			$form->add('fotoupld', 'file', array(
				'mapped' => false, 
				'disabled'	=> !$this->editable,
				'attr' => array('accept' => 'image/*')
			));
			
			$form->add('foto', 'hidden', array('mapped' => false, 'required'  => false, 'data'	=> ($persona != null && $persona->getFoto()!=null?$persona->getFoto()->getPath():'')));
				
			$form->add('certificatupld', 'file', array(
				'mapped' => false,
				'disabled'	=> !$this->editable, 
				'attr' => array('accept' => 'pdf/*')
			));
			
			$form->add('certificat', 'hidden', array('mapped' => false, 'required'  => false, 'data'	=> ($persona != null && $persona->getCertificat()!=null?$persona->getCertificat()->getPath():'')));
			
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
