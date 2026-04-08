from __future__ import annotations

import json
import re
from dataclasses import dataclass
from pathlib import Path


STOPWORDS = {
    "yang",
    "dan",
    "di",
    "ke",
    "dari",
    "untuk",
    "apa",
    "siapa",
    "adalah",
    "itu",
    "ini",
    "atau",
    "dengan",
    "pada",
    "tentang",
    "nya",
    "cara",
    "bagaimana",
    "apakah",
    "gimana",
}

NORMALIZATION_PATTERNS = (
    (r"\bchat\s*gpt\b", "chatgpt"),
    (r"\btop[\s-]*up\b", "topup"),
    (r"\be[\s-]*wallet\b", "ewallet"),
    (r"\bpw\b", "password"),
    (r"\bpass word\b", "password"),
    (r"\bkata\s*sandi\b", "password"),
    (r"\bsandi\b", "password"),
    (r"\bmobile\s*legends?\b", "mobile legends"),
    (r"\bfree\s*fire\b", "free fire"),
    (r"\bvirtual\s*account\b", "virtual account"),
    (r"\bhal+o+\b", "halo"),
    (r"\bha+i+\b", "hai"),
    (r"\bmakasih+\b", "makasih"),
)

CONVERSATIONAL_RESPONSES = (
    (
        (
            "halo",
            "hai",
            "hi",
            "hello",
            "assalamualaikum",
            "selamat pagi",
            "selamat siang",
            "selamat sore",
            "selamat malam",
            "pagi",
            "siang",
            "sore",
            "malam",
        ),
        (
            "Halo, selamat datang di Lyva Indonesia. "
            "Aku siap bantu jawab pertanyaan soal produk, pembayaran, transaksi, atau layanan yang tersedia di Lyva."
        ),
    ),
    (
        (
            "siapa kamu",
            "kamu siapa",
            "ini bot apa",
            "bot apa ini",
            "kamu bot apa",
        ),
        (
            "Aku adalah chatbot Lyva Indonesia. "
            "Tugasku membantu jawab pertanyaan seputar produk, pembayaran, cek transaksi, dan informasi umum tentang Lyva."
        ),
    ),
    (
        (
            "bisa bantu apa",
            "kamu bisa bantu apa",
            "fitur kamu apa",
            "kamu bisa apa",
        ),
        (
            "Aku bisa bantu jelaskan produk yang tersedia di Lyva Indonesia, "
            "metode pembayaran, estimasi proses, cara cek transaksi, dan kendala checkout dasar."
        ),
    ),
    (
        (
            "terima kasih",
            "makasih",
            "thanks",
            "thank you",
        ),
        "Sama-sama. Kalau masih ada yang ingin ditanyakan tentang Lyva Indonesia, langsung kirim saja ya.",
    ),
    (
        (
            "bye",
            "dadah",
            "sampai jumpa",
            "selamat tinggal",
        ),
        "Siap, sampai jumpa lagi. Kalau butuh bantuan tentang Lyva Indonesia, aku siap bantu kapan saja.",
    ),
)

FIXED_SUPPORT_RESPONSES = (
    (
        (
            "reset password",
            "lupa password",
            "ganti password",
            "ubah password",
            "password akun",
        ),
        (
            "Kalau mau reset password akun Lyva Indonesia, buka halaman login lalu pilih menu "
            "\"Lupa Password\" atau langsung ke `/forgot-password`. "
            "Masukkan email akunmu, lalu cek email untuk link reset password. "
            "Kalau email belum masuk, cek folder spam atau promosi juga ya."
        ),
    ),
)


def normalize(text: str) -> str:
    lowered = text.lower()
    for pattern, replacement in NORMALIZATION_PATTERNS:
        lowered = re.sub(pattern, replacement, lowered)
    return re.sub(r"\s+", " ", re.sub(r"[^a-zA-Z0-9\s]", " ", lowered)).strip()


def tokenize(text: str) -> set[str]:
    return {
        token
        for token in normalize(text).split()
        if token and token not in STOPWORDS and len(token) > 2
    }


@dataclass
class MatchResult:
    answer: str
    score: float


class KnowledgeBase:
    def __init__(self, knowledge_path: Path, unanswered_path: Path) -> None:
        self.knowledge_path = knowledge_path
        self.unanswered_path = unanswered_path
        self._ensure_files()

    def _ensure_files(self) -> None:
        self.knowledge_path.parent.mkdir(parents=True, exist_ok=True)
        self.unanswered_path.parent.mkdir(parents=True, exist_ok=True)

        if not self.knowledge_path.exists():
            self._write_json(
                self.knowledge_path,
                [
                    {
                        "question": "Apa itu Lyva Indonesia?",
                        "answer": (
                            "Lyva Indonesia adalah entri pengetahuan awal chatbot ini. "
                            "Silakan ganti dengan profil bisnis, layanan, visi, dan info resmi Lyva Indonesia."
                        ),
                        "keywords": ["lyva", "indonesia", "profil"],
                    }
                ],
            )

        if not self.unanswered_path.exists():
            self._write_json(self.unanswered_path, [])

    def _read_json(self, path: Path) -> list[dict]:
        with path.open("r", encoding="utf-8") as file:
            return json.load(file)

    def _write_json(self, path: Path, payload: list[dict]) -> None:
        with path.open("w", encoding="utf-8") as file:
            json.dump(payload, file, ensure_ascii=False, indent=2)

    def get_all_knowledge(self) -> list[dict]:
        return self._read_json(self.knowledge_path)

    def get_unanswered_questions(self) -> list[str]:
        data = self._read_json(self.unanswered_path)
        return [item["question"] for item in data][-10:][::-1]

    def add_knowledge(self, question: str, answer: str, keywords: list[str] | None = None) -> None:
        clean_question = question.strip()
        clean_answer = answer.strip()
        clean_keywords = self._build_keywords(clean_question, keywords)
        records = self.get_all_knowledge()
        question_key = normalize(clean_question)

        for index, record in enumerate(records):
            if normalize(str(record.get("question", ""))) != question_key:
                continue

            existing_keywords = record.get("keywords", [])
            merged_keywords = self._merge_keywords(existing_keywords, clean_keywords)
            records[index] = {
                "question": clean_question,
                "answer": clean_answer,
                "keywords": merged_keywords,
            }
            self._write_json(self.knowledge_path, records)
            self._remove_unanswered(clean_question)
            return

        records.append(
            {
                "question": clean_question,
                "answer": clean_answer,
                "keywords": clean_keywords,
            }
        )
        self._write_json(self.knowledge_path, records)
        self._remove_unanswered(clean_question)

    def _build_keywords(self, question: str, keywords: list[str] | None) -> list[str]:
        prepared: list[str] = []
        if isinstance(keywords, list):
            prepared.extend(str(item).strip() for item in keywords if str(item).strip())

        auto_keywords = [token for token in tokenize(question) if token != "lyva"]
        prepared.extend(auto_keywords)
        return self._merge_keywords([], prepared)

    def _merge_keywords(self, existing: list[str] | None, incoming: list[str] | None) -> list[str]:
        merged: list[str] = []

        for item in list(existing or []) + list(incoming or []):
            value = str(item).strip()
            if not value:
                continue
            if normalize(value) in {normalize(keyword) for keyword in merged}:
                continue
            merged.append(value)

        return merged[:15]

    def _remove_unanswered(self, question: str) -> None:
        question_key = normalize(question)
        records = self._read_json(self.unanswered_path)
        filtered = [item for item in records if normalize(item["question"]) != question_key]
        self._write_json(self.unanswered_path, filtered)

    def _save_unanswered(self, question: str) -> None:
        records = self._read_json(self.unanswered_path)
        question_key = normalize(question)

        for item in records:
            if normalize(item["question"]) == question_key:
                item["count"] += 1
                self._write_json(self.unanswered_path, records)
                return

        records.append({"question": question.strip(), "count": 1})
        self._write_json(self.unanswered_path, records)

    def _score_record(self, question: str, record: dict) -> MatchResult:
        normalized_question = normalize(question)
        input_tokens = tokenize(question)
        record_question = record.get("question", "")
        record_answer = record.get("answer", "")
        normalized_record_question = normalize(record_question)
        keyword_tokens = set()
        keyword_phrase_bonus = 0
        for keyword in record.get("keywords", []):
            keyword_tokens.update(tokenize(keyword))
            normalized_keyword = normalize(str(keyword))
            if normalized_keyword and normalized_keyword in normalized_question:
                keyword_phrase_bonus += 2

        question_tokens = tokenize(record_question)
        answer_tokens = tokenize(record_answer)

        overlap_question = len(input_tokens & question_tokens) * 2
        overlap_keywords = len(input_tokens & keyword_tokens) * 3
        overlap_answer = len(input_tokens & answer_tokens)

        exact_bonus = 5 if normalized_question == normalized_record_question else 0
        contains_bonus = 2 if normalized_record_question and normalized_record_question in normalized_question else 0
        score = overlap_question + overlap_keywords + overlap_answer + exact_bonus + contains_bonus + keyword_phrase_bonus

        return MatchResult(answer=record_answer, score=float(score))

    def _match_conversational_response(self, question: str) -> str | None:
        normalized_question = normalize(question)

        for triggers, answer in CONVERSATIONAL_RESPONSES:
            for trigger in triggers:
                normalized_trigger = normalize(trigger)
                if (
                    normalized_question == normalized_trigger
                    or normalized_question.startswith(f"{normalized_trigger} ")
                    or f" {normalized_trigger} " in f" {normalized_question} "
                ):
                    return answer

        return None

    def _match_fixed_support_response(self, question: str) -> str | None:
        normalized_question = normalize(question)

        for triggers, answer in FIXED_SUPPORT_RESPONSES:
            if any(trigger in normalized_question for trigger in triggers):
                return answer

        return None

    def answer(self, question: str) -> dict[str, str]:
        fixed_answer = self._match_fixed_support_response(question)
        if fixed_answer:
            self._remove_unanswered(question)
            return {"answer": fixed_answer}

        conversational_answer = self._match_conversational_response(question)
        if conversational_answer:
            self._remove_unanswered(question)
            return {"answer": conversational_answer}

        best_match = MatchResult(answer="", score=0.0)

        for record in self.get_all_knowledge():
            result = self._score_record(question, record)
            if result.score >= best_match.score:
                best_match = result

        if best_match.score >= 2:
            self._remove_unanswered(question)
            return {"answer": best_match.answer}

        self._save_unanswered(question)
        return {
            "answer": (
                "Aku belum tahu jawaban itu untuk sekarang. "
                "Tambahkan pengetahuan baru di form belajar agar aku bisa menjawabnya nanti."
            )
        }
