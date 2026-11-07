<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Label;
use Bookkeeping\Model\Entity\Invoice;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;
use PhpCollective\DecimalObject\Decimal;
use WorkReports\Model\Entity\WorkLabel;
use WorkReports\Model\Entity\WorkReportItem;

/**
 * The entities drawn in a colour say what they did before the colour was made in one place.
 */
class ColoredEntitiesTest extends TestCase
{
    /**
     * The theme as the test found it.
     */
    private mixed $theme = null;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->theme = Configure::read('UI.theme');
        Configure::write('UI.theme', 'light');
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Configure::write('UI.theme', $this->theme);

        parent::tearDown();
    }

    /**
     * A label is its colour, with text that reads on it.
     *
     * @return void
     * @link \App\Model\Entity\Label::_getStyle()
     * @link \WorkReports\Model\Entity\WorkLabel::_getStyle()
     */
    public function testALabelIsDrawnInItsColor(): void
    {
        $expected = 'background-color: #000000; color: #ffffff;';

        $this->assertSame($expected, (new Label(['color' => '#000000']))->get('style'));
        $this->assertSame($expected, (new WorkLabel(['color' => '#000000']))->get('style'));
    }

    /**
     * An invoice past its due date stands out, and a debt that is not due yet only reads red.
     *
     * @return void
     * @link \Bookkeeping\Model\Entity\Invoice::_getStyle()
     */
    public function testAnOverdueInvoiceStandsOut(): void
    {
        $invoice = fn(Date $due): Invoice => new Invoice(['debt' => Decimal::create(10), 'due_date' => $due]);

        $this->assertSame(
            'background-color: #ffc0c0; color: red;',
            $invoice(Date::now()->subDays(3))->get('style'),
        );
        $this->assertSame('color: red;', $invoice(Date::now()->addDays(3))->get('style'));
    }

    /**
     * Work still to be invoiced stands out the way an unpaid invoice does.
     *
     * @return void
     * @link \WorkReports\Model\Entity\WorkReportItem::_getInvoiceStyle()
     */
    public function testWorkToInvoiceStandsOut(): void
    {
        $this->assertSame(
            'background-color: #ffc0c0; color: red;',
            (new WorkReportItem(['to_invoice' => true, 'invoiced' => false]))->get('invoice_style'),
        );
        $this->assertSame('', (new WorkReportItem(['to_invoice' => true, 'invoiced' => true]))->get('invoice_style'));
    }
}
