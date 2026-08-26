<?php
/**
 * Tells whoever holds a task that it is theirs, or that it has changed.
 *
 * @var \App\View\AppView $this
 * @var string $title
 * @var \App\Model\Entity\Task $task
 */

// set title
$this->assign('title', $title);
?>
<h2><?= $this->fetch('title') ?></h2>
<?= $this->element('Tasks/email_detail', ['task' => $task]) ?>
