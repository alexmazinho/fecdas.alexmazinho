<?php
namespace FecdasBundle\Form;

use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Entity\EntityPersona;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class FormLlicencia extends AbstractType {

	protected $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('id', 'hidden');
		
		$codiclub = $this->options['codiclub'];

		$builder->add('persona', 'entity', array(
				'class' => 'FecdasBundle:EntityPersona',
				'query_builder' => function($repository) use ($codiclub) {
				return $repository->createQueryBuilder('e')
				->where('e.club = :codiclub')
				->andWhere('e.databaixa is null')
				->orderBy('e.cognoms', 'ASC')
				->setParameter('codiclub', $codiclub);
		},
		'choice_label' => 'llistaText',
		'empty_value' => '',
		'required'  => false,
		'read_only' => !$this->options['edit'],
		'property_path' => 'persona',
		'mapped' => true,
		//'mapped' => false,
		));

		$llistacategoria = 'llistaText';
		$current = $this->options['any'];
		if ($current > Date('Y')) {  // Seleccionar preu post (any posterior)
			$llistacategoria = 'llistaTextPost';
		}
		
		$tipusparte = $this->options['tipusparte'];
		$builder->add('categoria', 'entity', array(
			'class' => 'FecdasBundle:EntityCategoria',
			'query_builder' => function($repository) use ($tipusparte, $current) {
				return $repository->createQueryBuilder('c')
										->join('c.producte', 'o')
										->join('c.tipusparte', 'tp')
										->join('o.preus', 'p')	
										->where('tp.id = :tipusparte')
										->andwhere('p.anypreu = :anypreu')
										->andwhere('p.preu > 0')
										->orderBy('c.simbol', 'ASC')
										->setParameter('anypreu', $current)
										->setParameter('tipusparte', $tipusparte);
			},
			'choice_label' => $llistacategoria,
			'required'  => true,
			'read_only' => !$this->options['edit'],
		));

		$builder->add('datacaducitat', 'date', array(
			'widget' => 'single_text',
			'format' => 'dd/MM/yyyy',
			'attr'=>array('style'=>'display:none;'), ));
		$builder->add('datacaducitatshow', 'date', array(
			'widget' => 'single_text', 
			'format' => 'dd/MM/yyyy',
			'mapped'  => false,
			'read_only' => true,
			));
		
		$builder->add('pesca', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('escafandrisme', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('natacio', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('orientacio', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('biologia', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('fotocine', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('hockey', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('fotosubapnea', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('videosub', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('apnea', 'checkbox', array(
    	    'required'  => false,
			'read_only' => !$this->options['edit'],
			));
		
		$builder->add('rugbi', 'checkbox', array(
				'required'  => false,
				'read_only' => !$this->options['edit'],
		));
		
		$builder->add('besportiu', 'checkbox', array(
				'required'  => false,
				'read_only' => !$this->options['edit'],
		));

		$builder->add('bampolles', 'checkbox', array(
				'required'  => false,
				'read_only' => !$this->options['edit'],
		));
		
		$builder->add('nocmas', 'checkbox', array(
    	    'required'  => false,
			'read_only' => true,
			));
		
		$builder->add('fusell', 'checkbox', array(
    	    'required'  => false,
			'read_only' => true,
			));
		$builder->add('enviarllicencia', 'checkbox', array(
			'required'  => false,
			'read_only' => !$this->options['edit'],
			));
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityLlicencia'));
	}
	
	public function getName()
	{
		return 'llicencia';
	}

}
