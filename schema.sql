CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT,
    username TEXT,
    phone TEXT,
    balance INTEGER NOT NULL DEFAULT 0,
    withdrawn INTEGER NOT NULL DEFAULT 0,
    referral_count INTEGER NOT NULL DEFAULT 0,
    referred_by INTEGER,
    is_banned INTEGER NOT NULL DEFAULT 0,
    join_check_clicks INTEGER NOT NULL DEFAULT 0,
    state TEXT,
    state_data TEXT NOT NULL DEFAULT '{}',
    gate_ok_until INTEGER,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);

CREATE TABLE IF NOT EXISTS channels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    title TEXT
);

CREATE TABLE IF NOT EXISTS payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS templates (
    code TEXT PRIMARY KEY,
    content TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS buttons (
    code TEXT PRIMARY KEY,
    label TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS withdrawals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    payment_method TEXT NOT NULL,
    game_id TEXT NOT NULL,
    amount INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    resolved_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_withdrawals_user ON withdrawals(user_id);
CREATE INDEX IF NOT EXISTS idx_withdrawals_status ON withdrawals(status);
