<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use Cake\Utility\Hash;
use Cake\Utility\Text;

trait ErrorFormatterTrait
{
    /**
     * Get text with Validation errors
     *
     * @param array $validationErrors The Validation Errors from an entity
     * @return string
     */
    public function formatValidationErrors(array $validationErrors = []): string
    {
        // get Validation errors and append them into a string
        $flattened = array_values(Hash::flatten($validationErrors));

        return Text::toList($flattened, '<br>' . PHP_EOL, '<br>' . PHP_EOL);
    }

    /**
     * Generate Flash message with Validation errors
     *
     * @param array $validationErrors The Validation Errors from an entity
     * @return void
     */
    public function flashValidationErrors(array $validationErrors = []): void
    {
        // Generate Flash message with Validation errors
        $this->Flash->error(
            '<strong>' . __('Errors') . ':</strong><br>' . PHP_EOL . $this->formatValidationErrors($validationErrors),
            [
                'escape' => false,
            ]
        );
    }
}
