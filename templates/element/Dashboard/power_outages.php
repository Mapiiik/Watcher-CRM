<?php
/**
 * The masts the distributor is about to cut the power to, and how many lines go with each.
 *
 * Three ways this can read, and two of them are not the same thing at all: a reading that never
 * arrived is not an afternoon with nothing coming. The empty state is good news and has to look
 * like good news; an outage nobody could ask about has to look like the fault it is.
 *
 * Every way on leads to the network management system, because that is where the mast is kept.
 *
 * @var \App\View\AppView $this
 * @var list<\App\NMS\Dto\PowerOutage> $outages
 * @var int $total
 * @var \App\Http\Answer<mixed> $answer What came of the asking.
 * @var string|null $url Where the rest of them are, or null with no such application to point at.
 */

use App\NMS\Links;

$shown = 0;
?>
<?php if ($answer->unanswered()) : ?>
    <p>
        <?= $this->element('NMS/unavailable', ['answer' => $answer]) ?>
        <?= __('Data from Watcher NMS could not be loaded.') ?>
    </p>
<?php elseif ($total === 0) : ?>
    <p><?= __('No planned outage is known for any of our access points.') ?></p>
<?php else : ?>
    <table class="dashboard-table">
        <tbody>
            <?php foreach ($outages as $outage) : ?>
                <?php $shown++ ?>
                <?php $link = Links::accessPoint($outage->accessPointId) ?>
                <tr>
                    <td>
                        <?= $link === null
                            ? h($outage->accessPointName)
                            : $this->Html->link(
                                (string)$outage->accessPointName,
                                $link,
                                ['target' => '_blank'],
                            ) ?>
                        <br><small><?= h($outage->summary) ?></small>
                    </td>
                    <td>
                        <?= h($outage->beginsAt) ?>
                        <br><small><?= $outage->isCertain()
                            ? '<strong>' . h($outage->certainty?->label()) . '</strong>'
                            : h($outage->certainty?->label()) ?></small>
                    </td>
                    <td>
                        <?php // Everything fed from the mast, not only what hangs on it directly. ?>
                        <?php // Drawn rather than named: the card is narrow, and the word for it is ?>
                        <?php // longer than any number it could stand beside. The title says it. ?>
                        <span title="<?= h(__('Customer connections affected')) ?>">
                            &#128100; <?= $this->Number->format($outage->connections) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <?php if ($total > $shown && $url !== null) : ?>
        <p><?= $this->Html->link(__('and {0} more', $total - $shown), $url, ['target' => '_blank']) ?></p>
    <?php endif ?>
<?php endif ?>
