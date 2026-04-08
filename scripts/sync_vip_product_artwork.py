from __future__ import annotations

import io
import json
import re
import subprocess
import sys
import unicodedata
from dataclasses import dataclass
from difflib import SequenceMatcher
from pathlib import Path
from typing import Iterable
from urllib.error import HTTPError, URLError
from urllib.parse import quote
from urllib.request import Request, urlopen

from PIL import Image, ImageDraw, ImageFilter, ImageFont, ImageOps


PROJECT_ROOT = Path(__file__).resolve().parents[1]
PUBLIC_DIR = PROJECT_ROOT / "public"
ARTWORK_DIR = PUBLIC_DIR / "product-artwork"
REPORT_PATH = PROJECT_ROOT / "storage" / "app" / "vip-product-artwork-report.json"
MAP_PATH = PROJECT_ROOT / "resources" / "js" / "data" / "vip-product-artwork-map.ts"
OUTPUT_SIZE = 512


USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/136.0.0.0 Safari/537.36"
)


STOPWORDS = {
    "app",
    "apps",
    "card",
    "code",
    "driver",
    "gift",
    "global",
    "id",
    "indonesia",
    "login",
    "mobile",
    "pay",
    "premium",
    "promo",
    "store",
    "topup",
    "voucher",
    "wallet",
}


EXISTING_FILE_COPY_MAP = {
    "vip-game-arena-breakout-infinite-pc": ARTWORK_DIR / "arena-breakout-infinite.png",
    "vip-game-free-fire": ARTWORK_DIR / "free-fire.webp",
    "vip-game-free-fire-global": ARTWORK_DIR / "free-fire.webp",
    "vip-game-free-fire-max": ARTWORK_DIR / "free-fire-max.webp",
    "vip-game-free-fire-max-global": ARTWORK_DIR / "free-fire-max.webp",
    "vip-game-honor-of-kings-global": ARTWORK_DIR / "honor-of-kings.png",
    "vip-game-magic-chess-go-go": ARTWORK_DIR / "magic-chess-go-go.png",
    "vip-game-magic-chess-go-go-global": ARTWORK_DIR / "magic-chess-go-go.png",
    "vip-game-mobile-legends-a": ARTWORK_DIR / "mobile-legends-a-cover.png",
    "vip-game-mobile-legends-b": ARTWORK_DIR / "mobile-legends-b-cover.png",
    "vip-game-mobile-legends-brazil": ARTWORK_DIR / "mobile-legends-a-cover.png",
    "vip-game-mobile-legends-gift": ARTWORK_DIR / "mobile-legends-gift-skin-cover.png",
    "vip-game-mobile-legends-global": ARTWORK_DIR / "mobile-legends-a-cover.png",
    "vip-game-mobile-legends-malaysia": ARTWORK_DIR / "mobile-legends-b-cover.png",
    "vip-game-mobile-legends-philippines": ARTWORK_DIR / "mobile-legends-b-cover.png",
    "vip-game-mobile-legends-russia": ARTWORK_DIR / "mobile-legends-b-cover.png",
    "vip-game-mobile-legends-singapore": ARTWORK_DIR / "mobile-legends-a-cover.png",
    "vip-game-pubg-mobile-global": ARTWORK_DIR / "pubg-mobile.webp",
    "vip-game-pubg-mobile-id": ARTWORK_DIR / "pubg-mobile.webp",
}


COPY_ARTWORK_FROM = {
    "vip-prepaid-philippines-smart": "vip-prepaid-smart",
    "vip-prepaid-pln-promo": "vip-prepaid-pln",
    "vip-prepaid-razer-gold": "vip-game-voucher-razer-gold",
}


PREFERRED_DIRECT_URLS = {
    "vip-prepaid-dana": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/80/a7/b6/80a7b640-8d5d-7440-cfdb-2effacbdef0e/AppIcon-0-0-1x_U007ephone-0-1-0-0-85-220.png/512x512bb.jpg",
    "vip-prepaid-go-pay": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/3a/97/ed/3a97ed7d-f4df-c21e-60ea-968ee2a58fd5/AppIcon-0-0-1x_U007ephone-0-1-0-85-220.png/512x512bb.jpg",
    "vip-prepaid-go-pay-driver": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/f4/a2/2c/f4a22cbc-d779-845b-65c3-177bd7db6252/AppIcon-1x_U007emarketing-0-8-0-85-220-0.png/512x512bb.jpg",
    "vip-prepaid-grab": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/b6/4a/4c/b64a4c35-b15e-d137-93ac-12672c3dcced/GrabIcon-0-0-1x_U007emarketing-0-6-0-85-220.png/512x512bb.jpg",
    "vip-prepaid-grab-driver": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/ff/44/b9/ff44b98f-1b47-a7ae-fbc8-f478eb2e640d/AppIcon-0-0-1x_U007emarketing-0-6-0-85-220.png/512x512bb.jpg",
}


QUERY_OVERRIDES = {
    "vip-game-afk-journey": ["AFK Journey"],
    "vip-game-alight-motion": ["Alight Motion"],
    "vip-game-amazon-prime-video": ["Amazon Prime Video"],
    "vip-game-arena-breakout-infinite-pc": ["Arena Breakout Infinite", "Arena Breakout"],
    "vip-game-bstation-premium": ["Bstation"],
    "vip-game-call-of-duty-mobile-indonesia": ["Call of Duty Mobile"],
    "vip-game-canva-pro": ["Canva"],
    "vip-game-capcut-pro": ["CapCut"],
    "vip-game-chatgpt": ["ChatGPT"],
    "vip-game-dragonheir-silent-gods": ["Dragonheir Silent Gods", "Dragonheir"],
    "vip-game-ea-sports-fc-mobile": ["EA SPORTS FC Mobile", "EA Sports FC"],
    "vip-game-garena-undawn": ["Undawn"],
    "vip-game-honkai-impact-3": ["Honkai Impact 3rd"],
    "vip-game-honkai-star-rail": ["Honkai Star Rail"],
    "vip-game-honor-of-kings-global": ["Honor of Kings"],
    "vip-game-league-of-legends": ["League of Legends: Wild Rift", "League of Legends"],
    "vip-game-light-of-thel-new-era": ["Light of Thel"],
    "vip-game-marvel-rivals": ["Marvel Rivals"],
    "vip-game-nba-infinite-europe": ["NBA Infinite"],
    "vip-game-one-punch-man": ["One Punch Man"],
    "vip-game-point-blank-id": ["Point Blank"],
    "vip-game-pubg-new-state-mobile": ["NEW STATE Mobile", "PUBG New State"],
    "vip-game-ragnarok-x-next-generation": ["Ragnarok X: Next Generation"],
    "vip-game-ragnarok-m-eternal-love-sea": ["Ragnarok M Eternal Love"],
    "vip-game-ragnarok-origin": ["Ragnarok Origin"],
    "vip-game-revelation-infinite-journey": ["Revelation Infinite Journey", "Revelation M"],
    "vip-game-roblox-via-login": ["Roblox"],
    "vip-game-state-of-survival-zombie-war": ["State of Survival"],
    "vip-game-vidio-premier": ["Vidio"],
    "vip-game-viu-premium": ["Viu"],
    "vip-game-voucher-garena-shell": ["Garena"],
    "vip-game-voucher-megaxus": ["Megaxus"],
    "vip-game-voucher-pb-zepetto": ["Point Blank", "Zepetto"],
    "vip-game-voucher-psn": ["PlayStation App", "PlayStation"],
    "vip-game-voucher-razer-gold": ["Razer"],
    "vip-game-voucher-roblox": ["Roblox"],
    "vip-game-voucher-valorant": ["VALORANT", "Riot Mobile"],
    "vip-game-warp-plus": ["1.1.1.1"],
    "vip-game-wetv-premium": ["WeTV"],
    "vip-game-youtube-premium": ["YouTube"],
    "vip-game-zenless-zone-zero-zzz": ["Zenless Zone Zero"],
    "vip-game-zepeto": ["ZEPETO"],
    "vip-game-iqiyi-premium": ["iQIYI"],
    "vip-prepaid-alfamart-voucher": ["Alfagift", "Alfamart"],
    "vip-prepaid-axis": ["AXISnet", "AXIS"],
    "vip-prepaid-bri-brizzi": ["BRImo", "BRIZZI"],
    "vip-prepaid-byu": ["by.U"],
    "vip-prepaid-dana": ["DANA"],
    "vip-prepaid-doku": ["DOKU"],
    "vip-prepaid-globe": ["GlobeOne", "Globe"],
    "vip-prepaid-go-pay": ["GoPay"],
    "vip-prepaid-grab": ["Grab"],
    "vip-prepaid-indomaret": ["Klik Indomaret", "Indomaret"],
    "vip-prepaid-k-vision-dan-gol": ["K-VISION"],
    "vip-prepaid-likee": ["Likee"],
    "vip-prepaid-linkaja": ["LinkAja"],
    "vip-prepaid-mandiri-e-toll": ["Livin' by Mandiri", "Mandiri"],
    "vip-prepaid-maxim": ["Maxim"],
    "vip-prepaid-nex-parabola": ["NEX Parabola"],
    "vip-prepaid-orange-tv": ["Orange TV"],
    "vip-prepaid-ovo": ["OVO"],
    "vip-prepaid-pln": ["PLN Mobile"],
    "vip-prepaid-smart": ["Smart"],
    "vip-prepaid-smartfren": ["mySF", "Smartfren"],
    "vip-prepaid-starhub": ["StarHub"],
    "vip-prepaid-sun-telecom": ["Sun Cellular", "Smart"],
    "vip-prepaid-tapcash-bni": ["BNI TapCash Go", "wondr by BNI"],
    "vip-prepaid-telkomsel": ["MyTelkomsel", "Telkomsel"],
    "vip-prepaid-tix-id": ["TIX ID"],
    "vip-prepaid-tri": ["bima+", "Tri Indonesia"],
    "vip-prepaid-vietnam-topup": ["My Viettel"],
    "vip-prepaid-xl": ["myXL", "XL Axiata"],
    "vip-prepaid-xox": ["XOX"],
}


COUNTRY_OVERRIDES = {
    "vip-prepaid-globe": ["ph", "us", "id"],
    "vip-prepaid-philippines-smart": ["ph", "us", "id"],
    "vip-prepaid-smart": ["ph", "us", "id"],
    "vip-prepaid-starhub": ["sg", "us", "id"],
    "vip-prepaid-sun-telecom": ["ph", "us", "id"],
    "vip-prepaid-vietnam-topup": ["vn", "us", "id"],
    "vip-prepaid-xox": ["my", "us", "id"],
}


DIRECT_URL_FALLBACKS = {
    "vip-game-call-of-duty-mobile-indonesia": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/67/ab/41/67ab41bd-ed0b-7387-4763-121119f09c63/AppIcon-1x_U007emarketing-0-10-0-85-220-0.png/512x512bb.jpg",
    "vip-game-honkai-impact-3": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/09/28/b7/0928b747-1769-4bb6-d422-0f6aaf05d368/AppIcon-1x_U007emarketing-0-10-0-85-220-0.png/512x512bb.jpg",
    "vip-game-identity-v": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/7b/22/be/7b22be55-8655-97de-5f9c-518dff0668b7/AppIcon-0-0-1x_U007emarketing-0-8-0-85-220.png/512x512bb.jpg",
    "vip-game-league-of-legends": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/91/51/07/91510756-9bba-63d1-cd25-71b1f6bc07cc/AppIcon-0-0-1x_U007emarketing-0-8-0-85-220.png/512x512bb.jpg",
    "vip-game-lifeafter": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/be/20/b0/be20b026-3181-a06b-37b6-ed2c0eaea2d0/AppIcon-0-0-1x_U007emarketing-0-11-0-85-220.png/512x512bb.jpg",
    "vip-game-light-of-thel-new-era": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/30/00/57/30005717-3eb9-f872-bf40-4c6c0702873e/AppIcon-1x_U007emarketing-0-7-0-85-220-0.png/512x512bb.jpg",
    "vip-game-likee": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/70/e4/32/70e432f2-9fa6-1718-316e-10c0d2951ec4/AppIcon-0-0-1x_U007epad-0-1-0-85-220.png/512x512bb.jpg",
    "vip-game-lords-mobile": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/2c/e8/57/2ce857b9-4946-82a2-421c-372a41c3e2e0/AppIcon-1x_U007emarketing-0-8-0-85-220-0.png/512x512bb.jpg",
    "vip-game-nba-infinite-europe": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/35/b6/75/35b675de-87b3-a5b9-ad4f-5c8224ddf5c8/AppIcon-1x_U007emarketing-0-8-0-85-220-0.png/512x512bb.jpg",
    "vip-game-vidio-premier": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/c0/7a/0b/c07a0b3a-a7ad-4a44-1294-b1ce1592afb0/AppIcon-0-0-1x_U007epad-0-1-0-85-220.png/512x512bb.jpg",
    "vip-game-zepeto": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/b2/31/17/b23117c2-9f3f-9919-6f68-7271235695c3/AppIcon-0-0-1x_U007emarketing-0-8-0-sRGB-0-85-220.png/512x512bb.jpg",
    "vip-game-iqiyi-premium": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/b2/bc/cb/b2bccbb6-4536-e5c5-a1e6-a660ab1342a2/AppIcon-0-0-1x_U007emarketing-0-8-0-85-220.png/512x512bb.jpg",
    "vip-prepaid-ovo": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/64/bc/65/64bc6598-778a-4163-c606-5ea0db6dc14a/AppIcon-0-0-1x_U007emarketing-0-8-0-sRGB-85-220.png/512x512bb.jpg",
    "vip-prepaid-philippines-smart": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/d8/13/16/d8131622-28da-9450-4cf9-f9a62783dc9a/AppIcon-0-0-1x_U007emarketing-0-11-0-0-85-220.png/512x512bb.jpg",
    "vip-prepaid-pln": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/1e/28/75/1e2875f8-4934-c8b1-40a5-ebed19731318/AppIcon-0-0-1x_U007emarketing-0-11-0-85-220.png/512x512bb.jpg",
    "vip-prepaid-shopee-pay": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/1b/03/80/1b03808c-5949-0379-ba28-ec966b131e47/AppIcon-1x_U007emarketing-0-6-0-0-85-220-0.png/512x512bb.jpg",
    "vip-prepaid-smart": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/d8/13/16/d8131622-28da-9450-4cf9-f9a62783dc9a/AppIcon-0-0-1x_U007emarketing-0-11-0-0-85-220.png/512x512bb.jpg",
    "vip-prepaid-smartfren": "https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/6a/d1/6a/6ad16a30-0f1c-8c92-0f52-ca7a2e4fe1ad/AppIcon-1x_U007emarketing-0-8-0-85-220-0.png/512x512bb.jpg",
    "vip-prepaid-vietnam-topup": "https://flagcdn.com/w320/vn.png",
}


DOMAIN_FALLBACKS = {
    "vip-game-amazon-prime-video": "primevideo.com",
    "vip-game-bstation-premium": "bilibili.tv",
    "vip-game-canva-pro": "canva.com",
    "vip-game-capcut-pro": "capcut.com",
    "vip-game-chatgpt": "openai.com",
    "vip-game-steam-wallet-code": "store.steampowered.com",
    "vip-game-voucher-garena-shell": "garena.com",
    "vip-game-voucher-megaxus": "megaxus.com",
    "vip-game-voucher-psn": "playstation.com",
    "vip-game-voucher-razer-gold": "razer.com",
    "vip-game-voucher-valorant": "playvalorant.com",
    "vip-game-warp-plus": "1.1.1.1",
    "vip-prepaid-alfamart-voucher": "alfagift.id",
    "vip-prepaid-axis": "axis.co.id",
    "vip-prepaid-bri-brizzi": "bri.co.id",
    "vip-prepaid-byu": "byu.id",
    "vip-prepaid-dana": "dana.id",
    "vip-prepaid-doku": "doku.com",
    "vip-prepaid-globe": "globe.com.ph",
    "vip-prepaid-go-pay": "gopay.co.id",
    "vip-prepaid-grab": "grab.com",
    "vip-prepaid-indomaret": "indomaret.co.id",
    "vip-prepaid-k-vision-dan-gol": "k-vision.tv",
    "vip-prepaid-likee": "likee.video",
    "vip-prepaid-linkaja": "linkaja.id",
    "vip-prepaid-mandiri-e-toll": "bankmandiri.co.id",
    "vip-prepaid-maxim": "taximaxim.com",
    "vip-prepaid-nex-parabola": "mynex.co.id",
    "vip-prepaid-orange-tv": "orangetv.co.id",
    "vip-prepaid-ovo": "ovo.id",
    "vip-prepaid-pln": "pln.co.id",
    "vip-prepaid-razer-gold": "razer.com",
    "vip-prepaid-shopee-pay": "shopeepay.co.id",
    "vip-prepaid-smart": "smart.com.ph",
    "vip-prepaid-smartfren": "smartfren.com",
    "vip-prepaid-starhub": "starhub.com",
    "vip-prepaid-sun-telecom": "smart.com.ph",
    "vip-prepaid-tapcash-bni": "bni.co.id",
    "vip-prepaid-telkomsel": "telkomsel.com",
    "vip-prepaid-tix-id": "tix.id",
    "vip-prepaid-tri": "tri.co.id",
    "vip-prepaid-xl": "xl.co.id",
    "vip-prepaid-xox": "xox.com.my",
}


FONT_PATHS = {
    "regular": [
        Path(r"C:\Windows\Fonts\segoeui.ttf"),
        Path(r"C:\Windows\Fonts\arial.ttf"),
    ],
    "bold": [
        Path(r"C:\Windows\Fonts\segoeuib.ttf"),
        Path(r"C:\Windows\Fonts\arialbd.ttf"),
    ],
}


MOBILE_LEGENDS_VARIANTS = {
    "vip-game-mobile-legends-a": {
        "source": ARTWORK_DIR / "mobile-legends-a-cover.png",
        "label": "TYPE A",
        "accent": (255, 208, 98),
    },
    "vip-game-mobile-legends-b": {
        "source": ARTWORK_DIR / "mobile-legends-b-cover.png",
        "label": "TYPE B",
        "accent": (132, 209, 255),
    },
    "vip-game-mobile-legends-brazil": {
        "source": ARTWORK_DIR / "mobile-legends-a-cover.png",
        "label": "BRAZIL",
        "accent": (112, 230, 143),
    },
    "vip-game-mobile-legends-global": {
        "source": ARTWORK_DIR / "mobile-legends-a-cover.png",
        "label": "GLOBAL",
        "accent": (255, 208, 98),
    },
    "vip-game-mobile-legends-malaysia": {
        "source": ARTWORK_DIR / "mobile-legends-b-cover.png",
        "label": "MALAYSIA",
        "accent": (132, 209, 255),
    },
    "vip-game-mobile-legends-philippines": {
        "source": ARTWORK_DIR / "mobile-legends-b-cover.png",
        "label": "PHILIPPINES",
        "accent": (255, 158, 120),
    },
    "vip-game-mobile-legends-russia": {
        "source": ARTWORK_DIR / "mobile-legends-b-cover.png",
        "label": "RUSSIA",
        "accent": (255, 132, 132),
    },
    "vip-game-mobile-legends-singapore": {
        "source": ARTWORK_DIR / "mobile-legends-a-cover.png",
        "label": "SINGAPORE",
        "accent": (255, 102, 118),
    },
}


BRAND_CARD_SPECS = {
    "vip-game-bstation-premium": {
        "title": "BSTATION",
        "subtitle": "PREMIUM",
        "icon_url": "https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/1f/c4/9b/1fc49b10-e83b-b522-020d-22a68de9ec6e/AppIcon-inter-0-0-1x_U007epad-0-1-0-85-220.png/512x512bb.jpg",
        "top": (11, 16, 38),
        "bottom": (2, 5, 14),
        "accent": (0, 209, 255),
    },
    "vip-game-voucher-megaxus": {
        "title": "MEGAXUS",
        "subtitle": "VOUCHER",
        "icon_url": "https://corporate.megaxus.com/uploads/settings/16850921636034.webp",
        "top": (62, 20, 12),
        "bottom": (22, 6, 3),
        "accent": (255, 141, 69),
    },
    "vip-prepaid-nex-parabola": {
        "title": "NEX",
        "subtitle": "PARABOLA",
        "icon_url": "https://www.mynex.co.id/public-image/icon.png",
        "top": (41, 22, 83),
        "bottom": (12, 8, 30),
        "accent": (255, 164, 52),
    },
}


@dataclass
class DownloadResult:
    source: str
    query: str | None = None
    country: str | None = None
    matched_name: str | None = None
    seller: str | None = None
    score: float | None = None


def php_catalog_products() -> list[dict[str, str]]:
    php_code = """
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make("Illuminate\\\\Contracts\\\\Console\\\\Kernel")->bootstrap();
echo json_encode(
    $app->make("App\\\\Services\\\\VipaymentService")->getCatalogProducts(),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
"""
    completed = subprocess.run(
        ["php", "-r", php_code],
        cwd=PROJECT_ROOT,
        capture_output=True,
        text=True,
        check=True,
    )
    payload = json.loads(completed.stdout)
    unique: dict[str, dict[str, str]] = {}
    for item in payload:
        unique.setdefault(item["id"], item)
    return list(unique.values())


def normalize_text(value: str) -> str:
    value = unicodedata.normalize("NFKD", value)
    value = value.encode("ascii", "ignore").decode("ascii")
    value = value.lower()
    value = value.replace("&", " and ")
    value = re.sub(r"\([^)]*\)", " ", value)
    value = re.sub(r"[^a-z0-9+]+", " ", value)
    return re.sub(r"\s+", " ", value).strip()


def token_set(value: str) -> set[str]:
    return {token for token in normalize_text(value).split() if token and token not in STOPWORDS}


def similarity(left: str, right: str) -> float:
    return SequenceMatcher(None, normalize_text(left), normalize_text(right)).ratio()


def build_queries(product_id: str, name: str) -> list[str]:
    if product_id in QUERY_OVERRIDES:
        queries = QUERY_OVERRIDES[product_id]
    else:
        simplified = re.sub(r"\([^)]*\)", "", name).strip()
        queries = [name, simplified]
        if simplified.lower().startswith("voucher "):
            queries.append(simplified[8:].strip())
        if simplified.lower().endswith(" premium"):
            queries.append(simplified[:-8].strip())

    deduped: list[str] = []
    seen: set[str] = set()
    for query in queries:
        clean = re.sub(r"\s+", " ", query).strip()
        if clean and clean not in seen:
            deduped.append(clean)
            seen.add(clean)
    return deduped


def countries_for(product_id: str) -> list[str]:
    overrides = COUNTRY_OVERRIDES.get(product_id, [])
    countries = [*overrides, "id", "us"]
    deduped: list[str] = []
    for country in countries:
        if country not in deduped:
            deduped.append(country)
    return deduped


def score_result(product_name: str, query: str, result: dict[str, object]) -> float:
    title = str(result.get("trackName", ""))
    seller = str(result.get("sellerName", ""))
    title_tokens = token_set(title)
    query_tokens = token_set(query)
    product_tokens = token_set(product_name)

    overlap_query = 0.0
    if query_tokens:
        overlap_query = len(query_tokens & title_tokens) / len(query_tokens)

    overlap_product = 0.0
    if product_tokens:
        overlap_product = len(product_tokens & title_tokens) / len(product_tokens)

    score = 0.0
    score += similarity(query, title) * 40
    score += similarity(product_name, title) * 35
    score += overlap_query * 45
    score += overlap_product * 25

    normalized_query = normalize_text(query)
    normalized_title = normalize_text(title)
    normalized_seller = normalize_text(seller)

    if normalized_query == normalized_title:
        score += 25
    elif normalized_query and normalized_query in normalized_title:
        score += 15

    if query_tokens and query_tokens & token_set(seller):
        score += 10

    if not query_tokens & title_tokens:
        score -= 20

    if "unofficial" in normalized_seller:
        score -= 10

    return score


def fetch_json(url: str) -> dict[str, object]:
    request = Request(url, headers={"User-Agent": USER_AGENT, "Accept": "application/json"})
    with urlopen(request, timeout=25) as response:
        return json.loads(response.read().decode("utf-8"))


def try_itunes_search(product_id: str, product_name: str) -> tuple[str | None, DownloadResult | None]:
    best_url = None
    best_meta = None
    best_score = float("-inf")

    for query in build_queries(product_id, product_name):
        encoded_query = quote(query)
        for country in countries_for(product_id):
            url = f"https://itunes.apple.com/search?term={encoded_query}&entity=software&limit=8&country={country}"
            try:
                payload = fetch_json(url)
            except (HTTPError, URLError, TimeoutError, json.JSONDecodeError):
                continue

            for result in payload.get("results", []):
                artwork = str(result.get("artworkUrl512", "") or result.get("artworkUrl100", ""))
                if not artwork:
                    continue

                score = score_result(product_name, query, result)
                if score > best_score:
                    best_score = score
                    best_url = artwork
                    best_meta = DownloadResult(
                        source="itunes",
                        query=query,
                        country=country,
                        matched_name=str(result.get("trackName", "")),
                        seller=str(result.get("sellerName", "")),
                        score=round(score, 2),
                    )

    if best_score < 45:
        return None, None

    return best_url, best_meta


def google_favicon_url(domain: str) -> str:
    normalized = domain if re.match(r"^https?://", domain) else f"https://{domain}"
    return f"https://www.google.com/s2/favicons?sz=512&domain_url={quote(normalized, safe=':/')}"


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


def load_font(size: int, *, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = FONT_PATHS["bold" if bold else "regular"]
    for path in candidates:
        if path.exists():
            return ImageFont.truetype(str(path), size=size)
    return ImageFont.load_default()


def interpolate_color(start: tuple[int, int, int], end: tuple[int, int, int], ratio: float) -> tuple[int, int, int]:
    return tuple(int(start[index] * (1 - ratio) + end[index] * ratio) for index in range(3))


def draw_vertical_gradient(
    draw: ImageDraw.ImageDraw,
    top: tuple[int, int, int],
    bottom: tuple[int, int, int],
) -> None:
    for y in range(OUTPUT_SIZE):
        ratio = y / (OUTPUT_SIZE - 1)
        color = interpolate_color(top, bottom, ratio)
        draw.line((0, y, OUTPUT_SIZE, y), fill=(*color, 255))


def text_size(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.ImageFont) -> tuple[int, int]:
    left, top, right, bottom = draw.textbbox((0, 0), text, font=font)
    return right - left, bottom - top


def draw_pill(
    draw: ImageDraw.ImageDraw,
    text: str,
    x: int,
    y: int,
    font: ImageFont.ImageFont,
    fill: tuple[int, int, int, int],
    text_fill: tuple[int, int, int, int],
) -> None:
    width, height = text_size(draw, text, font)
    padding_x = 20
    padding_y = 12
    draw.rounded_rectangle(
        (x, y, x + width + (padding_x * 2), y + height + (padding_y * 2)),
        radius=height + padding_y,
        fill=fill,
    )
    draw.text((x + padding_x, y + padding_y - 1), text, font=font, fill=text_fill)


def draw_text_with_shadow(
    draw: ImageDraw.ImageDraw,
    position: tuple[int, int],
    text: str,
    font: ImageFont.ImageFont,
    fill: tuple[int, int, int, int],
) -> None:
    x, y = position
    draw.text((x, y + 3), text, font=font, fill=(0, 0, 0, 150))
    draw.text((x, y), text, font=font, fill=fill)


def generate_mobile_legends_card(
    source_path: Path,
    destination: Path,
    label: str,
    accent: tuple[int, int, int],
) -> None:
    with Image.open(source_path) as source_image:
        image = ImageOps.fit(
            source_image.convert("RGBA"),
            (OUTPUT_SIZE, OUTPUT_SIZE),
            method=Image.Resampling.LANCZOS,
            centering=(0.5, 0.18),
        )

    shadow = Image.new("RGBA", (OUTPUT_SIZE, OUTPUT_SIZE), (0, 0, 0, 0))
    shadow_draw = ImageDraw.Draw(shadow)
    for y in range(OUTPUT_SIZE):
        ratio = max(0.0, (y - 140) / 372)
        alpha = int(220 * ratio)
        shadow_draw.line((0, y, OUTPUT_SIZE, y), fill=(4, 7, 20, alpha))
    image.alpha_composite(shadow)

    glow = Image.new("RGBA", (OUTPUT_SIZE, OUTPUT_SIZE), (0, 0, 0, 0))
    glow_draw = ImageDraw.Draw(glow)
    glow_draw.ellipse((-70, -110, 320, 240), fill=(*accent, 135))
    glow_draw.ellipse((320, -80, 620, 220), fill=(255, 91, 158, 95))
    image.alpha_composite(glow.filter(ImageFilter.GaussianBlur(50)))

    draw = ImageDraw.Draw(image)
    title_font = load_font(46, bold=True)
    badge_font = load_font(21, bold=True)
    label_font = load_font(24, bold=True)

    draw_pill(draw, "VIP GAME", 32, 28, badge_font, (12, 19, 43, 210), (255, 255, 255, 255))
    draw_text_with_shadow(draw, (36, 366), "MOBILE", title_font, (255, 255, 255, 255))
    draw_text_with_shadow(draw, (36, 414), "LEGENDS", title_font, (255, 255, 255, 255))
    draw_pill(draw, label, 36, 462, label_font, (*accent, 235), (20, 17, 14, 255))

    image.save(destination, format="PNG", optimize=True)


def generate_brand_card(
    destination: Path,
    *,
    title: str,
    subtitle: str,
    icon_url: str,
    top: tuple[int, int, int],
    bottom: tuple[int, int, int],
    accent: tuple[int, int, int],
) -> None:
    image = Image.new("RGBA", (OUTPUT_SIZE, OUTPUT_SIZE), (0, 0, 0, 0))
    draw = ImageDraw.Draw(image)
    draw_vertical_gradient(draw, top, bottom)

    glow = Image.new("RGBA", (OUTPUT_SIZE, OUTPUT_SIZE), (0, 0, 0, 0))
    glow_draw = ImageDraw.Draw(glow)
    glow_draw.ellipse((-80, -120, 260, 190), fill=(*accent, 120))
    glow_draw.ellipse((290, 120, 640, 470), fill=(255, 255, 255, 48))
    image.alpha_composite(glow.filter(ImageFilter.GaussianBlur(60)))

    icon_bytes = fetch_bytes(icon_url)
    with Image.open(io.BytesIO(icon_bytes)) as icon_image:
        icon = icon_image.convert("RGBA")

    icon_box_size = 214
    icon_card = Image.new("RGBA", (icon_box_size, icon_box_size), (0, 0, 0, 0))
    icon_card_draw = ImageDraw.Draw(icon_card)
    icon_card_draw.rounded_rectangle(
        (0, 0, icon_box_size, icon_box_size),
        radius=54,
        fill=(255, 255, 255, 28),
        outline=(255, 255, 255, 54),
        width=2,
    )

    icon = ImageOps.contain(icon, (154, 154), Image.Resampling.LANCZOS)
    icon_x = (icon_box_size - icon.width) // 2
    icon_y = (icon_box_size - icon.height) // 2
    icon_card.paste(icon, (icon_x, icon_y), icon)
    image.alpha_composite(icon_card, (36, 42))

    draw = ImageDraw.Draw(image)
    eyebrow_font = load_font(22, bold=True)
    title_font = load_font(58, bold=True)
    note_font = load_font(22, bold=False)

    draw_pill(draw, "VIP DYNAMIC", 36, 278, eyebrow_font, (*accent, 230), (19, 16, 12, 255))
    draw_text_with_shadow(draw, (36, 336), title, title_font, (255, 255, 255, 255))
    draw_text_with_shadow(draw, (36, 402), subtitle, title_font, (255, 255, 255, 255))
    draw.text((38, 468), "Katalog VIP dinamis", font=note_font, fill=(235, 240, 255, 215))

    image.save(destination, format="PNG", optimize=True)


def try_generate_custom_artwork(product_id: str, destination: Path) -> str | None:
    mobile_legends_spec = MOBILE_LEGENDS_VARIANTS.get(product_id)
    if mobile_legends_spec:
        generate_mobile_legends_card(
            mobile_legends_spec["source"],
            destination,
            str(mobile_legends_spec["label"]),
            tuple(mobile_legends_spec["accent"]),
        )
        return "custom-mobile-legends-card"

    brand_card_spec = BRAND_CARD_SPECS.get(product_id)
    if brand_card_spec:
        generate_brand_card(destination, **brand_card_spec)
        return "custom-brand-card"

    return None


def write_map_file(entries: Iterable[str]) -> None:
    lines = [
        "export const vipProductArtworkMap: Record<string, { coverImage: string; iconImage: string }> = {",
    ]
    for product_id in sorted(entries):
        path = f"/product-artwork/{product_id}.png"
        lines.append(f"    '{product_id}': {{ coverImage: '{path}', iconImage: '{path}' }},")
    lines.append("};")
    lines.append("")
    MAP_PATH.write_text("\n".join(lines), encoding="utf-8")


def main() -> int:
    ARTWORK_DIR.mkdir(parents=True, exist_ok=True)
    REPORT_PATH.parent.mkdir(parents=True, exist_ok=True)

    catalog = php_catalog_products()
    by_id = {item["id"]: item for item in catalog}
    report: dict[str, object] = {
        "generatedAt": __import__("datetime").datetime.now().isoformat(),
        "totalProducts": len(catalog),
        "items": [],
    }

    completed_ids: set[str] = set()

    for product in catalog:
        product_id = product["id"]
        destination = ARTWORK_DIR / f"{product_id}.png"
        item_report = {
            "id": product_id,
            "name": product["name"],
            "categoryId": product["categoryId"],
            "status": "missing",
        }

        try:
            custom_source = try_generate_custom_artwork(product_id, destination)
        except (OSError, HTTPError, URLError, TimeoutError):
            custom_source = None

        if custom_source:
            completed_ids.add(product_id)
            item_report["status"] = "ok"
            item_report["source"] = custom_source
            report["items"].append(item_report)
            continue

        existing_source = EXISTING_FILE_COPY_MAP.get(product_id)
        if existing_source and existing_source.exists():
            save_square_png(existing_source.read_bytes(), destination)
            completed_ids.add(product_id)
            item_report["status"] = "ok"
            item_report["source"] = "existing-local"
            item_report["sourcePath"] = str(existing_source)
            report["items"].append(item_report)
            continue

        copy_from_id = COPY_ARTWORK_FROM.get(product_id)
        if copy_from_id and copy_from_id in completed_ids:
            source_path = ARTWORK_DIR / f"{copy_from_id}.png"
            if source_path.exists():
                save_square_png(source_path.read_bytes(), destination)
                completed_ids.add(product_id)
                item_report["status"] = "ok"
                item_report["source"] = "copied-from-generated"
                item_report["copiedFrom"] = copy_from_id
                report["items"].append(item_report)
                continue

        preferred_direct_url = PREFERRED_DIRECT_URLS.get(product_id)
        if preferred_direct_url:
            try:
                save_square_png(fetch_bytes(preferred_direct_url), destination)
                completed_ids.add(product_id)
                item_report["status"] = "ok"
                item_report["source"] = "preferred-direct-url"
                item_report["imageUrl"] = preferred_direct_url
                report["items"].append(item_report)
                continue
            except (OSError, HTTPError, URLError, TimeoutError):
                pass

        image_url, meta = try_itunes_search(product_id, product["name"])
        if image_url and meta:
            try:
                save_square_png(fetch_bytes(image_url), destination)
                completed_ids.add(product_id)
                item_report["status"] = "ok"
                item_report["source"] = meta.source
                item_report["query"] = meta.query
                item_report["country"] = meta.country
                item_report["matchedName"] = meta.matched_name
                item_report["seller"] = meta.seller
                item_report["score"] = meta.score
                item_report["imageUrl"] = image_url
                report["items"].append(item_report)
                continue
            except (OSError, HTTPError, URLError, TimeoutError):
                pass

        direct_url = DIRECT_URL_FALLBACKS.get(product_id)
        if direct_url:
            try:
                save_square_png(fetch_bytes(direct_url), destination)
                completed_ids.add(product_id)
                item_report["status"] = "ok"
                item_report["source"] = "direct-url"
                item_report["imageUrl"] = direct_url
                report["items"].append(item_report)
                continue
            except (OSError, HTTPError, URLError, TimeoutError):
                pass

        domain = DOMAIN_FALLBACKS.get(product_id)
        if domain:
            favicon_url = google_favicon_url(domain)
            try:
                save_square_png(fetch_bytes(favicon_url), destination)
                completed_ids.add(product_id)
                item_report["status"] = "ok"
                item_report["source"] = "favicon"
                item_report["domain"] = domain
                item_report["imageUrl"] = favicon_url
                report["items"].append(item_report)
                continue
            except (OSError, HTTPError, URLError, TimeoutError):
                pass

        item_report["attemptedQueries"] = build_queries(product_id, product["name"])
        item_report["attemptedCountries"] = countries_for(product_id)
        report["items"].append(item_report)

    write_map_file(completed_ids)
    report["completed"] = len(completed_ids)
    report["missing"] = len(catalog) - len(completed_ids)
    REPORT_PATH.write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding="utf-8")

    missing_ids = [item["id"] for item in report["items"] if item["status"] != "ok"]
    print(f"Downloaded artwork for {len(completed_ids)} / {len(catalog)} products.")
    if missing_ids:
        print("Missing:")
        for product_id in missing_ids:
            print(f"- {product_id}")

    return 0 if not missing_ids else 1


if __name__ == "__main__":
    sys.exit(main())
