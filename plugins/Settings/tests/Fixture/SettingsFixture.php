<?php
declare(strict_types=1);

namespace Settings\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SettingsFixture
 */
class SettingsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 'c32de3c5-4fcf-49cf-862c-7b38d4141398',
                'plugin' => 'Lorem ipsum dolor sit amet',
                'key' => 'Lorem ipsum dolor sit amet',
                'value' => ['network' => 'M-Net'],
                'created' => 1761497620,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1761497620,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
