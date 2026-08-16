<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Model\Entity\Label;
use App\Model\Table\LabelsTable;
use Override;

/**
 * How many customers each label picked out for the dashboard currently carries.
 *
 * Which labels appear, and to whom, is set on the label itself rather than here, so a new
 * segment reaches the dashboard without a deploy - the same way dynamic labels are
 * defined by their SQL.
 */
class LabelsCard extends AbstractCustomerListingCard
{
    /**
     * @param \App\Model\Table\LabelsTable $labels Labels table.
     * @param string|null $role The role of the signed-in operator.
     */
    public function __construct(private LabelsTable $labels, private ?string $role)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'labels';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Labels');
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        /** @var list<\App\Model\Entity\Label> $labels */
        $labels = $this->labels
            ->find()
            ->where(['Labels.show_on_dashboard' => true])
            ->orderBy(['Labels.name' => 'ASC'])
            ->all()
            ->filter(fn(Label $label): bool => $label->isOnDashboardFor($this->role))
            ->toList();

        // whether any label is meant for this card at all, as against every one of them
        // having nothing to report - the two read the same on screen otherwise
        $configured = $labels !== [];

        if (!$configured) {
            return ['labels' => [], 'counts' => [], 'urls' => [], 'configured' => false];
        }

        $counts = $this->labels->CustomerLabels->find();
        $counts = $counts
            ->select([
                'label_id' => 'CustomerLabels.label_id',
                'total' => $counts->func()->count('*'),
            ])
            ->where(['CustomerLabels.label_id IN' => array_column($labels, 'id')])
            ->groupBy('CustomerLabels.label_id')
            ->disableHydration()
            ->all()
            ->combine('label_id', 'total')
            ->toArray();

        // A label earns a line by having found something. Most of the checking ones sit at
        // zero most of the time, and a column of zeroes is where the one that is not zero
        // goes unread. The grouped count already leaves them out, so this only follows it.
        $labels = array_values(array_filter(
            $labels,
            fn(Label $label): bool => ($counts[$label->id] ?? 0) > 0,
        ));

        $urls = [];
        foreach ($labels as $label) {
            $urls[$label->id] = $this->customerListingUrl(['label_ids' => [$label->id]]);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'urls' => $urls,
            'configured' => true,
        ];
    }
}
