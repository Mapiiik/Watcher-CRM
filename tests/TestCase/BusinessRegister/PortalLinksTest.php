<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister;

use App\BusinessRegister\PortalLinks;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\BusinessRegister\PortalLinks Test Case
 */
#[CoversClass(PortalLinks::class)]
class PortalLinksTest extends TestCase
{
    /**
     * A Czech number is looked up in ARES, which takes it in the address.
     *
     * @return void
     * @link \App\BusinessRegister\PortalLinks::forIdentityNumber()
     */
    public function testCzechIdentityNumberLeadsToAres(): void
    {
        $this->assertSame(
            'https://ares.gov.cz/ekonomicke-subjekty?ico=27496139',
            PortalLinks::forIdentityNumber('27496139'),
        );
    }

    /**
     * A Croatian number is looked up in the court register, which keeps no number in its address.
     *
     * @return void
     * @link \App\BusinessRegister\PortalLinks::forIdentityNumber()
     */
    public function testCroatianIdentityNumberLeadsToTheCourtRegister(): void
    {
        $this->assertSame(
            'https://sudreg.pravosudje.hr/',
            PortalLinks::forIdentityNumber('80625159724'),
        );
    }

    /**
     * A number that does not hold up gets no link, so following one never lands nowhere.
     *
     * @return void
     * @link \App\BusinessRegister\PortalLinks::forIdentityNumber()
     */
    public function testInvalidIdentityNumberGetsNoLink(): void
    {
        $this->assertNull(PortalLinks::forIdentityNumber('12345678'));
        $this->assertNull(PortalLinks::forIdentityNumber('not a number'));
        $this->assertNull(PortalLinks::forIdentityNumber(null));
    }

    /**
     * A number is read whichever spacing it was stored with.
     *
     * @return void
     * @link \App\BusinessRegister\PortalLinks::forIdentityNumber()
     */
    public function testIdentityNumberIsReadThroughItsSpacing(): void
    {
        $this->assertSame(
            'https://ares.gov.cz/ekonomicke-subjekty?ico=27496139',
            PortalLinks::forIdentityNumber(' 274 961 39 '),
        );
    }
}
