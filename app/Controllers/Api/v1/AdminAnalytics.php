<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminAnalytics extends ApiController
{
    public function stats()
    {
        $db = Database::connect();

        $one = function (string $sql, array $binds = []) use ($db) {
            $q = $db->query($sql, $binds);
            if (!$q) return 0;
            $row = $q->getRowArray();
            return $row ? array_values($row)[0] : 0;
        };
        $all = function (string $sql, array $binds = []) use ($db) {
            $q = $db->query($sql, $binds);
            return $q ? $q->getResultArray() : [];
        };

        // KPI cards
        $totalUsers = (int) $one("SELECT COUNT(*) FROM users");
        $dauToday   = (int) $one("SELECT COUNT(DISTINCT user_id) FROM user_step_events WHERE DATE(is_date) = CURDATE()");
        $dauRate    = $totalUsers > 0 ? round($dauToday * 100 / $totalUsers, 1) : 0;

        $avgStepsUser = (int) $one("
            SELECT COALESCE(ROUND(SUM(steps) / NULLIF(COUNT(DISTINCT user_id),0)),0)
            FROM user_step_events WHERE DATE(is_date) = CURDATE()
        ");

        // 7-day retention: signed up 7-14 days ago AND had at least one step event in last 7 days
        $ret7 = (float) $one("
            SELECT COALESCE(ROUND(SUM(active) * 100.0 / NULLIF(COUNT(*),0),1),0) FROM (
                SELECT u.id,
                       CASE WHEN EXISTS(
                          SELECT 1 FROM user_step_events se
                          WHERE se.user_id = u.id
                            AND DATE(se.is_date) >= (CURDATE() - INTERVAL 7 DAY)
                       ) THEN 1 ELSE 0 END AS active
                FROM users u
                WHERE DATE(u.create_on) BETWEEN (CURDATE() - INTERVAL 14 DAY) AND (CURDATE() - INTERVAL 7 DAY)
            ) t
        ");

        // Goal hit rate today: users whose today's steps >= their daily_step_goal
        $goalHit = (float) $one("
            SELECT COALESCE(ROUND(SUM(CASE WHEN today_steps >= g.daily_step_goal THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*),0),1),0)
            FROM user_goals g
            JOIN (
                SELECT user_id, COALESCE(SUM(steps),0) AS today_steps
                FROM user_step_events WHERE DATE(is_date) = CURDATE() GROUP BY user_id
            ) s ON s.user_id = g.user_id
        ");

        // Total steps all time for avg session placeholder (we don't have session data)
        $totalSteps = (int) $one("SELECT COALESCE(SUM(steps),0) FROM user_step_events");

        // DAU chart last 30 days (distinct users per day)
        $dau30 = $all("
            SELECT DATE(is_date) AS d, COUNT(DISTINCT user_id) AS c
            FROM user_step_events
            WHERE DATE(is_date) >= (CURDATE() - INTERVAL 29 DAY)
            GROUP BY DATE(is_date)
            ORDER BY d
        ");

        // Step distribution buckets today
        $dist = $all("
            SELECT bucket, COUNT(*) AS c FROM (
                SELECT CASE
                    WHEN total < 2000  THEN '0-2K'
                    WHEN total < 5000  THEN '2-5K'
                    WHEN total < 10000 THEN '5-10K'
                    WHEN total < 20000 THEN '10-20K'
                    WHEN total < 30000 THEN '20-30K'
                    ELSE '30K+' END AS bucket
                FROM (
                    SELECT user_id, SUM(steps) AS total
                    FROM user_step_events WHERE DATE(is_date) = CURDATE()
                    GROUP BY user_id
                ) u
            ) t GROUP BY bucket
        ");

        // Activity level split
        $levels = $all("
            SELECT activity_level AS lvl, COUNT(*) AS c
            FROM user_goals
            GROUP BY activity_level
        ");

        // Monthly user growth last 6 months (organic vs referral)
        $growth = $all("
            SELECT DATE_FORMAT(create_on, '%Y-%m') AS ym,
                   SUM(CASE WHEN referral_user_id = 0 THEN 1 ELSE 0 END) AS organic,
                   SUM(CASE WHEN referral_user_id > 0 THEN 1 ELSE 0 END) AS referral
            FROM users
            WHERE create_on >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY DATE_FORMAT(create_on, '%Y-%m')
            ORDER BY ym
        ");

        // Cohort retention — last 6 weeks of signup cohorts, weekly buckets W0..W6
        $cohort = $all("
            SELECT
              YEARWEEK(u.create_on, 3) AS cohort_yw,
              MIN(DATE(u.create_on))   AS cohort_start,
              COUNT(DISTINCT u.id)     AS cohort_size,
              SUM(CASE WHEN w.week_no = 0 THEN 1 ELSE 0 END) AS w0,
              SUM(CASE WHEN w.week_no = 1 THEN 1 ELSE 0 END) AS w1,
              SUM(CASE WHEN w.week_no = 2 THEN 1 ELSE 0 END) AS w2,
              SUM(CASE WHEN w.week_no = 3 THEN 1 ELSE 0 END) AS w3,
              SUM(CASE WHEN w.week_no = 4 THEN 1 ELSE 0 END) AS w4,
              SUM(CASE WHEN w.week_no = 5 THEN 1 ELSE 0 END) AS w5,
              SUM(CASE WHEN w.week_no = 6 THEN 1 ELSE 0 END) AS w6
            FROM users u
            LEFT JOIN (
              SELECT u2.id AS uid,
                     FLOOR(DATEDIFF(DATE(se.is_date), DATE(u2.create_on)) / 7) AS week_no
              FROM users u2
              JOIN user_step_events se ON se.user_id = u2.id
              WHERE DATE(u2.create_on) >= (CURDATE() - INTERVAL 6 WEEK)
              GROUP BY u2.id, week_no
            ) w ON w.uid = u.id
            WHERE DATE(u.create_on) >= (CURDATE() - INTERVAL 6 WEEK)
            GROUP BY cohort_yw
            ORDER BY cohort_yw
        ");

        // Key metrics table (today / week / month / all time)
        $metric = function ($sql) use ($db) {
            $q = $db->query($sql);
            return $q ? (int) (array_values($q->getRowArray() ?? ['v'=>0])[0]) : 0;
        };

        $metrics = [
            'total_steps' => [
                'today' => $metric("SELECT COALESCE(SUM(steps),0) FROM user_step_events WHERE DATE(is_date) = CURDATE()"),
                'week'  => $metric("SELECT COALESCE(SUM(steps),0) FROM user_step_events WHERE DATE(is_date) >= (CURDATE() - INTERVAL 6 DAY)"),
                'month' => $metric("SELECT COALESCE(SUM(steps),0) FROM user_step_events WHERE DATE(is_date) >= (CURDATE() - INTERVAL 29 DAY)"),
                'all'   => $metric("SELECT COALESCE(SUM(steps),0) FROM user_step_events"),
            ],
            'distance_km' => [
                'today' => $metric("SELECT COALESCE(ROUND(SUM(kilometre)),0) FROM user_step_events WHERE DATE(is_date) = CURDATE()"),
                'week'  => $metric("SELECT COALESCE(ROUND(SUM(kilometre)),0) FROM user_step_events WHERE DATE(is_date) >= (CURDATE() - INTERVAL 6 DAY)"),
                'month' => $metric("SELECT COALESCE(ROUND(SUM(kilometre)),0) FROM user_step_events WHERE DATE(is_date) >= (CURDATE() - INTERVAL 29 DAY)"),
                'all'   => $metric("SELECT COALESCE(ROUND(SUM(kilometre)),0) FROM user_step_events"),
            ],
            'calories' => [
                'today' => $metric("SELECT COALESCE(ROUND(SUM(kcal)),0) FROM user_step_events WHERE DATE(is_date) = CURDATE()"),
                'week'  => $metric("SELECT COALESCE(ROUND(SUM(kcal)),0) FROM user_step_events WHERE DATE(is_date) >= (CURDATE() - INTERVAL 6 DAY)"),
                'month' => $metric("SELECT COALESCE(ROUND(SUM(kcal)),0) FROM user_step_events WHERE DATE(is_date) >= (CURDATE() - INTERVAL 29 DAY)"),
                'all'   => $metric("SELECT COALESCE(ROUND(SUM(kcal)),0) FROM user_step_events"),
            ],
            'litties' => [
                'today' => $metric("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE DATE(`date`) = CURDATE() AND type = 1"),
                'week'  => $metric("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE DATE(`date`) >= (CURDATE() - INTERVAL 6 DAY) AND type = 1"),
                'month' => $metric("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE DATE(`date`) >= (CURDATE() - INTERVAL 29 DAY) AND type = 1"),
                'all'   => $metric("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE type = 1"),
            ],
            'daily_claims' => [
                'today' => $metric("SELECT COUNT(*) FROM user_daily_claim_status WHERE DATE(last_claim_date) = CURDATE()"),
                'week'  => $metric("SELECT COUNT(*) FROM user_daily_claim_status WHERE DATE(last_claim_date) >= (CURDATE() - INTERVAL 6 DAY)"),
                'month' => $metric("SELECT COUNT(*) FROM user_daily_claim_status WHERE DATE(last_claim_date) >= (CURDATE() - INTERVAL 29 DAY)"),
                'all'   => $metric("SELECT COUNT(*) FROM user_daily_claim_status"),
            ],
            'signups' => [
                'today' => $metric("SELECT COUNT(*) FROM users WHERE DATE(create_on) = CURDATE()"),
                'week'  => $metric("SELECT COUNT(*) FROM users WHERE DATE(create_on) >= (CURDATE() - INTERVAL 6 DAY)"),
                'month' => $metric("SELECT COUNT(*) FROM users WHERE DATE(create_on) >= (CURDATE() - INTERVAL 29 DAY)"),
                'all'   => $metric("SELECT COUNT(*) FROM users"),
            ],
            'referral_signups' => [
                'today' => $metric("SELECT COUNT(*) FROM users WHERE referral_user_id > 0 AND DATE(create_on) = CURDATE()"),
                'week'  => $metric("SELECT COUNT(*) FROM users WHERE referral_user_id > 0 AND DATE(create_on) >= (CURDATE() - INTERVAL 6 DAY)"),
                'month' => $metric("SELECT COUNT(*) FROM users WHERE referral_user_id > 0 AND DATE(create_on) >= (CURDATE() - INTERVAL 29 DAY)"),
                'all'   => $metric("SELECT COUNT(*) FROM users WHERE referral_user_id > 0"),
            ],
        ];

        // Top 10 walkers (all-time)
        $topWalkers = $all("
            SELECT u.id, u.full_name, u.email, u.user_image, u.country_code,
                   COALESCE(SUM(se.steps),0)     AS steps,
                   COALESCE(SUM(se.kilometre),0) AS km,
                   COALESCE(SUM(se.kcal),0)      AS kcal,
                   COUNT(se.id)                  AS events
            FROM users u
            JOIN user_step_events se ON se.user_id = u.id
            GROUP BY u.id, u.full_name, u.email, u.user_image, u.country_code
            ORDER BY steps DESC
            LIMIT 10
        ");

        unset($topWalkers);

        return $this->success_response("Analytics.", [
            'kpi' => [
                'total_users'    => $totalUsers,
                'dau_today'      => $dauToday,
                'dau_rate'       => $dauRate,
                'avg_steps_user' => $avgStepsUser,
                'retention_7d'   => $ret7,
                'goal_hit_rate'  => $goalHit,
                'total_steps'    => $totalSteps,
            ],
            'dau_30d'       => $dau30,
            'step_dist'     => $dist,
            'activity_lvl'  => $levels,
            'user_growth'   => $growth,
            'cohort'        => $cohort,
            'metrics'       => $metrics,
        ]);
    }

    public function topWalkers()
    {
        $db = Database::connect();
        $req = service('request');
        $from = trim($req->getGet('from') ?? '');
        $to   = trim($req->getGet('to')   ?? '');

        $validDate = function ($s) {
            return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
        };

        $where = '';
        $binds = [];
        if ($from && $to && $validDate($from) && $validDate($to)) {
            if (strcmp($from, $to) > 0) { [$from, $to] = [$to, $from]; }
            $where  = "WHERE DATE(se.is_date) BETWEEN ? AND ?";
            $binds  = [$from, $to];
            $label  = "$from → $to";
        } elseif ($from && $validDate($from)) {
            $where  = "WHERE DATE(se.is_date) = ?";
            $binds  = [$from];
            $label  = $from;
        } elseif ($to && $validDate($to)) {
            $where  = "WHERE DATE(se.is_date) = ?";
            $binds  = [$to];
            $label  = $to;
        } else {
            $label  = 'All time';
        }

        $sql = "
            SELECT u.id, u.full_name, u.email, u.user_image, u.country_code,
                   COALESCE(SUM(se.steps),0)     AS steps,
                   COALESCE(SUM(se.kilometre),0) AS km,
                   COALESCE(SUM(se.kcal),0)      AS kcal,
                   COUNT(se.id)                  AS events
            FROM users u
            JOIN user_step_events se ON se.user_id = u.id
            $where
            GROUP BY u.id, u.full_name, u.email, u.user_image, u.country_code
            ORDER BY steps DESC
            LIMIT 10
        ";
        $q    = $db->query($sql, $binds);
        $rows = $q ? $q->getResultArray() : [];

        return $this->success_response("Top walkers.", [
            'range'       => ['from' => $from, 'to' => $to, 'label' => $label],
            'top_walkers' => $rows,
        ]);
    }
}
