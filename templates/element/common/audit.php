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
                    ['controller' => 'AppUsers', 'action' => 'view', $entity->creator->id],
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
                    ['controller' => 'AppUsers', 'action' => 'view', $entity->modifier->id],
                ) ?>
            <?php else : ?>
                <?= h($entity->modified_by) ?>
            <?php endif; ?>
        </td>
    </tr>
</table>
