CREATE TABLE IF NOT EXISTS stations (
    id TEXT PRIMARY KEY,
    number INTEGER NOT NULL,
    title TEXT NOT NULL,
    teaser_text TEXT,
    hint TEXT NOT NULL,
    lat NUMERIC NOT NULL,
    lon NUMERIC NOT NULL,
    correct_option_index INTEGER NOT NULL,
    solution_title TEXT NOT NULL,
    solution_caption TEXT,
    info TEXT NOT NULL,
    task_image TEXT NOT NULL,
    solution_image TEXT,
    category TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'published'
        CHECK (status IN ('published', 'archived')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS stations_number_unique ON stations(number);
CREATE INDEX IF NOT EXISTS stations_status_index ON stations(status);

CREATE TABLE IF NOT EXISTS options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    station_id TEXT NOT NULL,
    option_index INTEGER NOT NULL,
    option_text TEXT NOT NULL,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
    UNIQUE (station_id, option_index)
);

CREATE TABLE IF NOT EXISTS links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    station_id TEXT NOT NULL,
    title TEXT NOT NULL,
    url TEXT NOT NULL,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    must_change_password INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS station_suggestions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    hint TEXT,
    lat NUMERIC,
    lon NUMERIC,
    question TEXT,
    options_json TEXT,
    correct_option_index INTEGER,
    info TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'detail',
    suggested_by_name TEXT,
    suggested_by_email TEXT,
    task_image TEXT,
    solution_image TEXT,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'approved', 'rejected')),
    created_at TEXT NOT NULL,
    reviewed_at TEXT
);

CREATE INDEX IF NOT EXISTS suggestions_status_index ON station_suggestions(status);

-- Hilfstabelle für Rate-Limiting (Login und Vorschläge)
CREATE TABLE IF NOT EXISTS rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bucket TEXT NOT NULL,
    key TEXT NOT NULL,
    created_at INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS rate_limits_lookup ON rate_limits(bucket, key, created_at);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS categories (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    icon TEXT,
    color TEXT NOT NULL DEFAULT '#065f46'
        CHECK (length(color) = 7 AND color GLOB '#[0-9A-Fa-f][0-9A-Fa-f][0-9A-Fa-f][0-9A-Fa-f][0-9A-Fa-f][0-9A-Fa-f]'),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

INSERT OR IGNORE INTO categories (id, name, description, icon, color, created_at, updated_at) VALUES
    ('history', 'Geschichte', 'Historische Ereignisse und Orte.', NULL, '#92400e', datetime('now'), datetime('now')),
    ('art', 'Kunst', 'Kunstwerke und Skulpturen im Stadtbild.', NULL, '#9d174d', datetime('now'), datetime('now')),
    ('legend', 'Sagen', 'Sagen und Legenden aus Münster.', NULL, '#5b21b6', datetime('now'), datetime('now')),
    ('faith', 'Glaube', 'Kirchen und religiöse Orte.', NULL, '#1d4ed8', datetime('now'), datetime('now')),
    ('detail', 'Details', 'Kleine Besonderheiten am Wegesrand.', NULL, '#065f46', datetime('now'), datetime('now')),
    ('culture', 'Kultur', 'Kultur, Brauchtum und Kuriositäten.', NULL, '#b45309', datetime('now'), datetime('now'));

-- Pseudonyme Nutzungsstatistik (nur nach Einwilligung; player_id ist eine zufällige Browser-ID)
CREATE TABLE IF NOT EXISTS stat_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    player_id TEXT NOT NULL,
    station_id TEXT,
    event TEXT NOT NULL
        CHECK (event IN ('opened', 'location_verified', 'hint_used', 'wrong_answer', 'solution_shown', 'solved', 'game_completed')),
    attempts INTEGER,
    created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS stat_events_day ON stat_events(created_at);
CREATE INDEX IF NOT EXISTS stat_events_player ON stat_events(player_id);
CREATE UNIQUE INDEX IF NOT EXISTS stat_events_once ON stat_events(player_id, COALESCE(station_id, ''), event)
    WHERE event IN ('location_verified', 'solution_shown', 'solved', 'game_completed');

-- Beispieldaten für die Statistik (20 Ereignisse, Kennungen 'aaaaaaaa-…', 'bbbbbbbb-…', 'cccccccc-…').
-- Werden nur eingefügt, solange stat_events leer ist. Vor dem Livegang löschen:
-- DELETE FROM stat_events WHERE player_id LIKE 'aaaaaaaa-%' OR player_id LIKE 'bbbbbbbb-%' OR player_id LIKE 'cccccccc-%';
INSERT INTO stat_events (player_id, station_id, event, attempts, created_at)
SELECT column1, column2, column3, column4, column5 FROM (VALUES
    ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'jan-van-leiden', 'opened', NULL, '2026-10-14T10:02:11+02:00'),
    ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'jan-van-leiden', 'location_verified', NULL, '2026-10-14T10:09:40+02:00'),
    ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'jan-van-leiden', 'wrong_answer', 1, '2026-10-14T10:10:25+02:00'),
    ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'jan-van-leiden', 'solved', 1, '2026-10-14T10:11:02+02:00'),
    ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'schleifkerben', 'opened', NULL, '2026-10-14T10:25:48+02:00'),
    ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'kranich-am-dom', 'opened', NULL, '2026-10-15T15:40:05+02:00'),
    ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'kranich-am-dom', 'hint_used', NULL, '2026-10-15T15:44:31+02:00'),
    ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'kranich-am-dom', 'location_verified', NULL, '2026-10-15T15:52:19+02:00'),
    ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'kranich-am-dom', 'solved', 0, '2026-10-15T15:53:07+02:00'),
    ('cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'haus-der-niederlande', 'opened', NULL, '2026-10-16T18:03:50+02:00'),
    ('cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'haus-der-niederlande', 'wrong_answer', 1, '2026-10-16T18:09:12+02:00'),
    ('cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'haus-der-niederlande', 'wrong_answer', 2, '2026-10-16T18:09:48+02:00'),
    ('cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'haus-der-niederlande', 'solution_shown', NULL, '2026-10-16T18:10:30+02:00'),
    ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'schleifkerben', 'location_verified', NULL, '2026-10-18T11:15:22+02:00'),
    ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'schleifkerben', 'solved', 0, '2026-10-18T11:16:40+02:00'),
    ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'aegidii-inschriftenstein', 'opened', NULL, '2026-10-18T14:30:09+02:00'),
    ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'aegidii-inschriftenstein', 'hint_used', NULL, '2026-10-18T14:33:55+02:00'),
    ('cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'jan-van-leiden', 'opened', NULL, '2026-10-20T09:12:37+02:00'),
    ('cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'jan-van-leiden', 'location_verified', NULL, '2026-10-20T09:20:03+02:00'),
    ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', NULL, 'game_completed', NULL, '2026-10-20T16:45:00+02:00')
) WHERE NOT EXISTS (SELECT 1 FROM stat_events);
