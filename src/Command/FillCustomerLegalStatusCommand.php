<?php
declare(strict_types=1);

namespace App\Command;

use App\BusinessRegister\IdentityNumberStatus;
use App\Model\Entity\Customer;
use App\Model\Enum\CustomerLegalStatus;
use App\Model\Enum\LegalStatusGuess;
use App\Model\Table\CustomersTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Override;
use Throwable;

/**
 * Fill customer legal status command.
 *
 * Fills in who the customers are in law, from the business registers where they have an
 * identification number, and as a natural person where they have neither that nor a company.
 * Whatever cannot be found out is left as it was and listed, for somebody to fill in by hand.
 *
 * Only the customers with no legal status are asked about, unless told to check them all, and
 * even then only a register's answer overwrites what is there. A run can be repeated at any time:
 * it fills in what an earlier one could not.
 *
 * ARES turns a long run of lookups away for a while, without saying how many it takes or for how
 * long. A lookup turned away is asked again after a pause, and a register that keeps turning them
 * away is taken for down, so that the rest of the run does not wait on it customer by customer.
 *
 * Told to guess, it fills in what the registers could not say the way the installation told a
 * business from a household before there was a legal status, so that nothing is left empty and
 * nothing changes from how the customers were counted. Where the registers never say - the
 * Croatian one does not, and everybody there has an OIB - they need not be asked at all. A guess
 * only ever fills in what is empty, and the guesses that take a customer for a business are
 * listed for somebody to check.
 */
class FillCustomerLegalStatusCommand extends Command
{
    /**
     * How many customers in a row a register may turn away before the run stops waiting on it.
     *
     * @var int
     */
    private const GIVE_UP_AFTER = 3;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->setDescription(__(
            'Fills in who the customers are in law, from the business registers where they have an'
            . ' identification number.',
        ));

        $parser->addOption('recheck', [
            'short' => 'r',
            'help' => __(
                'Ask about the customers whose legal status is filled in too, and correct it where'
                . ' a register says otherwise.',
            ),
            'boolean' => true,
        ]);

        $parser->addOption('dry-run', [
            'short' => 'd',
            'help' => __('Do not write anything, only show what would be changed.'),
            'boolean' => true,
        ]);

        $parser->addOption('guess', [
            'short' => 'g',
            'help' => __(
                'Fill in what the business registers cannot say by a guess - {0}.',
                implode(', ', array_map(
                    fn(LegalStatusGuess $guess): string => $guess->value . ': ' . $guess->label(),
                    LegalStatusGuess::cases(),
                )),
            ),
            'choices' => array_map(fn(LegalStatusGuess $guess): string => $guess->value, LegalStatusGuess::cases()),
        ]);

        $parser->addOption('no-registers', [
            'short' => 'n',
            'help' => __('Do not ask the business registers at all and only guess, where they never say.'),
            'boolean' => true,
        ]);

        $parser->addOption('wait', [
            'short' => 'w',
            'help' => __('Seconds to wait before asking a business register again after it turned a lookup away.'),
            'default' => '60',
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $recheck = (bool)$args->getOption('recheck');
        $dryRun = (bool)$args->getOption('dry-run');
        $guess = LegalStatusGuess::tryFrom((string)$args->getOption('guess'));
        $noRegisters = (bool)$args->getOption('no-registers');
        $wait = max(0, (int)$args->getOption('wait'));

        if ($noRegisters && ($guess === null || $recheck)) {
            $io->error(__(
                'Without the business registers there is nothing to go on but a guess, and nothing to'
                . ' recheck by.',
            ));

            return static::CODE_ERROR;
        }

        try {
            $customers = $this->fetchTable(CustomersTable::class);

            $query = $customers->find()->orderBy(['Customers.nid' => 'ASC']);
            if (!$recheck) {
                $query->where(['Customers.legal_status IS' => null]);
            }

            $outcomes = [
                'filled' => 0,
                'guessed' => 0,
                'corrected' => 0,
                'confirmed' => 0,
                'kept' => 0,
                'unresolved' => 0,
            ];
            $changes = [];
            $guesses = [];
            $unresolved = [];
            $unreachedInARow = 0;

            /** @var \App\Model\Entity\Customer $customer */
            foreach ($query->all() as $customer) {
                $reason = null;
                $unreached = false;
                $found = null;

                if (!$noRegisters) {
                    $askedAgain = false;
                    do {
                        $reason = null;
                        $unreached = false;
                        $found = $this->lookUp($customer, $reason, $unreached);

                        $askAgain = $unreached && !$askedAgain && $unreachedInARow < self::GIVE_UP_AFTER;
                        if ($askAgain) {
                            $io->out(__(
                                'A business register turned the lookup of {0} away, asking again in {1} s.',
                                (string)$customer->identity_number,
                                $wait,
                            ));
                            sleep($wait);
                            $askedAgain = true;
                        }
                    } while ($askAgain);
                }

                $unreachedInARow = $unreached ? $unreachedInARow + 1 : 0;

                // A guess fills in only what nobody has, and never where a register could not be
                // reached: that one may yet answer, and the next run asks it again.
                $guessed = $found === null && $guess !== null && $customer->legal_status === null && !$unreached;
                if ($guessed) {
                    $found = $guess->legalStatusOf($customer);
                }

                if ($found === null) {
                    if ($customer->legal_status === null) {
                        $outcomes['unresolved']++;
                        $unresolved[] = $this->row($customer, (string)$reason);
                    } else {
                        $outcomes['kept']++;
                    }

                    continue;
                }

                if ($found === $customer->legal_status) {
                    $outcomes['confirmed']++;

                    continue;
                }

                $was = $customer->legal_status;

                if (!$dryRun) {
                    $customer->legal_status = $found;

                    // the rules guard much more than this one field, and a customer who broke one
                    // of them long ago is no reason to leave them without a legal status
                    if (!$customers->save($customer, ['checkRules' => false])) {
                        Log::error(sprintf(
                            'The legal status of customer %s could not be saved: %s',
                            $customer->number,
                            json_encode($customer->getErrors()),
                        ));

                        if ($was === null) {
                            $outcomes['unresolved']++;
                            $unresolved[] = $this->row($customer, __('The customer could not be saved.'));
                        } else {
                            $outcomes['kept']++;
                        }

                        continue;
                    }
                }

                if ($guessed) {
                    $outcomes['guessed']++;
                    // a household taken for one is what the installation counted them as anyway
                    if ($found !== CustomerLegalStatus::NaturalPerson) {
                        $guesses[] = $this->row($customer, (string)$reason, $found->label());
                    }

                    continue;
                }

                $outcomes[$was === null ? 'filled' : 'corrected']++;
                $changes[] = $this->row($customer, (string)$was?->label(), $found->label());
            }

            $io->helper('Table')->output([
                [__('Outcome'), __('Customers')],
                [__('Filled in'), (string)$outcomes['filled']],
                [__('Guessed'), (string)$outcomes['guessed']],
                [__('Corrected'), (string)$outcomes['corrected']],
                [__('Confirmed by the register'), (string)$outcomes['confirmed']],
                [__('Kept, the register could not say'), (string)$outcomes['kept']],
                [__('Left empty'), (string)$outcomes['unresolved']],
            ]);

            if ($changes !== []) {
                $io->out(__('Changed:'));
                $io->helper('Table')->output(array_merge(
                    [[__('Customer Number'), __('Customer'), __('Identity Number'), __('Was'), __('Now')]],
                    $changes,
                ));
            }

            if ($guesses !== []) {
                $io->out(__('Guessed to be in business, worth checking:'));
                $io->helper('Table')->output(array_merge(
                    [[__('Customer Number'), __('Customer'), __('Identity Number'), __('Reason'), __('Guessed')]],
                    $guesses,
                ));
            }

            if ($unresolved !== []) {
                $io->out(__('Left empty, to be filled in by hand:'));
                $io->helper('Table')->output(array_merge(
                    [[__('Customer Number'), __('Customer'), __('Identity Number'), __('Reason')]],
                    $unresolved,
                ));
            }

            if ($dryRun) {
                $io->warning(__('Nothing has been written, this was a dry run.'));
            }

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error while filling in the legal status of customers: ' . $e->getMessage());

            $io->error(__('Error while filling in the legal status of customers: {0}', $e->getMessage()));

            return static::CODE_ERROR;
        }
    }

    /**
     * Who the customer is in law, as far as it can be found out, null where it cannot.
     *
     * A customer with neither an identification number nor a company is a household. Anything
     * else takes a register, and a register that does not hold the number, cannot be reached or
     * does not say leaves nothing to go on.
     *
     * @param \App\Model\Entity\Customer $customer The customer to look up.
     * @param string|null $reason Why it could not be found out, set when null is returned.
     * @param bool $unreached Set where no register could be asked, which may pass.
     * @param-out string|null $reason
     * @return \App\Model\Enum\CustomerLegalStatus|null
     */
    private function lookUp(Customer $customer, ?string &$reason, bool &$unreached): ?CustomerLegalStatus
    {
        if (trim((string)$customer->identity_number) === '') {
            if (trim((string)$customer->company) !== '') {
                $reason = __('A company without an identification number.');

                return null;
            }

            // only an empty one is filled in like this: a sole trader may well have an
            // identification number nobody wrote down, and nothing here says otherwise
            if ($customer->legal_status !== null) {
                $reason = __('No identification number to look up.');

                return null;
            }

            return CustomerLegalStatus::NaturalPerson;
        }

        if (!$customer->verifyIdentityNumber()) {
            $reason = __('The identification number is not valid.');

            return null;
        }

        $answer = $customer->identityNumberAnswer();

        if ($answer->unanswered()) {
            $reason = __('No business register could be asked, try again later.');
            $unreached = true;

            return null;
        }

        if (!$answer->ok()) {
            $reason = __('No business register takes this number.');

            return null;
        }

        $check = $answer->data;

        if ($check->status === IdentityNumberStatus::NotFound) {
            $reason = __('Not in the business register.');

            return null;
        }

        if ($check->legalStatus === null) {
            $reason = __('The business register does not say.');

            return null;
        }

        return $check->legalStatus;
    }

    /**
     * One line of a listing: who the customer is, and whatever is to be said of them.
     *
     * @param \App\Model\Entity\Customer $customer The customer.
     * @param string ...$columns What is said of them.
     * @return list<string>
     */
    private function row(Customer $customer, string ...$columns): array
    {
        return [
            $customer->number,
            $customer->name,
            (string)$customer->identity_number,
            ...array_values($columns),
        ];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'customers fill_legal_status';
    }
}
