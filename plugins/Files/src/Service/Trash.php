<?php
declare(strict_types=1);

namespace Files\Service;

use App\Model\Audit\AuditTrail;
use Cake\Core\Configure;
use Cake\Database\Exception\QueryException;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Entity\RemovedFileLink;
use Files\Model\Table\FileLinksTable;
use Files\Model\Table\RemovedFileLinksTable;
use RuntimeException;
use Throwable;

/**
 * What was removed, until it comes back or its time is up.
 *
 * Everything that leaves a record passes through here, so a mistake can be undone wherever it was
 * made. What went in one act comes back in one act: {@see \Files\Service\FileStorage::unlink()}
 * hands such rows the same moment, and that moment is all that ties them together.
 */
class Trash
{
    use LocatorAwareTrait;

    /**
     * What PostgreSQL says when a row breaks a unique index or a foreign key.
     */
    private const UNIQUE_VIOLATION = 23505;
    private const FOREIGN_KEY_VIOLATION = 23503;

    /**
     * Where the content is kept.
     */
    private FileStorage $storage;

    /**
     * @param \Files\Service\FileStorage|null $storage Where the content is kept.
     */
    public function __construct(?FileStorage $storage = null)
    {
        $this->storage = $storage ?? new FileStorage();
    }

    /**
     * Why it cannot come back, or null where it can.
     *
     * The page asks this before it offers the button, and restoring asks again.
     *
     * @param \Files\Model\Entity\RemovedFileLink $link What was removed.
     * @return string|null
     */
    public function whatStopsIt(RemovedFileLink $link): ?string
    {
        if ($this->placeIsTaken($this->sameAct($link))) {
            return $this->placeTaken();
        }

        // A folder that went with its contents is put back along with them.
        if ($link->documentation === null && !$this->recordExists($link)) {
            return $this->recordGone();
        }

        return null;
    }

    /**
     * Puts it back, along with whatever went in the same act.
     *
     * Pages go to the end of their group, as the place they left may have been filled since. The
     * rows come back as they were, including who filed them.
     *
     * @param \Files\Model\Entity\RemovedFileLink $link What was removed.
     * @return int How many came back.
     * @throws \RuntimeException When it cannot come back.
     */
    public function restore(RemovedFileLink $link): int
    {
        $stops = $this->whatStopsIt($link);
        if ($stops !== null) {
            throw new RuntimeException($stops);
        }

        $group = $this->sameAct($link);
        $links = $this->fileLinks();
        $removed = $this->removedFileLinks();
        $trail = new AuditTrail();

        try {
            $links->getConnection()->transactional(function () use ($link, $group, $links, $removed, $trail): void {
                $this->bringBackTheFolder($link, $trail);

                foreach ($group as $one) {
                    $restored = $links->newEmptyEntity();
                    $restored->patch($one->extract($links->getSchema()->columns()), ['guard' => false]);
                    $restored->set('position', $this->storage->nextPosition(
                        $one->model,
                        $one->foreign_key,
                        $one->document_type,
                        $one->variant,
                    ));

                    $links->saveOrFail($restored, $trail->options() + ['keepFootprint' => true]);
                    $removed->deleteOrFail($one, $trail->options());
                }
            });
        } catch (QueryException $e) {
            throw new RuntimeException($this->refusalFor($e), 0, $e);
        }

        $trail->flush($links, $link);

        return count($group);
    }

    /**
     * Throws it away for good, and the content with it unless something else still wants that.
     *
     * @param \Files\Model\Entity\RemovedFileLink $link What was removed.
     * @return bool Whether the content went as well.
     */
    public function delete(RemovedFileLink $link): bool
    {
        $this->removedFileLinks()->deleteOrFail($link);

        return $this->storage->letGoOf($link->file_id);
    }

    /**
     * Throws away everything that has been in the trash for longer than this.
     *
     * One that cannot be thrown away does not stop the rest.
     *
     * @param int $days How long it is kept.
     * @return array<string, mixed>
     * @phpstan-return array{deleted: int, failed: int}
     */
    public function purge(int $days): array
    {
        $removed = $this->removedFileLinks();
        $deleted = 0;
        $failed = 0;

        /** @var iterable<\Files\Model\Entity\RemovedFileLink> $expired */
        $expired = $removed->find()
            ->where([$removed->aliasField('removed') . ' <' => DateTime::now()->subDays($days)])
            ->all();

        foreach ($expired as $link) {
            try {
                $this->delete($link);
                $deleted++;
            } catch (Throwable $e) {
                Log::error(sprintf('The trash could not let go of %s: %s', $link->id, $e->getMessage()));
                $failed++;
            }
        }

        return ['deleted' => $deleted, 'failed' => $failed];
    }

    /**
     * Whatever went in the same act as this one and belongs to the same document.
     *
     * @param \Files\Model\Entity\RemovedFileLink $link What was removed.
     * @return list<\Files\Model\Entity\RemovedFileLink>
     */
    private function sameAct(RemovedFileLink $link): array
    {
        $removed = $this->removedFileLinks();

        /** @var list<\Files\Model\Entity\RemovedFileLink> $found */
        $found = $removed->find()
            ->where([
                $removed->aliasField('model') => $link->model,
                $removed->aliasField('foreign_key') => $link->foreign_key,
                $removed->aliasField('document_type') => $link->document_type,
                $removed->aliasField('removed') => $link->removed,
            ])
            ->orderBy([$removed->aliasField('position') => 'ASC'])
            ->all()
            ->toList();

        return $found;
    }

    /**
     * Whether a document of the kind there can only be one of has been made again since.
     *
     * @param list<\Files\Model\Entity\RemovedFileLink> $group What would come back.
     * @return bool
     */
    private function placeIsTaken(array $group): bool
    {
        $variants = array_values(array_intersect(
            array_map(fn(RemovedFileLink $one): string => $one->variant, $group),
            FileLinksTable::ONE_PER_DOCUMENT,
        ));

        if ($variants === []) {
            return false;
        }

        $links = $this->fileLinks();

        return $links->exists([
            $links->aliasField('model') => $group[0]->model,
            $links->aliasField('foreign_key') => $group[0]->foreign_key,
            $links->aliasField('document_type') => $group[0]->document_type,
            $links->aliasField('variant') . ' IN' => $variants,
        ]);
    }

    /**
     * Whether the record it hung on is still there.
     *
     * Only what the application declared can be asked about, the plugin knows no other table.
     *
     * @param \Files\Model\Entity\RemovedFileLink $link What was removed.
     * @return bool
     */
    private function recordExists(RemovedFileLink $link): bool
    {
        $declared = Configure::read('Files.records');

        if (!is_array($declared) || !isset($declared[$link->model])) {
            return false;
        }

        return $this->fetchTable($link->model)->exists(['id' => $link->foreign_key]);
    }

    /**
     * Founds the folder again where it went with its contents.
     *
     * Not validated: it was accepted once, and what a kind of folder asks for may have changed.
     *
     * @param \Files\Model\Entity\RemovedFileLink $link What was removed.
     * @param \App\Model\Audit\AuditTrail $trail The act this is part of.
     * @return void
     */
    private function bringBackTheFolder(RemovedFileLink $link, AuditTrail $trail): void
    {
        if ($link->documentation === null) {
            return;
        }

        $folders = $this->fetchTable(Documentations::MODEL);

        if ($folders->exists(['id' => $link->foreign_key])) {
            return;
        }

        $folder = $folders->newEmptyEntity();
        $folder->patch($link->documentation, ['guard' => false]);

        $folders->saveOrFail($folder, $trail->options() + ['checkRules' => false, 'keepFootprint' => true]);
    }

    /**
     * What to say when the database refused what the checks let through.
     *
     * The place may have been taken since the question was asked, and a folder coming back may
     * find its kind or the record it hung on gone.
     *
     * @param \Cake\Database\Exception\QueryException $e What the database said.
     * @return string
     * @throws \Cake\Database\Exception\QueryException When it was about something else.
     */
    private function refusalFor(QueryException $e): string
    {
        return match ($e->getCode()) {
            self::UNIQUE_VIOLATION => $this->placeTaken(),
            self::FOREIGN_KEY_VIOLATION => $this->recordGone(),
            default => throw $e,
        };
    }

    /**
     * @return string
     */
    private function placeTaken(): string
    {
        return __d('files', 'This document has been made again since, so its place is taken.');
    }

    /**
     * @return string
     */
    private function recordGone(): string
    {
        return __d('files', 'The record it belonged to no longer exists.');
    }

    /**
     * @return \Files\Model\Table\FileLinksTable
     */
    private function fileLinks(): FileLinksTable
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        return $links;
    }

    /**
     * @return \Files\Model\Table\RemovedFileLinksTable
     */
    private function removedFileLinks(): RemovedFileLinksTable
    {
        /** @var \Files\Model\Table\RemovedFileLinksTable $removed */
        $removed = $this->fetchTable(RemovedFileLinksTable::class);

        return $removed;
    }
}
