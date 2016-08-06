<?php
namespace FecdasBundle\Form;

use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Controller\BaseController;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;


class FormLlicenciaRenew extends FormLlicencia {

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
        
                $parte = $llicencia->getParte(); 
                
                $form->add('enviarllicencia', 'choice', array(
                        'required'  => true,
                        'multiple'  => false,
                        'expanded'  => true,
                        'choices'   => array( BaseController::INDEX_ENVIARLLICENCIA => 'Si', BaseController::INDEX_NOENVIARLLICENCIA => 'No' ),
                        'read_only' => !$parte->isAllowEdit(),
                        'data'      => ($llicencia->getEnviarllicencia()?BaseController::INDEX_ENVIARLLICENCIA:BaseController::INDEX_NOENVIARLLICENCIA) 
                ));
            }
                
        });
        
        
		$builder->add('renovar', 'checkbox', array(
				'required'  => false,
				'label' => '',
				'mapped' => false,
		));
		
		$builder->get('renovar')->setData( true );
	}
	
	public function getName()
	{
		return 'llicencia_renew';
	}

}
