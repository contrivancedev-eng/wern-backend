<?php
namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Models\Operation;

class Event extends ApiController
{
    private $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function event_list()
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

        $where = ["e.status = 1"];
        $params = [];

        if ($type === 'upcoming') {
            $where[] = "e.event_datetime >= NOW()";
        } elseif ($type === 'past') {
            $where[] = "e.event_datetime < NOW()";
        }

        if ($search !== '') {
            $where[] = "(e.title LIKE ? OR e.location_name LIKE ? OR e.category LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT e.*, u.full_name AS organizer_name, u.nickname AS organizer_nickname, u.user_image AS organizer_image
                FROM events e
                JOIN users u ON u.id = e.organizer_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.event_datetime ASC, e.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $rows = $operation->db->query($sql, $params)->getResult();
        $events = [];
        foreach ($rows as $row) {
            $events[] = $this->format_event($operation, $row, $auth['user_id']);
        }

        return $this->success_response("Events fetched successfully.", [
            'events' => $events,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function detail()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $eventId = (int)$this->input_get('event_id');
        if ($eventId <= 0) return $this->error_response("event_id is required.");

        $event = $this->get_event_row($operation, $eventId);
        if (!$event) return $this->error_response("Event not found.");

        return $this->success_response("Event detail fetched successfully.", $this->format_event($operation, $event, $auth['user_id'], true));
    }

    public function rsvp()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $eventId = (int)$this->post_value('event_id');
        $status = trim((string)$this->post_value('status'));
        if ($eventId <= 0) return $this->error_response("event_id is required.");
        if ($status === '') $status = 'going';
        if (!in_array($status, ['going', 'interested', 'cancelled'], true)) {
            return $this->error_response("Invalid RSVP status.");
        }
        if (!$this->get_event_row($operation, $eventId)) return $this->error_response("Event not found.");

        $exists = $operation->get_data('event_rsvps', '*', [
            'event_id' => $eventId,
            'user_id' => $auth['user_id'],
        ]);

        if ($exists['num_rows'] > 0) {
            $operation->update_data('event_rsvps', ['id' => $exists['result'][0]->id], [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $operation->insert_data('event_rsvps', [
                'event_id' => $eventId,
                'user_id' => $auth['user_id'],
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success_response("Event RSVP updated successfully.", $this->event_counts($operation, $eventId, $auth['user_id']));
    }

    public function my_events()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $rows = $operation->db->query(
            "SELECT e.*, u.full_name AS organizer_name, u.nickname AS organizer_nickname, u.user_image AS organizer_image
             FROM event_rsvps r
             JOIN events e ON e.id = r.event_id
             JOIN users u ON u.id = e.organizer_id
             WHERE r.user_id = ? AND r.status IN ('going','interested') AND e.status = 1
             ORDER BY e.event_datetime ASC",
            [$auth['user_id']]
        )->getResult();

        $events = [];
        foreach ($rows as $row) {
            $events[] = $this->format_event($operation, $row, $auth['user_id']);
        }

        return $this->success_response("My events fetched successfully.", $events);
    }

    public function create()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $title = trim((string)$this->post_value('title'));
        $category = trim((string)$this->post_value('category'));
        $eventDateTime = trim((string)$this->post_value('event_datetime'));
        $locationName = trim((string)$this->post_value('location_name'));
        $distanceKm = $this->post_value('distance_km');
        $description = trim((string)$this->post_value('description'));
        $isFree = (int)($this->post_value('is_free') ?? 1);
        $priceText = trim((string)$this->post_value('price_text'));

        if ($title === '') return $this->error_response("Title is required.");
        if ($category === '') return $this->error_response("Category is required.");
        if ($eventDateTime === '') return $this->error_response("event_datetime is required.");
        if ($locationName === '') return $this->error_response("location_name is required.");

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

        $eventId = $operation->insert_data('events', [
            'organizer_id' => $auth['user_id'],
            'title' => $title,
            'category' => $category,
            'event_datetime' => date('Y-m-d H:i:s', strtotime($eventDateTime)),
            'location_name' => $locationName,
            'latitude' => $this->nullable_number($this->post_value('latitude')),
            'longitude' => $this->nullable_number($this->post_value('longitude')),
            'distance_km' => $this->nullable_number($distanceKm),
            'description' => $description !== '' ? $description : null,
            'image_url' => $imageUrl,
            'is_free' => $isFree ? 1 : 0,
            'price_text' => $priceText !== '' ? $priceText : null,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$eventId) return $this->error_response("Unable to create event.");

        return $this->success_response("Event created successfully.", $this->format_event($operation, $this->get_event_row($operation, $eventId), $auth['user_id'], true));
    }

    public function update($id = null)
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $eventId = (int)($this->post_value('event_id') ?: $id);
        if ($eventId <= 0) return $this->error_response("event_id is required.");

        $event = $this->get_event_row($operation, $eventId);
        if (!$event) return $this->error_response("Event not found.");
        if ((int)$event->organizer_id !== $auth['user_id']) return $this->error_response("You can update only your own event.");

        $fields = ['title', 'category', 'event_datetime', 'location_name', 'description', 'price_text'];
        $update = ['updated_at' => date('Y-m-d H:i:s')];
        foreach ($fields as $field) {
            $value = trim((string)$this->post_value($field));
            if ($value !== '') $update[$field] = $field === 'event_datetime' ? date('Y-m-d H:i:s', strtotime($value)) : $value;
        }

        foreach (['latitude', 'longitude', 'distance_km'] as $field) {
            $value = $this->post_value($field);
            if ($value !== null && $value !== '') $update[$field] = $this->nullable_number($value);
        }

        $isFree = $this->post_value('is_free');
        if ($isFree !== null && $isFree !== '') $update['is_free'] = (int)$isFree ? 1 : 0;

        $file = $this->first_file('image');
        if ($file) {
            $uploaded = $this->save_image($file);
            if (!$uploaded['status']) return $this->error_response($uploaded['error']);
            $update['image_url'] = $uploaded['url'];
        }

        $imageUrl = trim((string)$this->post_value('image_url'));
        if ($imageUrl !== '') $update['image_url'] = $imageUrl;

        $operation->update_data('events', ['id' => $eventId], $update);

        return $this->success_response("Event updated successfully.", $this->format_event($operation, $this->get_event_row($operation, $eventId), $auth['user_id'], true));
    }

    public function delete($id = null)
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $eventId = (int)($this->post_value('event_id') ?: $id);
        if ($eventId <= 0) return $this->error_response("event_id is required.");

        $event = $this->get_event_row($operation, $eventId);
        if (!$event) return $this->error_response("Event not found.");
        if ((int)$event->organizer_id !== $auth['user_id']) return $this->error_response("You can delete only your own event.");

        $operation->update_data('events', ['id' => $eventId], [
            'status' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success_response("Event deleted successfully.");
    }

    public function message_thread()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $eventId = (int)$this->input_get('event_id');
        $withUserId = (int)$this->input_get('with_user_id');
        if ($eventId <= 0) return $this->error_response("event_id is required.");

        $event = $this->get_event_row($operation, $eventId);
        if (!$event) return $this->error_response("Event not found.");

        if ($withUserId <= 0) $withUserId = (int)$event->organizer_id;
        if ($withUserId === $auth['user_id']) return $this->error_response("with_user_id must be another user.");

        $access = $this->ensure_message_access($event, $auth['user_id'], $withUserId);
        if (!$access['status']) return $this->error_response($access['error']);

        $messages = $operation->db->query(
            "SELECT m.*, u.full_name, u.nickname, u.user_image
             FROM event_messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.event_id = ?
               AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
               AND m.status = 1
             ORDER BY m.id ASC",
            [$eventId, $auth['user_id'], $withUserId, $withUserId, $auth['user_id']]
        )->getResult();

        $operation->db->query(
            "UPDATE event_messages SET is_read = 1, read_at = NOW()
             WHERE event_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0",
            [$eventId, $withUserId, $auth['user_id']]
        );

        return $this->success_response("Event messages fetched successfully.", [
            'event' => $this->basic_event($event),
            'with_user' => $this->user_summary($operation, $withUserId),
            'messages' => $messages,
        ]);
    }

    public function send_message()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'post');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $eventId = (int)$this->post_value('event_id');
        $receiverId = (int)$this->post_value('receiver_id');
        $message = trim((string)$this->post_value('message'));

        if ($eventId <= 0) return $this->error_response("event_id is required.");
        if ($message === '') return $this->error_response("Message is required.");

        $event = $this->get_event_row($operation, $eventId);
        if (!$event) return $this->error_response("Event not found.");

        if ($receiverId <= 0) $receiverId = (int)$event->organizer_id;
        if ($receiverId === $auth['user_id']) return $this->error_response("receiver_id must be another user.");

        $access = $this->ensure_message_access($event, $auth['user_id'], $receiverId);
        if (!$access['status']) return $this->error_response($access['error']);

        $messageId = $operation->insert_data('event_messages', [
            'event_id' => $eventId,
            'sender_id' => $auth['user_id'],
            'receiver_id' => $receiverId,
            'message' => $message,
            'is_read' => 0,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success_response("Message sent successfully.", $this->get_message($operation, $messageId));
    }

    public function conversations()
    {
        $operation = new Operation();
        $auth = $this->auth_user($operation, 'get');
        if (!$auth['status']) return $this->error_response($auth['error']);

        $rows = $operation->db->query(
            "SELECT m.*, e.title AS event_title,
                    CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS with_user_id
             FROM event_messages m
             JOIN (
                SELECT event_id,
                       LEAST(sender_id, receiver_id) AS user_a,
                       GREATEST(sender_id, receiver_id) AS user_b,
                       MAX(id) AS last_id
                FROM event_messages
                WHERE status = 1 AND (sender_id = ? OR receiver_id = ?)
                GROUP BY event_id, user_a, user_b
             ) latest ON latest.last_id = m.id
             JOIN events e ON e.id = m.event_id
             ORDER BY m.id DESC",
            [$auth['user_id'], $auth['user_id'], $auth['user_id']]
        )->getResult();

        $items = [];
        foreach ($rows as $row) {
            $unread = $operation->db->query(
                "SELECT COUNT(*) AS c FROM event_messages
                 WHERE event_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0 AND status = 1",
                [$row->event_id, $row->with_user_id, $auth['user_id']]
            )->getRow();

            $items[] = [
                'event_id' => (int)$row->event_id,
                'event_title' => $row->event_title,
                'with_user' => $this->user_summary($operation, (int)$row->with_user_id),
                'last_message' => $row->message,
                'last_message_at' => $row->created_at,
                'unread_count' => (int)($unread->c ?? 0),
            ];
        }

        return $this->success_response("Event conversations fetched successfully.", $items);
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

    private function get_event_row($operation, $eventId)
    {
        return $operation->db->query(
            "SELECT e.*, u.full_name AS organizer_name, u.nickname AS organizer_nickname, u.user_image AS organizer_image
             FROM events e
             JOIN users u ON u.id = e.organizer_id
             WHERE e.id = ? AND e.status = 1",
            [$eventId]
        )->getRow();
    }

    private function format_event($operation, $event, $viewerId, $includeDetail = false)
    {
        $counts = $this->event_counts($operation, (int)$event->id, $viewerId);

        $data = [
            'id' => (int)$event->id,
            'title' => $event->title,
            'category' => $event->category,
            'event_datetime' => $event->event_datetime,
            'location_name' => $event->location_name,
            'latitude' => $event->latitude !== null ? (float)$event->latitude : null,
            'longitude' => $event->longitude !== null ? (float)$event->longitude : null,
            'distance_km' => $event->distance_km !== null ? (float)$event->distance_km : null,
            'image_url' => $event->image_url,
            'is_free' => (bool)$event->is_free,
            'price_text' => $event->price_text,
            'going_count' => $counts['going_count'],
            'interested_count' => $counts['interested_count'],
            'my_rsvp_status' => $counts['my_rsvp_status'],
            'is_going' => $counts['my_rsvp_status'] === 'going',
            'organizer' => [
                'id' => (int)$event->organizer_id,
                'full_name' => $event->organizer_name,
                'nickname' => $event->organizer_nickname,
                'user_image' => $event->organizer_image,
            ],
            'created_at' => $event->created_at,
            'updated_at' => $event->updated_at,
        ];

        if ($includeDetail) {
            $data['description'] = $event->description;
            $data['attendees'] = $this->attendees($operation, (int)$event->id);
        }

        return $data;
    }

    private function event_counts($operation, $eventId, $viewerId)
    {
        $going = $operation->db->query("SELECT COUNT(*) AS c FROM event_rsvps WHERE event_id = ? AND status = 'going'", [$eventId])->getRow();
        $interested = $operation->db->query("SELECT COUNT(*) AS c FROM event_rsvps WHERE event_id = ? AND status = 'interested'", [$eventId])->getRow();
        $mine = $operation->db->query("SELECT status FROM event_rsvps WHERE event_id = ? AND user_id = ?", [$eventId, $viewerId])->getRow();

        return [
            'going_count' => (int)($going->c ?? 0),
            'interested_count' => (int)($interested->c ?? 0),
            'my_rsvp_status' => $mine ? $mine->status : null,
        ];
    }

    private function attendees($operation, $eventId)
    {
        return $operation->db->query(
            "SELECT r.user_id, r.status, r.created_at, u.full_name, u.nickname, u.user_image
             FROM event_rsvps r
             JOIN users u ON u.id = r.user_id
             WHERE r.event_id = ? AND r.status IN ('going','interested')
             ORDER BY r.id DESC
             LIMIT 20",
            [$eventId]
        )->getResult();
    }

    private function user_summary($operation, $userId)
    {
        return $operation->db->query(
            "SELECT id, full_name, nickname, user_image FROM users WHERE id = ?",
            [$userId]
        )->getRow();
    }

    private function basic_event($event)
    {
        return [
            'id' => (int)$event->id,
            'title' => $event->title,
            'category' => $event->category,
            'event_datetime' => $event->event_datetime,
            'location_name' => $event->location_name,
            'image_url' => $event->image_url,
        ];
    }

    private function get_message($operation, $messageId)
    {
        return $operation->db->query(
            "SELECT m.*, u.full_name, u.nickname, u.user_image
             FROM event_messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.id = ?",
            [$messageId]
        )->getRow();
    }

    private function ensure_message_access($event, $senderId, $otherUserId)
    {
        $organizerId = (int)$event->organizer_id;
        if ($senderId !== $organizerId && $otherUserId !== $organizerId) {
            return ['status' => false, 'error' => 'Event messages must include the organizer.'];
        }
        return ['status' => true];
    }

    private function nullable_number($value)
    {
        if ($value === null || $value === '') return null;
        return is_numeric($value) ? $value : null;
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

        $physicalDir = rtrim(WEb_ASSIST_PHYSICAL_PATH, '/\\') . DIRECTORY_SEPARATOR . 'events' . DIRECTORY_SEPARATOR;
        if (!is_dir($physicalDir)) {
            mkdir($physicalDir, 0777, true);
        }

        $fileName = uniqid('event_', true) . '.' . $ext;
        $target = $physicalDir . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['status' => false, 'error' => 'Unable to save image file.'];
        }

        return [
            'status' => true,
            'url' => rtrim(WEb_BASE_PATH, '/') . '/public/events/' . $fileName,
        ];
    }
}
?>
