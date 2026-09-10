<?php
declare(strict_types=1);

namespace App\Pdf;

use Com\Tecnick\Pdf\Import\SourceDocument;
use Throwable;

/**
 * The marks a document carries, written into its own metadata and read back out of it.
 *
 * XMP rather than a table beside it: the metadata stream is left uncompressed even when the rest
 * of the document is not, exactly so that anything can find it, and it travels with the file
 * wherever the file goes.
 *
 * A document that came from a scanner carries none of this, which is why nothing here throws when
 * it finds nothing - the caller falls back to the paper this one is a scan of.
 */
final class SignatureAnchors
{
    /**
     * What the marks are called. The provider is us and the user is the customer, which is the
     * same pair the signature block itself is labelled with.
     */
    public const PROVIDER_DATE = 'provider.date';
    public const PROVIDER_SIGNATURE = 'provider.signature';
    public const CUSTOMER_DATE = 'customer.date';
    public const CUSTOMER_SIGNATURE = 'customer.signature';

    /**
     * Where the vocabulary is defined, and what it is called in the metadata.
     */
    private const NAMESPACE_URI = 'https://github.com/Mapiiik/Watcher-CRM/ns/documents/1.0/';
    private const NAMESPACE_PREFIX = 'wcrm';
    private const PROPERTY = 'signatureAnchors';

    /**
     * Where in the XMP the fragment is put. The only key that takes a description of our own.
     */
    public const XMP_KEY = 'x:xmpmeta.rdf:RDF';

    /**
     * @var list<\App\Pdf\SignatureAnchor>
     */
    private array $anchors = [];

    /**
     * @param \App\Pdf\SignatureAnchor $anchor The mark.
     * @return void
     */
    public function add(SignatureAnchor $anchor): void
    {
        $this->anchors[] = $anchor;
    }

    /**
     * @param string $name What the mark is for.
     * @return \App\Pdf\SignatureAnchor|null
     */
    public function find(string $name): ?SignatureAnchor
    {
        foreach ($this->anchors as $anchor) {
            if ($anchor->name === $name) {
                return $anchor;
            }
        }

        return null;
    }

    /**
     * @return list<\App\Pdf\SignatureAnchor>
     */
    public function all(): array
    {
        return $this->anchors;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->anchors === [];
    }

    /**
     * The marks as a fragment of XMP, ready to be put into a document.
     *
     * The units and the origin are written down beside the numbers rather than left to be known,
     * so that a paper found on its own says what its own numbers mean.
     *
     * @return string
     */
    public function toXmp(): string
    {
        $written = json_encode([
            'units' => 'mm',
            'origin' => 'top-left',
            'anchors' => array_map(fn(SignatureAnchor $a): array => $a->toArray(), $this->anchors),
        ], JSON_UNESCAPED_SLASHES);

        return sprintf(
            '<rdf:Description rdf:about="" xmlns:%s="%s"><%s:%s>%s</%s:%s></rdf:Description>',
            self::NAMESPACE_PREFIX,
            self::NAMESPACE_URI,
            self::NAMESPACE_PREFIX,
            self::PROPERTY,
            htmlspecialchars((string)$written, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            self::NAMESPACE_PREFIX,
            self::PROPERTY,
        );
    }

    /**
     * The marks a finished document carries, if it carries any.
     *
     * @param string $pdf The document.
     * @return self
     */
    public static function fromPdf(string $pdf): self
    {
        $anchors = new self();

        try {
            $written = self::metadataOf($pdf);
        } catch (Throwable) {
            // A document that cannot be taken apart carries nothing as far as this is concerned.
            return $anchors;
        }

        if ($written === null) {
            return $anchors;
        }

        $matched = [];
        $found = preg_match(
            '#<' . self::NAMESPACE_PREFIX . ':' . self::PROPERTY . '>(.*?)</'
                . self::NAMESPACE_PREFIX . ':' . self::PROPERTY . '>#s',
            $written,
            $matched,
        );

        if ($found !== 1) {
            return $anchors;
        }

        $read = json_decode(htmlspecialchars_decode($matched[1], ENT_XML1 | ENT_QUOTES), true);
        if (!is_array($read) || !is_array($read['anchors'] ?? null)) {
            return $anchors;
        }

        foreach ($read['anchors'] as $anchor) {
            $one = is_array($anchor) ? SignatureAnchor::fromArray($anchor) : null;
            if ($one instanceof SignatureAnchor) {
                $anchors->add($one);
            }
        }

        return $anchors;
    }

    /**
     * The metadata of a document, followed from its catalogue.
     *
     * @param string $pdf The document.
     * @return string|null Nothing where it carries none.
     */
    private static function metadataOf(string $pdf): ?string
    {
        $source = new SourceDocument($pdf);

        $root = $source->getTrailer()['root'] ?? null;
        if (!is_string($root)) {
            return null;
        }

        $reference = self::entry($source->getObject($root), 'Metadata');
        if ($reference === null) {
            return null;
        }

        foreach ($source->getObject($reference) as $part) {
            if (is_array($part) && ($part[0] ?? '') === 'stream' && is_string($part[1] ?? null)) {
                return $part[1];
            }
        }

        return null;
    }

    /**
     * What a dictionary says under one name.
     *
     * The parser hands a dictionary over as its items one after another, keys and values
     * alternating, so the value wanted is whatever follows the key.
     *
     * @param array<mixed> $object The object as it was parsed.
     * @param string $key The name to look under.
     * @return string|null The reference it holds, where it holds one.
     */
    private static function entry(array $object, string $key): ?string
    {
        foreach ($object as $part) {
            if (!is_array($part) || ($part[0] ?? '') !== '<<' || !is_array($part[1] ?? null)) {
                continue;
            }

            $items = $part[1];
            foreach ($items as $at => $item) {
                if (!is_array($item) || ($item[0] ?? '') !== '/' || ($item[1] ?? '') !== $key) {
                    continue;
                }

                $value = $items[$at + 1] ?? null;
                if (is_array($value) && ($value[0] ?? '') === 'objref') {
                    return (string)($value[1] ?? '');
                }
            }
        }

        return null;
    }
}
