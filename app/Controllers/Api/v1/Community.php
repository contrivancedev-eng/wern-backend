<?php
namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Models\Operation;

class Community extends ApiController
{
    private $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $videoTypes = ['mp4', 'mov', 'avi', 'webm', 'mkv'];

    // Posts/comments can only be edited within this many minutes of publishing.
    const EDIT_WINDOW_MINUTES = 30;

    public function create_post()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $content = trim((string)($this->post_value('content') ?: $this->post_value('text')));
        $privacy = trim((string)$this->post_value('privacy'));
        if ($privacy === '') $privacy = 'public';

        $mediaFiles = array_merge(
            $this->collect_files('media'),
            $this->collect_files('image'),
            $this->collect_files('video')
        );
        $optionalFields = $this->optional_post_fields();
        if ($content === '' && empty($mediaFiles) && !$this->has_post_data($optionalFields)) {
            return $this->error_response("Post content or media is required.");
        }

        $insert = array_merge([
            'user_id'    => $auth['user_id'],
            'content'    => $content !== '' ? $content : null,
            'privacy'    => $privacy,
            'status'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $optionalFields);

        $postId = $operation->insert_data('community_posts', $insert);

        if (!$postId) return $this->error_response("Unable to create post.");

        $uploaded = $this->save_media_files($operation, $postId, $mediaFiles);
        if (!$uploaded['status']) return $this->error_response($uploaded['error']);

        return $this->success_response("Post created successfully.", $this->get_post_response($operation, $postId, $auth['user_id']));
    }

    public function feed()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $limit = (int)($this->input_get('limit') ?: 20);
        $offset = (int)($this->input_get('offset') ?: 0);
        if ($limit < 1 || $limit > 50) $limit = 20;
        if ($offset < 0) $offset = 0;

        $rows = $operation->db->query(
            "SELECT p.*, u.full_name, u.nickname, u.user_image
             FROM community_posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.status = 1
               AND p.id NOT IN (SELECT post_id FROM community_reports WHERE user_id = ?)
             ORDER BY p.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            [$auth['user_id']]
        )->getResult();

        $posts = [];
        foreach ($rows as $row) {
            $posts[] = $this->format_post($operation, $row, $auth['user_id']);
        }

        return $this->success_response("Community feed fetched successfully.", [
            'posts' => $posts,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function my_posts()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $rows = $operation->db->query(
            "SELECT p.*, u.full_name, u.nickname, u.user_image
             FROM community_posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.user_id = ? AND p.status != 0
             ORDER BY p.id DESC",
            [$auth['user_id']]
        )->getResult();

        $posts = [];
        foreach ($rows as $row) {
            $posts[] = $this->format_post($operation, $row, $auth['user_id']);
        }

        return $this->success_response("My community posts fetched successfully.", $posts);
    }

    public function post_detail()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->input_get('post_id');
        if ($postId <= 0) return $this->error_response("post_id is required.");

        $post = $this->get_post_row($operation, $postId);
        if (!$post) return $this->error_response("Post not found.");

        return $this->success_response("Post detail fetched successfully.", $this->format_post($operation, $post, $auth['user_id'], true));
    }

    public function update_post()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->post_value('post_id');
        if ($postId <= 0) return $this->error_response("post_id is required.");

        $post = $this->get_post_row($operation, $postId);
        if (!$post) return $this->error_response("Post not found.");
        if ((int)$post->user_id !== $auth['user_id']) return $this->error_response("You can update only your own post.");
        if (!$this->within_edit_window($post->created_at)) {
            return $this->error_response("This post can no longer be edited. Posts can only be edited within " . self::EDIT_WINDOW_MINUTES . " minutes of publishing.");
        }

        $content = trim((string)($this->post_value('content') ?: $this->post_value('text')));
        $privacy = trim((string)$this->post_value('privacy'));
        $update = ['updated_at' => date('Y-m-d H:i:s')];
        if ($content !== '') $update['content'] = $content;
        if ($privacy !== '') $update['privacy'] = $privacy;
        $update = array_merge($update, $this->optional_post_fields(false));

        $operation->update_data('community_posts', ['id' => $postId], $update);

        $mediaFiles = array_merge(
            $this->collect_files('media'),
            $this->collect_files('image'),
            $this->collect_files('video')
        );
        if (!empty($mediaFiles)) {
            $uploaded = $this->save_media_files($operation, $postId, $mediaFiles);
            if (!$uploaded['status']) return $this->error_response($uploaded['error']);
        }

        return $this->success_response("Post updated successfully.", $this->get_post_response($operation, $postId, $auth['user_id']));
    }

    public function delete_post()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->post_value('post_id');
        if ($postId <= 0) return $this->error_response("post_id is required.");

        $post = $this->get_post_row($operation, $postId);
        if (!$post) return $this->error_response("Post not found.");
        if ((int)$post->user_id !== $auth['user_id']) return $this->error_response("You can delete only your own post.");

        $operation->update_data('community_posts', ['id' => $postId], [
            'status' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success_response("Post deleted successfully.");
    }

    public function like_post()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->post_value('post_id');
        if ($postId <= 0) return $this->error_response("post_id is required.");
        if (!$this->get_post_row($operation, $postId)) return $this->error_response("Post not found.");

        $exists = $operation->get_data('community_likes', 'id', [
            'post_id' => $postId,
            'user_id' => $auth['user_id'],
        ]);

        if ($exists['num_rows'] == 0) {
            $operation->insert_data('community_likes', [
                'post_id' => $postId,
                'user_id' => $auth['user_id'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success_response("Post liked successfully.", $this->counts($operation, $postId, $auth['user_id']));
    }

    public function unlike_post()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->post_value('post_id');
        if ($postId <= 0) return $this->error_response("post_id is required.");

        $operation->delete_data('community_likes', [
            'post_id' => $postId,
            'user_id' => $auth['user_id'],
        ]);

        return $this->success_response("Post unliked successfully.", $this->counts($operation, $postId, $auth['user_id']));
    }

    public function add_comment()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->post_value('post_id');
        $comment = trim((string)$this->post_value('comment'));
        $parentId = (int)$this->post_value('parent_id');

        if ($postId <= 0) return $this->error_response("post_id is required.");
        if ($comment === '') return $this->error_response("Comment is required.");
        if (!$this->get_post_row($operation, $postId)) return $this->error_response("Post not found.");

        $commentId = $operation->insert_data('community_comments', [
            'post_id' => $postId,
            'user_id' => $auth['user_id'],
            'parent_id' => $parentId > 0 ? $parentId : null,
            'comment' => $comment,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success_response("Comment added successfully.", $this->get_comment($operation, $commentId));
    }

    public function get_comments()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->input_get('post_id');
        if ($postId <= 0) return $this->error_response("post_id is required.");

        $comments = $operation->db->query(
            "SELECT c.*, u.full_name, u.nickname, u.user_image
             FROM community_comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.post_id = ? AND c.status = 1
             ORDER BY c.id ASC",
            [$postId]
        )->getResult();

        return $this->success_response("Comments fetched successfully.", array_map([$this, 'decorate_comment'], $comments));
    }

    public function update_comment()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $commentId = (int)$this->post_value('comment_id');
        $comment = trim((string)$this->post_value('comment'));
        if ($commentId <= 0) return $this->error_response("comment_id is required.");
        if ($comment === '') return $this->error_response("Comment is required.");

        $row = $operation->get_data('community_comments', '*', ['id' => $commentId, 'status' => 1]);
        if ($row['num_rows'] == 0) return $this->error_response("Comment not found.");
        if ((int)$row['result'][0]->user_id !== $auth['user_id']) return $this->error_response("You can update only your own comment.");
        if (!$this->within_edit_window($row['result'][0]->created_at)) {
            return $this->error_response("This comment can no longer be edited. Comments can only be edited within " . self::EDIT_WINDOW_MINUTES . " minutes of publishing.");
        }

        $operation->update_data('community_comments', ['id' => $commentId], [
            'comment' => $comment,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success_response("Comment updated successfully.", $this->get_comment($operation, $commentId));
    }

    public function delete_comment()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $commentId = (int)$this->post_value('comment_id');
        if ($commentId <= 0) return $this->error_response("comment_id is required.");

        $row = $operation->get_data('community_comments', '*', ['id' => $commentId, 'status' => 1]);
        if ($row['num_rows'] == 0) return $this->error_response("Comment not found.");

        $comment = $row['result'][0];
        $post = $this->get_post_row($operation, (int)$comment->post_id);
        if ((int)$comment->user_id !== $auth['user_id'] && (!$post || (int)$post->user_id !== $auth['user_id'])) {
            return $this->error_response("You can delete only your own comment or comments on your post.");
        }

        $operation->update_data('community_comments', ['id' => $commentId], [
            'status' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success_response("Comment deleted successfully.");
    }

    public function save_post()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->post_value('post_id');
        if ($postId <= 0) return $this->error_response("post_id is required.");
        if (!$this->get_post_row($operation, $postId)) return $this->error_response("Post not found.");

        $exists = $operation->get_data('community_saved_posts', 'id', [
            'post_id' => $postId,
            'user_id' => $auth['user_id'],
        ]);
        if ($exists['num_rows'] == 0) {
            $operation->insert_data('community_saved_posts', [
                'post_id' => $postId,
                'user_id' => $auth['user_id'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success_response("Post saved successfully.");
    }

    public function unsave_post()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->post_value('post_id');
        if ($postId <= 0) return $this->error_response("post_id is required.");

        $operation->delete_data('community_saved_posts', [
            'post_id' => $postId,
            'user_id' => $auth['user_id'],
        ]);

        return $this->success_response("Post removed from saved list.");
    }

    public function saved_posts()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $rows = $operation->db->query(
            "SELECT p.*, u.full_name, u.nickname, u.user_image
             FROM community_saved_posts s
             JOIN community_posts p ON p.id = s.post_id
             JOIN users u ON u.id = p.user_id
             WHERE s.user_id = ? AND p.status = 1
               AND p.id NOT IN (SELECT post_id FROM community_reports WHERE user_id = ?)
             ORDER BY s.id DESC",
            [$auth['user_id'], $auth['user_id']]
        )->getResult();

        $posts = [];
        foreach ($rows as $row) {
            $posts[] = $this->format_post($operation, $row, $auth['user_id']);
        }

        return $this->success_response("Saved posts fetched successfully.", $posts);
    }

    public function report_post()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $postId = (int)$this->post_value('post_id');
        $reason = trim((string)$this->post_value('reason'));
        if ($postId <= 0) return $this->error_response("post_id is required.");
        if ($reason === '') return $this->error_response("Reason is required.");
        if (!$this->get_post_row($operation, $postId)) return $this->error_response("Post not found.");

        // Avoid duplicate report rows for the same user + post.
        $already = $operation->get_data('community_reports', 'id', [
            'post_id' => $postId,
            'user_id' => $auth['user_id'],
        ]);
        if ($already['num_rows'] == 0) {
            $operation->insert_data('community_reports', [
                'post_id' => $postId,
                'user_id' => $auth['user_id'],
                'reason' => $reason,
                'status' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success_response("Post reported successfully. It has been hidden from your feed.", [
            "post_id" => $postId,
            "hidden"  => true,
        ]);
    }

    private function within_edit_window($createdAt)
    {
        if (empty($createdAt)) return true; // be lenient if no timestamp is stored
        return (time() - strtotime($createdAt)) <= (self::EDIT_WINDOW_MINUTES * 60);
    }

    private function auth_user($operation, $method)
    {
        $token = $method === 'get' ? $this->input_get('token') : $this->post_value('token');
        if (!$token) return ['status' => false, 'error' => 'Token is required.'];

        $jwt = $operation->get_user_id_from_token($token);
        if (!$jwt['status']) return ['status' => false, 'error' => $jwt['error']];

        return ['status' => true, 'user_id' => (int)$jwt['user_id']];
    }

    private function post_value($key)
    {
        $request = service('request');
        $value = $request->getPost($key);
        if ($value !== null) return is_string($value) ? trim($value) : $value;

        $json = $this->json_body();
        return $json[$key] ?? null;
    }

    private function json_body()
    {
        static $decoded = null;
        if ($decoded !== null) return $decoded;

        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function get_post_row($operation, $postId)
    {
        return $operation->db->query(
            "SELECT p.*, u.full_name, u.nickname, u.user_image
             FROM community_posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = ? AND p.status = 1",
            [$postId]
        )->getRow();
    }

    private function get_post_response($operation, $postId, $viewerId)
    {
        $post = $this->get_post_row($operation, $postId);
        return $post ? $this->format_post($operation, $post, $viewerId, true) : null;
    }

    private function format_post($operation, $post, $viewerId, $includeComments = false)
    {
        $media = $operation->get_data('community_post_media', '*', ['post_id' => $post->id], 'id', 'ASC');
        $counts = $this->counts($operation, (int)$post->id, $viewerId);

        $data = [
            'id' => (int)$post->id,
            'user_id' => (int)$post->user_id,
            'full_name' => $post->full_name,
            'nickname' => $post->nickname,
            'user_image' => $post->user_image,
            'text' => $post->content,
            'content' => $post->content,
            'privacy' => $post->privacy,
            'feeling_activity' => $post->feeling_activity ?? null,
            'check_in' => $post->check_in ?? null,
            'walk_for_cause' => $post->walk_for_cause ?? null,
            'tag_achievement' => $post->tag_achievement ?? null,
            'media' => $media['result'] ?? [],
            'like_count' => $counts['like_count'],
            'comment_count' => $counts['comment_count'],
            'is_liked' => $counts['is_liked'],
            'is_saved' => $counts['is_saved'],
            'created_at' => $post->created_at,
            'created_at_ts' => !empty($post->created_at) ? strtotime($post->created_at) : null,
            'time_ago' => $this->time_ago($post->created_at),
            'updated_at' => $post->updated_at,
        ];

        if ($includeComments) {
            $rows = $operation->db->query(
                "SELECT c.*, u.full_name, u.nickname, u.user_image
                 FROM community_comments c
                 JOIN users u ON u.id = c.user_id
                 WHERE c.post_id = ? AND c.status = 1
                 ORDER BY c.id ASC",
                [$post->id]
            )->getResult();
            $data['comments'] = array_map([$this, 'decorate_comment'], $rows);
        }

        return $data;
    }

    private function optional_post_fields($includeEmptyAsNull = true)
    {
        $fields = [
            'feeling_activity' => ['feeling_activity', 'felling_activity', 'feeling', 'activity'],
            'check_in' => ['check_in', 'checkin', 'location'],
            'walk_for_cause' => ['walk_for_cause', 'cause'],
            'tag_achievement' => ['tag_achievement', 'tag_achivement', 'achievement'],
        ];
        $data = [];

        foreach ($fields as $field => $aliases) {
            $value = $this->first_post_value($aliases);
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $value = trim((string)$value);

            if ($value !== '') {
                $data[$field] = $value;
            } elseif ($includeEmptyAsNull) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function first_post_value($keys)
    {
        foreach ($keys as $key) {
            $value = $this->post_value($key);
            if ($value !== null && $value !== '') return $value;
        }
        return null;
    }

    private function has_post_data($data)
    {
        foreach ($data as $value) {
            if ($value !== null && $value !== '') return true;
        }
        return false;
    }

    private function counts($operation, $postId, $viewerId)
    {
        $like = $operation->db->query("SELECT COUNT(*) AS c FROM community_likes WHERE post_id = ?", [$postId])->getRow();
        $comment = $operation->db->query("SELECT COUNT(*) AS c FROM community_comments WHERE post_id = ? AND status = 1", [$postId])->getRow();
        $isLiked = $operation->db->query("SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?", [$postId, $viewerId])->getRow();
        $isSaved = $operation->db->query("SELECT id FROM community_saved_posts WHERE post_id = ? AND user_id = ?", [$postId, $viewerId])->getRow();

        return [
            'like_count' => (int)($like->c ?? 0),
            'comment_count' => (int)($comment->c ?? 0),
            'is_liked' => $isLiked ? true : false,
            'is_saved' => $isSaved ? true : false,
        ];
    }

    private function get_comment($operation, $commentId)
    {
        $row = $operation->db->query(
            "SELECT c.*, u.full_name, u.nickname, u.user_image
             FROM community_comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.id = ?",
            [$commentId]
        )->getRow();
        return $this->decorate_comment($row);
    }

    private function decorate_comment($row)
    {
        if ($row) {
            $row->created_at_ts = !empty($row->created_at) ? strtotime($row->created_at) : null;
            $row->time_ago      = $this->time_ago($row->created_at ?? null);
        }
        return $row;
    }

    /**
     * Relative "time ago" computed entirely on the server, so a just-published
     * post/comment always reads "Just now" regardless of the client's timezone.
     */
    private function time_ago($datetime)
    {
        if (empty($datetime)) return '';
        $ts = strtotime($datetime);
        if ($ts === false) return '';

        $diff = time() - $ts;
        if ($diff < 0) $diff = 0; // guard against minor clock skew (future timestamp)

        if ($diff < 60)     return 'Just now';
        if ($diff < 3600)   { $m = floor($diff / 60);    return $m . ' min'  . ($m > 1 ? 's' : '') . ' ago'; }
        if ($diff < 86400)  { $h = floor($diff / 3600);  return $h . ' hour' . ($h > 1 ? 's' : '') . ' ago'; }
        if ($diff < 604800) { $d = floor($diff / 86400); return $d . ' day'  . ($d > 1 ? 's' : '') . ' ago'; }
        return date('M d, Y', $ts);
    }

    private function collect_files($field)
    {
        if (empty($_FILES[$field])) return [];

        $files = $_FILES[$field];
        if (is_array($files['name'])) {
            $out = [];
            foreach ($files['name'] as $i => $name) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $out[] = [
                    'name' => $name,
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
            }
            return $out;
        }

        if (($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [];
        return [$files];
    }

    private function save_media_files($operation, $postId, $files)
    {
        foreach ($files as $file) {
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                return ['status' => false, 'error' => 'Media upload failed.'];
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $this->imageTypes, true)) {
                $mediaType = 'image';
                $folder = 'images';
            } elseif (in_array($ext, $this->videoTypes, true)) {
                $mediaType = 'video';
                $folder = 'videos';
            } else {
                return ['status' => false, 'error' => 'Unsupported media type.'];
            }

            $physicalDir = rtrim(WEb_ASSIST_PHYSICAL_PATH, '/\\') . DIRECTORY_SEPARATOR . 'community' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
            if (!is_dir($physicalDir)) {
                mkdir($physicalDir, 0777, true);
            }

            $fileName = uniqid('community_', true) . '.' . $ext;
            $target = $physicalDir . $fileName;
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                return ['status' => false, 'error' => 'Unable to save media file.'];
            }

            $url = rtrim(WEb_BASE_PATH, '/') . '/public/community/' . $folder . '/' . $fileName;
            $operation->insert_data('community_post_media', [
                'post_id' => $postId,
                'media_type' => $mediaType,
                'media_url' => $url,
                'thumbnail_url' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return ['status' => true];
    }
}
?>
