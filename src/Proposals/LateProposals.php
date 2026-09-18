<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\DocumentVariant;
use App\Service\ContractPrint\ContractDocuments;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use InvalidArgumentException;

/**
 * What makes a proposal's paperwork late, whichever agenda it belongs to.
 *
 * A paper is drawn up, sent, signed and filed the same way round whether it is about a contract or
 * about the customer, so the three things that can be late are written once. Only the conditions
 * are here: what may be reported at all, how it narrows to one record and how long the wait is
 * belong to the asking check.
 */
final class LateProposals
{
    /**
     * Which tables these conditions may be asked of. The name reaches the SQL as written, so it
     * is checked rather than trusted.
     *
     * @var list<string>
     */
    private const AGENDAS = ['ContractProposals', 'CustomerProposals'];

    /**
     * Drawn up and never sent, with the day it speaks about close enough to matter.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query What is being asked.
     * @param string $alias Which agenda's table.
     * @param int $within How many days ahead to look.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public static function neverSent(
        SelectQuery $query,
        string $alias,
        int $within,
        ?string $dates = null,
    ): SelectQuery {
        self::mustBeAnAgenda($alias);
        $dates = self::whereTheDaysAre($alias, $dates);

        return $query
            ->where([
                $dates . '.sent_date IS' => null,
                // A proposal for the spring is work in hand, not a fault.
                $alias . '.effective_from <=' => Date::today()->addDays(max(0, $within)),
            ])
            ->orderBy([$alias . '.effective_from' => 'ASC']);
    }

    /**
     * Sent, and nothing has come back.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query What is being asked.
     * @param string $alias Which agenda's table.
     * @param int $after How long the papers may be out first.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public static function unanswered(
        SelectQuery $query,
        string $alias,
        int $after,
        ?string $dates = null,
    ): SelectQuery {
        self::mustBeAnAgenda($alias);
        $dates = self::whereTheDaysAre($alias, $dates);

        return $query
            ->where([
                $dates . '.sent_date IS NOT' => null,
                $dates . '.conclusion_date IS' => null,
                $dates . '.sent_date <=' => Date::today()->subDays(max(0, $after)),
            ])
            // Longest out first: that is the one somebody should be ringing about.
            ->orderBy([$dates . '.sent_date' => 'ASC']);
    }

    /**
     * Signed, and the signed copy has not been filed.
     *
     * The gap between a day somebody typed in and a scan that has not arrived, rather than a fault
     * in either.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query What is being asked.
     * @param string $alias Which agenda's table.
     * @param string $model What the papers are filed against.
     * @param int $after How long after the signature it is worth asking.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public static function unfiled(
        SelectQuery $query,
        string $alias,
        string $model,
        int $after,
        ?string $dates = null,
    ): SelectQuery {
        self::mustBeAnAgenda($alias);
        $dates = self::whereTheDaysAre($alias, $dates);

        return $query
            ->where([
                $dates . '.conclusion_date IS NOT' => null,
                $alias . '.revoked IS' => null,
                $dates . '.conclusion_date <=' => Date::today()->subDays(max(0, $after)),
            ])
            ->where(function ($exp, SelectQuery $q) use ($alias, $model) {
                // A signed copy of the agreement, or a paper that is the customer's answer by
                // itself. A round put to the customer has one paper, so any signed copy is it.
                $answered = ['FiledCheck.variant IN' => self::signedByTheCustomer()];

                if ($model === ContractDocuments::MODEL) {
                    $answered = ['OR' => [
                        $answered + ['FiledCheck.document_type IN' => self::contractTypes(
                            fn(ContractDocumentType $type): bool => $type->isTheAgreement(),
                        )],
                        ['FiledCheck.document_type IN' => self::contractTypes(
                            fn(ContractDocumentType $type): bool => $type->speaksForItself(),
                        )],
                    ]];
                }

                $filed = $q->getConnection()->selectQuery()
                    ->select(['1'])
                    ->from(['FiledCheck' => 'file_links'])
                    ->where(['FiledCheck.model' => $model])
                    ->where($answered);

                $filed->where($filed->expr()->equalFields('FiledCheck.foreign_key', $alias . '.id'));

                return $exp->notExists($filed);
            })
            ->orderBy([$dates . '.conclusion_date' => 'ASC']);
    }

    /**
     * The variants that count as the customer's signature having come back.
     *
     * @return list<string>
     */
    private static function signedByTheCustomer(): array
    {
        $variants = [];

        foreach (DocumentVariant::cases() as $case) {
            if ($case->carriesTheCustomersSignature()) {
                $variants[] = $case->value;
            }
        }

        return $variants;
    }

    /**
     * The papers of a contract that answer the question asked, by the name they are filed under.
     *
     * @param callable(\App\Model\Enum\ContractDocumentType): bool $asked The question.
     * @return list<string>
     */
    private static function contractTypes(callable $asked): array
    {
        $types = [];

        foreach (ContractDocumentType::cases() as $case) {
            if ($asked($case)) {
                $types[] = $case->value;
            }
        }

        return $types;
    }

    /**
     * Which table the days of the sending are read from.
     *
     * Papers go out in an envelope and come back in one, so on the contract's side the days belong
     * to the round they went in rather than to the papers themselves - the caller joins it and
     * says so. A round put to the customer is its own envelope.
     *
     * @param string $alias The agenda being asked about.
     * @param string|null $dates Where its days are kept, when that is somewhere else.
     * @return string
     */
    private static function whereTheDaysAre(string $alias, ?string $dates): string
    {
        if ($dates === null) {
            return $alias;
        }

        self::mustBeAnAgenda($dates);

        return $dates;
    }

    /**
     * @param string $alias The table name being asked for.
     * @return void
     * @throws \InvalidArgumentException When it is not one of the agendas.
     */
    private static function mustBeAnAgenda(string $alias): void
    {
        if (!in_array($alias, self::AGENDAS, true)) {
            throw new InvalidArgumentException(sprintf('`%s` is not an agenda that has proposals.', $alias));
        }
    }
}
