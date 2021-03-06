<?php
namespace FecdasBundle\Form;

use FecdasBundle\Entity\EntityLlicencia;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class FormLlicenciaRenew extends FormLlicencia {

    private $uncheckpersones;
    
    public function __construct($uncheckuncheckpersones = array())
    {
        $this->uncheckuncheckpersones = $uncheckuncheckpersones;
    }
    
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari.
			// Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$llicencia = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($llicencia instanceof EntityLlicencia) {
				
				// Comprovar si té llicència per al periode
				$persona = $llicencia->getPersona();
				$parte = $llicencia->getParte();
				
				$llicenciaExistent = $persona->getLastLlicencia($parte->getDataalta(), $parte->getDatacaducitat());

				$form->add('persona', 'hidden', array( 
				        'mapped' 	=> false,
				        'data'      => $persona->getId() 
				));
				
				if ($llicenciaExistent == null) { // No en té
				    $unchecked = in_array($persona->getId(), $this->uncheckuncheckpersones);
				    
					$form->add('renovar', 'checkbox', array(
						'required'  => false,
						'label' 	=> '',
						'mapped' 	=> false,
					    'data'		=> !$unchecked
					));
				} else {
					$form->add('existent', 'hidden', array(
						'required'  => false,
						'mapped' 	=> false,
						'data'		=> $llicenciaExistent->getParte()->getId()
					));
				}
			}
		});
		
	}
	
	public function getName()
	{
		return 'llicencia_renew';
	}

}
