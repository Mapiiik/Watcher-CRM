<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Controller;

use Access\Authentication\LoginThrottle;
use Access\Controller\UsersController;
use Access\Test\TestCase\AccountsTrait;
use Cake\Cache\Cache;
use Cake\I18n\DateTime;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestEmailTransport;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * A new password through a link sent by e-mail.
 */
#[UsesClass(UsersController::class)]
class PasswordResetTest extends TestCase
{
    use AccountsTrait;
    use EmailTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear(LoginThrottle::CACHE);
        Log::drop('auth');
        Log::setConfig('auth', ['className' => ArrayLog::class, 'scopes' => ['auth']]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        Log::drop('auth');
        Cache::clear(LoginThrottle::CACHE);

        parent::tearDown();
    }

    /**
     * The link sets a new password once, and only its hash is kept.
     *
     * @return void
     */
    public function testTheLinkSetsANewPasswordOnce(): void
    {
        $id = (string)$this->addAccount()->get('id');

        $url = $this->askForLink('jnovak');
        $this->assertMailSentTo('jan.novak@example.com');
        $token = substr($url, strrpos($url, '/') + 1);
        $stored = $this->getTableLocator()->get('Users')->get($id);
        $this->assertSame(hash('sha256', $token), $stored->get('token'));

        $this->get($url);
        $this->assertResponseOk();
        $this->assertResponseContains('name="password_confirm"');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post($url, ['password' => 'Celer v zahradce 8y', 'password_confirm' => 'Celer v zahradce 8y']);
        $this->assertRedirect('/login');
        $stored = $this->getTableLocator()->get('Users')->get($id, finder: 'withSecrets');
        $this->assertTrue(password_verify('Celer v zahradce 8y', (string)$stored->get('password')));
        $this->assertNull($stored->get('token'));

        $this->get($url);
        $this->assertRedirectContains('/users/request-reset-password');
    }

    /**
     * A link stops working after an hour.
     *
     * @return void
     */
    public function testALinkStopsWorkingAfterAnHour(): void
    {
        $id = (string)$this->addAccount()->get('id');
        $url = $this->askForLink('jan.novak@example.com');

        $this->getTableLocator()->get('Users')->updateQuery()
            ->set(['token_expires' => DateTime::now()->subMinutes(1)])
            ->where(['id' => $id])
            ->execute();

        $this->get($url);
        $this->assertRedirectContains('/users/request-reset-password');
    }

    /**
     * The answer is the same whether an account was found or not, and an inactive account, or one
     * nobody can name alone, gets no mail.
     *
     * @return void
     */
    public function testNoAnswerSaysWhichAccountsExist(): void
    {
        $user = $this->addAccount();
        $this->askForLink('jnovak');
        $answer = $this->answer();
        TestEmailTransport::clearMessages();

        $this->askForLink('nobody');
        $this->assertSame($answer, $this->answer());

        $users = $this->getTableLocator()->get('Users');
        $users->updateQuery()->set(['active' => false])->where(['id' => $user->get('id')])->execute();
        $this->askForLink('jnovak');
        $this->assertSame($answer, $this->answer());

        $users->updateQuery()->set(['active' => true])->where(['id' => $user->get('id')])->execute();
        $this->addOtherAccount('jan.novak@example.com');
        $this->askForLink('jan.novak@example.com');

        $this->assertNoMailSent();
    }

    /**
     * An account gets a new link five minutes after the last at the soonest, so that asking again
     * and again neither fills its mailbox nor spoils the link on its way.
     *
     * @return void
     */
    public function testANewLinkIsSentFiveMinutesAfterTheLastAtTheSoonest(): void
    {
        $id = (string)$this->addAccount()->get('id');
        $first = $this->askForLink('jnovak');
        $this->assertNotSame('', $first);
        TestEmailTransport::clearMessages();

        $this->assertSame('', $this->askForLink('jnovak'));
        $this->get($first);
        $this->assertResponseOk();

        $this->getTableLocator()->get('Users')->updateQuery()
            ->set(['token_expires' => DateTime::now()->modify('+1 hour')->subMinutes(5)->subSeconds(1)])
            ->where(['id' => $id])
            ->execute();
        $this->assertNotSame('', $this->askForLink('jnovak'));
    }

    /**
     * Asking counts against the address like a failed sign-in, so that nobody sends mail without
     * end from one place.
     *
     * @return void
     */
    public function testAskingFromOneAddressIsLimited(): void
    {
        $this->addAccount();
        for ($i = 0; $i < 20; $i++) {
            $this->askForLink('nobody' . $i);
        }

        $this->assertSame('', $this->askForLink('jnovak'));
        $this->assertNoMailSent();
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertStringContainsString('password link blocked reference="jnovak"', implode("\n", $log->read()));
    }

    /**
     * What the last request answered, as each request leaves an answer of its own.
     *
     * @return mixed
     * @phpstan-impure
     */
    private function answer(): mixed
    {
        return $this->_requestSession?->read('Flash.flash.0.message');
    }

    /**
     * Asks for a link for a new password, and returns the address in the mail, if one was sent.
     *
     * @param string $reference A username or an e-mail.
     * @return string
     */
    private function askForLink(string $reference): string
    {
        $this->_session = [];
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/request-reset-password', ['reference' => $reference]);
        $this->assertRedirect('/login');

        $messages = TestEmailTransport::getMessages();
        if ($messages === []) {
            return '';
        }
        preg_match('~http://localhost(/users/reset-password/\S+)~', end($messages)->getBodyText(), $match);

        return $match[1] ?? '';
    }
}
