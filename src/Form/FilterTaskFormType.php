<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\FilterTaskModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterTaskFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('showCompleted', CheckboxType::class, [
                'required' => false
            ])
            ->add('showSubtasks', CheckboxType::class, [
                'required' => false
            ])
            ->add('onlyShowPastDue', CheckboxType::class, [
                'required' => false
            ])
            ->add('content', SearchType::class, [
                'required' => false
            ])
            ->add('tags', TextType::class, [
                'required' => false,
            ])
            ->add('parentTask', TextType::class, [
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => FilterTaskModel::class]);
    }
}
