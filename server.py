import base64
import hashlib
import hmac
import json
import os
import re
import sqlite3
import time
from datetime import datetime, timezone
from http import HTTPStatus
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import urlparse

BASE_DIR = Path(__file__).resolve().parent
DB_PATH = BASE_DIR / "apps.db"
DEFAULT_APPS_PATH = BASE_DIR / "apps.json"

def get_required_env(name, min_len=1):
    value = os.getenv(name, "").strip()
    if not value:
        raise RuntimeError(f"Missing required environment variable: {name}")
    if len(value) < min_len:
        raise RuntimeError(f"{name} must be at least {min_len} characters long")
    return value


ADMIN_PASSWORD = get_required_env("ADMIN_PASSWORD", min_len=8)
ADMIN_SECRET = get_required_env("ADMIN_SECRET", min_len=16)
TOKEN_TTL_SECONDS = int(os.getenv("TOKEN_TTL_SECONDS", "28800"))
PORT = int(os.getenv("PORT", "8000"))

LOGIN_WINDOW_SECONDS = 600
LOGIN_MAX_ATTEMPTS = 5
LOGIN_ATTEMPTS = {}


def utc_iso_now():
    return datetime.now(timezone.utc).isoformat()


def build_token(role, expiry_timestamp):
    payload = json.dumps({"exp": expiry_timestamp, "role": role}, separators=(",", ":")).encode("utf-8")
    payload_b64 = base64.urlsafe_b64encode(payload).rstrip(b"=")
    signature = hmac.new(ADMIN_SECRET.encode("utf-8"), payload_b64, hashlib.sha256).digest()
    signature_b64 = base64.urlsafe_b64encode(signature).rstrip(b"=")
    return f"{payload_b64.decode('utf-8')}.{signature_b64.decode('utf-8')}"


def get_token_payload(token):
    try:
        payload_b64, signature_b64 = token.split(".", 1)
    except ValueError:
        return None

    expected_signature = hmac.new(
        ADMIN_SECRET.encode("utf-8"),
        payload_b64.encode("utf-8"),
        hashlib.sha256,
    ).digest()
    expected_signature_b64 = base64.urlsafe_b64encode(expected_signature).rstrip(b"=").decode("utf-8")

    if not hmac.compare_digest(signature_b64, expected_signature_b64):
        return None

    padded_payload = payload_b64 + "=" * ((4 - len(payload_b64) % 4) % 4)
    try:
        payload = json.loads(base64.urlsafe_b64decode(padded_payload.encode("utf-8")).decode("utf-8"))
    except (json.JSONDecodeError, ValueError):
        return None
    exp = int(payload.get("exp", 0))
    if int(time.time()) >= exp:
        return None
    if payload.get("role") != "admin":
        return None
    return payload


def validate_token(token):
    return get_token_payload(token) is not None


def get_db_connection():
    connection = sqlite3.connect(DB_PATH)
    connection.row_factory = sqlite3.Row
    return connection


def initialize_database():
    connection = get_db_connection()
    with connection:
        connection.execute(
            """
            CREATE TABLE IF NOT EXISTS apps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category TEXT NOT NULL,
                bonus TEXT NOT NULL,
                withdraw_text TEXT NOT NULL,
                rating REAL NOT NULL,
                url TEXT NOT NULL,
                logo TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
            """
        )

    count = connection.execute("SELECT COUNT(*) AS count FROM apps").fetchone()["count"]
    if count == 0:
        seed_defaults(connection)

    connection.close()


def read_default_apps():
    if DEFAULT_APPS_PATH.exists():
        try:
            data = json.loads(DEFAULT_APPS_PATH.read_text(encoding="utf-8"))
            if isinstance(data, list):
                return data
        except json.JSONDecodeError:
            pass

    return [
        {
            "name": "Lucky Mango",
            "category": "New Apps",
            "bonus": "Sign-up Bonus: Rs 75",
            "withdraw": "Min Withdraw: Rs 100",
            "rating": 4.4,
            "url": "https://example.com/lucky-mango",
            "logo": "https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=900&q=60",
        }
    ]


def parse_rating_value(value):
    if isinstance(value, (int, float)):
        return float(value)

    text = str(value or "").strip()
    if not text:
        raise ValueError("Field 'rating' is required.")

    match = re.match(r"^(\d+(?:\.\d+)?)\s*(?:/\s*5)?$", text)
    if not match:
        raise ValueError("Field 'rating' must be like 4.5 or 4.5/5.")

    return float(match.group(1))


def get_app_validation_error(app):
    if not isinstance(app, dict):
        return "App payload must be an object."

    required_string_fields = ["name", "category", "bonus", "withdraw", "url", "logo"]
    for field in required_string_fields:
        value = str(app.get(field, "")).strip()
        if not value:
            return f"Field '{field}' is required."
        if field == "logo":
            # Support uploaded images sent as data URLs while still limiting abuse.
            if value.startswith("data:image/"):
                if len(value) > 2_500_000:
                    return "Field 'logo' image is too large. Use a smaller image."
            elif len(value) > 5000:
                return "Field 'logo' is too long."
        elif len(value) > 5000:
            return f"Field '{field}' is too long."

    try:
        rating = parse_rating_value(app.get("rating", ""))
    except ValueError as error:
        return str(error)

    if rating < 1 or rating > 5:
        return "Field 'rating' must be between 1 and 5."

    if not str(app.get("url", "")).startswith(("http://", "https://")):
        return "Field 'url' must start with http:// or https://."

    return None


def validate_app(app):
    return get_app_validation_error(app) is None


def insert_apps(connection, apps):
    now_iso = utc_iso_now()
    with connection:
        connection.executemany(
            """
            INSERT INTO apps (name, category, bonus, withdraw_text, rating, url, logo, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            """,
            [
                (
                    str(app["name"]).strip(),
                    str(app["category"]).strip(),
                    str(app["bonus"]).strip(),
                    str(app["withdraw"]).strip(),
                    parse_rating_value(app["rating"]),
                    str(app["url"]).strip(),
                    str(app["logo"]).strip(),
                    now_iso,
                )
                for app in apps
            ],
        )


def update_app(connection, app_id, app):
    now_iso = utc_iso_now()
    with connection:
        cursor = connection.execute(
            """
            UPDATE apps
            SET name = ?, category = ?, bonus = ?, withdraw_text = ?, rating = ?, url = ?, logo = ?, created_at = ?
            WHERE id = ?
            """,
            (
                str(app["name"]).strip(),
                str(app["category"]).strip(),
                str(app["bonus"]).strip(),
                str(app["withdraw"]).strip(),
                parse_rating_value(app["rating"]),
                str(app["url"]).strip(),
                str(app["logo"]).strip(),
                now_iso,
                app_id,
            ),
        )
    return cursor.rowcount > 0


def delete_app(connection, app_id):
    with connection:
        cursor = connection.execute("DELETE FROM apps WHERE id = ?", (app_id,))
    return cursor.rowcount > 0


def seed_defaults(connection):
    defaults = [app for app in read_default_apps() if validate_app(app)]
    if defaults:
        insert_apps(connection, defaults)


def select_apps(connection):
    rows = connection.execute(
        """
        SELECT id, name, category, bonus, withdraw_text, rating, url, logo, created_at
        FROM apps
        ORDER BY id DESC
        """
    ).fetchall()

    apps = []
    for row in rows:
        apps.append(
            {
                "id": row["id"],
                "name": row["name"],
                "category": row["category"],
                "bonus": row["bonus"],
                "withdraw": row["withdraw_text"],
                "rating": row["rating"],
                "url": row["url"],
                "logo": row["logo"],
                "createdAt": row["created_at"],
            }
        )
    return apps


class AppHandler(SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=str(BASE_DIR), **kwargs)

    def log_message(self, format_string, *args):
        super().log_message(format_string, *args)

    def end_headers(self):
        request_path = urlparse(self.path).path
        self.send_header("X-Content-Type-Options", "nosniff")
        self.send_header("X-Frame-Options", "DENY")
        self.send_header("Referrer-Policy", "strict-origin-when-cross-origin")
        self.send_header("Content-Security-Policy", "default-src 'self'; img-src 'self' data: https:; style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self'; connect-src 'self';")
        self.send_header("Access-Control-Allow-Origin", "*")
        if request_path.startswith("/api/"):
            self.send_header("Cache-Control", "no-store")
        elif request_path.endswith((".css", ".js", ".png", ".jpg", ".jpeg", ".webp", ".svg", ".ico", ".woff", ".woff2")):
            self.send_header("Cache-Control", "public, max-age=86400")
        elif request_path.endswith(".html") or request_path in {"", "/"}:
            self.send_header("Cache-Control", "public, max-age=300")
        super().end_headers()

    def do_OPTIONS(self):
        self.send_response(HTTPStatus.NO_CONTENT)
        self.send_header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Authorization")
        self.end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path == "/api/apps":
            self.handle_get_apps()
            return

        if parsed.path == "/api/health":
            self.send_json({"ok": True, "timestamp": utc_iso_now()})
            return

        if parsed.path == "/api/admin/session":
            self.handle_admin_session()
            return

        if parsed.path == "/api/auth/session":
            self.handle_auth_session()
            return

        if parsed.path == "/api/admin/export":
            self.handle_export_apps()
            return

        if parsed.path == "/":
            self.path = "/index.html"

        return super().do_GET()

    def do_POST(self):
        parsed = urlparse(self.path)
        if parsed.path == "/api/admin/login":
            self.handle_admin_login()
            return

        if parsed.path == "/api/apps":
            self.handle_add_app()
            return

        if parsed.path == "/api/apps/import":
            self.handle_import_apps()
            return

        if parsed.path == "/api/admin/reset":
            self.handle_reset_apps()
            return

        self.send_json({"error": "Not found"}, status=HTTPStatus.NOT_FOUND)

    def do_PUT(self):
        parsed = urlparse(self.path)
        app_id = self.get_app_id_from_path(parsed.path)
        if app_id is None:
            self.send_json({"error": "Not found"}, status=HTTPStatus.NOT_FOUND)
            return

        self.handle_update_app(app_id)

    def do_DELETE(self):
        parsed = urlparse(self.path)
        app_id = self.get_app_id_from_path(parsed.path)
        if app_id is None:
            self.send_json({"error": "Not found"}, status=HTTPStatus.NOT_FOUND)
            return

        self.handle_delete_app(app_id)

    def parse_json_body(self):
        content_length = int(self.headers.get("Content-Length", "0"))
        if content_length <= 0:
            return None

        body = self.rfile.read(content_length)
        try:
            return json.loads(body.decode("utf-8"))
        except json.JSONDecodeError:
            return None

    def get_app_id_from_path(self, path):
        prefix = "/api/apps/"
        if not path.startswith(prefix):
            return None

        app_id_text = path[len(prefix):].strip("/")
        if not app_id_text.isdigit():
            return None

        return int(app_id_text)

    def send_json(self, payload, status=HTTPStatus.OK):
        encoded = json.dumps(payload).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(encoded)))
        self.end_headers()
        self.wfile.write(encoded)

    def get_client_ip(self):
        forwarded = self.headers.get("X-Forwarded-For", "").split(",")[0].strip()
        return forwarded or self.client_address[0]

    def is_rate_limited(self):
        ip = self.get_client_ip()
        now = int(time.time())
        bucket = LOGIN_ATTEMPTS.get(ip, [])
        bucket = [timestamp for timestamp in bucket if now - timestamp < LOGIN_WINDOW_SECONDS]
        LOGIN_ATTEMPTS[ip] = bucket
        if len(bucket) >= LOGIN_MAX_ATTEMPTS:
            return True
        bucket.append(now)
        LOGIN_ATTEMPTS[ip] = bucket
        return False

    def get_bearer_token(self):
        auth_header = self.headers.get("Authorization", "")
        if not auth_header.startswith("Bearer "):
            return ""
        return auth_header[7:].strip()

    def require_admin(self):
        token = self.get_bearer_token()
        payload = get_token_payload(token) if token else None
        if not payload or payload.get("role") != "admin":
            self.send_json({"error": "Unauthorized"}, status=HTTPStatus.UNAUTHORIZED)
            return False
        return True

    def handle_get_apps(self):
        connection = get_db_connection()
        apps = select_apps(connection)
        connection.close()
        self.send_json({"apps": apps})

    def handle_admin_session(self):
        token = self.get_bearer_token()
        payload = get_token_payload(token) if token else None
        if payload and payload.get("role") == "admin":
            self.send_json({"ok": True})
            return
        self.send_json({"ok": False}, status=HTTPStatus.UNAUTHORIZED)

    def handle_admin_login(self):
        if self.is_rate_limited():
            self.send_json(
                {"error": "Too many attempts. Try again in a few minutes."},
                status=HTTPStatus.TOO_MANY_REQUESTS,
            )
            return

        payload = self.parse_json_body() or {}
        password = str(payload.get("password", ""))
        if not hmac.compare_digest(password, ADMIN_PASSWORD):
            self.send_json({"error": "Invalid credentials"}, status=HTTPStatus.UNAUTHORIZED)
            return

        token = build_token("admin", int(time.time()) + TOKEN_TTL_SECONDS)
        self.send_json({"token": token, "expiresIn": TOKEN_TTL_SECONDS, "role": "admin"})

    def handle_auth_session(self):
        token = self.get_bearer_token()
        payload = get_token_payload(token) if token else None
        if payload:
            self.send_json({"ok": True, "role": payload.get("role")})
            return
        self.send_json({"ok": False}, status=HTTPStatus.UNAUTHORIZED)

    def handle_add_app(self):
        if not self.require_admin():
            return

        payload = self.parse_json_body() or {}
        validation_error = get_app_validation_error(payload)
        if validation_error:
            self.send_json({"error": validation_error}, status=HTTPStatus.BAD_REQUEST)
            return

        connection = get_db_connection()
        insert_apps(connection, [payload])
        apps = select_apps(connection)
        connection.close()
        self.send_json({"apps": apps}, status=HTTPStatus.CREATED)

    def handle_import_apps(self):
        if not self.require_admin():
            return

        payload = self.parse_json_body() or {}
        apps = payload.get("apps") if isinstance(payload, dict) else None
        if not isinstance(apps, list) or not apps:
            self.send_json({"error": "apps must be a non-empty array"}, status=HTTPStatus.BAD_REQUEST)
            return

        validated = [app for app in apps if validate_app(app)]
        if len(validated) != len(apps):
            self.send_json({"error": "One or more apps are invalid"}, status=HTTPStatus.BAD_REQUEST)
            return

        connection = get_db_connection()
        with connection:
            connection.execute("DELETE FROM apps")
        insert_apps(connection, validated)
        latest = select_apps(connection)
        connection.close()
        self.send_json({"apps": latest})

    def handle_reset_apps(self):
        if not self.require_admin():
            return

        connection = get_db_connection()
        with connection:
            connection.execute("DELETE FROM apps")
        seed_defaults(connection)
        latest = select_apps(connection)
        connection.close()
        self.send_json({"apps": latest})

    def handle_export_apps(self):
        if not self.require_admin():
            return

        connection = get_db_connection()
        apps = select_apps(connection)
        connection.close()
        self.send_json({"apps": apps})

    def handle_update_app(self, app_id):
        if not self.require_admin():
            return

        payload = self.parse_json_body() or {}
        validation_error = get_app_validation_error(payload)
        if validation_error:
            self.send_json({"error": validation_error}, status=HTTPStatus.BAD_REQUEST)
            return

        connection = get_db_connection()
        updated = update_app(connection, app_id, payload)
        if not updated:
            connection.close()
            self.send_json({"error": "App not found"}, status=HTTPStatus.NOT_FOUND)
            return

        apps = select_apps(connection)
        connection.close()
        self.send_json({"apps": apps})

    def handle_delete_app(self, app_id):
        if not self.require_admin():
            return

        connection = get_db_connection()
        deleted = delete_app(connection, app_id)
        if not deleted:
            connection.close()
            self.send_json({"error": "App not found"}, status=HTTPStatus.NOT_FOUND)
            return

        apps = select_apps(connection)
        connection.close()
        self.send_json({"apps": apps})


if __name__ == "__main__":
    initialize_database()
    server = ThreadingHTTPServer(("0.0.0.0", PORT), AppHandler)
    print(f"PlayBonusHub server running at http://localhost:{PORT}")
    server.serve_forever()
