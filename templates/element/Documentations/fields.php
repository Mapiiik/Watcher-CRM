<?php
/**
 * What is said about a folder. What it hangs on is not among it - that comes from the address the
 * form was opened at, so a folder cannot be filed against a record somebody was not standing on.
 *
 * @var \App\View\AppView $this
 * @var array<string, string> $kinds
 */
?>
<?php
    echo $this->Form->control('documentation_type_id', [
        'label' => __d('app_files', 'Documentation Type'),
        'options' => $kinds,
        'empty' => true,
    ]);
    echo $this->Form->control('happened_on', [
        'label' => __d('app_files', 'Happened On'),
        'empty' => true,
        'title' => __d(
            'app_files',
            'The day it is about. Documentation kept up to date rather than recording something'
            . ' has none, and goes by its name instead.',
        ),
    ]);
    echo $this->Form->control('name', [
        'label' => __d('app_files', 'Name'),
        'title' => __d('app_files', 'Left empty, it reads as its day and its type.'),
    ]);
    echo $this->Form->control('note', ['label' => __d('app_files', 'Note')]);
