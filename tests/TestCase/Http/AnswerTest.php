<?php
declare(strict_types=1);

namespace App\Test\TestCase\Http;

use App\Http\Answer;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * App\Http\Answer Test Case
 *
 * The three states are the whole point, so each is asked all three questions. What is being pinned
 * down is that they do not collapse into each other - an installation that was never given a
 * service must not read as one that is down, and a service that answered nothing must not read as
 * one that did not answer.
 */
#[CoversClass(Answer::class)]
class AnswerTest extends TestCase
{
    /**
     * An answer is an answer, whatever it says.
     *
     * @return void
     * @link \App\Http\Answer::of()
     */
    public function testAnAnswerIsWorkedWith(): void
    {
        $answer = Answer::of(['name' => 'Hilltop']);

        $this->assertTrue($answer->ok());
        $this->assertFalse($answer->unanswered());
        $this->assertSame(['name' => 'Hilltop'], $answer->data);
        $this->assertSame(['name' => 'Hilltop'], $answer->orFail());
    }

    /**
     * An answer of nothing is still an answer - the other side was asked and said there is
     * nothing there, which is not the same as its having said nothing.
     *
     * @return void
     * @link \App\Http\Answer::of()
     */
    public function testAnAnswerOfNothingIsStillAnAnswer(): void
    {
        $answer = Answer::of([]);

        $this->assertTrue($answer->ok());
        $this->assertFalse($answer->unanswered());
        $this->assertSame([], $answer->or(['fallback']));
    }

    /**
     * A question that went unanswered says so, and says why.
     *
     * @return void
     * @link \App\Http\Answer::failed()
     */
    public function testAQuestionThatWentUnansweredSaysSo(): void
    {
        $answer = Answer::failed('Watcher NMS answered 500.');

        $this->assertFalse($answer->ok());
        $this->assertTrue($answer->unanswered());
        $this->assertNull($answer->data);
        $this->assertSame('Watcher NMS answered 500.', $answer->failure);
    }

    /**
     * A caller whose run the failure should end asks for it as an exception, carrying the reason
     * the client wrote down.
     *
     * @return void
     * @link \App\Http\Answer::orFail()
     */
    public function testAFailureIsThrownWhereTheCallerWantsItThrown(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Watcher NMS answered 500.');

        Answer::failed('Watcher NMS answered 500.')->orFail();
    }

    /**
     * Nobody asked is its own state: not an answer to work with, and not a failure to remark on.
     *
     * @return void
     * @link \App\Http\Answer::notAsked()
     */
    public function testNobodyHavingAskedIsNeitherAnAnswerNorAFailure(): void
    {
        $answer = Answer::notAsked();

        $this->assertFalse($answer->ok());
        $this->assertFalse($answer->unanswered());
        $this->assertNull($answer->failure);
    }

    /**
     * A command told to use a service this installation was never given cannot do it, so asking
     * for the answer outright ends the run there too.
     *
     * @return void
     * @link \App\Http\Answer::orFail()
     */
    public function testNobodyHavingAskedStillEndsTheRunOfWhoeverInsists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Watcher NMS is not configured.');

        Answer::notAsked()->orFail('Watcher NMS is not configured.');
    }

    /**
     * Falling back is for whoever can go on without the answer.
     *
     * @return void
     * @link \App\Http\Answer::or()
     */
    public function testFallingBackCoversBothWaysOfHavingNoAnswer(): void
    {
        $this->assertSame([], Answer::failed('down')->or([]));
        $this->assertSame([], Answer::notAsked()->or([]));
    }

    /**
     * Reading the data into another shape leaves the other two states alone, which is how a client
     * turns what arrived into what it hands over without writing the three states out every time.
     *
     * @return void
     * @link \App\Http\Answer::map()
     */
    public function testReadingTheDataLeavesAFailureAlone(): void
    {
        $read = Answer::of(['1', '2'])->map(fn(array $rows): int => count($rows));
        $this->assertSame(2, $read->data);

        // an answer that came to nothing stands in for one of any shape, which is what lets a
        // client hand the same failure back whatever it was going to answer with
        /** @var \App\Http\Answer<list<string>> $down */
        $down = Answer::failed('down');
        $failed = $down->map(fn(array $rows): int => count($rows));
        $this->assertTrue($failed->unanswered());
        $this->assertSame('down', $failed->failure);

        /** @var \App\Http\Answer<list<string>> $never */
        $never = Answer::notAsked();
        $notAsked = $never->map(fn(array $rows): int => count($rows));
        $this->assertFalse($notAsked->asked);
    }
}
