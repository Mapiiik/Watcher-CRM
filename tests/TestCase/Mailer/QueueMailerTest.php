<?php
declare(strict_types=1);

namespace App\Test\TestCase\Mailer;

use App\Mailer\QueueMailer;
use Cake\I18n\Date;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Mailer\QueueMailer Test Case
 */
class QueueMailerTest extends TestCase
{
    use EmailTrait;

    /**
     * Test subject
     *
     * @var \App\Mailer\QueueMailer
     */
    protected $Queue;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->loadRoutes();
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\TaxRatesController::index()
     */
    public function testServiceChange(): void
    {
        $mailer = new QueueMailer('contracts');
        $mailer->send(
            'serviceChange',
            [
                ['test@test.test'],
                [
                    'customer_name' => 'Bc. Martin Patočka',
                    'contract_number' => '000000-0000',
                    'installation_address' => 'Somewhere in the middle of nowhere',
                    'original_billing_name' => 'Original service name',
                    'original_billing_sum' => 111.11,
                    'new_billing_name' => 'New service name',
                    'new_billing_sum' => 222.22,
                    'new_billing_from' => h(new Date()),
                ],
            ]
        );

        $this->assertMailSentTo('test@test.test');
        $this->assertMailContainsText('000000-0000');
        $this->assertMailContainsText('Somewhere in the middle of nowhere');
        $this->assertMailContainsText('Original service name');
        $this->assertMailContainsText('111');
        $this->assertMailContainsText('New service name');
        $this->assertMailContainsText('222');
    }
}
