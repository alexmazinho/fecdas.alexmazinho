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
				
				/*if ($rebut->getComanda() == null) {
					$current = date('Y') - 1;
					$datadesde = \DateTime::createFromFormat('Y-m-d H:i:s', $current."-01-01 00:00:00");
					
					$options = array('class' => 'FecdasBundle:EntityComanda', 
									'query_builder' => function($repository) use ($datadesde) {
										return $repository->createQueryBuilder('c')->orderBy('c.dataentrada, c.num', 'DESC')
										->where(' c.databaixa IS NULL AND c.rebut IS NULL AND c.dataentrada >= :desde')
										->setParameter('desde', $datadesde);
									}, 
									'choice_label' 	=> 'InfoComanda', 
									'required'  	=> false,  
									'empty_value' 	=> 'Associar a ...',);
				} else {
					$id = $rebut->getComanda()->getId();
					
					$options = array('class' => 'FecdasBundle:EntityComanda',
							'query_builder' => function($repository) use ( $id ) {
								return $repository->createQueryBuilder('c')
								->where(' c.id = :id ')
								->setParameter('id', $id);
							},
							'choice_label' 	=> 'InfoComanda',
							'required'  	=> false,);
					*/
					/*$options = array(
						'class' 		=> 'FecdasBundle:EntityComanda',
						'choice_label' 	=> 'InfoComanda',
						'required'  	=> false,
						'mapped'  		=> false,
						'data'			=> $rebut->getComanda());*/
				//}
									
				/*$form->add('comanda', 'entity', $options);*/
				
				$form->add('comptabilitat', 'entity', array(
						'class' 		=> 'FecdasBundle:EntityComptabilitat',
						'choice_label' 	=> 'InfoComptabilitat',
						'empty_value' 	=> 'Pendent d\'enviar a comptabilitat',
						'required'  	=> false,
						'disabled' 		=> true,
				));
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('num', 'hidden');
		
		$builder->add('club', 'entity', array(
				'class' 		=> 'FecdasBundle:EntityClub',
				'query_builder' => function($repository) {
						return $repository->createQueryBuilder('c')
								->orderBy('c.nom', 'ASC')
								->where('c.activat = 1');
						}, 
				'choice_label' 	=> 'nom',
				'empty_value' 	=> 'Seleccionar Club',
				'required'  	=> false,
				'read_only' 	=> true,
		));
		
		$builder->add('datapagament', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
				'disabled' 	=> true,
		));
		
		$builder->add ( 'import', 'number', array (
				'required' 	=> true,
				'scale' 	=> 2,
		));
		
		$builder->add('dadespagament', 'text', array(
				'required' => false,
		));
		
		$builder->add('tipuspagament', 'choice', array(
				'required' 		=> true,
				'choices' 		=> BaseController::getTipusDePagament(),
				'empty_value' 	=> ''
		));
		
		$builder->add('comentari', 'textarea', array(
				'required' => false,
		));
		
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
