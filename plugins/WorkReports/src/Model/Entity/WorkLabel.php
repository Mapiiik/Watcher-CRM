<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Colors\ColorThemeSelector;
use App\Colors\ColorTransformer;
use App\Model\Entity\AppEntity;
use Cake\Core\Configure;

/**
 * WorkLabel Entity
 *
 * @property string $name
 * @property string|null $caption
 * @property string $color
 * @property bool $active
 * @property string $style
 */
class WorkLabel extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'caption' => true,
        'color' => true,
        'active' => true,
    ];

    /**
     * Drawn the way the labels of the customers are.
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $theme = Configure::read('UI.theme');
        $theme = is_string($theme) ? $theme : null;

        $backgroundColor = ColorThemeSelector::forTheme($this->color, $theme, factor: 1.0);

        return 'background-color: ' . $backgroundColor . ';'
            . ' color: ' . ColorTransformer::getContrastColor($backgroundColor) . ';';
    }
}
