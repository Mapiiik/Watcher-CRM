<?php
declare(strict_types=1);

namespace Settings\ValueObject;

/**
 * The way a typed setting is edited.
 *
 * A case names a whole manner of editing rather than an HTML element - `Json` is a text box holding
 * JSON, `TriState` a choice between the shipped value and either answer - and each carries enough
 * for the form to build it. Putting the markup together is left to the template, so nothing here
 * knows about HTML.
 */
enum SettingWidget: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Date = 'date';
    case TriState = 'tri_state';
    case Choice = 'choice';
    case Json = 'json';
}
