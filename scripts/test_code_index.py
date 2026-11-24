import contextlib
import os
from pathlib import Path
import sqlite3
import subprocess
import sys
import tempfile
import unittest


SCRIPT = Path(__file__).with_name("code-index.py")


class CodeIndexTest(unittest.TestCase):
    def git(self, *arguments):
        subprocess.run(["git", *arguments], cwd=self.root, check=True, capture_output=True)

    def write(self, path, text):
        file = self.root / path
        file.parent.mkdir(parents=True, exist_ok=True)
        file.write_text(text, encoding="utf-8")
        return file

    def run_index(self, *arguments):
        return subprocess.run(
            [sys.executable, str(self.root / "scripts/code-index.py"), *arguments],
            cwd=self.root,
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
            env={**os.environ, "PYTHONIOENCODING": "utf-8"},
        ).stdout

    def indexed_paths(self):
        with contextlib.closing(sqlite3.connect(self.root / ".code-index/code.sqlite3")) as database:
            self.assertEqual(database.execute("PRAGMA integrity_check").fetchone(), ("ok",))
            return {row[0] for row in database.execute("SELECT DISTINCT path FROM chunks")}

    def setUp(self):
        temporary = self.enterContext(tempfile.TemporaryDirectory())
        self.root = Path(temporary) / "repo"
        self.root.mkdir()
        self.git("init", "--quiet")
        if SCRIPT.exists():
            self.write("scripts/code-index.py", SCRIPT.read_text(encoding="utf-8"))

    def test_build_find_and_replace_tracked_code(self):
        ranked = self.write("app/CardTemplate.php", "<?php\nclass CardTemplate {}\n")
        stale = self.write("app/Board.php", "<?php\nclass Board {}\n")
        self.write("resources/js/App.vue", "<p>Шаблон карточки</p>\n")
        self.write("backend/app/Task.php", "legacy content\n")
        self.write("frontend/src/App.vue", "legacy content\n")
        self.write("app/.env", "SECRET=secret\n")
        self.write("app/token.php", "secret material\n")
        self.write("bootstrap/cache/services.php", "runtime cache\n")
        self.write("public/build/index.js", "generated bundle\n")
        self.write("resources/js/node_modules/index.js", "third-party package\n")
        self.git("add", "--", "app/CardTemplate.php", "app/Board.php", "app/.env", "resources",
                 "backend", "bootstrap", "frontend", "public")

        self.run_index("build")
        self.assertEqual(self.indexed_paths(), {"app/Board.php", "app/CardTemplate.php", "resources/js/App.vue"})
        self.assertIn("app/CardTemplate.php:1-2", self.run_index("query", "card template", "--limit", "1"))
        self.assertIn("Шаблон карточки", self.run_index("query", "шаблон карточки", "--show-code"))

        stale.unlink()
        ranked.write_text("<?php\nclass CardReminder {}\n", encoding="utf-8")
        self.run_index("build")
        self.assertNotIn("app/Board.php", self.indexed_paths())
        result = self.run_index("query", "card reminder", "--limit", "1", "--show-code")
        self.assertIn("class CardReminder", result)
        self.assertNotIn("class CardTemplate", result)

    def test_excludes_worktree_links_to_external_code(self):
        target = self.root.parent / "external"
        target.mkdir()
        (target / "Linked.php").write_text("private outside repository\n", encoding="utf-8")
        file = self.write("app/Linked.php", "original\n")
        self.git("add", "--", "app/Linked.php")
        file.unlink()
        file.parent.rmdir()
        if os.name == "nt":
            result = subprocess.run(["cmd", "/d", "/c", "mklink", "/J", str(file.parent), str(target)],
                                    capture_output=True)
            if result.returncode:
                self.skipTest("This filesystem does not allow creating a directory junction.")
        else:
            file.parent.symlink_to(target, target_is_directory=True)

        self.run_index("build")
        self.assertEqual(self.indexed_paths(), set())
