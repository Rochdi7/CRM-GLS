<?php

declare(strict_types=1);

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Media paths use the FIRST 8 CHARACTERS of the media UUID as directory
 * (instead of the default auto-increment id), so public URLs look like:
 *
 *   https://app.test/media/3f2a9c1d/photo.jpg
 *
 * - no sequential ids leaked in URLs,
 * - short, shareable links (full UUID kept on the media row for lookups).
 *
 * Collision note: 8 hex-ish chars across a school-CRM-sized library is
 * safe in practice; a collision would only matter if two media also shared
 * the same file name. Revisit only at very large scale.
 */
final class ShortUuidPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->prefix($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->prefix($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->prefix($media).'/responsive-images/';
    }

    private function prefix(Media $media): string
    {
        return substr((string) $media->uuid, 0, 8);
    }
}
