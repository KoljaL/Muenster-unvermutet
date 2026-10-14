CREATE TABLE IF NOT EXISTS stations (
    id TEXT PRIMARY KEY,
    number INTEGER NOT NULL,
    title TEXT NOT NULL,
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
    created_at TEXT NOT NULL
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
