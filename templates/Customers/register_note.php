<?php
/**
 * What a business register says about one of the customer's numbers, drawn on its own because
 * asking takes long enough that the page is better off not waiting for it.
 *
 * The check digit has its say on the page itself - that costs nothing and holds up even where no
 * register could be reached.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var \App\BusinessRegister\IdentityNumberCheck|null $identityNumberCheck
 * @var \App\BusinessRegister\VatNumberCheck|null $vatNumberCheck
 */

use App\BusinessRegister\IdentityNumberStatus;
use App\BusinessRegister\VatNumberStatus;

/**
 * A remark in brackets after a number, marked as an error where it is one.
 *
 * @param string $note What to say.
 * @param bool $wrong Whether it is something wrong rather than something to know.
 * @return string
 */
$remark = function (string $note, bool $wrong = false): string {
    $note = ' (' . h($note) . ')';

    return $wrong ? '<span class="error-text">' . $note . '</span>' : $note;
};

if ($identityNumberCheck !== null) {
    echo $remark(
        $identityNumberCheck->note(),
        wrong: $identityNumberCheck->status === IdentityNumberStatus::NotFound
            || !$customer->isKnownAs($identityNumberCheck->company),
    );

    // the clauses a contract prints follow the legal status, so one the register disagrees with
    // is said out loud - and so is one nobody filled in, where the register could have told
    if (
        $identityNumberCheck->legalStatus !== null
        && $identityNumberCheck->legalStatus !== $customer->legal_status
    ) {
        echo $remark(
            __('in the register as {0}', $identityNumberCheck->legalStatus->label()),
            wrong: true,
        );
    }
}

if ($vatNumberCheck !== null) {
    echo $remark(
        $vatNumberCheck->status->label(),
        wrong: $vatNumberCheck->status === VatNumberStatus::Invalid,
    );

    if ($vatNumberCheck->company !== null) {
        echo $remark(
            $vatNumberCheck->company,
            wrong: !$customer->isKnownAs($vatNumberCheck->company),
        );
    }
}
