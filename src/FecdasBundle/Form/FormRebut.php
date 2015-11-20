<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use FecdasBundle\Entity\EntityRebut;
use FecdasBundle\Controller\BaseController;

class FormRebut extends AbstractType {

	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$rebut = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($rebut instanceof EntityRebut) {
				
				$form->add('numrebut', 'text', array(
						'required' 	=> true,
						'disabled' 	=> true,
						'mapped'	=> false,
						'data'		=> $rebut->getNumRebut()
				));
				
				$form->add('comptabilitat', 'text', array(
						/*'class' 		=> 'FecdasBundle:EntityComptabilitat',
						'choice_label' 	=> 'InfoComptabilitat',
						'empty_value' 	=> 'pendent',*/
						'required'  	=> false,
						'disabled' 		=> true,
						'data'		=> (!$rebut->estaComptabilitzat()?'no enviat':$rebut->getComptabilitat()->getInfoComptabilitat()),
						'mapped'	=> false,
				));
				
				$form->add('dadespagament', 'text', array(
						'required' 		=> false,
						'disabled' 		=> $rebut->estaComptabilitzat(),
				));
				
				$form->add('tipuspagament', 'choice', array(
						'required' 		=> true,
						'choices' 		=> BaseController::getTipusDePagament(),
						'empty_value' 	=> '',
						'disabled' 		=> $rebut->estaComptabilitzat(),
				));
				
				$form->add('comentari', 'textarea', array(
						'required' => false,
						'disabled' 		=> $rebut->estaComptabilitzat(),
				));
				$form->add('club', 'entity', array(
						'class' 		=> 'FecdasBundle:EntityClub',
						'query_builder' => function($repository) {
								return $repository->createQueryBuilder('c')
										->orderBy('c.nom', 'ASC')
										->where('c.databaixa IS NULL');
										//->where('c.activat = 1');
								}, 
						'choice_label' 	=> 'nom',
						'empty_value' 	=> 'Seleccionar Club',
						'required'  	=> false,
						'disabled' 		=> $rebut->getId() > 0,
				));
				
				$form->add('datapagament', 'date', array(
						'required'  	=> true,
						'widget' 		=> 'single_text',
						'input' 		=> 'datetime',
						'empty_value' 	=> false,
						'format' 		=> 'dd/MM/yyyy',
						'disabled' 		=> $rebut->getId() > 0,
				));
				
				$form->add ( 'import', 'number', array (
						'required' 		=> true,
						'scale' 		=> 2,
						'disabled' 		=> $rebut->getId() > 0,
				));
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('num', 'hidden');
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityRebut'));
	}
		
	public function getName()
	{
		return 'rebut';
	}

}
