<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType; 
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class TypeTextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('input_text', TextareaType::class, [
                'label' => 'Entrer votre texte',
                'attr' => [
                    'spellcheck' => 'false', // Add this line to disable spellcheck
                ],
                'autocomplete' => true,
                'tom_select_options' => [
            'create' => true,
            'createOnBlur' => true,
            'delimiter' => ',',
        ],
            ])
            ->add('validate', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
