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

use App\Controller\Traits\ErrorFormatterTrait;
use App\Controller\Traits\NestingTrait;
use App\Controller\Traits\RedirectionTrait;
use AuditStash\Meta\RequestMetadata;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\I18n;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Override;

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
    use ErrorFormatterTrait;
    use NestingTrait;
    use RedirectionTrait;

    /**
     * The languages a page may be asked for, as the layout offers them.
     *
     * @var list<string>
     */
    private const LANGUAGES = ['cs_CZ', 'en_US', 'hr_HR'];

    /**
     * The themes a page may be asked for, as the layout offers them.
     *
     * @var list<string>
     */
    private const THEMES = ['default', 'light', 'dark', 'contrast'];

    /**
     * User settings
     *
     * @var array<string, mixed>
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
    #[Override]
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
     * Opens a listing with what was added last, until somebody sorts it by a column.
     *
     * An id is random, so it says nothing about what is new. Records that do not say when they were
     * added go last, or they would fill the first pages.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query The listing.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function newestFirst(SelectQuery $query): SelectQuery
    {
        if ($this->getRequest()->getQuery('sort') !== null) {
            return $query;
        }

        $alias = $query->getRepository()->getAlias();

        return $query->orderBy([$alias . '.created DESC NULLS LAST', $alias . '.id DESC']);
    }

    /**
     * What a listing of the table may be sorted by: its own columns and the number of the customer.
     *
     * @param \Cake\ORM\Table $table The table listed, joined with its customers.
     * @return array<string>
     */
    protected function sortableWithCustomerNumber(Table $table): array
    {
        return [...$table->getSchema()->columns(), 'Customers.nid'];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function paginate(
        RepositoryInterface|QueryInterface|string|null $object = null,
        array $settings = [],
    ): PaginatedInterface {
        try {
            // set maximal limit
            $this->paginate['maxLimit'] = 10000;

            // load limit from configurations
            $this->paginate['limit'] = Configure::read('UI.number_of_rows_per_page');

            return parent::paginate($object, $settings);
        } catch (NotFoundException) {
            $this->Flash->error(__(
                'Unable to find results on page {0}. Redirect to page 1.',
                $this->getRequest()->getQuery('page'),
            ));
            $response = $this->redirect(
                ['?' => ['page' => '1'] + $this->getRequest()->getQueryParams()]
                + $this->getRequest()->getParam('pass'),
            );
            if (!$response instanceof Response) {
                throw new NotFoundException('Unable to prepare redirection response.');
            }

            // Redirect if not called from CLI
            if (PHP_SAPI === 'cli') {
                throw new NotFoundException('Redirect to ' . $response->getHeaderLine('Location') . ' for non-CLI.');
            }
            header('Location: ' . $response->getHeaderLine('Location'), true, 303);

            exit;
        }
    }

    /**
     * Global beforeFilter
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @return void
     * @link https://book.cakephp.org/5/en/controllers.html#request-life-cycle-callbacks
     */
    #[Override]
    public function beforeFilter(EventInterface $event): void
    {
        # Load current user
        /** @var \Authorization\Identity|null $identity */
        $identity = $this->getRequest()->getAttribute('identity');

        # Load user settings
        // The column is filled by the settings form and holds nothing until somebody opens it,
        // so anything that is not a set of settings is read as none.
        $settings = $identity['user_settings'] ?? [];
        $this->user_settings = is_array($settings) ? $settings : [];

        # Load additional parameters
        $this->loadNesting();

        # Determine if we want to set the language
        if (in_array($this->getRequest()->getQuery('language'), self::LANGUAGES, true)) {
            $this->getRequest()->getSession()->write(
                'Config.UI.language',
                $this->getRequest()->getQuery('language'),
            );
        }

        # Set language configurations
        Configure::write(
            'UI.language',
            $this->getRequest()->getSession()->read(
                'Config.UI.language',
                $this->user_settings['language'] ?? I18n::getDefaultLocale(),
            ),
        );

        # Language settings in i18n locale
        if (Configure::read('UI.language')) {
            I18n::setLocale(Configure::read('UI.language'));
        }

        # Determine if we want to set a theme
        if (in_array($this->getRequest()->getQuery('theme'), self::THEMES, true)) {
            $this->getRequest()->getSession()->write(
                'Config.UI.theme',
                $this->getRequest()->getQuery('theme'),
            );
        }

        # Set theme configurations
        Configure::write(
            'UI.theme',
            $this->getRequest()->getSession()->read(
                'Config.UI.theme',
                ($this->user_settings['theme'] ?? 'default'),
            ),
        );

        # Determine if we want to set a pagination limit
        if (is_numeric($this->getRequest()->getQuery('limit'))) {
            $this->getRequest()->getSession()->write(
                'Config.UI.number_of_rows_per_page',
                (int)$this->getRequest()->getQuery('limit'),
            );
        }

        # Set pagination limit configurations
        Configure::write(
            'UI.number_of_rows_per_page',
            $this->getRequest()->getSession()->read(
                'Config.UI.number_of_rows_per_page',
                (int)($this->user_settings['number_of_rows_per_page'] ?? 20),
            ),
        );

        // Persisting audit log - current user
        /** @psalm-suppress RedundantCondition */
        if ($identity != null) {
            EventManager::instance()->on(
                new RequestMetadata(
                    request: $this->getRequest(),
                    userId: $identity['id'] ? (string)$identity['id'] : null,
                    userDisplay: $identity['username'] ? (string)$identity['username'] : null,
                ),
            );
        }

        parent::beforeFilter($event);

        # Answer a record where it belongs, when the address it was asked for at does not say so
        $redirect = $this->redirectToWhereTheRecordBelongs();
        if ($redirect !== null) {
            // the result of `Controller.initialize` is what stands in for the action, and returning
            // it from a listener has been deprecated since 5.2
            $event->setResult($redirect);
        }
    }

    /**
     * Whether whoever is asking is an administrator or a manager, who are offered the boxes that
     * override what the others are held to.
     *
     * @return bool
     */
    protected function mayOverride(): bool
    {
        return in_array($this->getRequest()->getAttribute('identity')['role'] ?? null, ['admin', 'manager'], true);
    }

    /**
     * Whether this request may be offered to price a connection below the contract's minimum, and
     * even then it is a box that has to be ticked.
     *
     * @return bool
     */
    protected function mayGoBelowMinimum(): bool
    {
        return $this->mayOverride();
    }

    /**
     * Generate actual URL with added query paramaters
     *
     * @param array <string, mixed> $query Query parameters to be added to the URL
     * @return string
     * @psalm-suppress PossiblyUnusedMethod
     */
    protected function urlWithQuery(array $query = []): string
    {
        $request = $this->getRequest();

        return Router::url(
            ['?' => $query + $request->getQueryParams()] + $request->getParam('pass'),
        );
    }
}
