<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Queue $queue
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $queue->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $queue->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(__('List Queues'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="queues form content">
            <?= $this->Form->create($queue) ?>
            <fieldset>
                <legend><?= __('Edit Queue') ?></legend>
                <?php
                    $derivedHelp = __('Leave empty to derive it from the advertised speed.');
                    echo $this->Form->control('name');
                    echo $this->Form->control('caption');
                    echo $this->Form->control('fup_limit', ['label' => __('FUP Limit')]);
                    echo $this->Form->control('data_limit');
                    echo $this->Form->control('overlimit_fragment');
                    echo $this->Form->control('overlimit_cost');
                    echo $this->Form->control('speed_down');
                    echo $this->Form->control('speed_up');
                    echo $this->Form->control('speed_down_common', [
                        'label' => __('Speed Down Commonly Available'),
                        'help' => $derivedHelp,
                    ]);
                    echo $this->Form->control('speed_up_common', [
                        'label' => __('Speed Up Commonly Available'),
                        'help' => $derivedHelp,
                    ]);
                    echo $this->Form->control('speed_down_minimum', [
                        'label' => __('Speed Down Minimum'),
                        'help' => $derivedHelp,
                    ]);
                    echo $this->Form->control('speed_up_minimum', [
                        'label' => __('Speed Up Minimum'),
                        'help' => $derivedHelp,
                    ]);
                    echo $this->Form->control('cto_category');
                    ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
