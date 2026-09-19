<?php
declare(strict_types=1);

namespace App\Pdf\Trait;

use Cake\I18n\Date;
use Cake\I18n\Number;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;

/**
 * The table of services and their monthly prices.
 *
 * Shared by the contract and by the list of what a customer has, so that one service reads the
 * same on both.
 */
trait BillingTableTrait
{
    /**
     * The services, split the way they are agreed: at the price list's prices or at prices of
     * their own, and running already or still to start.
     *
     * What starts later is shown and left out of the total, because the total is what is paid now.
     *
     * @param iterable<\App\Model\Entity\Billing> $standard Running, at the price list's prices
     * @param iterable<\App\Model\Entity\Billing> $individual Running, at prices of their own
     * @param iterable<\App\Model\Entity\Billing> $futureStandard Still to start, at the price list's prices
     * @param iterable<\App\Model\Entity\Billing> $futureIndividual Still to start, at prices of their own
     * @param \Cake\I18n\Date $billingReferenceDate Reference date for billing relevance
     * @param string $format Additional font format
     * @return \PhpCollective\DecimalObject\Decimal Total of what is running
     */
    protected function printServices(
        iterable $standard,
        iterable $individual,
        iterable $futureStandard,
        iterable $futureIndividual,
        Date $billingReferenceDate,
        string $format,
    ): Decimal {
        $listed = static fn(iterable $billings): array => is_array($billings)
            ? array_values($billings)
            : iterator_to_array($billings, false);
        [$standard, $individual, $futureStandard, $futureIndividual] = array_map(
            $listed,
            [$standard, $individual, $futureStandard, $futureIndividual],
        );

        $totalCost = Decimal::create(0, 2);

        if ($standard !== []) {
            $this->printServicesHeading($this->billingText('sections.billing_pricelist'), $format);
            $totalCost = $totalCost->add($this->billingTable($standard, $billingReferenceDate, $format));
            $this->Ln();
        }

        if ($individual !== []) {
            $this->printServicesHeading($this->billingText('sections.billing_individual'), $format);
            $totalCost = $totalCost->add($this->billingTable($individual, $billingReferenceDate, $format));
            $this->afterIndividualPrices($format);
        }

        if ($futureStandard !== []) {
            $this->printServicesHeading($this->billingText('sections.billing_future_pricelist'), $format);
            $this->billingTable($futureStandard, $billingReferenceDate, $format);
            $this->Ln();
        }

        if ($futureIndividual !== []) {
            $this->printServicesHeading($this->billingText('sections.billing_future_individual'), $format);
            $this->billingTable($futureIndividual, $billingReferenceDate, $format);
            $this->afterIndividualPrices($format);
        }

        return $totalCost;
    }

    /**
     * Heads one of the tables of services. A document that sets them apart differently says so.
     *
     * @param string $text The heading
     * @param string $format Additional font format
     * @return void
     */
    protected function printServicesHeading(string $text, string $format): void
    {
        $this->printBillingHeading($text, $format);
    }

    /**
     * What follows a table of individually priced services. Nothing but air, unless a document
     * has something to say about those prices.
     *
     * @param string $format Additional font format
     * @return void
     */
    protected function afterIndividualPrices(string $format): void
    {
        $this->Ln();
    }

    /**
     * Heads one of the billing tables.
     *
     * These sit closer to their table than an ordinary section heading does, and they carry
     * the block's own font style, so they are set here rather than through the shared one.
     *
     * @param string $text The heading
     * @param string $format Additional font format
     * @return void
     */
    protected function printBillingHeading(string $text, string $format): void
    {
        $this->keepTogether(static::HEADING_ORPHAN_GUARD);

        $this->SetFont(static::FONT_FAMILY, 'B' . $format, static::HEADING_FONT_SIZE);
        $this->frameLeft();
        $this->Cell(static::PAGE_WIDTH, 3, $text);
        $this->Ln();

        $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
    }

    /**
     * Prints billing table.
     *
     * @param iterable<\App\Model\Entity\Billing> $billings Billings
     * @param \Cake\I18n\Date $billingReferenceDate Reference date for billing relevance
     * @param string $format Additional font format
     * @return \PhpCollective\DecimalObject\Decimal Total cost
     */
    protected function billingTable(iterable $billings, Date $billingReferenceDate, string $format): Decimal
    {
        $this->SetFont(static::FONT_FAMILY, '' . $format, static::BODY_FONT_SIZE);
        $this->Cell(140, static::LINE_HEIGHT, $this->billingText('service'));
        $this->Cell(35, static::LINE_HEIGHT, $this->billingText('price_per_month'), align: 'R');
        $this->Ln();

        $totalCost = Decimal::create(0, 2);

        foreach ($billings as $billing) {
            $this->SetFont(static::FONT_FAMILY, 'B' . $format, static::BODY_FONT_SIZE);
            $this->Cell(
                140,
                static::LINE_HEIGHT,
                $billing->name
                . ($billing->billing_from > $billingReferenceDate
                    ? ' ' . strtr($this->billingText('from'), [
                        '{date}' => (string)$billing->billing_from,
                    ])
                    : '')
                . ($billing->billing_until
                    ? ' ' . strtr($this->billingText('until'), [
                        '{date}' => (string)$billing->billing_until,
                    ])
                    : ''),
                align: 'L',
                stretch: 1,
            );
            $this->Cell(35, static::LINE_HEIGHT, Number::currency($billing->sum->toFloat()), align: 'R');
            $this->Ln();

            if ($billing->percentage_discount_sum->isPositive()) {
                $this->SetFont(static::FONT_FAMILY, '' . $format, static::BODY_FONT_SIZE);
                $this->Cell(
                    140,
                    static::LINE_HEIGHT,
                    strtr($this->billingText('percentage_discount'), [
                        '{percentage}' => (string)$billing->percentage_discount,
                    ]),
                );
                $this->Cell(
                    35,
                    static::LINE_HEIGHT,
                    Number::currency($billing->percentage_discount_sum->negate()->toFloat()),
                    align: 'R',
                );
                $this->Ln();
            }
            if ($billing->fixed_discount_sum->isPositive()) {
                $this->SetFont(static::FONT_FAMILY, '' . $format, static::BODY_FONT_SIZE);
                $this->Cell(140, static::LINE_HEIGHT, $this->billingText('fixed_discount'));
                $this->Cell(
                    35,
                    static::LINE_HEIGHT,
                    Number::currency($billing->fixed_discount_sum->negate()->toFloat()),
                    align: 'R',
                );
                $this->Ln();
            }

            /** @psalm-suppress ImplicitToStringCast */
            $totalCost = $totalCost->add($billing->total_price);
        }

        return $totalCost;
    }

    /**
     * Reads one of the labels the billing tables share.
     *
     * @param string $key Key under the billing block
     * @return string
     */
    protected function billingText(string $key): string
    {
        return Settings::getString('core.documents.common.billing.' . $key);
    }
}
