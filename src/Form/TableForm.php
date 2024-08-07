<?php declare(strict_types=1);

namespace Table\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\I18n\Translator\TranslatorAwareTrait;
use Table\Api\Adapter\TableAdapter;

class TableForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * @var \Table\Api\Adapter\TableAdapter
     */
    protected $apiAdapterTable;

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
                'name' => 'o:is_associative',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Manage multiple labels by code', // @translate
                    'value_options' => [
                        '0' => 'Yes', // @translate
                        '1' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'o-slug',
                    'required' => false,
                    'value' => '0',
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
                'type' => CommonElement\DataTextarea::class,
                'options' => [
                    'label' => 'List of code and label separated by "=", with optional language', // @translate
                    'data_options' => [
                        'code' => null,
                        'label' => null,
                        'lang' => null,
                    ],
                ],
                'attributes' => [
                    'id' => 'o-codes',
                    'rows' => '20',
                ],
            ])
        ;

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:codes',
            'required' => false,
            'filters' => [
                [
                    'name' => \Laminas\Filter\Callback::class,
                    'options' => [
                        'callback' => [$this->apiAdapterTable, 'cleanListOfCodesAndLabelsAndLangs'],
                    ],
                ],
            ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\Callback::class,
                    'options' => [
                        'callback' => [$this, 'validateCodes'],
                        'bind' => true,
                        'messages' => [
                            'callbackValue' => $this->translator->translate(
                                'Some codes are not unique once transliterated or languages are not unique by codes or some codes have languages and some none.' // @translate
                            ),
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function validateCodes($codes, $context): bool
    {
        if (empty($codes)) {
            return true;
        }

        // The context is the post.

        // TODO Output messages of the error store when validating, without dividing the checks.
        $errorStore = new \Omeka\Stdlib\ErrorStore();
        return $this->apiAdapterTable->validateCodes($codes, [
            'o:is_associative' => !empty($context['o:is_associative']),
            'error_store' => $errorStore,
        ]);
    }

    public function setApiAdapterTable(TableAdapter $apiTableAdapter): self
    {
        $this->apiAdapterTable = $apiTableAdapter;
        return $this;
    }
}
