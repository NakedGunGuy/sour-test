<?php

declare(strict_types=1);

use Sauerkraut\Database\Seeder;

class PostsSeeder extends Seeder
{
    public function run(): void
    {
        $existing = $this->db->queryOne('SELECT COUNT(*) as cnt FROM posts');

        if ((int) $existing['cnt'] > 0) {
            return;
        }

        $this->seedPosts();
        $this->seedPostTags();
    }

    private function seedPosts(): void
    {
        $posts = [
            ['Getting Started with Sauerkraut', 'getting-started', 'A quick intro to the framework.', 1, 1],
            ['Design Tokens in CSS', 'design-tokens', 'How to use HSL-based design tokens for theming.', 2, 1],
            ['HTMX Patterns', 'htmx-patterns', 'Common patterns for htmx with server-rendered partials.', 1, 0],
        ];

        foreach ($posts as [$title, $slug, $body, $categoryId, $published]) {
            $this->db->execute(
                'INSERT INTO posts (title, slug, body, category_id, published) VALUES (?, ?, ?, ?, ?)',
                [$title, $slug, $body, $categoryId, $published],
            );
        }
    }

    private function seedPostTags(): void
    {
        $postTags = [
            [1, 1], [1, 3],
            [2, 2],
            [3, 1], [3, 3], [3, 4],
        ];

        foreach ($postTags as [$postId, $tagId]) {
            $this->db->execute(
                'INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)',
                [$postId, $tagId],
            );
        }
    }
}
