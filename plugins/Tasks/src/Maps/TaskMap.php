<?php
declare(strict_types=1);

namespace Tasks\Maps;

use App\Model\Entity\Task;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use Cake\View\Helper\HtmlHelper;
use Maps\DrawnMap;
use Maps\Marker;
use Maps\Position;

/**
 * The open tasks drawn where they are to be done.
 *
 * A task is done at the installation address of its contract; a task holding an access point
 * instead is done at the access point. One holding neither cannot be put on a map and is left off,
 * the same way the network map passes over a place without coordinates.
 *
 * The bubbles are written here rather than in the template because a marker carries its own, and
 * one is built out of the task, its customer and its contract at once.
 */
abstract class TaskMap
{
    use LocatorAwareTrait;

    /**
     * What marks a task whose state names no colour of its own.
     */
    private const FALLBACK_COLOR = '#7f7f7f';

    /**
     * A bubble is read while the map is still wanted, so what it points at opens beside it.
     *
     * @var array<string, string>
     */
    private const LINK_OPTIONS = ['target' => '_blank'];

    /**
     * How much of a task's text a bubble shows.
     */
    private const TEXT_LINES = 10;
    private const TEXT_LENGTH = 1000;

    /**
     * @param \Cake\View\Helper\HtmlHelper $html What the bubbles are written with.
     */
    public function __construct(private readonly HtmlHelper $html)
    {
    }

    /**
     * Draws the tasks still waiting to be done.
     *
     * @param string|null $taskTypeId Only tasks of this type, when one is named.
     * @param string|null $taskStateId Only tasks in this state, when one is named.
     * @return \Maps\DrawnMap
     */
    public function draw(?string $taskTypeId = null, ?string $taskStateId = null): DrawnMap
    {
        $markers = [];

        foreach ($this->tasks($taskTypeId, $taskStateId) as $task) {
            $position = $this->positionOf($task);

            if ($position === null) {
                continue;
            }

            $markers[$task->id] = new Marker(
                position: $position,
                title: $this->title($task),
                color: $this->colorOf($task),
                content: $this->bubble($task),
                locked: true,
            );
        }

        return new DrawnMap($markers, []);
    }

    /**
     * The open tasks, most pressing first, with everything a bubble reads.
     *
     * @param string|null $taskTypeId Only tasks of this type, when one is named.
     * @param string|null $taskStateId Only tasks in this state, when one is named.
     * @return iterable<\App\Model\Entity\Task>
     */
    abstract protected function tasks(?string $taskTypeId, ?string $taskStateId): iterable;

    /**
     * Where the task is to be done, or null where it cannot be put on a map at all.
     *
     * @param \App\Model\Entity\Task $task The task being drawn.
     * @return \Maps\Position|null
     */
    abstract protected function positionOf(Task $task): ?Position;

    /**
     * What a task's state says it looks like.
     */
    private function colorOf(Task $task): string
    {
        $color = $task->task_state->color ?? null;

        return is_string($color) && $color !== '' ? $color : self::FALLBACK_COLOR;
    }

    /**
     * What hovering over the marker says.
     */
    private function title(Task $task): string
    {
        return implode(' - ', array_filter([$task->number, $task->subject]));
    }

    /**
     * What the marker's bubble says.
     *
     * The number heads it, the state stands beside it, and the summary says who the task is for
     * and where - the same line the listing carries. The beginning of the text follows, for the
     * ones that carry one.
     */
    private function bubble(Task $task): string
    {
        $heading = '<div class="maps-bubble-heading">'
            . $this->html->link(
                $task->number,
                ['controller' => 'Tasks', 'action' => 'view', $task->id],
                self::LINK_OPTIONS,
            )
            . '<span class="maps-bubble-state">' . h($task->task_state->name ?? null) . '</span>'
            . '</div>';

        $lines = [
            $heading,
            $this->paragraph('maps-bubble-lead', $task->subject ?? $task->task_type->name ?? null),
            $this->paragraph('maps-bubble-meta', $task->getSummaryText(with_subject: false)),
            $this->paragraph('maps-bubble-text', $this->opening($task->text), keepLineBreaks: true),
        ];

        return '<div class="maps-bubble" style="border-left-color: ' . h($this->colorOf($task)) . '">'
            . implode('', array_filter($lines))
            . '</div>';
    }

    /**
     * One part of a bubble, or nothing at all when there is nothing to say.
     *
     * Line breaks are written out as markup rather than left to a white-space rule, so that the
     * map can measure the text on one line and give the bubble the width its longest line asks
     * for - a row of dashes written as a separator, most of all.
     */
    private function paragraph(string $class, ?string $text, bool $keepLineBreaks = false): string
    {
        $text = trim((string)$text);

        if ($text === '') {
            return '';
        }

        $written = h($text);

        return '<p class="' . $class . '">'
            . ($keepLineBreaks ? nl2br($written, false) : $written)
            . '</p>';
    }

    /**
     * As much of a task's text as a bubble can carry.
     *
     * Counted in lines rather than in characters, because these texts are written in lines - a
     * separator, a name, a date - and cutting one in half reads worse than stopping at its end.
     * The length is a backstop for a text written as one very long line.
     */
    private function opening(?string $text): ?string
    {
        $text = trim((string)$text);

        if ($text === '') {
            return null;
        }

        $lines = preg_split('/\R/', $text) ?: [];
        $opening = implode("\n", array_slice($lines, 0, self::TEXT_LINES));
        $shortened = count($lines) > self::TEXT_LINES;

        if (mb_strlen($opening) > self::TEXT_LENGTH) {
            $opening = Text::truncate($opening, self::TEXT_LENGTH, ['ellipsis' => '', 'exact' => false]);
            $shortened = true;
        }

        // Whatever the cut landed on - a blank line, a space between words - the mark that there
        // is more belongs against the text rather than adrift after it.
        $opening = rtrim($opening);

        return $shortened ? $opening . '…' : $opening;
    }
}
