<?php
/**
 * Renders an image with a fallback URL.
 *
 * This element displays an image from an Image entity or a direct URL. If the
 * image is not available or invalid, it shows a fallback image.
 *
 * Variables expected:
 * - `image`: The Image entity, an array with a `url` key, or a string URL.
 * - `fallback`: The URL to use if the primary image is not available.
 * - `options`: An array of HTML attributes for the `<img>` tag.
 */

$imageUrl = $fallback; // Default to fallback

if (!empty($image)) {
    // Assuming $image is an Image entity with a direct_url property
    if (is_object($image) && property_exists($image, 'direct_url') && !empty($image->direct_url)) {
        $imageUrl = $this->Url->build($image->direct_url, ['fullBase' => true]);
    }
    // Assuming $image is an array with a direct_url key
    elseif (is_array($image) && !empty($image['direct_url'])) {
        $imageUrl = $this->Url->build($image['direct_url'], ['fullBase' => true]);
    }
    // Assuming $image is a string URL
    elseif (is_string($image)) {
        $imageUrl = $this->Url->build($image, ['fullBase' => true]);
    }
}

echo $this->Html->image($imageUrl, $options ?? []);
