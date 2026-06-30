<?php

class StepEvent
{
    protected $db;

    const STEP_LENGTH_KM = 0.000762;
    const KCAL_PER_STEP = 0.04;
    const DAILY_GOAL    = 10000;

    public function __construct()
    {
        $this->db = $this->connectDatabase();
    }

    /* ================= DATABASE ================= */

    private function connectDatabase()
    {
        $config = require __DIR__ . '/../config.php';

        $conn = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );

        if ($conn->connect_error) {
            throw new Exception('DB connection failed: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');
        return $conn;
    }

    private function ensureConnection()
    {
        if (!$this->db->ping()) {
            $this->db = $this->connectDatabase();
        }
    }

    /* ================= SOCKET HANDLER ================= */

    public function handle($socket, array $data)
    {
        try {
            $this->ensureConnection();

            /* ===== VALIDATION ===== */
            if (empty($data['user_id']) || empty($data['category_id'])) {
                throw new Exception('user_id and category_id are required');
            }

            $user_id     = (int)$data['user_id'];
            $category_id = (int)$data['category_id'];

            if ($user_id <= 0 || $category_id <= 0) {
                throw new Exception('Invalid user_id or category_id');
            }

            if (!isset($data['steps'], $data['timestamp'], $data['type'])) {
                throw new Exception('Missing required fields');
            }

            $steps = (int)$data['steps'];
            // if ($steps < 1 || $steps > 50) {
            //     throw new Exception('Invalid step count (1–50)');
            // }

            /* ===== SAFE TIMESTAMP ===== */
            $timestamp = (int)$data['timestamp'];
            if ($timestamp <= 0 || $timestamp > time()) {
                $timestamp = time();
            }

            $event_time = date('Y-m-d H:i:s', $timestamp);
            $is_date    = date('Y-m-d', $timestamp);

            /* ===== CALCULATIONS ===== */
            $kilometre = round($steps * self::STEP_LENGTH_KM, 4);
            $kcal      = round($steps * self::KCAL_PER_STEP, 2);

            $type = (string)$data['type'];
            $lat  = isset($data['lat']) ? (float)$data['lat'] : null;
            $lng  = isset($data['lng']) ? (float)$data['lng'] : null;

            /* ===== INSERT STEP EVENT ===== */
            $stmt = $this->db->prepare("
                INSERT INTO user_step_events
                (user_id, category_id, steps, kilometre, kcal, type, latitude, longitude, event_time, is_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception($this->db->error);
            }

            $stmt->bind_param(
                "iiiddsddss",
                $user_id,
                $category_id,
                $steps,
                $kilometre,
                $kcal,
                $type,
                $lat,
                $lng,
                $event_time,
                $is_date
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();

            /* ===== TODAY SUMMARY ===== */
            $todaySummary = $this->getTodayProgress($user_id, $category_id, $is_date);

            /* ===== CALCULATE EARN LITTIES ===== */
            $totalStepsToday = $todaySummary['steps'];

            $points = $this->calculatePoints($totalStepsToday);
            $description = "Earned $points points for $totalStepsToday steps";

            /* ===== INSERT OR UPDATE earn_litties ===== */
            $stmt = $this->db->prepare("
                SELECT id FROM earn_litties 
                WHERE user_id = ? AND earn_category_id = ? AND DATE(date) = ?
            ");
            $stmt->bind_param("iis", $user_id, $category_id, $is_date);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
            $stmt->close();

            if ($existing) {
                // Update existing
                $stmt = $this->db->prepare("
                    UPDATE earn_litties
                    SET points = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("dsi", $points, $description, $existing['id']);
                $stmt->execute();
                $stmt->close();
            } else {
                // Insert new
                $stmt = $this->db->prepare("
                    INSERT INTO earn_litties
                    (user_id, earn_category_id, points, description, date)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iidss", $user_id, $category_id, $points, $description, $event_time);
                $stmt->execute();
                $stmt->close();
            }

            /* ===== REFRESH TODAY SUMMARY (WITH LITTIES) ===== */
            $todaySummary = $this->getTodayProgress($user_id, $category_id, $is_date);

            /* ===== SOCKET RESPONSE ===== */
            $socket->emit('step_ack', [
                'status'      => true,
                'user_id'     => $user_id,
                'category_id' => $category_id,
                'steps'       => $todaySummary['steps'],
                'goal'        => $todaySummary['goal'],
                'kilometre'   => $todaySummary['kilometre'],
                'kcal'        => $todaySummary['kcal'],
                'litres'      => $todaySummary['litres']
            ]);

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $socket->emit('server_error', [
                'status'  => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /* ================= POINTS LOGIC ================= */
    private function calculatePoints($steps)
    {
        if ($steps >= 1 && $steps <= 100) return 0.1;
        if ($steps >= 101 && $steps <= 1000) return 1.0;
        if ($steps >= 1001 && $steps <= 5000) return 5.0;
        if ($steps > 5000) return 10.0;
        return 0;
    }

    /* ================= TODAY SUMMARY ================= */
    public function getTodayProgress($user_id, $category_id, $today)
    {
        $this->ensureConnection();

        /* ===== STEP TOTAL ===== */
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(steps),0)     AS total_steps,
                COALESCE(SUM(kilometre),0) AS total_km,
                COALESCE(SUM(kcal),0)      AS total_kcal
            FROM user_step_events
            WHERE user_id = ? 
              AND category_id = ?
              AND is_date = ?
        ");

        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        $stmt->bind_param("iis", $user_id, $category_id, $today);
        $stmt->execute();
        $stepsData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        /* ===== LITRES TOTAL ===== */
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(points),0) AS total_litres
            FROM earn_litties
            WHERE user_id = ?
              AND earn_category_id = ?
              AND DATE(date) = ?
        ");

        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        $stmt->bind_param("iis", $user_id, $category_id, $today);
        $stmt->execute();
        $litresData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'steps'     => (int)$stepsData['total_steps'],
            'goal'      => self::DAILY_GOAL,
            'kilometre' => round((float)$stepsData['total_km'], 1),
            'kcal'      => round((float)$stepsData['total_kcal'], 0),
            'litres'    => round((float)$litresData['total_litres'], 2)
        ];
    }

    public function __destruct()
    {
        if ($this->db) {
            $this->db->close();
        }
    }
}
