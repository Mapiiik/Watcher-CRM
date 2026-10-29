<?php
/**
 * The day a minimum term usually runs to, offered in the label of the field rather than filled into it.
 *
 * One click ticks the box, turns the fields on and fills the day in. The field itself only ever
 * holds what the operator put there, so a form drawn again for another starting day cannot leave
 * a stale day behind - the offer beside it changes instead.
 *
 * @var \App\View\AppView $this
 * @var \Cake\I18n\Date $offered The day offered.
 * @var string $box The id of the box saying there is a term.
 * @var string $day The id of the field the day goes into.
 * @var array<string> $alsoEnabled The ids of further fields the box turns on together with the day.
 */

// Weighed like the text around it rather than like the label it sits in. The space is a hard one
// inside, because the label of a tick is a flex box, which drops a plain one between its items.
echo $this->Html->tag('span', '&nbsp;(' . $this->Html->link(
    __('Set the obligation until {0}', $offered),
    '#',
    [
        'onclick' => sprintf(
            'document.getElementById(%s).checked = true;'
            . '%s.forEach(function (id) { document.getElementById(id).disabled = false; });'
            . 'document.getElementById(%s).value = %s; return false;',
            json_encode($box, JSON_THROW_ON_ERROR),
            json_encode([$day, ...$alsoEnabled], JSON_THROW_ON_ERROR),
            json_encode($day, JSON_THROW_ON_ERROR),
            json_encode($offered->toDateString(), JSON_THROW_ON_ERROR),
        ),
    ],
) . ')', ['class' => 'offered', 'style' => 'font-weight: normal;']);
