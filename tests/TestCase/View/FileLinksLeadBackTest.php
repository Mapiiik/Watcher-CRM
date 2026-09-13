<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\View\AppView;
use Cake\TestSuite\TestCase;
use Files\Model\Entity\FileLink;
use Files\View\Helper\RecordHelper;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * A file link says which record it is filed against, and leads there.
 *
 * The plugin knows a model and a key and nothing more, so where each model is read is the
 * application's to say. What is not named there is offered as plain words rather than a link,
 * which is why a model quietly missing from the map shows up as nothing at all.
 */
#[UsesClass(RecordHelper::class)]
class FileLinksLeadBackTest extends TestCase
{
    /**
     * @return void
     */
    public function testAFiledRecordLeadsBackToItsOwnPage(): void
    {
        $helper = new RecordHelper(new AppView());

        foreach (['Documentations', 'ContractProposals', 'CustomerProposals'] as $model) {
            $url = $helper->urlFor(new FileLink([
                'model' => $model,
                'foreign_key' => '0f3a1a9c-6f4a-4a1e-9d0a-5c6b7a8d9e01',
            ]));

            $this->assertIsArray($url, $model . ' is filed against and has nowhere to lead.');
            $this->assertSame($model, $url['controller'] ?? null);
            $this->assertSame('view', $url['action'] ?? null);
            $this->assertContains('0f3a1a9c-6f4a-4a1e-9d0a-5c6b7a8d9e01', $url);
        }
    }

    /**
     * @return void
     */
    public function testAModelNobodyNamedLeadsNowhere(): void
    {
        $helper = new RecordHelper(new AppView());

        $this->assertNull($helper->urlFor(new FileLink([
            'model' => 'SomethingNobodyDeclared',
            'foreign_key' => '0f3a1a9c-6f4a-4a1e-9d0a-5c6b7a8d9e01',
        ])));
    }
}
