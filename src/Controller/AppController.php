<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use AuditLog\Meta\RequestMetadata;
use Cake\Cache\Cache;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\I18n;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/4/en/controllers/components/form-protection.html
         */
        $this->loadComponent('FormProtection');

        // switch to Legacy UI layout if requested
        if ($this->getRequest()->getParam('ui-mode') === 'legacy') {
            $this->viewBuilder()->setLayout('legacy');
        }
    }

    # App > paginate

    /**
     * Customize pagination
     *
     * @param \Cake\ORM\Table|string|\Cake\ORM\Query|null $object Table to paginate
     * (e.g: Table instance, 'TableName' or a Query object)
     * @param array $settings The settings/configuration used for pagination.
     * @return \Cake\ORM\ResultSet|\Cake\Datasource\ResultSetInterface|null Query results
     */
    public function paginate($object = null, $settings = [])
    {
        try {
            // set maximal limit
            $this->paginate['maxLimit'] = 10000;

            // load limit from session
            $this->paginate['limit'] = $this->getRequest()->getSession()->read('Config.limit', 20);

            return parent::paginate($object, $settings);
        } catch (NotFoundException $e) {
            $this->Flash->error(__(
                'Unable to find results on page {0}. Redirect to page 1.',
                $this->getRequest()->getQuery('page')
            ));
            $this->redirect(
                ['?' => ['page' => '1'] + $this->getRequest()->getQueryParams()]
                + $this->getRequest()->getParam('pass')
            );

            return null;
        }
    }

    /**
     * Global beforeFilter
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(EventInterface $event)
    {
        # We check if we have a language set
        if ($this->getRequest()->getQuery('language')) {
            $this->getRequest()->getSession()->write('Config.language', $this->getRequest()->getQuery('language'));
        }

        $language = $this->getRequest()->getSession()->read('Config.language', I18n::getDefaultLocale());

        if ($language) {
            I18n::setLocale($language);
        }

        # We check if we have a high contrast set
        if (is_numeric($this->getRequest()->getQuery('high-contrast'))) {
            $this->getRequest()->getSession()->write(
                'Config.high-contrast',
                (int)$this->getRequest()->getQuery('high-contrast') == 1
            );
        }

        # We check if we have a paginate limit set
        if (is_numeric($this->getRequest()->getQuery('limit'))) {
            $this->getRequest()->getSession()->write(
                'Config.limit',
                (int)$this->getRequest()->getQuery('limit')
            );
        }

        # Disable SecurityComponent POST validation for CakeDC/Users
        if ($this->getRequest()->getParam('plugin') === 'CakeDC/Users') {
            $this->Security->setConfig('validatePost', false);
        }

        // Persisting audit log - current user
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity != null) {
            EventManager::instance()->on(
                new RequestMetadata($this->getRequest(), $identity['username'])
            );
        }

        parent::beforeFilter($event);
    }

    /**
     * Normalize non-ASCII characters to ASCII counterparts where possible.
     *
     * @param string $str Text with non-ASCII characters
     * @return string
     */
    public function removeAccents($str): string
    {
        static $normalizeChars = null;
        if ($normalizeChars === null) {
            $normalizeChars = [
                'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'Ae',
                'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
                'Č' => 'C', 'Ç' => 'C',
                'č' => 'c', 'ç' => 'c',
                'Ď' => 'D', 'Ð' => 'Dj',
                'ď' => 'd',
                'Ě' => 'E', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
                'ě' => 'e', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
                'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
                'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                'Ň' => 'N', 'Ñ' => 'N',
                'ň' => 'n', 'ñ' => 'n',
                'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
                'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ð' => 'o',
                'Ř' => 'R',
                'ř' => 'r',
                'Š' => 'S', 'Ś' => 'S',
                'š' => 's', 'ś' => 's',
                'Ť' => 'T',
                'ť' => 't',
                'Ů' => 'U', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
                'ů' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
                'Ý' => 'Y', 'Ÿ' => 'Y',
                'ý' => 'y', 'ÿ' => 'y',
                'Ž' => 'Z',
                'ž' => 'z',
                'Þ' => 'B',
                'ß' => 'Ss',
                'þ' => 'b',
                'ƒ' => 'f',
            ];
        }

        return strtr($str, $normalizeChars);
    }

    /**
     * Generate password.
     *
     * @param int $length Length of new password
     * @param string $possible Available chars for password
     * @return string
     */
    public function generatePassword(
        $length = 8,
        $possible = '123456789ABCDEFGHJKLMNPQRSTUVWXabcdefghjkmnopqrstuvwx'
    ): string {
        // start with a blank password
        $password = '';

        // set up a counter
        $i = 0;

        // add random characters to $password until $length is reached
        while ($i < $length) {
            // pick a random character from the possible ones
            $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
            // we don't want this character if it's already in the password
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }
        // done!
        return $password;
    }

    /**
     * Check if Git binary is callable.
     *
     * @return bool
     */
    public static function gitBinaryCallable(): bool
    {
        exec('git > /dev/null 2>&1', $response, $exit_code);

        return $exit_code === 1;
    }

    /**
     * Check if Git repository is present.
     *
     * @return bool
     */
    public static function gitRepositoryPresent(): bool
    {
        return file_exists(dirname(__DIR__, 2) . '/.git');
    }

    /**
     * Get application version if installed from Git.
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return Cache::remember(
            'app_version',
            function () {
                if (AppController::gitRepositoryPresent() && AppController::gitBinaryCallable()) {
                    $version = shell_exec('git describe --tags 2>/dev/null');
                    if (is_string($version)) {
                        return rtrim($version);
                    }
                }

                return 'Not installed via Git';
            }
        );
    }

    /**
     * Get application changelog if installed from Git.
     *
     * @return string
     */
    public static function getChangelog(): string
    {
        return Cache::remember(
            'app_changelog',
            function () {
                if (AppController::gitRepositoryPresent() && AppController::gitBinaryCallable()) {
                    $changelog = shell_exec('git log -10 --decorate=short');
                    if (is_string($changelog)) {
                            return rtrim($changelog);
                    }
                }

                return 'Not installed via Git';
            }
        );
    }
}
