<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\VendorAssetsCheckCommand;
use App\Command\VendorAssetsUpdateCommand;
use App\Service\VendorAssets;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;
use FilesystemIterator;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * The libraries in `webroot/vendor` against where they come from, with the registry and Google Fonts
 * answering from here.
 */
#[CoversClass(VendorAssets::class)]
#[CoversClass(VendorAssetsCheckCommand::class)]
#[CoversClass(VendorAssetsUpdateCommand::class)]
class VendorAssetsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use HttpClientTrait;

    /**
     * A name longer than a tar header holds, which comes in an entry of its own.
     *
     * @var string
     */
    private const LONG_NAME = 'dist/a-stylesheet-whose-name-is-long-enough-to-need-the-extended-header'
        . '-which-npm-writes-for-it.css';

    private const ROOT = TMP . 'vendor_assets' . DS;

    private const VENDOR = self::ROOT . 'webroot' . DS . 'vendor' . DS;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->removeRoot();
        mkdir(self::VENDOR, 0777, true);
        mkdir(self::ROOT . 'webroot' . DS . 'css');

        Configure::write('VendorAssets.roots', [rtrim(self::VENDOR, DS)]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->removeRoot();

        parent::tearDown();
    }

    /**
     * Without the tests asking otherwise, the folders are those of the application and of its plugins.
     *
     * @return void
     */
    public function testTheFoldersAreThoseOfTheApplicationAndItsPlugins(): void
    {
        Configure::delete('VendorAssets.roots');
        $this->loadPlugins(['Maps', 'Migrations']);

        $folders = (new VendorAssets())->folders();
        $this->clearPlugins();

        $this->assertSame(WWW_ROOT . 'vendor' . DS . 'jquery', $folders['webroot/vendor/jquery']);
        $this->assertArrayHasKey('plugins/Maps/webroot/vendor/leaflet', $folders);

        // a plugin composer brings is none of the application's
        $this->assertSame([], preg_grep('~^/|^vendor/~', array_keys($folders)));
    }

    /**
     * A folder as its source says so, and that a newer version is out.
     *
     * @return void
     */
    public function testAFolderAsItsSourceTellsWhatIsNewer(): void
    {
        $this->mockPackage('lib', ['1.0.0' => $this->files('one'), '2.0.0' => $this->files('two')]);
        $this->makeFolder('lib', $this->libManifest(), $this->local('one'));

        $this->exec('vendor_assets check');

        $this->assertExitSuccess();
        $this->assertOutputRegExp('~tmp/vendor_assets/webroot/vendor/lib +1\.0\.0 +as the source, 2\.0\.0 is out$~m');
    }

    /**
     * A file changed by hand is found out, and a version others ask for says so.
     *
     * @return void
     */
    public function testAFolderThatDiffersFromItsSourceFailsTheCheck(): void
    {
        $this->mockPackage('lib', ['1.0.0' => $this->files('one'), '2.0.0' => $this->files('two')]);
        $manifest = ['pinnedBy' => 'Some\Listener'] + $this->libManifest();
        $this->makeFolder('lib', $manifest, ['lib.min.js' => 'changed'] + $this->local('one'));

        $this->exec('vendor_assets check');

        $this->assertExitError();
        $this->assertOutputContains('DIFFERS: lib.min.js, 2.0.0 is out (kept for Some\Listener)');
    }

    /**
     * Each folder is checked, what fails to be is said, and the others still are.
     *
     * @return void
     */
    public function testWhatCannotBeCheckedIsSaid(): void
    {
        $this->mockClientGet('https://registry.npmjs.org/gone', $this->newClientResponse(404));
        $this->mockClientGet('https://registry.npmjs.org/odd', $this->newClientResponse(200, [], '{"name":"odd"}'));
        $this->makeFolder('a-gone', ['npm' => 'gone', 'version' => '1.0.0', 'files' => $this->map()], []);
        $this->makeFolder('b-odd', ['npm' => 'odd', 'version' => '1.0.0', 'files' => $this->map()], []);
        $this->makeFolder('c-no-files', ['npm' => 'lib', 'version' => '1.0.0'], []);
        $this->makeFolder('d-no-version', ['npm' => 'lib', 'files' => $this->map()], []);
        mkdir(self::VENDOR . 'e-broken');
        file_put_contents(self::VENDOR . 'e-broken' . DS . 'vendor.json', '{');
        $this->makeFolder('f-fine', ['url' => ['https://example.com/f.css' => 'f.css']], ['f.css' => 'f']);
        $this->mockClientGet('https://example.com/f.css', $this->newClientResponse(200, [], 'f'));

        $this->exec('vendor_assets check');

        $this->assertExitError();
        $this->assertOutputRegExp('~a-gone +NOT CHECKED: https://registry\.npmjs\.org/gone answered 404$~m');
        $this->assertOutputContains('NOT CHECKED: The registry knows no odd');
        $this->assertOutputRegExp('~c-no-files +NOT CHECKED: .*c-no-files/vendor\.json names no files~');
        $this->assertOutputRegExp('~d-no-version +NOT CHECKED: .*d-no-version/vendor\.json names no version~');
        $this->assertOutputRegExp('~e-broken +NOT CHECKED: .*e-broken/vendor\.json names no files~');
        $this->assertOutputRegExp('~f-fine +- +as the source$~m');
    }

    /**
     * Fonts are checked file by file, and so is which characters the stylesheet takes each one for.
     *
     * @return void
     */
    public function testFontsAreCheckedWithTheCharactersTheyCover(): void
    {
        $this->mockFonts('v3', 'https://fonts.gstatic.com/s/testsans/v3/');
        $files = ['latin.woff2' => 'L3', 'latin-ext.woff2' => 'E3', 'OFL.txt' => 'licence'];
        $this->makeFolder('test-sans', $this->fontManifest('v3'), $files);
        file_put_contents(self::ROOT . 'webroot' . DS . 'css' . DS . 'fonts.css', implode("\n", [
            '@font-face { src: url("../vendor/test-sans/latin.woff2"); unicode-range: U+0000-00FF; }',
            '@font-face { src: url("../vendor/test-sans/latin-ext.woff2"); unicode-range: U+0100-017F; }',
        ]));

        $this->exec('vendor_assets check');

        $this->assertExitError();
        $this->assertOutputRegExp('~test-sans +v3 +DIFFERS: unicode-range of latin-ext\.woff2$~m');
    }

    /**
     * An address Google Fonts nearly never give is not taken for the font.
     *
     * @return void
     */
    public function testAnAnswerGoogleRarelyGivesIsNotTakenForTheFont(): void
    {
        $this->mockFonts('v3', 'https://fonts.gstatic.com/l/font?kit=');
        $this->makeFolder('test-sans', $this->fontManifest('v3'), []);

        $this->exec('vendor_assets check');

        $this->assertExitError();
        $this->assertOutputContains('NOT CHECKED: Google Fonts gave https://fonts.gstatic.com/l/font?kit=latin.woff2');
    }

    /**
     * A face of the family Google Fonts do not serve is said, not left out.
     *
     * @return void
     */
    public function testAFaceGoogleDoesNotServeIsSaid(): void
    {
        $this->mockFonts('v3', 'https://fonts.gstatic.com/s/testsans/v3/');
        $manifest = $this->fontManifest('v3');
        $manifest['files']['cyrillic normal 400'] = 'cyrillic.woff2';
        $this->makeFolder('test-sans', $manifest, []);

        $this->exec('vendor_assets check');

        $this->assertExitError();
        $this->assertOutputContains('NOT CHECKED: Google Fonts have no cyrillic normal 400 of Test Sans:wght@400');
    }

    /**
     * An update takes the newest version, files and all, and the folder says which it is.
     *
     * @return void
     */
    public function testAFolderTakesTheNewestVersion(): void
    {
        $this->mockPackage('lib', ['1.0.0' => $this->files('one'), '2.0.0' => $this->files('two')]);
        $this->makeFolder('lib', $this->libManifest(), $this->local('one'));

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/lib/');

        $this->assertExitSuccess();
        $this->assertOutputContains('tmp/vendor_assets/webroot/vendor/lib is now 2.0.0');
        $this->assertOutputNotContains('Change the addresses');
        foreach ($this->local('two') as $file => $content) {
            $this->assertStringEqualsFile(self::VENDOR . 'lib' . DS . $file, $content);
        }
        $this->assertStringEqualsFile(self::VENDOR . 'lib' . DS . 'vendor.json', implode("\n", [
            '{',
            '    "npm": "lib",',
            '    "version": "2.0.0",',
            '    "files": {',
            '        "LICENSE": "LICENSE",',
            '        "dist/lib.min.js": "lib.min.js",',
            '        "' . self::LONG_NAME . '": "css/lib.css"',
            '    }',
            '}',
            '',
        ]));
    }

    /**
     * A new library starts as a folder that says where it comes from, and takes its files whole.
     *
     * @return void
     */
    public function testANewLibraryIsTakenWhole(): void
    {
        $this->mockPackage('lib', ['1.0.0' => $this->files('one')]);
        $this->makeFolder('lib', $this->libManifest(), []);

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/lib');

        $this->assertExitSuccess();
        $this->assertStringEqualsFile(self::VENDOR . 'lib' . DS . 'css' . DS . 'lib.css', 'one css');
    }

    /**
     * What a folder without `vendor.json` says of itself is nothing at all.
     *
     * @return void
     */
    public function testAFolderWithoutAManifestSaysNothing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::VENDOR . 'nothing has no vendor.json');

        (new VendorAssets())->manifest(self::VENDOR . 'nothing');
    }

    /**
     * A tarball other than the one the registry describes is not unpacked.
     *
     * @return void
     */
    public function testATarballOtherThanTheRegistrySaysIsRefused(): void
    {
        $this->mockPackage('lib', ['1.0.0' => $this->files('one'), '2.0.0' => $this->files('two')], tampered: true);
        $this->makeFolder('lib', $this->libManifest(), $this->local('one'));

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/lib');

        $this->assertExitError();
        $this->assertErrorContains('The tarball of lib 2.0.0 is not what the registry says');
        $this->assertStringEqualsFile(self::VENDOR . 'lib' . DS . 'lib.min.js', 'one js');
    }

    /**
     * A version that lacks one of the files leaves all of them as they were.
     *
     * @return void
     */
    public function testAVersionWithoutAFileLeavesTheFolderAsItWas(): void
    {
        $this->mockPackage('lib', [
            '1.0.0' => $this->files('one'),
            '2.0.0' => ['LICENSE' => 'licence two', 'dist/lib.min.js' => 'two js'],
        ]);
        $this->makeFolder('lib', $this->libManifest(), $this->local('one'));

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/lib');

        $this->assertExitError();
        $this->assertErrorContains('lib 2.0.0 has no ' . self::LONG_NAME);
        $this->assertStringEqualsFile(self::VENDOR . 'lib' . DS . 'LICENSE', 'licence one');

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/lib 9.9.9');

        $this->assertExitError();
        $this->assertErrorContains('lib has no version 9.9.9');
    }

    /**
     * What others ask for at a version changes only to a version named, and they are to follow.
     *
     * @return void
     */
    public function testAPinnedFolderTakesOnlyAVersionNamed(): void
    {
        $this->mockPackage('lib', ['1.0.0' => $this->files('one'), '2.0.0' => $this->files('two')]);
        $this->makeFolder('lib', ['pinnedBy' => 'Some\Listener'] + $this->libManifest(), $this->local('one'));

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/lib');

        $this->assertExitError();
        $this->assertErrorContains('Some\Listener asks for this version, name the one to take');

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/lib 2.0.0');

        $this->assertExitSuccess();
        $this->assertOutputContains('Change the addresses in Some\Listener as well.');
        $this->assertStringEqualsFile(self::VENDOR . 'lib' . DS . 'lib.min.js', 'two js');
    }

    /**
     * Only a folder that says where it comes from is updated.
     *
     * @return void
     */
    public function testAFolderThatSaysNothingIsLeftAlone(): void
    {
        $this->exec('vendor_assets update webroot/vendor/jquery');

        $this->assertExitError();
        $this->assertErrorContains('webroot/vendor/jquery does not say where it comes from');
    }

    /**
     * Google Fonts serve their newest version, and nothing else can be asked for.
     *
     * @return void
     */
    public function testFontsTakeWhatGoogleServes(): void
    {
        $this->mockFonts('v4', 'https://fonts.gstatic.com/s/testsans/v4/');
        $this->makeFolder('test-sans', $this->fontManifest('v3'), ['latin.woff2' => 'L3', 'latin-ext.woff2' => 'E3']);

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/test-sans v3');

        $this->assertExitError();
        $this->assertErrorContains('Google Fonts serve v4 only, not v3');

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/test-sans');

        $this->assertExitSuccess();
        $this->assertOutputContains('is now v4');
        $this->assertOutputContains('Compare `unicode-range`');
        $this->assertStringEqualsFile(self::VENDOR . 'test-sans' . DS . 'latin-ext.woff2', 'E4');
        $this->assertStringEqualsFile(self::VENDOR . 'test-sans' . DS . 'OFL.txt', 'licence');
        $manifest = (string)file_get_contents(self::VENDOR . 'test-sans' . DS . 'vendor.json');
        $this->assertStringContainsString('"version": "v4"', $manifest);
    }

    /**
     * Files taken from an address have no version, and the folder says none.
     *
     * @return void
     */
    public function testFilesFromAnAddressAreTakenAsTheyAre(): void
    {
        $this->mockClientGet('https://example.com/a.css', $this->newClientResponse(200, [], 'new'));
        $this->makeFolder('a', ['url' => ['https://example.com/a.css' => 'a.css']], ['a.css' => 'old']);

        $this->exec('vendor_assets update tmp/vendor_assets/webroot/vendor/a');

        $this->assertExitSuccess();
        $this->assertOutputContains('a is now what its source has');
        $this->assertStringEqualsFile(self::VENDOR . 'a' . DS . 'a.css', 'new');
        $this->assertStringEqualsFile(self::VENDOR . 'a' . DS . 'vendor.json', '{"url":{"https://example.com/a.css":"a.css"}}');
    }

    /**
     * Files of a package, by their path in it.
     *
     * @param string $version What tells the versions apart.
     * @return array<string, string>
     */
    private function files(string $version): array
    {
        return [
            'LICENSE' => "licence $version",
            'dist/lib.min.js' => "$version js",
            self::LONG_NAME => "$version css",
        ];
    }

    /**
     * Those files in the folder.
     *
     * @param string $version What tells the versions apart.
     * @return array<string, string>
     */
    private function local(string $version): array
    {
        return array_combine(array_values($this->map()), array_values($this->files($version)));
    }

    /**
     * What the folder of the package says at first.
     *
     * @return array<string, mixed>
     */
    private function libManifest(): array
    {
        return ['npm' => 'lib', 'version' => '1.0.0', 'files' => $this->map()];
    }

    /**
     * @return array<string, string>
     */
    private function map(): array
    {
        return ['LICENSE' => 'LICENSE', 'dist/lib.min.js' => 'lib.min.js', self::LONG_NAME => 'css/lib.css'];
    }

    /**
     * @param string $name The folder.
     * @param array<string, mixed> $manifest What it says of itself.
     * @param array<string, string> $files What it holds.
     * @return void
     */
    private function makeFolder(string $name, array $manifest, array $files): void
    {
        $folder = self::VENDOR . $name . DS;
        mkdir($folder);

        file_put_contents($folder . 'vendor.json', json_encode($manifest, JSON_UNESCAPED_SLASHES));
        foreach ($files as $file => $content) {
            if (!is_dir(dirname($folder . $file))) {
                mkdir(dirname($folder . $file));
            }
            file_put_contents($folder . $file, $content);
        }
    }

    /**
     * The registry with a package of the given versions, the last of them the newest.
     *
     * @param string $package The package.
     * @param array<string, array<string, string>> $versions Files by their path in the package, by version.
     * @param bool $tampered Whether the tarballs are other than the registry says.
     * @return void
     */
    private function mockPackage(string $package, array $versions, bool $tampered = false): void
    {
        $metadata = ['dist-tags' => ['latest' => array_key_last($versions)], 'versions' => []];

        foreach ($versions as $version => $files) {
            $tarball = (string)gzencode($this->tar($files));
            $url = "https://registry.npmjs.org/$package/-/$package-$version.tgz";
            $digest = base64_encode(hash('sha512', $tampered ? $tarball . 'x' : $tarball, true));

            $metadata['versions'][$version] = ['dist' => ['tarball' => $url, 'integrity' => "sha512-$digest"]];
            $this->mockClientGet($url, $this->newClientResponse(200, [], $tarball));
        }

        $this->mockClientGet(
            "https://registry.npmjs.org/$package",
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode($metadata)),
        );
    }

    /**
     * A tar archive the way npm packs one: everything in `package/`, with a directory of its own, a
     * name split into a prefix, and a name too long for either.
     *
     * @param array<string, string> $files Files by their path in the package.
     * @return string
     */
    private function tar(array $files): string
    {
        $tar = $this->tarEntry('package/', '', '5');

        foreach ($files as $path => $content) {
            $name = 'package/' . $path;

            if (strlen($name) > 100) {
                $record = ' path=' . $name . "\n";
                $tar .= $this->tarEntry('PaxHeader/long', strlen($record) + 3 . $record, 'x');
                $tar .= $this->tarEntry(substr($name, 0, 100), $content);
            } elseif (str_contains($path, '/')) {
                $tar .= $this->tarEntry(basename($name), $content, '0', dirname($name));
            } else {
                $tar .= $this->tarEntry($name, $content);
            }
        }

        return $tar . str_repeat("\0", 1024);
    }

    /**
     * @param string $name The name in the header.
     * @param string $data The content.
     * @param string $type The type of the entry.
     * @param string $prefix What comes before the name.
     * @return string
     */
    private function tarEntry(string $name, string $data, string $type = '0', string $prefix = ''): string
    {
        $header = str_repeat("\0", 512);
        $header = substr_replace($header, str_pad($name, 100, "\0"), 0, 100);
        $header = substr_replace($header, sprintf('%011o', strlen($data)) . "\0", 124, 12);
        $header = substr_replace($header, $type, 156, 1);
        $header = substr_replace($header, "ustar\00000", 257, 8);
        $header = substr_replace($header, str_pad($prefix, 155, "\0"), 345, 155);

        return $header . str_pad($data, (int)ceil(strlen($data) / 512) * 512, "\0");
    }

    /**
     * @param string $version The version it says it is.
     * @return array<string, mixed>
     */
    private function fontManifest(string $version): array
    {
        return [
            'googleFonts' => 'Test Sans:wght@400',
            'version' => $version,
            'files' => ['latin normal 400' => 'latin.woff2', 'latin-ext normal 400' => 'latin-ext.woff2'],
            'url' => ['https://github.com/test-sans/OFL.txt' => 'OFL.txt'],
        ];
    }

    /**
     * Google Fonts serving a family of two subsets, and a third face the folder does not take.
     *
     * @param string $version The version they serve.
     * @param string $base Where the files are.
     * @return void
     */
    private function mockFonts(string $version, string $base): void
    {
        $css = '';
        foreach (['latin-ext' => 'U+0100-024F', 'latin' => 'U+0000-00FF'] as $subset => $range) {
            $css .= "/* $subset */\n@font-face {\n  font-family: 'Test Sans';\n  font-style: normal;\n"
                . "  font-weight: 400;\n  src: url({$base}{$subset}.woff2) format('woff2');\n"
                . "  unicode-range: $range;\n}\n";

            $file = ($subset === 'latin' ? 'L' : 'E') . substr($version, 1);
            $this->mockClientGet("{$base}{$subset}.woff2", $this->newClientResponse(200, [], $file));
        }
        $css .= "/* vietnamese */\n@font-face {\n  font-style: normal;\n}\n";

        $this->mockClientGet(
            'https://fonts.googleapis.com/css2?display=swap&family=Test+Sans:wght@400',
            $this->newClientResponse(200, [], $css),
        );
        $this->mockClientGet('https://github.com/test-sans/OFL.txt', $this->newClientResponse(200, [], 'licence'));
    }

    /**
     * @return void
     */
    private function removeRoot(): void
    {
        if (!is_dir(self::ROOT)) {
            return;
        }

        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::ROOT, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($entries as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir(self::ROOT);
    }
}
