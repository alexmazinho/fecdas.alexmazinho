<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Controller\BaseController;
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
				
				/*$form->add( 'dataregistre', 'text', array(
							'required' => true,
							'data' 		=> $registreStock->getDataregistre()->format('d/m/Y'),
				));*/
				
				$form->add('dataregistre', 'date',	array(
						'required' => true,
						'widget' 	=> 'single_text',
						'format' => 'dd/MM/yyyy',
						//'data' 		=> $registreStock->getDataregistre()->format('d/m/Y'),
				));
				
				/*$form->add(	'factura', 'entity', array (
							'class' => 'FecdasBundle:EntityFactura',
							'choice_label' 	=> 'numFactura',
							'empty_value' => '',
							'required'  => false,
							'data' 		=> $registreStock->getFactura(),
							//'property_path' => 'numFactura',
				));*/
			
				$form->add(	'factura', 'hidden', array (
							'data' 		=> $registreStock->getFactura()!=null?$registreStock->getFactura()->getId():'',
				));
			
				$form->add(	'producte', 'entity', array(
							'class' => 'FecdasBundle:EntityProducte',
							'query_builder' => function($repository) {
								return $repository->createQueryBuilder('p')
										->where('p.stockable = 1')
										->andWhere('p.databaixa IS NULL')
										->orderBy('p.descripcio', 'ASC');
									},
							'choice_label' => 'descripcio',
							'empty_value' => '',
							'required'  => true,
							//'property_path' => 'descripcio',
				));
				
				if (!$registreStock->anulat()) {
					$form->add( 'databaixa', 'hidden', array(
						'required' 	=> false,
					));
				} else {
					/*$form->add( 'databaixa', 'text', array(
								'required' 	=> false,
								'data' 		=> $registreStock->anulat()?$registreStock->getDatabaixa()->format('d/m/Y'):'',
					));*/
					
					$form->add('databaixa', 'date',	array(
						'required' => true,
						'widget' 	=> 'single_text',
						'format' => 'dd/MM/yyyy',
						//'data' 		=> $registreStock->getDatabaixa()->format('d/m/Y')
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

		$builder->add ('stock', 'integer', array (
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
