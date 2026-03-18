<?php

declare(strict_types=1);

use Sauerkraut\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Technology', 'technology'],
            ['Design', 'design'],
            ['Business', 'business'],
        ];

        foreach ($categories as [$name, $slug]) {
            $this->db->execute(
                'INSERT OR IGNORE INTO categories (name, slug) VALUES (?, ?)',
                [$name, $slug],
            );
        }
    }
}
