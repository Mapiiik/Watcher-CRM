<?php
declare(strict_types=1);

namespace App\Bulk\Filter;

use App\NMS\ApiClient as NMSApiClient;
use Cake\Collection\CollectionInterface;
use Cake\Validation\Validation;

/**
 * Filters customers by the access point of one of their contracts.
 *
 * Renders a multiselect of access points plus a "cascade" toggle. When the
 * toggle is on, each selected access point is expanded to its whole subtree
 * (all transitive descendants via `parent_access_point_id`), so a parent
 * selection also covers every access point below it.
 *
 * The access point list is loaded from the NMS API; when unavailable the
 * options are empty and the filter simply offers nothing to select.
 */
final class AccessPointFilter extends AbstractBulkRecipientFilter
{
    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return 'access_point';
    }

    /**
     * @inheritDoc
     */
    public function controls(mixed $value): array
    {
        $selected = is_array($value) && isset($value['ids']) && is_array($value['ids']) ? $value['ids'] : [];
        $cascade = is_array($value) && !empty($value['cascade']);

        return [
            [
                'name' => 'access_point_ids',
                'options' => [
                    'label' => __('Access Points'),
                    'options' => $this->accessPointOptions(),
                    'multiple' => true,
                    'empty' => false,
                    'val' => $selected,
                ],
            ],
            [
                'name' => 'access_point_cascade',
                'options' => [
                    'type' => 'checkbox',
                    'label' => __('Include sub access points (whole subtree)'),
                    'checked' => $cascade,
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildValue(array $data): mixed
    {
        $rawIds = $data['access_point_ids'] ?? null;
        $ids = is_array($rawIds)
            ? array_values(array_filter(
                $rawIds,
                static fn(mixed $id): bool => is_string($id) && Validation::uuid($id),
            ))
            : [];

        if ($ids === []) {
            return null;
        }

        return [
            'ids' => $ids,
            'cascade' => (bool)($data['access_point_cascade'] ?? false),
        ];
    }

    /**
     * @inheritDoc
     */
    public function conditions(mixed $value): ?array
    {
        $ids = $this->matchedAccessPointIds($value);
        if ($ids === []) {
            return null;
        }

        $filterQuery = $this->customerMessages->Customers->Contracts
            ->find()
            ->select(['customer_id'])
            ->distinct()
            ->where(['Contracts.access_point_id IN' => $ids]);

        return ['Customers.id IN' => $filterQuery];
    }

    /**
     * Effective access point ids this filter matches: the validated selection,
     * expanded with the whole subtree when the cascade toggle is on. Empty when
     * the filter is inactive. Shared by {@see conditions()} and the preview
     * grouping, so both stay consistent.
     *
     * @param mixed $value Stored filter value.
     * @return array<string>
     */
    public function matchedAccessPointIds(mixed $value): array
    {
        if (!is_array($value) || !isset($value['ids']) || !is_array($value['ids'])) {
            return [];
        }

        $ids = array_values(array_filter(
            $value['ids'],
            static fn(mixed $id): bool => is_string($id) && Validation::uuid($id),
        ));
        if ($ids === []) {
            return [];
        }

        return empty($value['cascade']) ? $ids : $this->expandSubtree($ids);
    }

    /**
     * Selectable (active) access points as a value => name list.
     *
     * @return array<array-key, string>
     */
    private function accessPointOptions(): array
    {
        $accessPoints = NMSApiClient::getAccessPointsList(onlyActive: true);
        if ($accessPoints === null) {
            $this->warning = __('The access points list could not be loaded. Please, try again.');

            return [];
        }

        return $accessPoints;
    }

    /**
     * Expand the given access point ids with all of their transitive
     * descendants (children, grandchildren, …).
     *
     * The tree is built from the full access point set (active and archived)
     * so descendants that still carry contracts are never silently dropped.
     * Falls back to the input ids when the NMS list is unavailable.
     *
     * @param array<string> $ids Selected access point ids.
     * @return array<string>
     */
    private function expandSubtree(array $ids): array
    {
        $accessPoints = NMSApiClient::getAccessPoints();
        if (!$accessPoints instanceof CollectionInterface) {
            return $ids;
        }

        // parent id => list of direct child ids
        $childrenByParent = [];
        foreach ($accessPoints as $accessPoint) {
            $parentId = $accessPoint['parent_access_point_id'] ?? null;
            if (is_string($parentId) && isset($accessPoint['id']) && is_string($accessPoint['id'])) {
                $childrenByParent[$parentId][] = $accessPoint['id'];
            }
        }

        $collected = [];
        $stack = $ids;
        while ($stack !== []) {
            $current = array_pop($stack);
            if (isset($collected[$current])) {
                // already visited — guards against cycles
                continue;
            }
            $collected[$current] = true;

            foreach ($childrenByParent[$current] ?? [] as $childId) {
                if (!isset($collected[$childId])) {
                    $stack[] = $childId;
                }
            }
        }

        return array_keys($collected);
    }
}
