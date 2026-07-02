#!/usr/bin/env python3
"""
Convert PNG/JPG/JPEG under a static export tree to WebP and patch HTML/CSS/JS references.

Safe default root: leadwerk_importer/source_assets (run from ACM repo root).

Paths below are referenced by literal strings in leadwerk_importer PHP — never convert/delete
them here, or fill_options / preflight / Starlink seed will break until PHP is updated.
"""
from __future__ import annotations

from pathlib import Path
from PIL import Image
import json
import argparse
import sys
from datetime import datetime


IMAGE_EXTENSIONS = {".png", ".jpg", ".jpeg"}
TEXT_EXTENSIONS = {".html", ".css", ".js"}
WEBP_QUALITY = 85

# leadwerk_importer/includes/class-leadwerk-importer.php (preflight, fill_options, starlink)
IMPORTER_LITERAL_SOURCE_PATHS = frozenset(
    {
        "logo.png",
        "favicon-192x192.png",
        "favicon-32x32.png",
        "apple-touch-icon.png",
        "assets/images/Logo-final-weiss-rz.png",
        # Starlink-Modal: PHP nutzt Fotos/…; Duplikat unter assets/images/ nicht anfassen.
        "Fotos/starlink-popup.png",
        "assets/images/starlink-popup.png",
    }
)


def normalize_path(path: Path) -> str:
    return path.as_posix()


def is_inside_ignored_folder(path: Path) -> bool:
    ignored_parts = {
        "node_modules",
        ".git",
        ".next",
        "dist",
        "build",
        "__pycache__",
    }
    return any(part in ignored_parts for part in path.parts)


def is_importer_protected(root: Path, file_path: Path) -> bool:
    try:
        rel = normalize_path(file_path.resolve().relative_to(root.resolve()))
    except ValueError:
        return False
    rel_lower = rel.lower()
    for prot in IMPORTER_LITERAL_SOURCE_PATHS:
        if rel_lower == prot.lower():
            return True
    return False


def collect_images(root: Path):
    images = []
    for file_path in root.rglob("*"):
        if not file_path.is_file():
            continue
        if is_inside_ignored_folder(file_path):
            continue
        if file_path.suffix.lower() not in IMAGE_EXTENSIONS:
            continue
        if is_importer_protected(root, file_path):
            continue
        images.append(file_path)
    return images


def collect_text_files(root: Path):
    text_files = []
    for file_path in root.rglob("*"):
        if not file_path.is_file():
            continue
        if is_inside_ignored_folder(file_path):
            continue
        if file_path.suffix.lower() in TEXT_EXTENSIONS:
            text_files.append(file_path)
    return text_files


def convert_image_to_webp(image_path: Path, quality: int):
    webp_path = image_path.with_suffix(".webp")

    with Image.open(image_path) as img:
        if img.mode in ("RGBA", "LA"):
            converted = img.convert("RGBA")
        else:
            converted = img.convert("RGB")

        converted.save(webp_path, "WEBP", quality=quality, method=6)

    if not webp_path.exists() or webp_path.stat().st_size == 0:
        raise RuntimeError(f"WebP conversion failed: {image_path}")

    return webp_path


def build_replacement_variants(root: Path, old_path: Path, new_path: Path):
    old_rel = normalize_path(old_path.relative_to(root))
    new_rel = normalize_path(new_path.relative_to(root))

    old_name = old_path.name
    new_name = new_path.name

    variants = []

    variants.append((old_rel, new_rel))
    variants.append((f"./{old_rel}", f"./{new_rel}"))
    variants.append((old_rel.replace(" ", "%20"), new_rel.replace(" ", "%20")))
    variants.append((f"./{old_rel.replace(' ', '%20')}", f"./{new_rel.replace(' ', '%20')}"))
    variants.append((old_name, new_name))
    variants.append((old_name.replace(" ", "%20"), new_name.replace(" ", "%20")))
    variants.append((old_rel.replace("/", "\\"), new_rel.replace("/", "\\")))

    return variants


def update_text_references(root: Path, mappings):
    text_files = collect_text_files(root)
    changed_files = []

    for text_file in text_files:
        try:
            content = text_file.read_text(encoding="utf-8")
            original_content = content
        except UnicodeDecodeError:
            try:
                content = text_file.read_text(encoding="latin-1")
                original_content = content
            except Exception:
                continue

        for item in mappings:
            old_path = Path(item["old_absolute_path"])
            new_path = Path(item["new_absolute_path"])

            variants = build_replacement_variants(root, old_path, new_path)
            variants.sort(key=lambda pair: len(pair[0]), reverse=True)

            for old_value, new_value in variants:
                content = content.replace(old_value, new_value)

        if content != original_content:
            text_file.write_text(content, encoding="utf-8")
            changed_files.append(normalize_path(text_file.relative_to(root)))

    return changed_files


def create_backup_manifest(root: Path, images):
    manifest = {
        "created_at": datetime.now().isoformat(timespec="seconds"),
        "root": normalize_path(root),
        "total_images": len(images),
        "skipped_importer_literals": sorted(IMPORTER_LITERAL_SOURCE_PATHS),
        "images": [],
    }

    for image in images:
        manifest["images"].append(
            {
                "filename": image.name,
                "stem": image.stem,
                "extension": image.suffix,
                "relative_path": normalize_path(image.relative_to(root)),
                "absolute_path": normalize_path(image.resolve()),
                "target_webp_relative_path": normalize_path(
                    image.with_suffix(".webp").relative_to(root)
                ),
                "target_webp_absolute_path": normalize_path(
                    image.with_suffix(".webp").resolve()
                ),
                "size_bytes": image.stat().st_size,
            }
        )

    manifest_path = root / "webp-conversion-manifest.json"
    manifest_path.write_text(
        json.dumps(manifest, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    return manifest_path


def run_conversion(root: Path, quality: int, dry_run: bool, delete_originals: bool):
    root = root.resolve()

    if not root.exists() or not root.is_dir():
        raise ValueError(f"Root folder not found: {root}")

    images = collect_images(root)
    manifest_path = create_backup_manifest(root, images)

    print(f"\nRoot: {root}")
    print(f"Images to convert (protected paths excluded): {len(images)}")
    print(f"Manifest saved: {manifest_path}")

    if dry_run:
        print("\nDRY RUN — no conversion, deletion, or text updates.\n")
        for img in images[:200]:
            print(f"[DRY] {img.relative_to(root)} -> {img.with_suffix('.webp').relative_to(root)}")
        if len(images) > 200:
            print(f"... and {len(images) - 200} more (see manifest JSON).")
        return

    mappings = []
    failed = []

    print("\nConverting images...\n")

    for image_path in images:
        try:
            webp_path = convert_image_to_webp(image_path, quality)

            mappings.append(
                {
                    "old_filename": image_path.name,
                    "new_filename": webp_path.name,
                    "old_relative_path": normalize_path(image_path.relative_to(root)),
                    "new_relative_path": normalize_path(webp_path.relative_to(root)),
                    "old_absolute_path": normalize_path(image_path.resolve()),
                    "new_absolute_path": normalize_path(webp_path.resolve()),
                    "old_size_bytes": image_path.stat().st_size,
                    "new_size_bytes": webp_path.stat().st_size,
                }
            )

            print(f"[OK] {image_path.relative_to(root)} -> {webp_path.relative_to(root)}")

        except Exception as e:
            failed.append(
                {
                    "file": normalize_path(image_path.relative_to(root)),
                    "error": str(e),
                }
            )
            print(f"[FAILED] {image_path.relative_to(root)} | {e}")

    print("\nUpdating HTML, CSS and JS references...\n")
    changed_files = update_text_references(root, mappings)

    for file in changed_files:
        print(f"[UPDATED] {file}")

    deleted_files = []

    if delete_originals:
        print("\nDeleting original files...\n")

        for item in mappings:
            old_file = Path(item["old_absolute_path"])
            new_file = Path(item["new_absolute_path"])

            if new_file.exists() and new_file.stat().st_size > 0 and old_file.exists():
                old_file.unlink()
                deleted_files.append(item["old_relative_path"])
                print(f"[DELETED] {item['old_relative_path']}")

    report = {
        "created_at": datetime.now().isoformat(timespec="seconds"),
        "root": normalize_path(root),
        "quality": quality,
        "converted_count": len(mappings),
        "failed_count": len(failed),
        "updated_text_files_count": len(changed_files),
        "deleted_originals": delete_originals,
        "skipped_importer_literals": sorted(IMPORTER_LITERAL_SOURCE_PATHS),
        "converted": mappings,
        "failed": failed,
        "updated_text_files": changed_files,
        "deleted_files": deleted_files,
    }

    report_path = root / "webp-conversion-report.json"
    report_path.write_text(
        json.dumps(report, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    print("\nDone.")
    print(f"Report saved: {report_path}")


def main():
    parser = argparse.ArgumentParser(
        description="Convert PNG/JPG/JPEG to WebP and update HTML/CSS/JS. "
        "Use --path leadwerk_importer/source_assets from ACM repo root."
    )

    parser.add_argument(
        "--path",
        default="leadwerk_importer/source_assets",
        help="Root folder (default: leadwerk_importer/source_assets)",
    )

    parser.add_argument(
        "--quality",
        type=int,
        default=WEBP_QUALITY,
        help="WebP quality 1–100 (default: 85)",
    )

    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="List actions only; no file changes.",
    )

    parser.add_argument(
        "--keep-originals",
        action="store_true",
        help="Keep PNG/JPEG after successful conversion.",
    )

    args = parser.parse_args()
    root = Path(args.path)
    if not root.is_absolute():
        root = (Path.cwd() / root).resolve()

    try:
        run_conversion(
            root=root,
            quality=args.quality,
            dry_run=args.dry_run,
            delete_originals=not args.keep_originals,
        )
    except ValueError as e:
        print(e, file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
