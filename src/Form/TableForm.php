<?php declare(strict_types=1);

namespace Table\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;
use Omeka\Form\Element as OmekaElement;

class TableForm extends Form
{
    public function init(): void
    {
        $this
            ->setAttribute('id', 'table-form')
            ->add([
                'name' => 'o:title',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Title', // @translate
                ],
                'attributes' => [
                    'id' => 'o-title',
                    'required' => true,
                ],
            ])
            ->add([
                'name' => 'o:slug',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Slug', // @translate
                ],
                'attributes' => [
                    'id' => 'o-slug',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'o:lang',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Language', // @translate
                ],
                'attributes' => [
                    'id' => 'o-lang',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'o:source',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Source (generally a url)', // @translate
                ],
                'attributes' => [
                    'id' => 'o-source',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'o:comment',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Comment', // @translate
                ],
                'attributes' => [
                    'id' => 'o-comment',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'o:codes',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'List of code and label separated by "="', // @translate
                    'as_key_value' => true,
                ],
                'attributes' => [
                    'id' => 'o-codes',
                    'rows' => '20',
                ],
            ])
        ;
    }
}
