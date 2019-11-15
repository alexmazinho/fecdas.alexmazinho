<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityDocencia;


class FormDocencia extends AbstractType {

    private $currentuser;
    private $admin;
	
    public function __construct($options)
	{
	    $this->currentuser = isset($options['currentuser'])?$options['currentuser']:null;
	    $this->admin = isset($options['admin'])?$options['admin']:false;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$docencia = $event->getData();
			$metadocent = null;

			if ($docencia == null) $docencia = new EntityDocencia;
			
			/* Check we're looking at the right data/form */
			if ($docencia instanceof EntityDocencia) {
				$metadocent = $docencia->getMetadocent();
				$curs = $docencia->getCurs();
				/*if ($curs != null && !$curs->editable()) {
					// Curs en procés
					$editable = false;	
				}*/
				
				$editable = true;
				if ($curs != null) $editable = $curs->editable() && ($this->admin || $curs->getEditor() === $this->currentuser || $this->currentuser->hasRoleClub($curs->getClub(), BaseController::ROLE_CLUB));
				
				$form->add('metadocent', 'hidden', array(
				    'mapped'	=> false,
				    'data'		=> $metadocent!=null?$metadocent->getId():0
				));
				
				$form->add('auxdni', 'text', array(
				    'mapped'	=> false,
				    'data'		=> $metadocent!=null?$metadocent->getDni():'',
				    'disabled'	=> true
				));
				
				$form->add('auxnom', 'text', array(
				    'mapped'	=> false,
				    'data'		=> $metadocent!=null?$metadocent->getNomcognoms():'',
				    'disabled'	=> true
				));
				
				$form->add ('hteoria', 'integer', array (
				    'required' 	=> true,
				    'scale' 	=> 0,
				    'data'		=> $docencia!=null?$docencia->getHteoria():0,
				    'empty_data' => '',
				    'attr'		=>	array('readonly' => !$editable)
				));
				
				$form->add ('haula', 'integer', array (
				    'required' 	=> true,
				    'scale' 	=> 0,
				    'data'		=> $docencia!=null?$docencia->getHaula():0,
				    'empty_data' => '',
				    'attr'		=>	array('readonly' => !$editable)
				));
				
				$form->add ('hpiscina', 'integer', array (
				    'required' 	=> true,
				    'scale' 	=> 0,
				    'data'		=> $docencia!=null?$docencia->getHpiscina():0,
				    'empty_data' => '',
				    'attr'		=>	array('readonly' => !$editable)
				));
				
				$form->add ('hmar', 'integer', array (
				    'required' 	=> true,
				    'scale' 	=> 0,
				    'data'		=> $docencia!=null?$docencia->getHmar():0,
				    'empty_data' => '',
				    'attr'		=>	array('readonly' => !$editable)
				));
				
				$form->add ('ipiscina', 'integer', array (
				    'required' 	=> true,
				    'scale' 	=> 0,
				    'data'		=> $docencia!=null?$docencia->getIpiscina():0,
				    'empty_data' => '',
				    'attr'		=>	array('readonly' => !$editable)
				));
				
				$form->add ('imar', 'integer', array (
				    'required' 	=> true,
				    'scale' 	=> 0,
				    'data'		=> $docencia!=null?$docencia->getImar():0,
				    'empty_data' => '',
				    'attr'		=>	array('readonly' => !$editable)
				));
				
				$form->add('carnet', 'text', array(
				    'required' 	=> false,
				    'attr'		=>	array('readonly' => !$editable)
				));
			}
		
			
			
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('databaixa', 'hidden');
		
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
