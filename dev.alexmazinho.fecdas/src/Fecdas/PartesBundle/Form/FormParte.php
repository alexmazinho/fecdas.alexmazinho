<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class FormParte extends AbstractType {

	protected $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder->add('id', 'hidden');

		$current_year = date("Y");
		
		if (!$this->options['nova'])
			$builder->add('dataalta', 'datetime',
					array('date_widget' => 'single_text','time_widget' => 'single_text', 'date_format' => 'dd/MM/yyyy', 'read_only' => true));
		else 
			$builder->add('dataalta', 'datetime',
					array('date_widget' => 'choice','time_widget' => 'choice', 'date_format' => 'dd/MM/yyyy', 'years' => range($current_year, $current_year+1)));
		
		$builder->add('any', 'text', array(
				'property_path'  => false,
				'read_only' => true,
		));

		$codiclub = $this->options['codiclub'];
		if ($this->options['nova'] and $this->options['admin'] and $this->options['edit']) {
			$cluboptions = array('class' => 'FecdasPartesBundle:EntityClub', 'property' => 'llistaText', 'multiple' => false,
					'query_builder' => function($repository) {
					return $repository->createQueryBuilder('c')->orderby('c.nom');
					},
			);
			$builder->add('club', 'entity', $cluboptions);
		} else {
			$cluboptions = array('class' => 'FecdasPartesBundle:EntityClub', 'property' => 'llistaText', 'multiple' => false,
					'query_builder' => function($repository) use ($codiclub) {
					return $repository->createQueryBuilder('c')->orderby('c.nom')
					->where('c.codi = :codiclub')
					->setParameter('codiclub', $codiclub);
					},
			);
			$builder->add('club', 'entity', $cluboptions);
		}
		
		$tipusparte = $this->options['tipusparte'];
		$llistatipus = $this->options['llistatipus'];
		if ($this->options['nova'] and $this->options['edit']) {
			// Mostra només la llista dels permesos
			if (count($llistatipus) > 0) {
				/* Si la llista només té una llicència, required = true*/
				
				$tipusparteoptions = array('class' => 'FecdasPartesBundle:EntityParteType', 
						'query_builder' => function($repository) use ($llistatipus) {
						return $repository->createQueryBuilder('t')->orderBy('t.descripcio', 'ASC')
						->where($repository->createQueryBuilder('t')->expr()->in('t.id', ':llistatipus'))
						->setParameter('llistatipus', $llistatipus);
				}, 'property' => 'descripcio', 'required'  => count($llistatipus) == 1,  
				);
			} else {
				// Per evitar errors de llista buida $llistatipus
				$tipusparteoptions = array('class' => 'FecdasPartesBundle:EntityParteType', 'property' => 'descripcio',);
				
				$tipusparteoptions = array('class' => 'FecdasPartesBundle:EntityParteType', 'property' => 'descripcio',
						'query_builder' => function($repository) {
							return $repository->createQueryBuilder('t')
							->where('t.id = -1');
						},
				);
			}
		} else {
			$tipusparteoptions = array('class' => 'FecdasPartesBundle:EntityParteType', 'property' => 'descripcio',
					'query_builder' => function($repository) use ($tipusparte) {
					return $repository->createQueryBuilder('t')->orderBy('t.id', 'ASC')
					->where('t.id = :tipusparte')
					->setParameter('tipusparte', $tipusparte); 
					},
			);
		}
		
		$builder->add('tipus', 'entity', $tipusparteoptions);
		
		$builder->add('datapagament', 'date',
				array('widget' => 'single_text', 'format' => 'dd/MM/yyyy', 'years' => range(1990, 2020), 'read_only' => true));
		
	}
	
	public function getName()
	{
		return 'parte';
	}

}
