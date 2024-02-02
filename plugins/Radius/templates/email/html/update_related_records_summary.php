<?php
/**
 * @var \Cake\View\View $this
 * @var string $title
 * @var \Radius\Updater\ChangeLog\ChangeLog|null $changelog
 */

// set title
$this->assign('title', $title);
?>
<style>
table, td, th {
  border: 1px solid;
}

table {
  width: 100%;
  border-collapse: collapse;
}
</style>
<h2><?= __d('radius', 'Change Log') ?></h2>
<h4><?= $this->fetch('title') ?></h4>
<?= $this->element('Accounts/UpdateRelatedRecordsSummary', [
    'changelog' => $changelog,
]) ?>
