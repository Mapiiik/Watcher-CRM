<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Table\ContractProposalsTable;
use App\Service\ContractPrint\ContractDocuments;
use Cake\ORM\Query\SelectQuery;
use Cake\View\Cell;
use InvalidArgumentException;

/**
 * The documents filed against a record, wherever somebody is looking at that record.
 *
 * A cell rather than an element, so that a page says which record it is about and nothing else. It
 * was an element first, and every page that wanted the table had to remember to load the proposals
 * and their papers and to say which columns to draw - three chances to get it wrong for one table.
 *
 * Which columns those are follows from what is being looked at rather than being asked for: a
 * contract's own page has no business repeating the contract on every row.
 */
class DocumentsCell extends Cell
{
    /**
     * What the papers may be looked at from.
     *
     * @var list<string>
     */
    public const SCOPES = ['proposal', 'contract', 'customer'];

    /**
     * List of valid options that can be passed into this cell's constructor.
     *
     * @var list<string>
     */
    protected array $_validCellOptions = ['ours', 'manage'];

    /**
     * Whether this is the side we drew up rather than the scans that came back.
     */
    protected bool $ours = false;

    /**
     * Whether the pages may be reordered and let go of from here.
     */
    protected bool $manage = false;

    /**
     * Default display method.
     *
     * @param string $of What is being looked at - one of {@see self::SCOPES}.
     * @param string|null $id Which one.
     * @return void
     * @throws \InvalidArgumentException When asked for something that is not a scope.
     */
    public function display(string $of, ?string $id): void
    {
        $proposals = $id === null ? [] : $this->proposalsOf($of, $id)->all()->toList();

        $this->set('proposals', $proposals);
        $this->set('filed', (new ContractDocuments())->filedAgainst($proposals));
        $this->set('ours', $this->ours);
        $this->set('manage', $this->manage);
        // Neither column says anything the page it is on has not already said.
        $this->set('showContract', $of === 'customer');
        $this->set('showProposal', $of !== 'proposal');
    }

    /**
     * The proposals the papers of that record hang on, newest first.
     *
     * @param string $of What is being looked at.
     * @param string $id Which one.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractProposal>
     */
    private function proposalsOf(string $of, string $id): SelectQuery
    {
        /** @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractProposal> $proposals */
        $proposals = $this->fetchTable(ContractProposalsTable::class)
            ->find()
            ->contain(['Contracts']);

        return match ($of) {
            'proposal' => $proposals
                ->where(['ContractProposals.id' => $id]),
            'contract' => $proposals
                ->where(['ContractProposals.contract_id' => $id])
                ->orderBy(['ContractProposals.effective_from' => 'DESC']),
            'customer' => $proposals
                ->where(['Contracts.customer_id' => $id])
                ->orderBy([
                    'Contracts.number' => 'ASC',
                    'ContractProposals.effective_from' => 'DESC',
                ]),
            default => throw new InvalidArgumentException(
                sprintf('`%s` is not something documents hang on.', $of),
            ),
        };
    }
}
