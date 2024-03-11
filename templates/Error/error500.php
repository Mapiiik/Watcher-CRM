<?php
use Cake\Core\Configure;
use Cake\Error\Debugger;

/**
 * @var \App\View\AppView $this
 * @var \Cake\Database\StatementInterface $error
 * @var string $message
 * @var string $url
 */

$this->setLayout('error');

if (Configure::read('debug')) :
    $this->setLayout('dev_error');

    $this->assign('title', $message);
    $this->assign('templateName', 'error500.php');

    $this->start('file');
    ?>
    <?php if ($error instanceof Error) : ?>
        <?php $file = $error->getFile() ?>
        <?php $line = $error->getLine() ?>
        <strong>Error in: </strong>
        <?= $this->Html->link(
            sprintf('%s, line %s', Debugger::trimPath($file), $line),
            Debugger::editorUrl($file, $line)
        ); ?>
    <?php endif; ?>
    <?php
    echo $this->element('auto_table_warning');

    $this->end();
endif;
?>
<h2><?= __d('cake', 'An Internal Error Has Occurred.') ?></h2>
<p class="error">
    <strong><?= __d('cake', 'Error') ?>: </strong>
    <?= h($message) ?>
</p>
