<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DocumentationType $documentationType
 */
$said = fn(bool $yes): string => $yes ? __d('app_files', 'Yes') : __d('app_files', 'No');
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_files', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('app_files', 'Edit'),
                ['action' => 'edit', $documentationType->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('app_files', 'Delete'),
                ['action' => 'delete', $documentationType->id],
                [
                    'confirm' => __d(
                        'app_files',
                        'Are you sure you want to delete {0}?',
                        $documentationType->name,
                    ),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __d('app_files', 'List Documentation Types'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="documentationTypes view content">
            <h3><?= h($documentationType->name) ?></h3>
            <table>
                <tr>
                    <th><?= __d('app_files', 'Position') ?></th>
                    <td><?= $this->Number->format($documentationType->position) ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_files', 'Currently Offered') ?></th>
                    <td><?= $said($documentationType->currently_offered) ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_files', 'Date Required') ?></th>
                    <td><?= $said($documentationType->date_required) ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_files', 'Customer Required') ?></th>
                    <td><?= $said($documentationType->customer_required) ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_files', 'Contract Required') ?></th>
                    <td><?= $said($documentationType->contract_required) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __d('app_files', 'Note') ?></strong>
                <blockquote><?= $this->Text->autoParagraph(h($documentationType->note)) ?></blockquote>
            </div>
            <div class="related">
                <h4><?= __d('app_files', 'Documentations') ?></h4>
                <?php $this->Preview->load() ?>
                <?= $this->cell('Files.Documentations', [$documentationType->documentations ?? []]) ?>
            </div>
            <?= $this->element('common/audit', ['entity' => $documentationType]) ?>
        </div>
    </div>
</div>
