<?php
declare(strict_types=1);

namespace Tasks\Model\Entity;

use App\Colors\ColorThemeSelector;
use App\Model\Entity\AppEntity;
use Cake\Core\Configure;

/**
 * What a task is, in both applications.
 *
 * What a task hangs on - a customer, a contract, an access point - is left to each application,
 * along with the one line that reads it out. Everything here is about the task itself.
 *
 * @property string $id
 * @property int $nid
 * @property string $task_state_id
 * @property string $task_type_id
 * @property string|null $subject
 * @property string|null $text
 * @property int $priority
 * @property string|null $user_id
 * @property string|null $email
 * @property string|null $phone
 * @property \Cake\I18n\Date|null $start_date
 * @property \Cake\I18n\Date|null $finish_date
 * @property \Cake\I18n\Date|null $estimated_date
 * @property \Cake\I18n\Date|null $critical_date
 * @property string $number
 * @property string $summary_text
 * @property string $collaborator_names
 * @property string $style
 *
 * @property \App\Model\Entity\TaskState $task_state
 * @property \App\Model\Entity\TaskType $task_type
 * @property \App\Model\Entity\AppUser|null $user
 * @property array<\App\Model\Entity\AppUser> $collaborators
 */
class Task extends AppEntity
{
    /**
     * The priorities a task is offered, ordered from the least to the most pressing.
     */
    public const PRIORITY_LOW = -10;
    public const PRIORITY_NORMAL = 0;
    public const PRIORITY_HIGH = 10;
    public const PRIORITY_URGENT = 50;

    /**
     * getter for task number
     *
     * @return string
     */
    protected function _getNumber(): string
    {
        return strval($this->nid);
    }

    /**
     * getter for summary text
     *
     * @return string
     */
    protected function _getSummaryText(): string
    {
        return $this->getSummaryText();
    }

    /**
     * The one line that says what a task is about.
     *
     * What there is to say differs between the applications, so each writes its own. Whoever
     * already shows the subject - a listing that has it as its heading, say - asks for it to be
     * left out rather than reading it twice.
     *
     * @param bool $with_subject Whether the subject heads the line.
     * @return string
     */
    public function getSummaryText(bool $with_subject = true): string
    {
        return $with_subject ? $this->subject ?? $this->task_type->name ?? '' : '';
    }

    /**
     * The people on a task beside whoever holds it, named in one line.
     *
     * Read out wherever a task is drawn - its own page, the listings it turns up in, the emails
     * about it - so the line is written once. Empty where nobody is on it, and equally where the
     * list was never read together with the task.
     *
     * @return string
     */
    protected function _getCollaboratorNames(): string
    {
        $names = [];

        foreach ($this->get('collaborators') ?? [] as $person) {
            $names[] = (string)$person->get('name');
        }

        return implode(', ', $names);
    }

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        if (!isset($this->task_state->color)) {
            // no dynamic style
            return '';
        }

        $theme = Configure::read('UI.theme');
        $theme = is_string($theme) ? $theme : null;

        $backgroundColor = ColorThemeSelector::forTheme(
            $this->task_state->color,
            $theme,
        );

        return 'background-color: ' . $backgroundColor . ';';
    }

    /**
     * Get task priority options method
     *
     * @return array<int, string>
     */
    public function getPriorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => __d('tasks', 'Low'),
            self::PRIORITY_NORMAL => __d('tasks', 'Normal'),
            self::PRIORITY_HIGH => __d('tasks', 'High'),
            self::PRIORITY_URGENT => __d('tasks', 'Urgent'),
        ];
    }

    /**
     * Get task priority name method
     *
     * @return string
     */
    public function getPriorityName(): string
    {
        return $this->getPriorityOptions()[$this->priority] ?? (string)$this->priority;
    }
}
