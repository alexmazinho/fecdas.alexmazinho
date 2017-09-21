<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class FormComandaDetall extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(
				FormEvents::PRE_SET_DATA,
				function (FormEvent $event) {
					// Esdeveniment abans de carregar dades de l'entitat al formulari
					$form = $event->getForm();
		
					$producte = null;
					$preu = 0;
					$iva = 0;
					$unitats = 1;
					$descompte = 0;
					$total = 0;
						
					$detall = $event->getData();
					$comanda = null;
					$disabledFields = false;
					if ($detall != null) {
						$producte = ($detall != null?$detall->getProducte():null);
						$preu = $detall->getPreuunitat();
						if ($preu == 0 && $producte != null) $preu = $producte->getCurrentPreu(); // Si no té cap preu, afegir el preu del producte
						$iva = $detall->getIvaunitat();
						//if ($iva == 0 && $producte != null) $iva = $producte->getCurrentIva(); // Si no té cap IVA, afegir el del producte
						$unitats = $detall->getUnitats();
						$descompte = $detall->getDescomptedetall();
						$total = $detall->getTotal();
						$comanda = $detall->getComanda();
						
						if ($detall != null && $detall->esBaixa()) $disabledFields = true;
						if ($comanda != null && !$comanda->esNova()) $disabledFields = true;
					}
					
					$form->add('producte', 'entity', array(
							'class' 		=> 'FecdasBundle:EntityProducte',
							'choice_label' 	=> 'descripcio',
							'placeholder' 	=> 'Seleccionar Producte',
							'required'  	=> false,
							'disabled' 		=> $disabledFields,
					));
					
					$form->add ( 'preuunitat', 'number', array (
							'required' 	=> true,
							'disabled' 	=> true,
							'scale' 	=> 2,
							'data'		=> $preu,
							'disabled' 	=> $disabledFields,
					));
						
					$form->add ( 'ivaunitat', 'integer', array (
							'required' => true,
							'disabled' 	=> true,
							'scale' 	=> 0,
							'data'		=> $iva * 100,
							'disabled' 	=> $disabledFields,
					));
					
					
					$form->add ( 'unitats', 'integer', array (
							'required' => true,
							'scale' 	=> 0,
							'data'		=> $unitats,
							//'disabled' 	=> $disabledFields,
					));
					
					$form->add ( 'descomptedetall', 'integer', array (
							'required' 	=> true,
							/*'disabled' 	=> true,*/
							'scale' 	=> 0,
							'data'		=> $descompte * 100,
							'disabled' 	=> $disabledFields,
					));
					
					$form->add ( 'total', 'number', array (
							'required' => true,
							'disabled' 	=> true,
							'scale' 	=> 2,
							'data'		=> $total
					));
					
					$form->add('anotacions', 'textarea', array(
							'required' 	=> false,
							'attr'		=> array( 'rows' => 1, 'resize' => 'vertical' ),
							'disabled' 	=> $disabledFields,
					));
				}
		);

		$builder->add('id', 'hidden');
		
		
		$builder->add ( 'totalnet', 'number', array (
				'required' 	=> true,
				'disabled' 	=> true,
				'mapped' 	=> false,
				'scale' 	=> 2,
				'data'		=> 0
		));
		
		$builder->add ( 'totaliva', 'number', array (
				'required' => true,
				'disabled' 	=> true,
				'mapped' 	=> false,
				'scale' 	=> 2,
				'data'		=> 0
		));
		
		
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
				'data_class' => 'FecdasBundle\Entity\EntityComandaDetall',	// => Crida al constructor sense paràmetres per defecte 
				/*'empty_data' => function (FormInterface $form) {
					return new EntityComandaDetall($this->comanda, $form->get('producte')->getData(), 0, 0, '');
				},*/
		));
		
	}
		
	public function getName()
	{
		return 'detallcomanda';
	}

}
