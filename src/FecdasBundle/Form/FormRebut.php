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
				
				$editable = $rebut->esIngres() || $rebut->getTipuspagament() != BaseController::TIPUS_PAGAMENT_TPV;
				
				
				$form->add('numrebut', 'text', array(
						'required' 	=> true,
						'disabled' 	=> true,
						'mapped'	=> false,
						'data'		=> $rebut->getNumRebut()
				));
				
				$form->add('comptabilitat', 'text', array(
						/*'class' 		=> 'FecdasBundle:EntityComptabilitat',
						'choice_label' 	=> 'InfoComptabilitat',
						'placeholder' 	=> 'pendent',*/
						'required'  	=> false,
						'disabled' 		=> true,
						'data'		=> (!$rebut->estaComptabilitzat()?'no enviat':$rebut->getComptabilitat()->getInfoComptabilitat()),
						'mapped'	=> false,
				));
				
				$form->add('dadespagament', 'text', array(
						'required' 		=> false,
						//'disabled' 		=> $rebut->estaComptabilitzat(),
						'disabled'		=> !$editable
				));
				
				$form->add('tipuspagament', 'choice', array(
						'required' 		=> true,
						'choices' 		=> BaseController::getTipusDePagament(),
						'placeholder' 	=> '',
						//'disabled' 		=> $rebut->estaComptabilitzat(),
						'disabled'		=> !$editable || $rebut->estaComptabilitzat()
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
										//->join('c.tipus', 't', 'WITH', 't.id != \''.BaseController::TIPUS_CLUB_PERSONA_FISICA.'\'');
								}, 
						'choice_label' 	=> 'nom',
						//'placeholder' 	=> 'Seleccionar club',  
						'placeholder' 	=> '',	// Important deixar en blanc pel bon comportament del select2
						'required'  	=> false,
						'data'			=> $rebut->getClub(),
						'disabled' 		=> $rebut->getId() > 0,
				));
				
				$form->add('datapagament', 'date', array(
						'required'  	=> true,
						'widget' 		=> 'single_text',
						'input' 		=> 'datetime',
						'placeholder' 	=> false,
						'format' 		=> 'dd/MM/yyyy',
						//'disabled' 		=> $rebut->estaComptabilitzat() || !$rebut->esIngres(),
						'disabled' 		=>  !$editable || $rebut->estaComptabilitzat(),		
				));
				
				$form->add ( 'import', 'text', array (
						'required' 		=> true,
						//'scale' 		=> 2,
						//'disabled' 		=> $rebut->estaComptabilitzat() || !$rebut->esIngres(),
						'disabled'		=> !$editable || $rebut->estaComptabilitzat()		
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
