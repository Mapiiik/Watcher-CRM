<?php
declare(strict_types=1);

namespace App\Test\TestCase\Pdf;

use App\Pdf\Canvas;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Pdf\Canvas Test Case
 */
#[CoversClass(Canvas::class)]
class CanvasTest extends TestCase
{
    /**
     * Every page draws its rules as thick as the documents are set to, 0.57 points as TCPDF drew
     * them: each page starts from the defaults of PDF, a point wide, and the engine says nothing
     * of the width on its own.
     *
     * @return void
     */
    public function testEveryPageDrawsItsRulesAsThickAsTheDocumentsAreSet(): void
    {
        $canvas = new Canvas();
        $canvas->SetCompression(false);
        $canvas->AddPage();
        $canvas->Line(10, 10, 100, 10);
        $canvas->AddPage();
        $canvas->Line(10, 10, 100, 10);

        $this->assertSame(2, preg_match_all('/^0\.570000 w$/m', $canvas->Output()));
    }
}
