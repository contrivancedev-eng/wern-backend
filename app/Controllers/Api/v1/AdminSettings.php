<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminSettings extends ApiController
{
    public function get()
    {
        // Identity comes from the session, never from client input.
        $adminId = (int) session()->get('authID');

        $db = Database::connect();
        $rowOf = function ($sql, $binds = []) use ($db) {
            $q = $db->query($sql, $binds);
            return $q ? ($q->getRowArray() ?? []) : [];
        };
        $allOf = function ($sql) use ($db) {
            $q = $db->query($sql);
            return $q ? $q->getResultArray() : [];
        };

        $admin = $adminId > 0
            ? $rowOf("SELECT id, name, email, status, created_at FROM admin WHERE id = ?", [$adminId])
            : $rowOf("SELECT id, name, email, status, created_at FROM admin ORDER BY id ASC LIMIT 1");

        $settings = $rowOf("SELECT id, twilio_sid, twilio_token, twilio_phone FROM settings ORDER BY id ASC LIMIT 1");

        $admins   = $allOf("SELECT id, name, email, status, created_at FROM admin ORDER BY id ASC");

        return $this->success_response("Settings.", [
            'admin'     => $admin,
            'admins'    => $admins,
            'twilio'    => $settings,
        ]);
    }

    public function updateProfile()
    {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true) ?: [];
        $id   = (int) session()->get('authID'); // session, not client input
        $name = trim($data['name']  ?? '');
        $email= trim($data['email'] ?? '');

        if ($id <= 0)      return $this->error_response("Not authenticated.");
        if ($name === '')  return $this->error_response("Name is required.");
        if ($email === '') return $this->error_response("Email is required.");

        $db = Database::connect();
        $exists = $db->query("SELECT id FROM admin WHERE id = ?", [$id])->getRowArray();
        if (!$exists) return $this->error_response("Admin not found.");

        $dup = $db->query("SELECT id FROM admin WHERE email = ? AND id != ?", [$email, $id])->getRowArray();
        if ($dup) return $this->error_response("Another admin uses that email.");

        $db->table('admin')->where('id', $id)->update([
            'name'  => $name,
            'email' => $email,
        ]);
        // Keep the session greeting in sync.
        session()->set(['authName' => $name, 'authEmail' => $email]);
        return $this->success_response("Profile updated.", ['id'=>$id, 'name'=>$name, 'email'=>$email]);
    }

    public function changePassword()
    {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true) ?: [];
        $id       = (int) session()->get('authID'); // session, not client input
        $current  = trim($data['current_password'] ?? '');
        $next     = trim($data['new_password']     ?? '');
        $confirm  = trim($data['confirm_password'] ?? '');

        if ($id <= 0)         return $this->error_response("Not authenticated.");
        if ($current === '')  return $this->error_response("Current password required.");
        if ($next === '')     return $this->error_response("New password required.");
        if (strlen($next) < 6)return $this->error_response("New password must be at least 6 characters.");
        if ($next !== $confirm) return $this->error_response("Passwords do not match.");

        $db  = Database::connect();
        $row = $db->query("SELECT id, password FROM admin WHERE id = ?", [$id])->getRowArray();
        if (!$row) return $this->error_response("Admin not found.");

        // Verify current password against bcrypt, falling back to legacy md5.
        $stored = (string) $row['password'];
        $ok = preg_match('/^[a-f0-9]{32}$/i', $stored)
            ? hash_equals($stored, md5($current))
            : password_verify($current, $stored);
        if (!$ok) return $this->error_response("Current password is incorrect.");

        $db->table('admin')->where('id', $id)->update([
            'password' => password_hash($next, PASSWORD_DEFAULT),
        ]);
        return $this->success_response("Password changed.");
    }

    public function updateTwilio()
    {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true) ?: [];

        $payload = [];
        foreach (['twilio_sid','twilio_token','twilio_phone'] as $k) {
            if (isset($data[$k])) $payload[$k] = trim($data[$k]);
        }
        if (!$payload) return $this->error_response("No Twilio fields provided.");

        $db = Database::connect();
        $existing = $db->table('settings')->orderBy('id', 'ASC')->get()->getRowArray();
        if ($existing) $db->table('settings')->where('id', $existing['id'])->update($payload);
        else           $db->table('settings')->insert($payload);

        return $this->success_response("Twilio settings updated.", $payload);
    }
}
