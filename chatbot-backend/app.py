from __future__ import annotations

import os
from pathlib import Path

from flask import Flask, jsonify, redirect, render_template, request, session, url_for

from knowledge_base import KnowledgeBase


BASE_DIR = Path(__file__).resolve().parent
DATA_DIR = BASE_DIR / "data"

app = Flask(__name__)
app.config["SECRET_KEY"] = os.environ.get("SECRET_KEY", "lyva-local-chatbot-secret")

kb = KnowledgeBase(
    knowledge_path=DATA_DIR / "knowledge.json",
    unanswered_path=DATA_DIR / "unanswered.json",
)


def get_history() -> list[dict[str, str]]:
    history = session.get("history", [])
    if not isinstance(history, list):
        history = []
    return history


@app.route("/", methods=["GET"])
def index():
    return render_template(
        "index.html",
        history=get_history(),
        unanswered=kb.get_unanswered_questions(),
    )


@app.route("/chat", methods=["POST"])
def chat():
    question = request.form.get("question", "").strip()
    if not question:
        return redirect(url_for("index"))

    result = kb.answer(question)
    history = get_history()
    history.append({"role": "user", "text": question})
    history.append({"role": "bot", "text": result["answer"]})
    session["history"] = history[-20:]
    return redirect(url_for("index"))


@app.route("/api/chat", methods=["POST"])
def api_chat():
    payload = request.get_json(silent=True) or {}
    question = str(payload.get("question", "")).strip()

    if not question:
        return jsonify({"ok": False, "error": "Question is required"}), 400

    result = kb.answer(question)
    return jsonify(
        {
            "ok": True,
            "question": question,
            "answer": result["answer"],
        }
    )


@app.route("/learn", methods=["POST"])
def learn():
    question = request.form.get("teach_question", "").strip()
    answer = request.form.get("teach_answer", "").strip()
    keywords_text = request.form.get("keywords", "").strip()

    if question and answer:
        keywords = [item.strip() for item in keywords_text.split(",") if item.strip()]
        kb.add_knowledge(question=question, answer=answer, keywords=keywords)

    return redirect(url_for("index"))


@app.route("/api/learn", methods=["POST"])
def api_learn():
    payload = request.get_json(silent=True) or {}
    question = str(payload.get("question", "")).strip()
    answer = str(payload.get("answer", "")).strip()
    keywords = payload.get("keywords", [])

    if not question or not answer:
        return jsonify({"ok": False, "error": "Question and answer are required"}), 400

    if not isinstance(keywords, list):
        keywords = []

    clean_keywords = [str(item).strip() for item in keywords if str(item).strip()]
    kb.add_knowledge(question=question, answer=answer, keywords=clean_keywords)

    return jsonify(
        {
            "ok": True,
            "message": "Knowledge saved",
            "question": question,
        }
    )


@app.route("/api/unanswered", methods=["GET"])
def api_unanswered():
    return jsonify(
        {
            "ok": True,
            "items": kb.get_unanswered_questions(),
        }
    )


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"ok": True, "service": "lyva-chatbot"})


@app.route("/reset", methods=["POST"])
def reset():
    session["history"] = []
    return redirect(url_for("index"))


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)
