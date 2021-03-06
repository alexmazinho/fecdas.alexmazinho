<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FecdasBundle\Entity\EntityStock;


class FormStock extends AbstractType {
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$registreStock = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($registreStock instanceof EntityStock) {
				
				$form->add('dataregistre', 'date',	array(
						'required' => true,
						'widget' 	=> 'single_text',
						'format' => 'dd/MM/yyyy',
				));
				
				$form->add('club', 'entity', array (
				            'class' => 'FecdasBundle:EntityClub',
				            'data' 		=> $registreStock->getClub(),
				));
				
				$form->add(	'producte', 'entity', array(
							'class' => 'FecdasBundle:EntityProducte',
							'query_builder' => function($repository) {
								return $repository->createQueryBuilder('p')
										->where('p.stockable = 1')
										->andWhere('p.databaixa IS NULL')
										->orderBy('p.descripcio', 'ASC');
									},
							'choice_label' => 'getAbreviaturaDescripcio',
							'placeholder' => '',
							'required'  => true,
				));
				
				if (!$registreStock->anulat()) {
					$form->add( 'databaixa', 'hidden', array(
						'required' 	=> false,
					));
				} else {
					$form->add('databaixa', 'date',	array(
						'required' => true,
						'widget' 	=> 'single_text',
						'format' => 'dd/MM/yyyy',
					));
				}
			}
			
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add( 'preuunitat', 'number', array (
					'required' 	=> true,
					'scale' 	=> 2,
		));
			
		$builder->add ('unitats', 'integer', array (
				'required' => true,
				'scale' => 0
		));

		$builder->add('comentaris', 'textarea', array(
				'required' => false,
				'attr' => array('rows' => '2'),
		));
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityStock'));
	}
		
	public function getName()
	{
		return 'registrestock';
	}

}
