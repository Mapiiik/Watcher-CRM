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
                'modified' => 1761497620,
                // who wrote an overlay is nullable, and the users are an application's own - the
                // plugin travels between them and names none of them
            ],
        ];
        parent::init();
    }
}
