<?php
declare(strict_types=1);

namespace App\Test\TestCase\NMS;

use App\NMS\Links;
use App\Test\Traits\ConfigureTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\NMS\Links Test Case
 *
 * Where a thing of the other application's is to be found - and, the question worth asking here,
 * what is offered where there is no other application to point at.
 */
#[CoversClass(Links::class)]
class LinksTest extends TestCase
{
    use ConfigureTestTrait;

    /**
     * With a Watcher NMS to point at, each thing gets the path it is kept under over there.
     *
     * @return void
     * @link \App\NMS\Links::accessPoint()
     */
    public function testEachThingGetsThePathItIsKeptUnder(): void
    {
        $this->withConfigure(['Nms.url' => 'https://nms.example.com']);

        $this->assertSame('https://nms.example.com', Links::home());
        $this->assertSame('https://nms.example.com/access-points/one', Links::accessPoint('one'));
        $this->assertSame(
            'https://nms.example.com/routeros-devices/view/two',
            Links::routerosDevice('two'),
        );
        $this->assertSame(
            'https://nms.example.com/ip-address-ranges/view/three',
            Links::ipAddressRange('three'),
        );
    }

    /**
     * Without one, nothing is offered - which is what lets a page show the plain name instead.
     *
     * Building the path regardless would point the link back at this application, at an address
     * that does not exist here. The pages that ask are written to take nothing for an answer, so
     * this is the whole of that branch: no address, no link, and the name stands on its own.
     *
     * @return void
     * @link \App\NMS\Links::accessPoint()
     */
    public function testWithoutAWatcherNmsNothingIsOffered(): void
    {
        $this->withConfigure(['Nms.url' => '']);

        $this->assertNull(Links::home());
        $this->assertNull(Links::accessPoint('one'));
        $this->assertNull(Links::routerosDevice('two'));
        $this->assertNull(Links::ipAddressRange('three'));
    }

    /**
     * A trailing slash on the configured address does not become a double one in the path.
     *
     * @return void
     * @link \App\NMS\Links::accessPoint()
     */
    public function testATrailingSlashIsNotDoubled(): void
    {
        $this->withConfigure(['Nms.url' => 'https://nms.example.com/']);

        $this->assertSame('https://nms.example.com/access-points/one', Links::accessPoint('one'));
    }
}
