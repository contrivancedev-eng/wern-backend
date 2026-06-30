<?php
namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Models\Operation;

class Challenge extends ApiController
{
    private $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function challenge_list()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $limit = (int)($this->input_get('limit') ?: 20);
        $offset = (int)($this->input_get('offset') ?: 0);
        $type = trim((string)$this->input_get('type'));
        $search = trim((string)$this->input_get('search'));
        if ($limit < 1 || $limit > 50) $limit = 20;
        if ($offset < 0) $offset = 0;

        $where = ["c.status = 1"];
        $params = [];
        if ($type === 'active') {
            $where[] = "c.start_date <= CURDATE() AND c.end_date >= CURDATE()";
        } elseif ($type === 'upcoming') {
            $where[] = "c.start_date > CURDATE()";
        } elseif ($type === 'past') {
            $where[] = "c.end_date < CURDATE()";
        }

        if ($search !== '') {
            $where[] = "(c.title LIKE ? OR c.difficulty LIKE ? OR c.reward_text LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $rows = $operation->db->query(
            "SELECT c.*
             FROM challenges c
             WHERE " . implode(' AND ', $where) . "
             ORDER BY c.end_date ASC, c.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        )->getResult();

        $challenges = [];
        foreach ($rows as $row) {
            $challenges[] = $this->format_challenge($operation, $row, $auth['user_id']);
        }

        return $this->success_response("Challenges fetched successfully.", [
            'challenges' => $challenges,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function detail()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $challengeId = (int)$this->input_get('challenge_id');
        if ($challengeId <= 0) return $this->error_response("challenge_id is required.");

        $challenge = $this->get_challenge_row($operation, $challengeId);
        if (!$challenge) return $this->error_response("Challenge not found.");

        return $this->success_response("Challenge detail fetched successfully.", $this->format_challenge($operation, $challenge, $auth['user_id'], true));
    }

    public function join()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $challengeId = (int)$this->post_value('challenge_id');
        if ($challengeId <= 0) return $this->error_response("challenge_id is required.");

        $challenge = $this->get_challenge_row($operation, $challengeId);
        if (!$challenge) return $this->error_response("Challenge not found.");

        $exists = $operation->get_data('challenge_participants', '*', [
            'challenge_id' => $challengeId,
            'user_id' => $auth['user_id'],
        ]);

        $progress = $this->calculate_progress($operation, $challenge, $auth['user_id']);
        $completedAt = $progress >= (int)$challenge->target_steps ? date('Y-m-d H:i:s') : null;

        if ($exists['num_rows'] > 0) {
            $operation->update_data('challenge_participants', ['id' => $exists['result'][0]->id], [
                'status' => 'joined',
                'progress_steps' => $progress,
                'completed_at' => $completedAt,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $operation->insert_data('challenge_participants', [
                'challenge_id' => $challengeId,
                'user_id' => $auth['user_id'],
                'status' => 'joined',
                'progress_steps' => $progress,
                'completed_at' => $completedAt,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success_response("Challenge joined successfully.", $this->format_challenge($operation, $challenge, $auth['user_id'], true));
    }

    public function leave()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $challengeId = (int)$this->post_value('challenge_id');
        if ($challengeId <= 0) return $this->error_response("challenge_id is required.");

        $operation->update_data('challenge_participants', [
            'challenge_id' => $challengeId,
            'user_id' => $auth['user_id'],
        ], [
            'status' => 'left',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success_response("Challenge left successfully.");
    }

    public function my_challenges()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $rows = $operation->db->query(
            "SELECT c.*
             FROM challenge_participants p
             JOIN challenges c ON c.id = p.challenge_id
             WHERE p.user_id = ? AND p.status = 'joined' AND c.status = 1
             ORDER BY c.end_date ASC, c.id DESC",
            [$auth['user_id']]
        )->getResult();

        $challenges = [];
        foreach ($rows as $row) {
            $challenges[] = $this->format_challenge($operation, $row, $auth['user_id']);
        }

        return $this->success_response("My challenges fetched successfully.", $challenges);
    }

    public function leaderboard()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $challengeId = (int)$this->input_get('challenge_id');
        if ($challengeId <= 0) return $this->error_response("challenge_id is required.");

        $challenge = $this->get_challenge_row($operation, $challengeId);
        if (!$challenge) return $this->error_response("Challenge not found.");

        $this->sync_joined_progress($operation, $challenge);

        return $this->success_response("Challenge leaderboard fetched successfully.", [
            'challenge' => $this->basic_challenge($challenge),
            'my_rank' => $this->user_rank($operation, $challengeId, $auth['user_id']),
            'leaderboard' => $this->leaderboard_rows($operation, $challengeId),
        ]);
    }

    public function update_progress()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $challengeId = (int)$this->post_value('challenge_id');
        if ($challengeId <= 0) return $this->error_response("challenge_id is required.");

        $challenge = $this->get_challenge_row($operation, $challengeId);
        if (!$challenge) return $this->error_response("Challenge not found.");

        $progress = $this->calculate_progress($operation, $challenge, $auth['user_id']);
        $manualProgress = $this->post_value('progress_steps');
        if ($manualProgress !== null && $manualProgress !== '' && is_numeric($manualProgress)) {
            $progress = max($progress, (int)$manualProgress);
        }

        $completedAt = $progress >= (int)$challenge->target_steps ? date('Y-m-d H:i:s') : null;
        $exists = $operation->get_data('challenge_participants', '*', [
            'challenge_id' => $challengeId,
            'user_id' => $auth['user_id'],
        ]);
        if ($exists['num_rows'] == 0) return $this->error_response("Join challenge before updating progress.");

        $operation->update_data('challenge_participants', ['id' => $exists['result'][0]->id], [
            'progress_steps' => $progress,
            'completed_at' => $completedAt,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success_response("Challenge progress updated successfully.", $this->format_challenge($operation, $challenge, $auth['user_id'], true));
    }

    public function create()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $title = trim((string)$this->post_value('title'));
        $difficulty = trim((string)$this->post_value('difficulty'));
        $targetSteps = (int)$this->post_value('target_steps');
        $startDate = trim((string)$this->post_value('start_date'));
        $endDate = trim((string)$this->post_value('end_date'));
        $rewardText = trim((string)$this->post_value('reward_text'));
        $description = trim((string)$this->post_value('description'));

        if ($title === '') return $this->error_response("Title is required.");
        if ($difficulty === '') return $this->error_response("Difficulty is required.");
        if ($targetSteps <= 0) return $this->error_response("target_steps is required.");
        if ($startDate === '') return $this->error_response("start_date is required.");
        if ($endDate === '') return $this->error_response("end_date is required.");
        if ($rewardText === '') return $this->error_response("reward_text is required.");

        $imageUrl = null;
        $file = $this->first_file('image');
        if ($file) {
            $uploaded = $this->save_image($file);
            if (!$uploaded['status']) return $this->error_response($uploaded['error']);
            $imageUrl = $uploaded['url'];
        } else {
            $imageUrl = trim((string)$this->post_value('image_url'));
            if ($imageUrl === '') $imageUrl = null;
        }

        $challengeId = $operation->insert_data('challenges', [
            'creator_id' => $auth['user_id'],
            'title' => $title,
            'difficulty' => $difficulty,
            'target_steps' => $targetSteps,
            'start_date' => date('Y-m-d', strtotime($startDate)),
            'end_date' => date('Y-m-d', strtotime($endDate)),
            'reward_text' => $rewardText,
            'description' => $description !== '' ? $description : null,
            'image_url' => $imageUrl,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$challengeId) return $this->error_response("Unable to create challenge.");

        return $this->success_response("Challenge created successfully.", $this->format_challenge($operation, $this->get_challenge_row($operation, $challengeId), $auth['user_id'], true));
    }

    public function update($id = null)
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $challengeId = (int)($this->post_value('challenge_id') ?: $id);
        if ($challengeId <= 0) return $this->error_response("challenge_id is required.");

        $challenge = $this->get_challenge_row($operation, $challengeId);
        if (!$challenge) return $this->error_response("Challenge not found.");
        if ((int)$challenge->creator_id !== $auth['user_id']) return $this->error_response("You can update only your own challenge.");

        $update = ['updated_at' => date('Y-m-d H:i:s')];
        foreach (['title', 'difficulty', 'reward_text', 'description'] as $field) {
            $value = trim((string)$this->post_value($field));
            if ($value !== '') $update[$field] = $value;
        }
        foreach (['start_date', 'end_date'] as $field) {
            $value = trim((string)$this->post_value($field));
            if ($value !== '') $update[$field] = date('Y-m-d', strtotime($value));
        }
        $targetSteps = $this->post_value('target_steps');
        if ($targetSteps !== null && $targetSteps !== '' && is_numeric($targetSteps)) $update['target_steps'] = (int)$targetSteps;

        $file = $this->first_file('image');
        if ($file) {
            $uploaded = $this->save_image($file);
            if (!$uploaded['status']) return $this->error_response($uploaded['error']);
            $update['image_url'] = $uploaded['url'];
        }
        $imageUrl = trim((string)$this->post_value('image_url'));
        if ($imageUrl !== '') $update['image_url'] = $imageUrl;

        $operation->update_data('challenges', ['id' => $challengeId], $update);

        return $this->success_response("Challenge updated successfully.", $this->format_challenge($operation, $this->get_challenge_row($operation, $challengeId), $auth['user_id'], true));
    }

    public function delete($id = null)
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $challengeId = (int)($this->post_value('challenge_id') ?: $id);
        if ($challengeId <= 0) return $this->error_response("challenge_id is required.");

        $challenge = $this->get_challenge_row($operation, $challengeId);
        if (!$challenge) return $this->error_response("Challenge not found.");
        if ((int)$challenge->creator_id !== $auth['user_id']) return $this->error_response("You can delete only your own challenge.");

        $operation->update_data('challenges', ['id' => $challengeId], [
            'status' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success_response("Challenge deleted successfully.");
    }

    private function format_challenge($operation, $challenge, $viewerId, $includeDetail = false)
    {
        $participant = $this->participant_row($operation, (int)$challenge->id, $viewerId);
        $progress = $participant ? max((int)$participant->progress_steps, $this->calculate_progress($operation, $challenge, $viewerId)) : 0;
        $percent = (int)$challenge->target_steps > 0 ? min(100, round(($progress / (int)$challenge->target_steps) * 100)) : 0;
        $participantCount = $this->participant_count($operation, (int)$challenge->id);

        if ($participant && $progress !== (int)$participant->progress_steps) {
            $operation->update_data('challenge_participants', ['id' => $participant->id], [
                'progress_steps' => $progress,
                'completed_at' => $progress >= (int)$challenge->target_steps ? date('Y-m-d H:i:s') : $participant->completed_at,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $data = [
            'id' => (int)$challenge->id,
            'title' => $challenge->title,
            'difficulty' => $challenge->difficulty,
            'target_steps' => (int)$challenge->target_steps,
            'start_date' => $challenge->start_date,
            'end_date' => $challenge->end_date,
            'days_left' => $this->days_left($challenge->end_date),
            'reward_text' => $challenge->reward_text,
            'image_url' => $challenge->image_url,
            'participant_count' => $participantCount,
            'is_joined' => $participant && $participant->status === 'joined',
            'my_progress_steps' => $progress,
            'my_progress_percent' => $percent,
            'my_rank' => $participant ? $this->user_rank($operation, (int)$challenge->id, $viewerId) : null,
            'is_completed' => $progress >= (int)$challenge->target_steps,
            'created_at' => $challenge->created_at,
            'updated_at' => $challenge->updated_at,
        ];

        if ($includeDetail) {
            $data['description'] = $challenge->description;
            $data['leaderboard'] = $this->leaderboard_rows($operation, (int)$challenge->id);
        }

        return $data;
    }

    private function calculate_progress($operation, $challenge, $userId)
    {
        $row = $operation->db->query(
            "SELECT COALESCE(SUM(steps),0) AS steps
             FROM user_step_events
             WHERE user_id = ? AND DATE(is_date) BETWEEN ? AND ?",
            [$userId, $challenge->start_date, $challenge->end_date]
        )->getRow();

        return (int)($row->steps ?? 0);
    }

    private function sync_joined_progress($operation, $challenge)
    {
        $participants = $operation->db->query(
            "SELECT * FROM challenge_participants WHERE challenge_id = ? AND status = 'joined'",
            [$challenge->id]
        )->getResult();

        foreach ($participants as $participant) {
            $progress = max((int)$participant->progress_steps, $this->calculate_progress($operation, $challenge, (int)$participant->user_id));
            if ($progress !== (int)$participant->progress_steps) {
                $operation->update_data('challenge_participants', ['id' => $participant->id], [
                    'progress_steps' => $progress,
                    'completed_at' => $progress >= (int)$challenge->target_steps ? date('Y-m-d H:i:s') : $participant->completed_at,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function leaderboard_rows($operation, $challengeId)
    {
        $rows = $operation->db->query(
            "SELECT p.user_id, p.progress_steps, p.completed_at, u.full_name, u.nickname, u.user_image
             FROM challenge_participants p
             JOIN users u ON u.id = p.user_id
             WHERE p.challenge_id = ? AND p.status = 'joined'
             ORDER BY p.progress_steps DESC, p.completed_at ASC, p.updated_at ASC
             LIMIT 10",
            [$challengeId]
        )->getResult();

        $rank = 1;
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'rank' => $rank++,
                'user_id' => (int)$row->user_id,
                'full_name' => $row->full_name,
                'nickname' => $row->nickname,
                'user_image' => $row->user_image,
                'progress_steps' => (int)$row->progress_steps,
                'completed_at' => $row->completed_at,
            ];
        }
        return $out;
    }

    private function user_rank($operation, $challengeId, $userId)
    {
        $participant = $this->participant_row($operation, $challengeId, $userId);
        if (!$participant || $participant->status !== 'joined') return null;

        $row = $operation->db->query(
            "SELECT COUNT(*) + 1 AS rank_no
             FROM challenge_participants
             WHERE challenge_id = ? AND status = 'joined' AND progress_steps > ?",
            [$challengeId, (int)$participant->progress_steps]
        )->getRow();

        return (int)($row->rank_no ?? 1);
    }

    private function participant_count($operation, $challengeId)
    {
        $row = $operation->db->query(
            "SELECT COUNT(*) AS c FROM challenge_participants WHERE challenge_id = ? AND status = 'joined'",
            [$challengeId]
        )->getRow();
        return (int)($row->c ?? 0);
    }

    private function participant_row($operation, $challengeId, $userId)
    {
        return $operation->db->query(
            "SELECT * FROM challenge_participants WHERE challenge_id = ? AND user_id = ?",
            [$challengeId, $userId]
        )->getRow();
    }

    private function get_challenge_row($operation, $challengeId)
    {
        return $operation->db->query(
            "SELECT * FROM challenges WHERE id = ? AND status = 1",
            [$challengeId]
        )->getRow();
    }

    private function basic_challenge($challenge)
    {
        return [
            'id' => (int)$challenge->id,
            'title' => $challenge->title,
            'difficulty' => $challenge->difficulty,
            'target_steps' => (int)$challenge->target_steps,
            'start_date' => $challenge->start_date,
            'end_date' => $challenge->end_date,
            'image_url' => $challenge->image_url,
        ];
    }

    private function days_left($endDate)
    {
        $today = strtotime(date('Y-m-d'));
        $end = strtotime($endDate);
        if (!$end || $end < $today) return 0;
        return (int)ceil(($end - $today) / 86400);
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

    private function first_file($field)
    {
        if (empty($_FILES[$field])) return null;
        if (($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        return $_FILES[$field];
    }

    private function save_image($file)
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['status' => false, 'error' => 'Image upload failed.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->imageTypes, true)) {
            return ['status' => false, 'error' => 'Unsupported image type.'];
        }

        $physicalDir = rtrim(WEb_ASSIST_PHYSICAL_PATH, '/\\') . DIRECTORY_SEPARATOR . 'challenges' . DIRECTORY_SEPARATOR;
        if (!is_dir($physicalDir)) {
            mkdir($physicalDir, 0777, true);
        }

        $fileName = uniqid('challenge_', true) . '.' . $ext;
        $target = $physicalDir . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['status' => false, 'error' => 'Unable to save image file.'];
        }

        return [
            'status' => true,
            'url' => rtrim(WEb_BASE_PATH, '/') . '/public/challenges/' . $fileName,
        ];
    }
}
?>
