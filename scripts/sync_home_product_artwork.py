from __future__ import annotations

import io
from pathlib import Path
from urllib.request import Request, urlopen

from PIL import Image, ImageDraw


PROJECT_ROOT = Path(__file__).resolve().parents[1]
ARTWORK_DIR = PROJECT_ROOT / "public" / "product-artwork"
MAP_PATH = PROJECT_ROOT / "resources" / "js" / "data" / "home-product-artwork-map.ts"
OUTPUT_SIZE = 512


USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/136.0.0.0 Safari/537.36"
)


COPY_FROM_FILE = {
    "afk-journey": ARTWORK_DIR / "vip-game-afk-journey.png",
    "arena-of-valor": ARTWORK_DIR / "vip-game-arena-of-valor.png",
    "blood-strike-login": ARTWORK_DIR / "vip-game-blood-strike.png",
    "delta-force": ARTWORK_DIR / "vip-game-delta-force.png",
    "eggy-party": ARTWORK_DIR / "vip-game-eggy-party.png",
    "free-fire-global": ARTWORK_DIR / "vip-game-free-fire-global.png",
    "genshin-impact": ARTWORK_DIR / "vip-game-genshin-impact.png",
    "hok-login": ARTWORK_DIR / "honor-of-kings.png",
    "indosat": ARTWORK_DIR / "vip-prepaid-indosat.png",
    "ps-store": ARTWORK_DIR / "vip-game-voucher-psn.png",
    "razer-gold": ARTWORK_DIR / "vip-game-voucher-razer-gold.png",
    "smartfren": ARTWORK_DIR / "vip-prepaid-smartfren.png",
    "steam-wallet": ARTWORK_DIR / "vip-game-steam-wallet-code.png",
    "telkomsel": ARTWORK_DIR / "vip-prepaid-telkomsel.png",
    "tri": ARTWORK_DIR / "vip-prepaid-tri.png",
    "xl": ARTWORK_DIR / "vip-prepaid-xl.png",
    "zenless-zone-zero": ARTWORK_DIR / "vip-game-zenless-zone-zero-zzz.png",
    "ace-racer": ARTWORK_DIR / "vip-game-ace-racer.png",
}


DIRECT_URLS = {
    "astra-knights": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/ae/62/3d/ae623d74-f365-c019-f33a-d2ee8481e69b/AppIcon-0-0-1x_U007emarketing-0-8-0-85-220.png/512x512bb.jpg",
    "bigo-live": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/88/0a/e5/880ae53a-131f-de0a-b9e2-fecb52ec57f8/AppIcon-0-0-1x_U007epad-0-1-0-85-220.png/512x512bb.jpg",
    "crystal-of-atlan": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/e6/78/e2/e678e269-33f4-9a7e-9385-de243dbf895a/AppIcon-1x_U007emarketing-0-8-0-85-220-0.png/512x512bb.jpg",
    "google-play": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/88/2f/4a/882f4ac6-edce-75db-847a-0cfa60d14a2e/SuperG_ios26-0-1x_U007epad-0-0-0-1-0-0-sRGB-0-0-0-85-220-0.png/512x512bb.jpg",
    "hero-reborn": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/8e/99/dd/8e99ddde-39ce-df72-a831-1e6e942f2183/AppIcon-0-0-1x_U007emarketing-0-11-0-85-220.png/512x512bb.jpg",
    "isekai-feast": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/16/ed/cb/16edcb65-5151-ddff-be91-2a38f72e063b/AppIcon-1x_U007emarketing-0-7-0-0-85-220-0.png/512x512bb.jpg",
    "solo-leveling-arise": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/98/b4/35/98b43571-e1d9-3c2b-3c98-d61e788c7cfd/AppIcon-0-0-1x_U007emarketing-0-8-0-85-220.png/512x512bb.jpg",
    "wuxia-rising": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/0d/f4/76/0df476f4-ba91-f732-ef35-517ebc48d2e2/AppIcon-0-0-1x_U007emarketing-0-8-0-85-220.png/512x512bb.jpg",
    "wuthering-waves": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/7b/26/65/7b26657c-c08c-30c3-6b83-2bd22c53da6f/AppIcon-0-0-1x_U007emarketing-0-8-0-85-220.png/512x512bb.jpg",
}


def fetch_bytes(url: str) -> bytes:
    request = Request(url, headers={"User-Agent": USER_AGENT})
    with urlopen(request, timeout=30) as response:
        return response.read()


def save_square_png(image_bytes: bytes, destination: Path) -> None:
    with Image.open(io.BytesIO(image_bytes)) as image:
        image = image.convert("RGBA")
        canvas = Image.new("RGBA", (OUTPUT_SIZE, OUTPUT_SIZE), (0, 0, 0, 0))
        resized = image.copy()
        resized.thumbnail((OUTPUT_SIZE, OUTPUT_SIZE), Image.Resampling.LANCZOS)
        offset_x = (OUTPUT_SIZE - resized.width) // 2
        offset_y = (OUTPUT_SIZE - resized.height) // 2
        canvas.paste(resized, (offset_x, offset_y), resized)
        canvas.save(destination, format="PNG", optimize=True)


def generate_app_store_icon(destination: Path) -> None:
    image = Image.new("RGBA", (OUTPUT_SIZE, OUTPUT_SIZE), (0, 0, 0, 0))
    draw = ImageDraw.Draw(image)

    for y in range(OUTPUT_SIZE):
        ratio = y / (OUTPUT_SIZE - 1)
        top = (67, 158, 255)
        bottom = (18, 105, 255)
        color = tuple(int(top[index] * (1 - ratio) + bottom[index] * ratio) for index in range(3))
        draw.line([(0, y), (OUTPUT_SIZE, y)], fill=(*color, 255))

    draw.rounded_rectangle((28, 28, 484, 484), radius=112, fill=(20, 118, 255, 255))
    draw.ellipse((88, 92, 424, 428), fill=(255, 255, 255, 28))

    line_width = 34
    line_color = (255, 255, 255, 255)
    draw.line((186, 146, 330, 390), fill=line_color, width=line_width)
    draw.line((326, 146, 182, 390), fill=line_color, width=line_width)
    draw.line((148, 298, 364, 298), fill=line_color, width=line_width)

    draw.rounded_rectangle((157, 124, 223, 162), radius=18, fill=line_color)
    draw.rounded_rectangle((289, 124, 355, 162), radius=18, fill=line_color)
    draw.rounded_rectangle((144, 279, 368, 317), radius=18, fill=line_color)

    image.save(destination, format="PNG", optimize=True)


def write_map_file(product_ids: list[str]) -> None:
    lines = [
        "export const homeProductArtworkMap: Record<string, { coverImage: string; iconImage: string }> = {",
    ]
    for product_id in sorted(product_ids):
        path = f"/product-artwork/{product_id}.png"
        lines.append(f"    '{product_id}': {{ coverImage: '{path}', iconImage: '{path}' }},")
    lines.append("};")
    lines.append("")
    MAP_PATH.write_text("\n".join(lines), encoding="utf-8")


def main() -> int:
    ARTWORK_DIR.mkdir(parents=True, exist_ok=True)

    written_ids: list[str] = []

    generate_app_store_icon(ARTWORK_DIR / "apple-store.png")
    written_ids.append("apple-store")

    for product_id, source_path in COPY_FROM_FILE.items():
        save_square_png(source_path.read_bytes(), ARTWORK_DIR / f"{product_id}.png")
        written_ids.append(product_id)

    for product_id, url in DIRECT_URLS.items():
        save_square_png(fetch_bytes(url), ARTWORK_DIR / f"{product_id}.png")
        written_ids.append(product_id)

    write_map_file(written_ids)
    print(f"Generated artwork for {len(written_ids)} home products.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
