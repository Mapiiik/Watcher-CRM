<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;
use Settings\ValueObject\SettingChoices;

/**
 * What the wait for a signed paper is counted from.
 *
 * Counting from the installation says the customer has had the service for long enough to
 * have signed for it. Counting from the day the papers went out says they have had the
 * papers for long enough - which is the fairer question, because nobody can sign what has
 * not reached them, and the two dates are often weeks apart.
 *
 * The third answer is the two together, and it exists because the first two disagree about
 * what an unrecorded sending means. Which of them an installation works by is a matter of
 * how its office runs, so it is asked of the settings rather than decided here.
 */
enum UnsignedDeadlineAnchor: string implements EnumLabelInterface, SettingChoices
{
    use EnumOptionsTrait;

    /**
     * Where the settings say which of these is meant.
     *
     * Kept on the enum, because the query that counts the days and the command that acts on
     * the answer have to be reading the same one.
     *
     * @var string
     */
    public const SETTINGS_PATH = 'core.contracts.unsigned.anchor';

    /**
     * The day the service went in. Every contract carries one, so nothing escapes.
     */
    case Installation = 'installation';

    /**
     * The day the papers went out, and nothing else.
     *
     * The strict reading, and the one to know the cost of: a version nobody has recorded a
     * sending for has no day to count from, so it is never chased and never blocked. That
     * goes quiet exactly where the paperwork is worst kept, which is either the point - the
     * office chases only what it knows it sent - or a hole, depending on how the sending
     * column is actually filled in.
     */
    case Sending = 'sending';

    /**
     * The day the papers went out, falling back on the installation where nobody wrote it
     * down.
     *
     * The customer gets the fairer clock wherever the sending is on record, and the version
     * nobody has recorded anything about is still chased, on the day it went in.
     */
    case SendingOrInstallation = 'sending-or-installation';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Installation => __('From the day the service was installed'),
            self::Sending => __('From the day the papers were sent, and only where that is recorded'),
            self::SendingOrInstallation =>
                __('From the day the papers were sent, or from the installation where that is not recorded'),
        };
    }

    /**
     * The one the settings name, or the installation where they name nothing that means
     * anything.
     *
     * Falling back on the installation rather than on either sending reading is deliberate:
     * it is the date every contract carries, so a setting nobody has touched behaves the way
     * the checks always have.
     *
     * @param string|null $value The stored setting value.
     * @return self
     */
    public static function fromSetting(?string $value): self
    {
        return self::tryFrom((string)$value) ?? self::Installation;
    }

    /**
     * The date this anchor is read from, as SQL over the contract and its version.
     *
     * A NULL here is what carries "there is no day to count from" into the query, and the
     * caller has to keep it NULL rather than reach past it - which is the whole difference
     * between the strict sending anchor and the one that falls back.
     *
     * @return string
     */
    public function sql(): string
    {
        return match ($this) {
            self::Installation => 'Contracts.installation_date',
            self::Sending => self::LAST_SENDING,
            self::SendingOrInstallation =>
                'COALESCE(' . self::LAST_SENDING . ', Contracts.installation_date)',
        };
    }

    /**
     * When the papers for this version last went out.
     *
     * The sending is recorded on the proposal the papers were drawn from, because that is what a
     * paper is: a version may have several behind it over the years. The latest one is what counts
     * - sending the papers again is giving the customer a fresh chance, and the wait starts over.
     */
    private const LAST_SENDING = '(
        SELECT MAX(SentProposals.sent_date)
        FROM contract_version_proposals SentProposals
        WHERE SentProposals.contract_version_id = ContractVersions.id
    )';
}
