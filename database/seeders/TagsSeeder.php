<?php

declare(strict_types=1);

use Sauerkraut\Database\Seeder;

class TagsSeeder extends Seeder
{
    public function run(): void
    {
        $tags = ['php', 'css', 'htmx', 'javascript'];

        foreach ($tags as $tag) {
            $this->db->execute('INSERT OR IGNORE INTO tags (name) VALUES (?)', [$tag]);
        }
    }
}
