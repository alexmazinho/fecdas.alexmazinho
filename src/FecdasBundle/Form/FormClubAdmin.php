<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use FecdasBundle\Entity\EntityClub;

class FormClubAdmin extends FormClub {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$club = $event->getData();
			
			/* Check we're looking at the right data/form */
			if ($club instanceof EntityClub) {
				
				$form->add('clubs', 'entity', array(
					'class' 		=> 'FecdasBundle:EntityClub',
					'choice_label' 	=> 'nom',
					'label' 		=> 'Filtre per club: ',
					'mapped' 		=> false,
					'required'  	=> false,
					'data'			=> $club
				));
				
				
				$form->add('saldoclub', 'number', array(
					'grouping' 	=> true,
					'scale' 	=> 2,
					'mapped' 	=> false,
					'data'		=> $club->getSaldo(),
					'attr'		=>	array('readonly' => true)
				));

				$form->add('activat', 'checkbox', array(
					'required'  => false,
					'data'		=> ($club->getActivat()?true:false)
				));

				$form->add('nouclub', 'hidden', array(
					'mapped' => false,
				));
			}
		});
		
		$builder->add('nom', 'text', array('attr'	=>	array('readonly' => false)));
		$builder->add('nomfiscal', 'text', array('attr'	=>	array('readonly' => false)));
		$builder->add('mail', 'email', array('attr'	=>	array('readonly' => false)));
		$builder->add('cif', 'text', array('attr'	=>	array('readonly' => false)));
		$builder->add('databaixa', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> '',
				'format' 		=> 'dd/MM/yyyy',
				//'attr'			=>	array('readonly' => true)
		));
		
		$builder->add('notes', 'textarea', array(
		          'required'            => false,
		          'attr' 		=> array('rows' => 2, 'resize' => 'vertical')
		));
		
		$builder->add('codi', 'text', array(
			     'required'           => true,
			     'attr'			     =>	array('readonly' => true)
		));
			
		$tipuscluboptions = array('class' => 'FecdasBundle:EntityClubType', 'choice_label' => 'tipus',
				'query_builder' => function($repository) {
				return $repository->createQueryBuilder('t')->orderBy('t.id', 'ASC');
			},
		);
				
		$builder->add('tipus', 'entity', $tipuscluboptions);
			
		$builder->add('estat', 'entity', array('class' => 'FecdasBundle:EntityClubEstat', 'choice_label' => 'descripcio',
				'query_builder' => function($repository) {
					return $repository->createQueryBuilder('e')->orderBy('e.codi', 'ASC');
				})
		);
				
		$builder->add('impressio', 'checkbox', array(
				'required'  => false,
		));
			
		$builder->add('limitcredit', 'number', array(
				'grouping' 	=> true,
				'scale' 	=> 2
		));
			
		$builder->add('limitnotificacio', 'datetime', array(
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
				'attr'			=>	array('readonly' => true)
		));
		
		$builder->add('tipusparte', 'entity', array('class' => 'FecdasBundle:EntityParteType', 'choice_label' => 'descripcio', 'multiple' => true, 'required' => false,
				'query_builder' => function($repository) {
					return $repository->createQueryBuilder('e')->where('e.actiu = true')->orderBy('e.id', 'ASC');
				})
		);
	
		$builder->add('enviarllicencia', 'checkbox', array(
		    'required'  => false,
		));
		
		// Saldos. Comptabilitat
		$builder->add('compte', 'text', array(
				'required'  => true,
		));
		
		$builder->add('romanent', 'number', array(
				'grouping' 	=> true,
				'scale' 	=> 2,
				'attr'		=>	array('readonly' => true)
		));
			
		$builder->add('totalpagaments', 'number', array(
				'grouping' 	=> true,
				'scale' 	=> 2,
				'attr'		=>	array('readonly' => true)
		));
		
		$builder->add('totalllicencies', 'number', array(
				'grouping' 	=> true,
				'scale' 	=> 2,
				'attr'		=>	array('readonly' => true)
		));
		
		$builder->add('totalduplicats', 'number', array(
				'grouping' 	=> true,
				'scale' 	=> 2,
				'attr'		=>	array('readonly' => true)
		));
		
		$builder->add('totalaltres', 'number', array(
				'grouping' 	=> true,
				'scale' 	=> 2,
				'attr'		=>	array('readonly' => true)
		));
		
		$builder->add('ajustsubvencions', 'number', array(
				'grouping' 	=> true,
				'scale' 	=> 2,
				'attr'		=>	array('readonly' => true)
		));
		
	}
	
	
}
