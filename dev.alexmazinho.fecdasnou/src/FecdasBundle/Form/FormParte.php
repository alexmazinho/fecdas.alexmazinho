<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormParte extends AbstractType {

	protected $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('id', 'hidden');

		//$current_year = date("Y");
		$end_year = date("Y");
		if (date("m") == 12 and date("d") >= 10) $end_year++; // A partir 10/12 poden fer llicències any següent 

		$builder->add('dataalta', 'datetime', array(
				'read_only' => true,
				'widget' => 'single_text',
				'input' => 'datetime',
				'empty_value' => false,
				'format' => 'dd/MM/yyyy HH:mm',
		));
		
		
		
		$builder->add('any', 'text', array(
				'mapped'  => false,
				'read_only' => true,
		));

		$tipusparte = $this->options['tipusparte'];
		$llistatipus = $this->options['llistatipus'];
		if ($this->options['nova'] and $this->options['edit']) {
			// Mostra només la llista dels permesos
			$tipusparteoptions = array('class' => 'FecdasBundle:EntityParteType', 
					'query_builder' => function($repository) use ($llistatipus) {
					return $repository->createQueryBuilder('t')->orderBy('t.descripcio', 'ASC')
					->where($repository->createQueryBuilder('t')->expr()->in('t.id', ':llistatipus'))
					->setParameter('llistatipus', $llistatipus);
			}, 'property' => 'descripcio', 'required'  => count($llistatipus) == 1, 'empty_value' => 'Selecciona una...',
			);
			$builder->add('tipus', 'entity', $tipusparteoptions);
		} else {
			$tipusparteoptions = array('class' => 'FecdasBundle:EntityParteType', 
					'query_builder' => function($repository) use ($tipusparte) {
					return $repository->createQueryBuilder('t')->orderBy('t.id', 'ASC')
					->where('t.id = :tipusparte')
					->setParameter('tipusparte', $tipusparte); 
					}, 'property' => 'descripcio', 'read_only' => true,
			);
			$builder->add('tipus', 'entity', $tipusparteoptions);
			//$builder->add('tipus', 'text', array('mapped'  => false, 'data' => $llistatipus[0], 'read_only' => true,));
		}
		
		$builder->add('datapagament', 'date',
				array('widget' => 'single_text', 'format' => 'dd/MM/yyyy', 'years' => range(1990, 2020), 'read_only' => true));
		
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityParte'));
	}
	
	public function getName()
	{
		return 'parte';
	}

}
