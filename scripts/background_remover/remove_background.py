#!/usr/bin/env python3

from __future__ import annotations

import argparse
import io
from pathlib import Path

from PIL import Image
from rembg import new_session, remove


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Remove image background and save a transparent PNG.")
    parser.add_argument("--input", required=True, help="Path to the source image.")
    parser.add_argument("--output", required=True, help="Path to the output PNG.")
    parser.add_argument("--model", default="u2netp", help="Rembg model name to use.")
    return parser.parse_args()


def estimate_border_background(image: Image.Image) -> tuple[tuple[int, int, int], float]:
    rgb_image = image.convert("RGB")
    width, height = rgb_image.size
    pixels = rgb_image.load()
    border_samples: list[tuple[int, int, int]] = []

    for x in range(width):
        border_samples.append(pixels[x, 0])
        if height > 1:
            border_samples.append(pixels[x, height - 1])

    for y in range(1, max(1, height - 1)):
        border_samples.append(pixels[0, y])
        if width > 1:
            border_samples.append(pixels[width - 1, y])

    if not border_samples:
        return (255, 255, 255), 255.0

    avg = tuple(round(sum(sample[channel] for sample in border_samples) / len(border_samples)) for channel in range(3))
    spread = sum(
        (abs(sample[0] - avg[0]) + abs(sample[1] - avg[1]) + abs(sample[2] - avg[2])) / 3 for sample in border_samples
    ) / len(border_samples)

    return (int(avg[0]), int(avg[1]), int(avg[2])), float(spread)


def remove_uniform_background_pixels(original_image: Image.Image, removed_image: Image.Image) -> Image.Image:
    background_color, spread = estimate_border_background(original_image)

    if spread > 22:
        return removed_image

    hard_threshold = max(18.0, spread * 1.8 + 10.0)
    soft_threshold = hard_threshold + 42.0
    original_rgba = original_image.convert("RGBA")
    result_rgba = removed_image.convert("RGBA")
    original_pixels = original_rgba.load()
    result_pixels = result_rgba.load()
    width, height = result_rgba.size

    for y in range(height):
        for x in range(width):
            source_red, source_green, source_blue, _ = original_pixels[x, y]
            output_red, output_green, output_blue, output_alpha = result_pixels[x, y]
            distance = (
                ((source_red - background_color[0]) ** 2)
                + ((source_green - background_color[1]) ** 2)
                + ((source_blue - background_color[2]) ** 2)
            ) ** 0.5

            if distance <= hard_threshold:
                next_alpha = 0
            elif distance >= soft_threshold:
                next_alpha = output_alpha
            else:
                factor = (distance - hard_threshold) / (soft_threshold - hard_threshold)
                next_alpha = min(output_alpha, round(output_alpha * factor))

            result_pixels[x, y] = (output_red, output_green, output_blue, int(next_alpha))

    return result_rgba


def crop_to_visible_bounds(image: Image.Image) -> Image.Image:
    rgba_image = image.convert("RGBA")
    alpha_channel = rgba_image.getchannel("A")
    bounds = alpha_channel.getbbox()

    if bounds is None:
        return rgba_image

    padding = max(6, round(min(rgba_image.size) * 0.03))
    left = max(0, bounds[0] - padding)
    upper = max(0, bounds[1] - padding)
    right = min(rgba_image.width, bounds[2] + padding)
    lower = min(rgba_image.height, bounds[3] + padding)

    return rgba_image.crop((left, upper, right, lower))


def main() -> int:
    args = parse_args()
    input_path = Path(args.input).expanduser().resolve()
    output_path = Path(args.output).expanduser().resolve()

    if not input_path.is_file():
        raise FileNotFoundError(f"Input image not found: {input_path}")

    output_path.parent.mkdir(parents=True, exist_ok=True)
    original_image = Image.open(input_path).convert("RGBA")
    session = new_session(args.model)
    output_bytes = remove(input_path.read_bytes(), session=session)
    removed_image = Image.open(io.BytesIO(output_bytes)).convert("RGBA")
    refined_image = remove_uniform_background_pixels(original_image, removed_image)
    cropped_image = crop_to_visible_bounds(refined_image)
    cropped_image.save(output_path, format="PNG")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
