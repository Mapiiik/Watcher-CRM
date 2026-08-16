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

        if ($labels === []) {
            return ['labels' => [], 'counts' => [], 'urls' => []];
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

        $urls = [];
        foreach ($labels as $label) {
            $urls[$label->id] = $this->customerListingUrl(['label_ids' => [$label->id]]);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'urls' => $urls,
        ];
    }
}
