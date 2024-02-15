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

use App\Controller\Traits\AdditionalParametersTrait;
use App\Controller\Traits\ErrorFormatterTrait;
use App\Controller\Traits\RedirectionTrait;
use AuditLog\Meta\RequestMetadata;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\I18n;
use Cake\Routing\Router;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    use AdditionalParametersTrait;
    use ErrorFormatterTrait;
    use RedirectionTrait;

    /*
     * User settings
     */
    protected array $user_settings = [];

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

        $this->loadComponent('Flash');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        $this->loadComponent('FormProtection');
    }

    /**
     * @inheritDoc
     */
    public function paginate(
        RepositoryInterface|QueryInterface|string|null $object = null,
        array $settings = []
    ): PaginatedInterface {
        try {
            // set maximal limit
            $this->paginate['maxLimit'] = 10000;

            // load limit from configurations
            $this->paginate['limit'] = Configure::read('UI.number_of_rows_per_page');

            return parent::paginate($object, $settings);
        } catch (NotFoundException $e) {
            $this->Flash->error(__(
                'Unable to find results on page {0}. Redirect to page 1.',
                $this->getRequest()->getQuery('page')
            ));
            $response = $this->redirect(
                ['?' => ['page' => '1'] + $this->getRequest()->getQueryParams()]
                + $this->getRequest()->getParam('pass')
            );

            // Redirect if not called from CLI
            if (PHP_SAPI === 'cli') {
                throw new NotFoundException('Redirect to ' . $response->getHeaderLine('Location') . ' for non-CLI.');
            } else {
                header('Location: ' . $response->getHeaderLine('Location'), true, 303);
            }

            exit;
        }
    }

    /**
     * Global beforeFilter
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @return \Cake\Http\Response|null|void
     * @link https://book.cakephp.org/5/en/controllers.html#request-life-cycle-callbacks
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function beforeFilter(EventInterface $event)
    {
        # Load current user
        /** @var \Authorization\Identity|null $identity */
        $identity = $this->getRequest()->getAttribute('identity');

        # Load user settings
        $this->user_settings = $identity['user_settings'] ?? [];

        # Load additional parameters
        $this->loadAdditionalParameters();

        # Determine if we want to set the language
        if ($this->getRequest()->getQuery('language')) {
            $this->getRequest()->getSession()->write(
                'Config.UI.language',
                $this->getRequest()->getQuery('language')
            );
        }

        # Set language configurations
        Configure::write(
            'UI.language',
            $this->getRequest()->getSession()->read(
                'Config.UI.language',
                $this->user_settings['language'] ?? I18n::getDefaultLocale()
            )
        );

        # Language settings in i18n locale
        if (Configure::read('UI.language')) {
            I18n::setLocale(Configure::read('UI.language'));
        }

        # Determine if we want to set a theme
        if ($this->getRequest()->getQuery('theme')) {
            $this->getRequest()->getSession()->write(
                'Config.UI.theme',
                $this->getRequest()->getQuery('theme')
            );
        }

        # Set theme configurations
        Configure::write(
            'UI.theme',
            $this->getRequest()->getSession()->read(
                'Config.UI.theme',
                ($this->user_settings['theme'] ?? 'default')
            )
        );

        # Determine if we want to set a pagination limit
        if (is_numeric($this->getRequest()->getQuery('limit'))) {
            $this->getRequest()->getSession()->write(
                'Config.UI.number_of_rows_per_page',
                (int)$this->getRequest()->getQuery('limit')
            );
        }

        # Set pagination limit configurations
        Configure::write(
            'UI.number_of_rows_per_page',
            $this->getRequest()->getSession()->read(
                'Config.UI.number_of_rows_per_page',
                (int)($this->user_settings['number_of_rows_per_page'] ?? 20)
            )
        );

        // Persisting audit log - current user
        if ($identity != null) {
            EventManager::instance()->on(
                new RequestMetadata($this->getRequest(), $identity['username'])
            );
        }

        parent::beforeFilter($event);
    }

    /**
     * Generate actual URL with added query paramaters
     *
     * @param array $query Query parameters to be added to the URL
     * @return string
     */
    public function urlWithQuery(array $query = []): string
    {
        $request = $this->getRequest();

        return Router::url(
            ['?' => $query + $request->getQueryParams()] + $request->getParam('pass')
        );
    }
}
