<?php
/**
 * One folder, open.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Documentation $documentation
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_files', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('app_files', 'Add Files'),
                ['action' => 'addFiles', $documentation->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('app_files', 'Edit Documentation'),
                ['action' => 'edit', $documentation->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('app_files', 'Delete Documentation'),
                ['action' => 'delete', $documentation->id],
                [
                    'confirm' => __d(
                        'app_files',
                        'Delete {0} and everything in it?',
                        $documentation->heading,
                    ),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __d('app_files', 'List Documentations'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?php if ($documentation->contract_id !== null) : ?>
                <?= $this->AuthLink->link(
                    __d('app_files', 'Contract Documents'),
                    [
                        'controller' => 'Contracts',
                        'action' => 'documents',
                        $documentation->contract_id,
                        'customer_id' => $documentation->customer_id,
                    ],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="documentations view content">
            <h3><?= h($documentation->heading) ?></h3>
            <table>
                <tr>
                    <th><?= __d('app_files', 'Documentation Type') ?></th>
                    <td><?= h($documentation->documentation_type->name ?? '') ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_files', 'Happened On') ?></th>
                    <td><?= h($documentation->happened_on) ?></td>
                </tr>
                <?php if ($documentation->customer !== null) : ?>
                <tr>
                    <th><?= __d('app_files', 'Customer') ?></th>
                    <td><?=
                        $this->AuthLink->link(
                            $documentation->customer->name_for_lists,
                            ['controller' => 'Customers', 'action' => 'view', $documentation->customer_id],
                        )
                        ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($documentation->contract !== null) : ?>
                <tr>
                    <th><?= __d('app_files', 'Contract') ?></th>
                    <td><?=
                        $this->AuthLink->link(
                            (string)$documentation->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $documentation->contract_id],
                        )
                        ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php if (trim((string)$documentation->note) !== '') : ?>
            <div class="text">
                <blockquote><?= $this->Text->autoParagraph(h($documentation->note)) ?></blockquote>
            </div>
            <?php endif; ?>
            <div class="related">
                <?php $this->Preview->load() ?>
                <?= $this->cell('Files.Documentations::contents', [$documentation]) ?>
            </div>
            <?= $this->element('common/audit', ['entity' => $documentation]) ?>
        </div>
    </div>
</div>
