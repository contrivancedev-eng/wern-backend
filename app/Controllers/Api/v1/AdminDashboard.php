<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminDashboard extends ApiController
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

        // KPIs
        $totalUsers   = (int) $one("SELECT COUNT(*) FROM users");
        $activeUsers  = (int) $one("SELECT COUNT(*) FROM users WHERE status = 1");
        $newToday     = (int) $one("SELECT COUNT(*) FROM users WHERE DATE(create_on) = CURDATE()");
        $walkingNow   = (int) $one("SELECT COUNT(DISTINCT user_id) FROM user_step_events WHERE event_time >= (NOW() - INTERVAL 5 MINUTE)");
        $stepsToday   = (int) $one("SELECT COALESCE(SUM(steps),0) FROM user_step_events WHERE DATE(is_date) = CURDATE()");
        $kmToday      = (float) $one("SELECT COALESCE(SUM(kilometre),0) FROM user_step_events WHERE DATE(is_date) = CURDATE()");
        $kcalToday    = (float) $one("SELECT COALESCE(SUM(kcal),0) FROM user_step_events WHERE DATE(is_date) = CURDATE()");
        $littiesToday = (int) $one("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE DATE(`date`) = CURDATE() AND type = 1");
        $littiesTotal = (int) $one("SELECT COALESCE(SUM(CASE WHEN type=1 THEN points ELSE -points END),0) FROM earn_litties");

        // Weekly steps trend (last 7 days)
        $stepsTrend = $all("
            SELECT DATE(is_date) AS d, COALESCE(SUM(steps),0) AS steps
            FROM user_step_events
            WHERE DATE(is_date) >= (CURDATE() - INTERVAL 6 DAY)
            GROUP BY DATE(is_date)
            ORDER BY d
        ");

        // User growth last 30 days
        $userGrowth = $all("
            SELECT DATE(create_on) AS d, COUNT(*) AS c
            FROM users
            WHERE DATE(create_on) >= (CURDATE() - INTERVAL 29 DAY)
            GROUP BY DATE(create_on)
            ORDER BY d
        ");

        // Cause distribution (total steps per category, all-time)
        $causeDist = $all("
            SELECT ec.id, ec.category_name AS name, COALESCE(SUM(se.steps),0) AS steps
            FROM earn_category ec
            LEFT JOIN user_step_events se ON ec.id = se.category_id
            GROUP BY ec.id, ec.category_name
            ORDER BY steps DESC
        ");

        // Transaction trends last 14 days (earn vs deduct)
        $txTrends = $all("
            SELECT DATE(`date`) AS d,
                   SUM(CASE WHEN type=1 THEN points ELSE 0 END) AS earned,
                   SUM(CASE WHEN type=2 THEN points ELSE 0 END) AS spent
            FROM earn_litties
            WHERE DATE(`date`) >= (CURDATE() - INTERVAL 13 DAY)
            GROUP BY DATE(`date`)
            ORDER BY d
        ");

        // Peak hours today
        $peakHours = $all("
            SELECT HOUR(event_time) AS h, COUNT(*) AS c
            FROM user_step_events
            WHERE DATE(is_date) = CURDATE()
            GROUP BY HOUR(event_time)
            ORDER BY h
        ");

        // Top walkers today
        $topWalkers = $all("
            SELECT u.id, u.full_name, u.user_image, COALESCE(SUM(se.steps),0) AS steps
            FROM users u
            LEFT JOIN user_step_events se ON u.id = se.user_id AND DATE(se.is_date) = CURDATE()
            GROUP BY u.id, u.full_name, u.user_image
            HAVING steps > 0
            ORDER BY steps DESC
            LIMIT 10
        ");

        // Recent signups
        $recentUsers = $all("
            SELECT id, full_name, email, create_on
            FROM users
            ORDER BY id DESC
            LIMIT 8
        ");

        // Country breakdown — users by phone country_code + step activity joined via user
        $codeMap = [
            '+1'   => ['US',  'United States',  '🇺🇸', 39.8, -98.6],
            '+44'  => ['GB',  'United Kingdom', '🇬🇧', 54.0,  -2.0],
            '+49'  => ['DE',  'Germany',        '🇩🇪', 51.1,  10.4],
            '+91'  => ['IN',  'India',          '🇮🇳', 20.6,  78.9],
            '+971' => ['AE',  'UAE',            '🇦🇪', 23.4,  53.8],
            '+63'  => ['PH',  'Philippines',    '🇵🇭', 12.9, 121.8],
            '+974' => ['QA',  'Qatar',          '🇶🇦', 25.3,  51.2],
            '+966' => ['SA',  'Saudi Arabia',   '🇸🇦', 23.9,  45.1],
            '+86'  => ['CN',  'China',          '🇨🇳', 35.9, 104.2],
            '+33'  => ['FR',  'France',         '🇫🇷', 46.2,   2.2],
            '+61'  => ['AU',  'Australia',      '🇦🇺',-25.3, 133.8],
            '+81'  => ['JP',  'Japan',          '🇯🇵', 36.2, 138.3],
            '+880' => ['BD',  'Bangladesh',     '🇧🇩', 23.7,  90.4],
            '+92'  => ['PK',  'Pakistan',       '🇵🇰', 30.4,  69.3],
            '+65'  => ['SG',  'Singapore',      '🇸🇬',  1.4, 103.8],
        ];
        $rawCountries = $all("
            SELECT u.country_code AS code,
                   COUNT(DISTINCT u.id) AS users,
                   COALESCE(SUM(se.steps), 0) AS steps,
                   COUNT(DISTINCT se.user_id) AS active_users
            FROM users u
            LEFT JOIN user_step_events se ON se.user_id = u.id
            WHERE u.country_code IS NOT NULL AND u.country_code != ''
            GROUP BY u.country_code
            ORDER BY users DESC
        ");
        $countries = [];
        foreach ($rawCountries as $row) {
            $code = $row['code'];
            $info = $codeMap[$code] ?? [$code, $code, '🌐', 0, 0];
            $countries[] = [
                'phone_code'   => $code,
                'iso'          => $info[0],
                'name'         => $info[1],
                'flag'         => $info[2],
                'lat'          => $info[3],
                'lng'          => $info[4],
                'users'        => (int) $row['users'],
                'steps'        => (int) $row['steps'],
                'active_users' => (int) $row['active_users'],
            ];
        }

        // GPS event clusters (last 7 days) — bucket by rounded lat/lng, cap at 50 markers
        $gpsClusters = $all("
            SELECT ROUND(latitude, 2) AS lat, ROUND(longitude, 2) AS lng,
                   COUNT(*) AS events, COUNT(DISTINCT user_id) AS walkers, SUM(steps) AS steps
            FROM user_step_events
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
              AND DATE(is_date) >= (CURDATE() - INTERVAL 7 DAY)
            GROUP BY ROUND(latitude, 2), ROUND(longitude, 2)
            ORDER BY events DESC
            LIMIT 50
        ");

        return $this->success_response("Dashboard stats.", [
            'kpi' => [
                'total_users'    => $totalUsers,
                'active_users'   => $activeUsers,
                'new_today'      => $newToday,
                'walking_now'    => $walkingNow,
                'steps_today'    => $stepsToday,
                'km_today'       => round($kmToday, 2),
                'kcal_today'     => round($kcalToday, 2),
                'litties_today'  => $littiesToday,
                'litties_total'  => $littiesTotal,
            ],
            'steps_trend'   => $stepsTrend,
            'user_growth'   => $userGrowth,
            'cause_dist'    => $causeDist,
            'tx_trends'     => $txTrends,
            'peak_hours'    => $peakHours,
            'top_walkers'   => $topWalkers,
            'recent_users'  => $recentUsers,
            'countries'     => $countries,
            'gps_clusters'  => $gpsClusters,
        ]);
    }
}
