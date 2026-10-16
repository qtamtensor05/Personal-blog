<?php

namespace Tests\Unit\Modules\Media;

use App\Modules\Media\MediaReferenceFinder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaReferenceFinderTest extends TestCase
{
    private MediaReferenceFinder $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = new MediaReferenceFinder;
    }

    #[Test]
    public function it_finds_a_media_id_nested_in_a_document(): void
    {
        $document = ['type' => 'doc', 'content' => [['type' => 'image', 'attrs' => ['mediaId' => 7]]]];

        $this->assertTrue($this->finder->documentContains($document, 7));
    }

    #[Test]
    public function it_matches_string_ids_and_known_keys_only(): void
    {
        $this->assertTrue($this->finder->documentContains(['logoMediaId' => '12'], 12));
        $this->assertFalse($this->finder->documentContains(['unrelated' => 12], 12));
        $this->assertFalse($this->finder->documentContains(['mediaId' => 13], 12));
    }

    #[Test]
    public function it_ignores_scalars_and_empty_documents(): void
    {
        $this->assertFalse($this->finder->documentContains(null, 1));
        $this->assertFalse($this->finder->documentContains('mediaId', 1));
        $this->assertFalse($this->finder->documentContains([], 1));
    }
}
