<?php
/**
 * What is said about a kind of folder, on the form that makes one and the form that changes one.
 *
 * @var \App\View\AppView $this
 */
?>
<?php
    echo $this->Form->control('name', ['label' => __d('app_files', 'Name')]);
    echo $this->Form->control('position', [
        'label' => __d('app_files', 'Position'),
        'title' => __d('app_files', 'Where it sits in the list somebody picks from.'),
    ]);
    echo $this->Form->control('currently_offered', [
        'label' => __d('app_files', 'Currently Offered'),
        'title' => __d(
            'app_files',
            'A type that is no longer offered stays on what is already filed under it.',
        ),
    ]);
    echo $this->Form->control('date_required', [
        'label' => __d('app_files', 'Date Required'),
        'title' => __d(
            'app_files',
            'For documentation about something that happened. What is kept up to date has no day to give.',
        ),
    ]);
    echo $this->Form->control('customer_required', ['label' => __d('app_files', 'Customer Required')]);
    echo $this->Form->control('contract_required', ['label' => __d('app_files', 'Contract Required')]);
    echo $this->Form->control('note', ['label' => __d('app_files', 'Note')]);
