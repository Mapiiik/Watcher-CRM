<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AppEntity $entity
 */
?>
<table>
    <tr>
        <th><?= __('Id') ?></th>
        <td><?= h($entity->id) ?></td>
    </tr>

    <tr>
        <th><?= __('Created') ?></th>
        <td><?= h($entity->created) ?></td>
    </tr>

    <tr>
        <th><?= __('Created By') ?></th>
        <td>
            <?php if ($entity->creator !== null) : ?>
                <?= $this->Html->link(
                    $entity->creator->username ?? '(' . $entity->creator->id . ')',
                    ['plugin' => null, 'controller' => 'AppUsers', 'action' => 'view', $entity->creator->id],
                ) ?>
            <?php else : ?>
                <?= h($entity->created_by) ?>
            <?php endif; ?>
        </td>
    </tr>

    <tr>
        <th><?= __('Modified') ?></th>
        <td><?= h($entity->modified) ?></td>
    </tr>

    <tr>
        <th><?= __('Modified By') ?></th>
        <td>
            <?php if ($entity->modifier !== null) : ?>
                <?= $this->Html->link(
                    $entity->modifier->username ?? '(' . $entity->modifier->id . ')',
                    ['plugin' => null, 'controller' => 'AppUsers', 'action' => 'view', $entity->modifier->id],
                ) ?>
            <?php else : ?>
                <?= h($entity->modified_by) ?>
            <?php endif; ?>
        </td>
    </tr>

    <?php if ($entity->has('removed')) : ?>
    <tr>
        <th><?= __('Removed') ?></th>
        <td><?= h($entity->removed) ?></td>
    </tr>

    <tr>
        <th><?= __('Removed By') ?></th>
        <td>
            <?php if ($entity->remover !== null) : ?>
                <?= $this->Html->link(
                    $entity->remover->username ?? '(' . $entity->remover->id . ')',
                    ['plugin' => null, 'controller' => 'AppUsers', 'action' => 'view', $entity->remover->id],
                ) ?>
            <?php else : ?>
                <?= h($entity->removed_by) ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endif; ?>

    <?php if ($entity->has('revoked')) : ?>
    <tr>
        <th><?= __('Revoked') ?></th>
        <td><?= h($entity->revoked) ?></td>
    </tr>

    <tr>
        <th><?= __('Revoked By') ?></th>
        <td>
            <?php if ($entity->revoker !== null) : ?>
                <?= $this->Html->link(
                    $entity->revoker->username ?? '(' . $entity->revoker->id . ')',
                    ['plugin' => null, 'controller' => 'AppUsers', 'action' => 'view', $entity->revoker->id],
                ) ?>
            <?php else : ?>
                <?= h($entity->revoked_by) ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endif; ?>
</table>
