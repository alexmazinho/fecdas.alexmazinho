<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityStock;


class FormStock extends AbstractType  implements EventSubscriberInterface {

	// Mètode del subscriptor => implements EventSubscriberInterface
	public function preSetData(FormEvent $event) {
		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
		$form = $event->getForm();
		$registreStock = $event->getData();
	
		/* Check we're looking at the right data/form */
		if ($registreStock instanceof EntityStock) {
			
			$form->add( 'dataregistre', 'text', array(
						'required' => true,
						'data' 		=> $registreStock->getDataregistre()->format('d/m/Y'),
			));
			
			$preuunitat = 0;
			if ($registreStock->getId() == 0 && $registreStock->getProducte() != null) $preuunitat = $registreStock->getProducte()->getPreu(date('Y')); 
			
			$form->add( 'preuunitat', 'number', array (
						'required' 	=> true,
						'scale' 	=> 2,
						'data' 		=> $preuunitat,
			));
		
		
			$form->add(	'factura', 'entity', array (
						'class' => 'FecdasBundle:EntityFactura',
						'choice_label' => 'llistaText',
						'empty_value' => '',
						'required'  => true,
						'data' 		=> $registreStock->getFactura(),
						'property_path' => 'numFactura',
			));
		
			$form->add( 'databaixa', 'text', array(
						'required' 	=> false,
						'data' 		=> $registreStock->anulat()?$registreStock->getDatabaixa()->format('d/m/Y'):'',
			));
		}
	}
	
	
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventSubscriber ( new FormStock () );
		
		$builder->add('id', 'hidden');
		
		$builder->add(	'producte', 'entity', array(
						'class' => 'FecdasBundle:EntityProducte',
						'query_builder' => function($repository) {
							return $repository->createQueryBuilder('p')
									->where('p.stockable = 1')
									->andWhere('p.databaixa IS NULL')
									->orderBy('p.descripcio', 'ASC');
								},
						'choice_label' => 'llistaText',
						'empty_value' => '',
						'required'  => true,
						'property_path' => 'descripcio',
		));
		
		
		
		
		$builder->add('tipus', 'choice', array(
				'choices'   => array(BaseController::REGISTRE_STOCK_ENTRADA => 'Entrada', BaseController::REGISTRE_STOCK_SORTIDA => 'Sortida' ),
				'multiple'  => false,
				'expanded'  => false,
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
		return 'registreStock';
	}

}
