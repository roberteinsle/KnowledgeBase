#!/usr/bin/env python3
"""Builds the GitHub wiki from handbuch/ and optionally pushes it.

    scripts/build-wiki.py                 # build into build/wiki
    scripts/build-wiki.py --push          # build, commit and push to <origin>.wiki.git

The wiki is generated only; never edit it by hand. The wiki repository is
derived from the "origin" remote, so no account or URL is hard-coded.
"""

from __future__ import annotations

import argparse
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "handbuch"
DEFAULT_OUT = ROOT / "build" / "wiki"

# Sidebar order; pages not listed follow alphabetically.
ORDER = ["Home", "Sign-in", "Account", "Pages", "Import-and-Export", "Search", "Spaces-Groups-and-Permissions", "Administration", "Operations"]

# [Text](Seite.md) or [Text](Seite.md#anker) -> [Text](Seite) / [Text](Seite#anker)
LINK = re.compile(r"\]\((?!https?://|mailto:|#)([^)\s]+?)\.md(#[^)\s]*)?\)")


def title_of(markdown: str, fallback: str) -> str:
    match = re.search(r"^#\s+(.+?)\s*$", markdown, re.MULTILINE)
    return match.group(1) if match else fallback.replace("-", " ")


def convert(markdown: str) -> str:
    return LINK.sub(lambda m: "](" + m.group(1) + (m.group(2) or "") + ")", markdown)


def build(out: Path) -> list[Path]:
    if not SOURCE.is_dir():
        sys.exit(f"{SOURCE} not found")

    if out.exists():
        for item in out.iterdir():
            if item.name == ".git":
                continue
            shutil.rmtree(item) if item.is_dir() else item.unlink()
    out.mkdir(parents=True, exist_ok=True)

    order = {name: i for i, name in enumerate(ORDER)}
    pages = sorted(SOURCE.glob("*.md"), key=lambda p: (order.get(p.stem, len(ORDER)), p.stem.lower()))
    sidebar = ["**Manual**", ""]

    for page in pages:
        text = page.read_text(encoding="utf-8")
        (out / page.name).write_text(convert(text), encoding="utf-8")
        sidebar.append(f"- [{title_of(text, page.stem)}]({page.stem})")

    (out / "_Sidebar.md").write_text("\n".join(sidebar) + "\n", encoding="utf-8")
    (out / "_Footer.md").write_text(
        "_Generated from `handbuch/` in the repository – please edit it there, not here._\n", encoding="utf-8"
    )
    return pages


def git(*args: str, cwd: Path) -> str:
    return subprocess.run(["git", *args], cwd=cwd, check=True, capture_output=True, text=True).stdout.strip()


def wiki_remote() -> str:
    origin = git("remote", "get-url", "origin", cwd=ROOT)
    return re.sub(r"(\.git)?/?$", "", origin) + ".wiki.git"


def push() -> None:
    remote = wiki_remote()
    source_sha = git("rev-parse", "--short", "HEAD", cwd=ROOT)

    with tempfile.TemporaryDirectory() as tmp:
        checkout = Path(tmp) / "wiki"
        try:
            subprocess.run(["git", "clone", "--quiet", remote, str(checkout)], check=True)
        except subprocess.CalledProcessError:
            sys.exit(
                f"Could not clone {remote}.\n"
                "Create the first wiki page once in the GitHub web UI (Wiki → Create the first page), then retry."
            )

        build(checkout)
        git("add", "--all", cwd=checkout)
        if not git("status", "--porcelain", cwd=checkout):
            print("Wiki is already up to date.")
            return

        git("commit", "--quiet", "-m", f"Update wiki from handbuch/ ({source_sha})", cwd=checkout)
        subprocess.run(["git", "push", "--quiet"], cwd=checkout, check=True)
        print(f"Wiki pushed to {remote}.")


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--out", type=Path, default=DEFAULT_OUT, help="output directory (default: build/wiki)")
    parser.add_argument("--push", action="store_true", help="commit and push to the wiki repository")
    args = parser.parse_args()

    if args.push:
        push()
    else:
        pages = build(args.out)
        print(f"{len(pages)} pages written to {args.out}")


if __name__ == "__main__":
    main()
