"""Build and search a local SQLite index of tracked Kanban source code."""

import argparse
from collections import Counter
from contextlib import closing
from hashlib import blake2b
import json
from math import log1p, sqrt
from pathlib import Path, PurePosixPath
import re
import sqlite3
import stat
import subprocess
from tempfile import NamedTemporaryFile


ROOT = Path(__file__).resolve().parents[1]
DATABASE = ROOT / ".code-index/code.sqlite3"
SOURCE_DIRS = {"app", "config", "database", "docker", "resources", "routes", "scripts", "tests"}
SOURCE_SUFFIXES = {".Caddyfile", ".css", ".js", ".php", ".py", ".sh", ".ts", ".vue"}
ROOT_FILES = {
    ".githooks/pre-commit", ".github/workflows/ci.yml", "Dockerfile", "artisan",
    "bootstrap/app.php", "bootstrap/providers.php", "compose.yaml", "composer.json", "eslint.config.js",
    "package.json", "phpunit.xml", "pint.json", "playwright.config.ts", "public/index.php", "render.yaml",
    "tsconfig.json", "vite.config.ts",
}
CAMEL_BOUNDARY = re.compile(r"(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])")
WORD = re.compile(r"[^\W\d_]+|\d+")


def embedding(text):
    """Encode code words and split identifiers into a normalized sparse vector."""
    counts = Counter(WORD.findall(CAMEL_BOUNDARY.sub(" ", text).casefold()))
    vector = Counter()
    for word, frequency in counts.items():
        digest = blake2b(word.encode("utf-8"), digest_size=4).digest()
        bucket = str(int.from_bytes(digest[:3], "little"))
        sign = 1 if digest[3] & 1 else -1
        vector[bucket] += sign * log1p(frequency)

    norm = sqrt(sum(weight ** 2 for weight in vector.values()))
    return {bucket: weight / norm for bucket, weight in vector.items()} if norm else {}


def tracked_sources():
    listing = subprocess.check_output(["git", "ls-files", "-z", "--cached"], cwd=ROOT)
    for name in sorted(set(listing.decode("utf-8").split("\0")) - {""}):
        source_path = PurePosixPath(name)
        parts = source_path.parts
        if name in ROOT_FILES or (
            parts[0] in SOURCE_DIRS
            and source_path.suffix in SOURCE_SUFFIXES
            and not any(part.startswith(".") or part in {"node_modules", "vendor"} for part in parts)
        ):
            yield name


def has_worktree_link(path):
    # Inspect ancestors as well as the file so an NTFS junction cannot redirect a tracked path.
    for candidate in (path, *path.parents):
        if candidate == ROOT:
            break
        try:
            info = candidate.lstat()
        except FileNotFoundError:
            continue
        if stat.S_ISLNK(info.st_mode) or getattr(info, "st_file_attributes", 0) & stat.FILE_ATTRIBUTE_REPARSE_POINT:
            return True
    return False


def source_chunks(name):
    path = ROOT / name
    if has_worktree_link(path) or not path.is_file():
        return

    lines = path.read_text(encoding="utf-8-sig").splitlines()
    chunk_size = 48
    stride = 40
    for offset in range(0, len(lines), stride):
        end = min(offset + chunk_size, len(lines))
        code = "\n".join(lines[offset:end])
        if code.strip():
            yield name, offset + 1, end, code, json.dumps(embedding(f"{name}\n{name}\n{code}"))
        if end == len(lines):
            break


def build():
    DATABASE.parent.mkdir(exist_ok=True)
    with NamedTemporaryFile(dir=DATABASE.parent, suffix=".sqlite3", delete=False) as temporary:
        temporary_path = Path(temporary.name)
    try:
        with closing(sqlite3.connect(temporary_path)) as database, database:
            database.execute("""
                CREATE TABLE chunks (
                    path TEXT NOT NULL,
                    start_line INTEGER NOT NULL,
                    end_line INTEGER NOT NULL,
                    code TEXT NOT NULL,
                    vector TEXT NOT NULL,
                    PRIMARY KEY (path, start_line)
                )
            """)
            rows = (chunk for name in tracked_sources() for chunk in source_chunks(name))
            database.executemany("INSERT INTO chunks VALUES (?, ?, ?, ?, ?)", rows)
            files, chunks = database.execute("SELECT COUNT(DISTINCT path), COUNT(*) FROM chunks").fetchone()
        temporary_path.replace(DATABASE)
    finally:
        temporary_path.unlink(missing_ok=True)
    print(f"Indexed {files} files and {chunks} chunks in {DATABASE.relative_to(ROOT).as_posix()}")


def query(text, limit, show_code):
    wanted = embedding(text)
    if not wanted:
        raise ValueError("Search requires at least one word or number.")
    if not DATABASE.is_file():
        raise ValueError("Index is missing. Run 'python scripts/code-index.py build' first.")

    matches = []
    with closing(sqlite3.connect(f"{DATABASE.as_uri()}?mode=ro", uri=True)) as database:
        for path, start, end, encoded in database.execute("SELECT path, start_line, end_line, vector FROM chunks"):
            vector = json.loads(encoded)
            score = sum(weight * vector.get(bucket, 0) for bucket, weight in wanted.items())
            if score > 0:
                matches.append((-score, path, start, end))

        if not matches:
            print("No matching code chunks.")
        for score, path, start, end in sorted(matches)[:limit]:
            print(f"{-score:.3f}  {path}:{start}-{end}")
            if show_code:
                code, = database.execute("SELECT code FROM chunks WHERE path = ? AND start_line = ?", (path, start)).fetchone()
                print(f"{code}\n")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)
    commands.add_parser("build", help="rebuild the index from the working tree")
    search = commands.add_parser("query", help="search by cosine similarity")
    search.add_argument("text")
    search.add_argument("--limit", type=int, default=5)
    search.add_argument("--show-code", action="store_true")
    arguments = parser.parse_args()

    try:
        if has_worktree_link(DATABASE):
            raise ValueError("Index path must be a regular local path without links.")
        if arguments.command == "build":
            build()
        else:
            if arguments.limit < 1:
                raise ValueError("--limit must be at least 1.")
            query(arguments.text, arguments.limit, arguments.show_code)
    except (OSError, ValueError, sqlite3.Error, subprocess.CalledProcessError) as error:
        parser.exit(1, f"{error}\n")


if __name__ == "__main__":
    main()
