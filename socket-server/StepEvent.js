const db = require("./db");

/* ============================
   CONSTANTS  (match HTTP API)
============================ */
const STEP_LENGTH_KM = 0.000762;
const KCAL_PER_STEP  = 0.04;
const DAILY_GOAL     = 10000;
const IST_OFFSET_SEC = 5 * 3600 + 30 * 60;

/* ============================
   RESPONSE WRAPPERS  (match ApiController.php)
============================ */
function successResponse(message = "Success!", data = {}) {
  const out = { status_code: 200, status: true, message };
  if (data && Object.keys(data).length) out.data = data;
  return out;
}

function errorResponse(message = "Error!", data = {}) {
  const out = { status_code: 200, status: false, message };
  if (data && Object.keys(data).length) out.data = data;
  return out;
}

/* ============================
   POINT SYSTEM
============================ */
function calculatePoints(steps) {
  if (steps >= 1   && steps <= 100)   return 0.1;
  if (steps >= 101 && steps <= 1000)  return 1.0;
  if (steps >= 1001 && steps <= 5000) return 5.0;
  if (steps > 5000) return 10.0;
  return 0;
}

/* ============================
   TODAY SUMMARY
   When category_id is 0/null → totals across ALL categories.
   When category_id > 0       → totals for that category only.
============================ */
async function getTodayProgress(user_id, category_id, today) {
  const useAll = !category_id || category_id <= 0;

  const stepSql = useAll
    ? `SELECT COALESCE(SUM(steps),0)     AS total_steps,
              COALESCE(SUM(kilometre),0) AS total_km,
              COALESCE(SUM(kcal),0)      AS total_kcal
       FROM user_step_events
       WHERE user_id=? AND is_date=?`
    : `SELECT COALESCE(SUM(steps),0)     AS total_steps,
              COALESCE(SUM(kilometre),0) AS total_km,
              COALESCE(SUM(kcal),0)      AS total_kcal
       FROM user_step_events
       WHERE user_id=? AND category_id=? AND is_date=?`;

  const stepParams = useAll ? [user_id, today] : [user_id, category_id, today];
  const [[s]] = await db.query(stepSql, stepParams);

  const litSql = useAll
    ? `SELECT COALESCE(SUM(points),0) AS total_litres
       FROM earn_litties
       WHERE user_id=? AND DATE(date)=?`
    : `SELECT COALESCE(SUM(points),0) AS total_litres
       FROM earn_litties
       WHERE user_id=? AND earn_category_id=? AND DATE(date)=?`;

  const litParams = useAll ? [user_id, today] : [user_id, category_id, today];
  const [[l]] = await db.query(litSql, litParams);

  return {
    steps:     Number(s.total_steps),
    goal:      DAILY_GOAL,
    kilometre: Number(Number(s.total_km).toFixed(1)),
    kcal:      Math.round(s.total_kcal),
    litres:    Number(Number(l.total_litres).toFixed(2))
  };
}

/* ============================
   SOCKET HANDLER
============================ */
async function handleStep(socket, data) {
  try {
    const user_id     = Number(data.user_id);
    const steps       = Number(data.steps);
    const type        = String(data.type ?? "");
    const timestamp   = Number(data.timestamp);
    // If client sends null/0/undefined → store 0 (no default fallback).
    const category_id = Number(data.category_id) > 0 ? Number(data.category_id) : 0;

    if (!user_id || user_id <= 0) {
      return socket.emit("step_ack", errorResponse("Invalid user_id"));
    }

    if (data.steps === undefined || data.steps === null ||
        data.timestamp === undefined || data.timestamp === null ||
        type === "") {
      return socket.emit("step_ack", errorResponse("Missing required fields: steps, timestamp, type"));
    }

    // Anti-cheat
    if (steps < 1 || steps > 5000) {
      return socket.emit("step_ack", errorResponse("Invalid step count"));
    }

    /* TIMESTAMP (same logic as HTTP) */
    const nowSec = Math.floor(Date.now() / 1000);
    let ts = timestamp;
    if (!ts || ts <= 0 || ts > nowSec) ts = nowSec;

    const istTs      = ts + IST_OFFSET_SEC;
    const event_time = new Date(istTs * 1000).toISOString().slice(0, 19).replace("T", " ");
    const is_date    = new Date(istTs * 1000).toISOString().slice(0, 10);

    const kilometre = Number((steps * STEP_LENGTH_KM).toFixed(4));
    const kcal      = Number((steps * KCAL_PER_STEP).toFixed(2));

    const lat = (data.lat === undefined || data.lat === null || data.lat === "") ? null : Number(data.lat);
    const lng = (data.lng === undefined || data.lng === null || data.lng === "") ? null : Number(data.lng);

    /* INSERT step event */
    await db.query(
      `INSERT INTO user_step_events
       (user_id, category_id, steps, kilometre, kcal, type, latitude, longitude, event_time, is_date)
       VALUES (?,?,?,?,?,?,?,?,?,?)`,
      [user_id, category_id, steps, kilometre, kcal, type, lat, lng, event_time, is_date]
    );

    /* Compute points + upsert earn_litties */
    let summary = await getTodayProgress(user_id, category_id, is_date);
    const totalStepsToday = summary.steps;
    const points = calculatePoints(totalStepsToday);
    const description = `Earned ${points} points for ${totalStepsToday} steps`;

    const [rows] = await db.query(
      `SELECT id FROM earn_litties
       WHERE user_id=? AND earn_category_id=? AND DATE(date)=?`,
      [user_id, category_id, is_date]
    );

    if (rows.length) {
      await db.query(
        `UPDATE earn_litties SET points=?, description=? WHERE id=?`,
        [points, description, rows[0].id]
      );
    } else {
      await db.query(
        `INSERT INTO earn_litties
         (user_id, earn_category_id, points, description, date)
         VALUES (?,?,?,?,?)`,
        [user_id, category_id, points, description, event_time]
      );
    }

    summary = await getTodayProgress(user_id, category_id, is_date);

    /* Response: same shape as HTTP success_response */
    socket.emit("step_ack", successResponse("Step event saved", {
      user_id,
      category_id,
      steps:     summary.steps,
      goal:      summary.goal,
      kilometre: summary.kilometre,
      kcal:      summary.kcal,
      litres:    summary.litres
    }));

  } catch (e) {
    console.error("STEP ERROR:", e);
    socket.emit("step_ack", errorResponse("Server error"));
  }
}

module.exports = { handleStep };
