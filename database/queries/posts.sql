-- name: GetPost :one
SELECT * FROM posts WHERE id = ?;

-- name: ListPosts :many
SELECT * FROM posts ORDER BY created_at DESC;

-- name: ListPostsByCategory :many
SELECT * FROM posts WHERE category_id = ? ORDER BY created_at DESC;

-- name: CreatePost :exec
INSERT INTO posts (title, slug, body, category_id, published) VALUES (?, ?, ?, ?, ?);

-- name: UpdatePost :exec
UPDATE posts SET title = ?, slug = ?, body = ?, category_id = ?, published = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?;

-- name: DeletePost :exec
DELETE FROM posts WHERE id = ?;

-- name: GetCategory :one
SELECT * FROM categories WHERE id = ?;

-- name: ListCategories :many
SELECT * FROM categories ORDER BY name;

-- name: CreateCategory :exec
INSERT INTO categories (name, slug) VALUES (?, ?);

-- name: ListTags :many
SELECT * FROM tags ORDER BY name;

-- name: TagsForPost :many
SELECT t.* FROM tags t JOIN post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = ?;
