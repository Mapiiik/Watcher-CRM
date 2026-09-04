<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper\FormHelper as CakeFormHelper;
use Override;

/**
 * The form helper, taught one word Cake does not know.
 *
 * A hint under a field is written as `'help' => __('...')` beside the label. Cake has no such
 * option and passes what it does not know through to the input as an attribute, where it renders
 * as nothing at all - so the hint is picked out here and put under the field instead, as the small
 * grey line the rest of the application uses.
 */
class FormHelper extends CakeFormHelper
{
    /**
     * Initialization hook method.
     *
     * @param array<string, mixed> $config The configuration settings provided to this helper.
     * @return void
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTemplates([
            'inputContainer' => '<div class="{{containerClass}} {{type}}{{required}}">'
                . '{{content}}{{help}}</div>',
            'inputContainerError' => '<div class="{{containerClass}} {{type}}{{required}} error">'
                . '{{content}}{{error}}{{help}}</div>',
        ]);
    }

    /**
     * Generates a form control element complete with label and wrapper div.
     *
     * @param string $fieldName This should be "modelname.fieldname".
     * @param array<string, mixed> $options Each type of input takes different options.
     * @return string Containing the HTML input and label.
     */
    #[Override]
    public function control(string $fieldName, array $options = []): string
    {
        $help = $options['help'] ?? null;
        unset($options['help']);

        if ($help !== null && $help !== '') {
            $options['templateVars']['help'] = '<small class="hint">' . h($help) . '</small>';
        }

        return parent::control($fieldName, $options);
    }
}
