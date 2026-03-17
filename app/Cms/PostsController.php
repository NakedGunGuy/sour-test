<?php

declare(strict_types=1);

namespace App\Cms;

use Sauerkraut\CMS\CmsController;
use Sauerkraut\Request;

class PostsController extends CmsController
{
    protected function beforeStore(string $table, array $data, Request $request): array
    {
        // Auto-generate slug from title if left empty
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->slugify($data['title']);
        }

        return $data;
    }

    protected function beforeUpdate(string $table, string $id, array $data, Request $request): array
    {
        // Re-generate slug if title changed and slug is empty
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->slugify($data['title']);
        }

        return $data;
    }

    private function slugify(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}
