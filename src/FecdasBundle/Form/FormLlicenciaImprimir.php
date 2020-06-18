<?php
namespace FecdasBundle\Form;

use FecdasBundle\Entity\EntityLlicencia;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;


class FormLlicenciaImprimir extends FormLlicenciaSortida {

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
				//$persona = $llicencia->getPersona();
					
				$form->add('imprimir', 'checkbox', array(
					'mapped'  	=> false,
				    'required'  => false,
					//'data'		=> $persona->getMail() == '' && !$llicencia->getImpresa()
				    'data'		=> $llicencia->getImprimir() && !$llicencia->getImpresa() || (isset($this->options['checkall']) && $this->options['checkall']==1)
				));
			}
		});
	}
	
	public function getName()
	{
		return 'llicencia_perimprimir';
	}

}
