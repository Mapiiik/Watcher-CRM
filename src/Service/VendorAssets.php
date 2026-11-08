<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Client;
use RuntimeException;

/**
 * The libraries kept in `webroot/vendor/<library>`, of the application and of its plugins.
 *
 * A `vendor.json` in each folder says where its files come from:
 *
 * - `npm` and `version`: files out of the tarball of that package in the npm registry,
 * - `googleFonts` and `version`: a family of Google Fonts, one file per subset, style and weight,
 * - `url`: files taken as they are from an address, such as Milligram out of `cakephp/app`.
 *
 * `files` names the files of the package or the font, and where they go in the folder. A folder can
 * have `url` beside the other two, for a licence the package does not carry. `pinnedBy` says what
 * else asks for that very version, so that it is not updated on its own.
 *
 * `VendorAssets.roots` replaces where the folders are looked for, which the tests do.
 *
 * @phpstan-type Manifest array{
 *     npm?: string,
 *     googleFonts?: string,
 *     version?: string,
 *     pinnedBy?: string,
 *     files?: array<string, string>,
 *     url?: array<string, string>,
 * }
 * @phpstan-type Source array{files: array<string, string>, version: string|null, newest: string|null}
 */
class VendorAssets
{
    /**
     * Google Fonts give each browser what it reads, and woff2 is what a current one does.
     *
     * @var string
     */
    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) '
        . 'Chrome/130.0 Safari/537.36';

    /**
     * @var \Cake\Http\Client
     */
    private Client $http;

    /**
     * The faces of the families asked for, by `<subset> <style> <weight>`.
     *
     * @var array<string, array<string, array{url: string, range: string}>>
     */
    private array $faces = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->http = new Client(['timeout' => 120]);
    }

    /**
     * The folders that say where they come from, by their path from the root of the application.
     *
     * @return array<string, string>
     */
    public function folders(): array
    {
        $folders = [];

        foreach ($this->roots() as $root) {
            foreach (glob($root . DS . '*' . DS . 'vendor.json') ?: [] as $manifest) {
                $folder = dirname($manifest);
                $name = str_starts_with($folder, ROOT . DS) ? substr($folder, strlen(ROOT . DS)) : $folder;

                $folders[str_replace(DS, '/', $name)] = $folder;
            }
        }

        return $folders;
    }

    /**
     * What a folder says of itself.
     *
     * @param string $folder The folder.
     * @return array<string, mixed>
     * @phpstan-return Manifest
     */
    public function manifest(string $folder): array
    {
        $path = $folder . DS . 'vendor.json';
        $json = is_file($path) ? file_get_contents($path) : false;
        if ($json === false) {
            throw new RuntimeException(sprintf('%s has no vendor.json', $folder));
        }

        $manifest = json_decode($json, true);
        if (!is_array($manifest) || !is_array($manifest['files'] ?? $manifest['url'] ?? null)) {
            throw new RuntimeException(sprintf('%s names no files', $path));
        }
        if ((isset($manifest['npm']) || isset($manifest['googleFonts'])) && !is_string($manifest['version'] ?? null)) {
            throw new RuntimeException(sprintf('%s names no version', $path));
        }

        /** @phpstan-var Manifest $manifest */
        return $manifest;
    }

    /**
     * A folder against its source at the version it records.
     *
     * @param string $folder The folder.
     * @return array{version: string|null, newest: string|null, pinnedBy: string|null, differ: array<string>}
     *   What differs are files, and `unicode-range` of fonts in the stylesheets beside the folder.
     */
    public function compare(string $folder): array
    {
        $manifest = $this->manifest($folder);
        $source = $this->source($manifest, $manifest['version'] ?? null);

        $differ = [];
        foreach ($source['files'] as $file => $content) {
            $path = $folder . DS . $file;

            if (!is_file($path) || file_get_contents($path) !== $content) {
                $differ[] = $file;
            }
        }

        return [
            'version' => $manifest['version'] ?? null,
            'newest' => $source['newest'],
            'pinnedBy' => $manifest['pinnedBy'] ?? null,
            'differ' => array_merge($differ, $this->unicodeRangesThatDiffer($folder, $manifest)),
        ];
    }

    /**
     * Replaces the files of a folder with those of another version of its source.
     *
     * Everything is fetched before anything is written, so a source that lacks a file leaves the
     * folder as it was.
     *
     * @param string $folder The folder.
     * @param string|null $version The version, or the newest one.
     * @return string|null The version the folder holds now.
     */
    public function update(string $folder, ?string $version): ?string
    {
        $manifest = $this->manifest($folder);

        if ($version === null && isset($manifest['pinnedBy'])) {
            throw new RuntimeException(
                sprintf('%s asks for this version, name the one to take', $manifest['pinnedBy']),
            );
        }

        $source = $this->source($manifest, $version ?? 'latest');

        if (isset($manifest['googleFonts']) && $version !== null && $version !== $source['version']) {
            throw new RuntimeException(sprintf('Google Fonts serve %s only, not %s', $source['version'], $version));
        }

        foreach ($source['files'] as $file => $content) {
            $path = $folder . DS . $file;

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, $content);
        }

        if (isset($manifest['version'])) {
            $manifest['version'] = (string)$source['version'];
            $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

            file_put_contents($folder . DS . 'vendor.json', json_encode($manifest, $flags) . "\n");
        }

        return $source['version'];
    }

    /**
     * Where the folders are: the webroot of the application, and of the plugins it carries itself.
     *
     * @return array<string>
     */
    private function roots(): array
    {
        /** @var array<string>|null $roots */
        $roots = Configure::read('VendorAssets.roots');
        if ($roots !== null) {
            return $roots;
        }

        $roots = [WWW_ROOT . 'vendor'];
        foreach (Plugin::loaded() as $plugin) {
            $path = Plugin::path($plugin);

            if (str_starts_with($path, ROOT . DS . 'plugins' . DS)) {
                $roots[] = $path . 'webroot' . DS . 'vendor';
            }
        }

        return $roots;
    }

    /**
     * The files a folder should hold at a version, which version they are, and the newest one.
     *
     * Google Fonts serve their newest version only, whatever is asked for.
     *
     * @param array<string, mixed> $manifest What the folder says of itself.
     * @phpstan-param Manifest $manifest
     * @param string|null $version A version, `latest`, or null for a source without versions.
     * @return array<string, mixed> The files by their place in the folder.
     * @phpstan-return Source
     */
    private function source(array $manifest, ?string $version): array
    {
        $source = ['files' => [], 'version' => null, 'newest' => null];

        if (isset($manifest['npm'])) {
            $source = $this->npm($manifest['npm'], (string)$version, $manifest['files'] ?? []);
        } elseif (isset($manifest['googleFonts'])) {
            $source = $this->googleFonts($manifest['googleFonts'], $manifest['files'] ?? []);
        }

        foreach ($manifest['url'] ?? [] as $url => $file) {
            $source['files'][$file] = $this->fetch($url);
        }

        return $source;
    }

    /**
     * Files out of the tarball of a version of an npm package, checked against what the registry says.
     *
     * @param string $package The package.
     * @param string $version The version, or `latest`.
     * @param array<string, string> $map Files of the package, and their places in the folder.
     * @return array<string, mixed>
     * @phpstan-return Source
     */
    private function npm(string $package, string $version, array $map): array
    {
        $registry = 'https://registry.npmjs.org/' . str_replace('/', '%2F', $package);

        $metadata = json_decode($this->fetch($registry, 'application/vnd.npm.install-v1+json'), true);
        if (!is_array($metadata) || !is_string($metadata['dist-tags']['latest'] ?? null)) {
            throw new RuntimeException(sprintf('The registry knows no %s', $package));
        }

        /**
         * @var array{
         *     dist-tags: array{latest: string},
         *     versions: array<string, array{dist: array{tarball: string, integrity: string}}>,
         * } $metadata
         */
        $latest = $metadata['dist-tags']['latest'];
        $version = $version === 'latest' ? $latest : $version;
        $dist = $metadata['versions'][$version]['dist']
            ?? throw new RuntimeException(sprintf('%s has no version %s', $package, $version));

        $tarball = $this->fetch($dist['tarball']);
        [$algorithm, $digest] = explode('-', $dist['integrity'], 2) + ['', ''];
        if (!in_array($algorithm, hash_algos(), true) || base64_encode(hash($algorithm, $tarball, true)) !== $digest) {
            throw new RuntimeException(
                sprintf('The tarball of %s %s is not what the registry says', $package, $version),
            );
        }

        $contents = $this->untar((string)gzdecode($tarball));

        $files = [];
        foreach ($map as $inPackage => $inFolder) {
            $files[$inFolder] = $contents[$inPackage]
                ?? throw new RuntimeException(sprintf('%s %s has no %s', $package, $version, $inPackage));
        }

        return ['files' => $files, 'version' => $version, 'newest' => $latest];
    }

    /**
     * The files of a family of Google Fonts, as a current browser gets them.
     *
     * @param string $family The family as the CSS API takes it, such as `Fira Sans:ital,wght@0,400;1,400`.
     * @param array<string, string> $map Faces as `<subset> <style> <weight>`, and their files in the folder.
     * @return array<string, mixed>
     * @phpstan-return Source
     */
    private function googleFonts(string $family, array $map): array
    {
        $faces = $this->googleFontFaces($family);

        $files = [];
        $versions = [];
        foreach ($map as $face => $file) {
            $url = $faces[$face]['url']
                ?? throw new RuntimeException(sprintf('Google Fonts have no %s of %s', $face, $family));

            // a file of a version, which Google answers with nearly always, but once gave something else
            if (!preg_match('~^https://fonts\.gstatic\.com/s/[a-z0-9]+/(v\d+)/[\w-]+\.woff2$~', $url, $match)) {
                throw new RuntimeException(
                    sprintf('Google Fonts gave %s for %s of %s, try again', $url, $face, $family),
                );
            }

            $files[$file] = $this->fetch($url);
            $versions[] = $match[1];
        }
        $version = implode(',', array_unique($versions));

        return ['files' => $files, 'version' => $version, 'newest' => $version];
    }

    /**
     * The faces of a family by `<subset> <style> <weight>`, with the address and `unicode-range` of each.
     *
     * @param string $family The family as the CSS API takes it.
     * @return array<string, array{url: string, range: string}>
     */
    private function googleFontFaces(string $family): array
    {
        if (!isset($this->faces[$family])) {
            $query = 'display=swap&family=' . str_replace(' ', '+', $family);
            $css = $this->fetch('https://fonts.googleapis.com/css2?' . $query);

            preg_match_all('~/\*\s*([\w-]+)\s*\*/\s*@font-face\s*\{([^}]*)\}~', $css, $blocks, PREG_SET_ORDER);

            $this->faces[$family] = [];
            foreach ($blocks as [, $subset, $body]) {
                if (
                    preg_match('~font-style:\s*(\w+)~', $body, $style)
                    && preg_match('~font-weight:\s*([\d ]+)~', $body, $weight)
                    && preg_match('~src:\s*url\(([^)]+)\)~', $body, $url)
                    && preg_match('~unicode-range:\s*([^;]+)~', $body, $range)
                ) {
                    $face = $subset . ' ' . $style[1] . ' ' . trim($weight[1]);
                    $this->faces[$family][$face] = ['url' => $url[1], 'range' => trim($range[1])];
                }
            }
        }

        return $this->faces[$family];
    }

    /**
     * The fonts whose @font-face in the stylesheets beside the folder covers other characters than
     * Google's does.
     *
     * @param string $folder The folder.
     * @param array<string, mixed> $manifest What the folder says of itself.
     * @phpstan-param Manifest $manifest
     * @return array<string> The files, each with what is to be checked.
     */
    private function unicodeRangesThatDiffer(string $folder, array $manifest): array
    {
        if (!isset($manifest['googleFonts'])) {
            return [];
        }

        $stylesheets = glob(dirname($folder, 2) . DS . 'css' . DS . '*.css') ?: [];
        $css = implode("\n", array_map(fn(string $file): string => (string)file_get_contents($file), $stylesheets));
        $faces = $this->googleFontFaces($manifest['googleFonts']);

        $differ = [];
        foreach ($manifest['files'] ?? [] as $face => $file) {
            $pattern = '~@font-face\s*\{[^}]*/' . preg_quote($file, '~') . '[^}]*unicode-range:\s*([^;]+)~';

            if (!preg_match($pattern, $css, $match) || trim($match[1]) !== ($faces[$face]['range'] ?? null)) {
                $differ[] = 'unicode-range of ' . $file;
            }
        }

        return $differ;
    }

    /**
     * The regular files of a tar archive, by their path without the folder the archive wraps them in.
     *
     * @param string $tar The archive.
     * @return array<string, string>
     */
    private function untar(string $tar): array
    {
        $files = [];
        $longName = null;
        $length = strlen($tar);

        for ($offset = 0; $offset + 512 <= $length;) {
            $header = substr($tar, $offset, 512);
            if (trim($header, "\0") === '') {
                break;
            }

            $size = (int)octdec(trim(substr($header, 124, 12), "\0 "));
            $data = substr($tar, $offset + 512, $size);
            $type = $header[156];
            $offset += 512 + (int)ceil($size / 512) * 512;

            // a name too long for the header comes before it, in an entry of its own
            if ($type === 'x') {
                $longName = preg_match('~\d+ path=([^\n]*)~', $data, $match) ? $match[1] : null;
                continue;
            }

            if ($type === '0' || $type === "\0") {
                $prefix = rtrim(substr($header, 345, 155), "\0");
                $name = rtrim(substr($header, 0, 100), "\0");
                $path = $longName ?? ($prefix === '' ? $name : $prefix . '/' . $name);

                $files[(string)preg_replace('~^[^/]*/~', '', $path)] = $data;
            }

            $longName = null;
        }

        return $files;
    }

    /**
     * @param string $url The address.
     * @param string|null $accept What to ask for.
     * @return string
     */
    private function fetch(string $url, ?string $accept = null): string
    {
        $headers = ['User-Agent' => self::BROWSER] + ($accept === null ? [] : ['Accept' => $accept]);

        $response = $this->http->get($url, [], ['headers' => $headers, 'redirect' => 3]);
        if (!$response->isOk()) {
            throw new RuntimeException(sprintf('%s answered %d', $url, $response->getStatusCode()));
        }

        return $response->getStringBody();
    }
}
