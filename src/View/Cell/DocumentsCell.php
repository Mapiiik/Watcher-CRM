<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Enum\ContractPrintType;
use App\Model\Enum\CustomerPrintType;
use App\Model\Enum\DocumentVariant;
use App\Model\Table\ContractProposalsTable;
use App\Model\Table\CustomerProposalsTable;
use App\Service\ContractPrint\ContractDocuments;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\View\Cell;
use InvalidArgumentException;

/**
 * The documents filed against a record, wherever somebody is looking at that record.
 *
 * A cell rather than an element, so that a page says which record it is about and nothing else. It
 * was an element first, and every page that wanted the table had to remember to load the rounds
 * and their papers and to say which columns to draw - three chances to get it wrong for one table.
 *
 * Which columns those are follows from what is being looked at rather than being asked for: a
 * contract's own page has no business repeating the contract on every row.
 *
 * The two agendas share the table. A contract's papers hang off a proposal of that contract and a
 * customer's off a round put to the customer, and on the customer's own page both belong - a
 * consent has no contract, so that column is simply empty on its rows.
 */
class DocumentsCell extends Cell
{
    /**
     * What the papers may be looked at from.
     *
     * @var list<string>
     */
    public const SCOPES = ['contractProposal', 'customerProposal', 'contract', 'customer'];

    /**
     * List of valid options that can be passed into this cell's constructor.
     *
     * @var list<string>
     */
    protected array $_validCellOptions = ['ours', 'manage', 'withContracts'];

    /**
     * Whether this is the side we drew up rather than the scans that came back.
     */
    protected bool $ours = false;

    /**
     * Whether the pages may be reordered and let go of from here.
     */
    protected bool $manage = false;

    /**
     * Whether the papers of the customer's contracts belong here too. Only asked on the customer.
     */
    protected bool $withContracts = true;

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
        if (!in_array($of, self::SCOPES, true)) {
            throw new InvalidArgumentException(sprintf('`%s` is not something documents hang on.', $of));
        }

        $this->set('rows', $id === null ? [] : $this->rows($of, $id));
        $this->set('ours', $this->ours);
        $this->set('manage', $this->manage);
        // Neither column says anything the page it is on has not already said.
        $this->set('showContract', $of === 'customer' && $this->withContracts);
        $this->set('showProposal', in_array($of, ['contract', 'customer'], true));
    }

    /**
     * One row to a page, what was put to the customer first and the contracts' papers after it.
     *
     * That way round because a paper about the customer themselves is about all of them, while one
     * about a contract is about a corner of it.
     *
     * @param string $of What is being looked at.
     * @param string $id Which one.
     * @return list<array<string, mixed>>
     */
    private function rows(string $of, string $id): array
    {
        $rows = [];

        $customers = $this->customerProposals($of, $id);
        $filed = (new CustomerDocuments())->filedAgainst($customers);
        $documents = $this->labelled(CustomerPrintType::cases());

        foreach ($customers as $proposal) {
            $rows = array_merge($rows, $this->pagesOf([
                'id' => (string)$proposal->id,
                'controller' => 'CustomerProposals',
                'label' => $proposal->effective_from . ' - ' . $proposal->purpose->label(),
                // A consent belongs to nobody's contract, so the column stays empty on its rows.
                'contract_id' => null,
                'contract' => '',
                'documents' => $documents,
            ], $filed));
        }

        $contracts = $this->contractProposals($of, $id);
        $filed = (new ContractDocuments())->filedAgainst($contracts);
        $documents = $this->labelled(ContractPrintType::cases());

        foreach ($contracts as $proposal) {
            $rows = array_merge($rows, $this->pagesOf([
                'id' => (string)$proposal->id,
                'controller' => 'ContractProposals',
                'label' => $proposal->effective_from . ' - ' . $proposal->purpose->label(),
                'contract_id' => (string)$proposal->contract_id,
                'contract' => (string)($proposal->contract->number ?? ''),
                'documents' => $documents,
            ], $filed));
        }

        return $rows;
    }

    /**
     * The pages of one round, on the side being looked at.
     *
     * @param array<string, mixed> $round What they hang on.
     * @param array<string, array<string, array<string, list<\Files\Model\Entity\FileLink>>>> $filed What is on file.
     * @return list<array<string, mixed>>
     */
    private function pagesOf(array $round, array $filed): array
    {
        $rows = [];

        foreach ($filed[$round['id']] ?? [] as $document_type => $byVariant) {
            foreach ($byVariant as $variant => $links) {
                $case = DocumentVariant::tryFrom((string)$variant);
                if ($case === null || $case->isDrawnUpByUs() !== $this->ours) {
                    continue;
                }

                foreach ($links as $link) {
                    $rows[] = [
                        'round' => $round,
                        'document' => $round['documents'][$document_type] ?? (string)$document_type,
                        'variant' => $case->label(),
                        'link' => $link,
                        'keys' => [
                            'contract' => (string)($round['contract_id'] ?? ''),
                            'round' => $round['id'],
                            'document' => $round['id'] . '/' . $document_type,
                            'variant' => $round['id'] . '/' . $document_type . '/' . $variant,
                        ],
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * The proposals of contracts in view.
     *
     * @param string $of What is being looked at.
     * @param string $id Which one.
     * @return list<\App\Model\Entity\ContractProposal>
     */
    private function contractProposals(string $of, string $id): array
    {
        if ($of === 'customerProposal' || ($of === 'customer' && !$this->withContracts)) {
            return [];
        }

        /** @var \App\Model\Table\ContractProposalsTable $proposals */
        $proposals = $this->fetchTable(ContractProposalsTable::class);

        $query = $proposals->find()->contain(['Contracts']);
        $query = match ($of) {
            'contractProposal' => $query->where(['ContractProposals.id' => $id]),
            'contract' => $query->where(['ContractProposals.contract_id' => $id]),
            default => $query
                ->where(['Contracts.customer_id' => $id])
                ->orderBy(['Contracts.number' => 'ASC']),
        };

        /** @var list<\App\Model\Entity\ContractProposal> $found */
        $found = $query->orderByDesc('ContractProposals.effective_from')->all()->toList();

        return $found;
    }

    /**
     * The rounds put to a customer in view.
     *
     * @param string $of What is being looked at.
     * @param string $id Which one.
     * @return list<\App\Model\Entity\CustomerProposal>
     */
    private function customerProposals(string $of, string $id): array
    {
        if (!in_array($of, ['customerProposal', 'customer'], true)) {
            return [];
        }

        /** @var \App\Model\Table\CustomerProposalsTable $proposals */
        $proposals = $this->fetchTable(CustomerProposalsTable::class);

        $query = $of === 'customerProposal'
            ? $proposals->find()->where(['CustomerProposals.id' => $id])
            : $proposals->find()->where(['CustomerProposals.customer_id' => $id]);

        /** @var list<\App\Model\Entity\CustomerProposal> $found */
        $found = $query->orderByDesc('CustomerProposals.effective_from')->all()->toList();

        return $found;
    }

    /**
     * A set of document types, by the value they are filed under.
     *
     * @param array<\App\Model\Enum\ContractPrintType|\App\Model\Enum\CustomerPrintType> $cases The types.
     * @return array<string, string>
     */
    private function labelled(array $cases): array
    {
        $labelled = [];

        foreach ($cases as $case) {
            $labelled[$case->value] = $case->label();
        }

        return $labelled;
    }
}
