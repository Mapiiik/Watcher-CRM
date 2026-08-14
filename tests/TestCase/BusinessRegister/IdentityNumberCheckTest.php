<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister;

use App\BusinessRegister\IdentityNumberCheck;
use App\BusinessRegister\IdentityNumberStatus;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\BusinessRegister\IdentityNumberCheck Test Case
 */
#[CoversClass(IdentityNumberCheck::class)]
class IdentityNumberCheckTest extends TestCase
{
    /**
     * Naming the holder already says the register found the number, so that is the whole answer.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumberCheck::note()
     */
    public function testTheHolderIsTheWholeAnswer(): void
    {
        $check = new IdentityNumberCheck(IdentityNumberStatus::Found, 'NETAIR, s.r.o.');

        $this->assertSame('NETAIR, s.r.o.', $check->note());
    }

    /**
     * With nobody to name, the status is spelled out instead.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumberCheck::note()
     */
    public function testWithoutAHolderTheStatusIsSpelledOut(): void
    {
        $check = new IdentityNumberCheck(IdentityNumberStatus::NotFound);

        $this->assertSame(IdentityNumberStatus::NotFound->label(), $check->note());
    }
}
