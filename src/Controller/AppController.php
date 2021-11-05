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

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
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
            $this->paginate['maxLimit'] = 1000;

            return parent::paginate($object, $settings);
        } catch (NotFoundException $e) {
            $this->Flash->error(__(
                'Unable to find results on page {0}. Redirect to page 1.',
                $this->request->getQuery('page')
            ));
            $this->redirect(
                ['?' => ['page' => '1'] + $this->request->getQueryParams()]
                + $this->request->getParam('pass')
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
        if ($this->request->getQuery('language')) {
            $this->request->getSession()->write('Config.language', $this->request->getQuery('language'));
        }

        $language = $this->request->getSession()->read('Config.language', I18n::getDefaultLocale());

        if ($language) {
            I18n::setLocale($language);
        }

        # Disable SecurityComponent POST validation for CakeDC/Users
        if ($this->request->getParam('plugin') === 'CakeDC/Users') {
            $this->Security->setConfig('validatePost', false);
        }

        parent::beforeFilter($event);
    }

    /**
     * Normalize non-ASCII characters to ASCII counterparts where possible.
     *
     * @param string $str Text with non-ASCII characters
     * @return string
     */
    public function squashCharacters($str): string
    {
        static $normalizeChars = null;
        if ($normalizeChars === null) {
            $normalizeChars = [
                'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'Ae',
                'Č' => 'C', 'Ç' => 'C',
                'Ě' => 'E', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
                'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
                'Ð' => 'Dj',
                'Ñ' => 'N',
                'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
                'Ř' => 'R',
                'Ů' => 'U', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
                'Ý' => 'Y',
                'Þ' => 'B',
                'ß' => 'Ss',
                'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
                'č' => 'c','ç' => 'c',
                'ě' => 'e', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
                'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                'ð' => 'o', 'ň' => 'n', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
                'ů' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u',
                'ý' => 'y',
                'þ' => 'b',
                'ÿ' => 'y',
                'Š' => 'S', 'š' => 's', 'ś' => 's',
                'Ž' => 'Z', 'ž' => 'z',
                'ƒ' => 'f',
            ];
        }

        return strtr($str, $normalizeChars);
    }

    /**
     * Generate password.
     *
     * @param int $length
     * @return string
     */
    public function generatePassword($length = 8, $possible = '123456789ABCDEFGHJKLMNPQRSTUVWXabcdefghjkmnopqrstuvwx'): string
    {
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
}
