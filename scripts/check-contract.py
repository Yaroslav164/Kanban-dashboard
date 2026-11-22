"""Compare the independently generated Swagger document with the approved contract."""
from pathlib import Path
from difflib import unified_diff
import json
import yaml

root = Path(__file__).resolve().parents[1]
def canonical(path):
    return json.dumps(yaml.safe_load(path.read_text(encoding="utf-8")), sort_keys=True, indent=2).splitlines()

expected = canonical(root / "docs/openapi.yaml")
actual = canonical(root / "storage/api-docs/api-docs.yaml")
if actual != expected:
    raise SystemExit("\n".join(unified_diff(expected, actual, "contract", "annotations")))
print("OpenAPI contract matches the generated YAML.")
