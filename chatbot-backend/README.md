# Lyva Indonesia Local Chatbot

Chatbot ini dibuat tanpa API AI eksternal. Jawabannya diambil dari basis pengetahuan lokal yang bisa kamu tambah sendiri.

## Fitur

- Chatbot web lokal berbasis Flask
- Tidak terhubung ke API seperti ChatGPT
- Menyimpan pertanyaan yang belum terjawab
- Bisa diajari jawaban baru lewat form admin sederhana

## Cara menjalankan lokal

```bash
pip install -r requirements.txt
python app.py
```

Setelah itu buka browser ke:

```bash
http://127.0.0.1:5000
```

## Endpoint API untuk website utama

Kalau website `lyvaindonesia.com` sudah ada, pakai endpoint ini dari frontend kamu:

- `POST /api/chat`
- `POST /api/learn`
- `GET /api/unanswered`
- `GET /health`

Contoh request chat:

```bash
curl -X POST http://127.0.0.1:5000/api/chat \
  -H "Content-Type: application/json" \
  -d "{\"question\":\"Apa itu Lyva Indonesia?\"}"
```

Contoh response:

```json
{
  "ok": true,
  "question": "Apa itu Lyva Indonesia?",
  "answer": "Lyva Indonesia adalah entri awal chatbot lokal ini. Ubah jawaban ini dengan profil resmi Lyva Indonesia agar chatbot sesuai kebutuhan bisnis kamu."
}
```

Contoh JavaScript di website kamu:

```html
<script>
  async function kirimChat(pesan) {
    const response = await fetch("/api/chat", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ question: pesan })
    });

    const data = await response.json();
    return data.answer;
  }
</script>
```

## Cara bot belajar

1. Tanya sesuatu tentang Lyva Indonesia.
2. Jika bot belum tahu, pertanyaan akan masuk ke daftar "Pertanyaan Belum Terjawab".
3. Isi form "Ajari Bot" dengan pertanyaan dan jawaban resmi.
4. Setelah disimpan, bot bisa menjawab pertanyaan yang serupa di percobaan berikutnya.

## Catatan penting

Bot ini bukan AI generatif. Ia cocok sebagai tahap awal untuk FAQ, customer service sederhana, atau knowledge bot internal. Jika nanti kamu mau versi yang lebih pintar tapi tetap tidak memakai API luar, fondasi ini bisa di-upgrade ke model lokal.

## Deploy ke VPS

Untuk VPS Linux, umumnya nanti jalankan backend ini di belakang Nginx, misalnya:

```bash
pip install -r requirements.txt
gunicorn --bind 127.0.0.1:8200 app:app
```

Untuk production, lebih baik pakai WSGI server seperti `gunicorn` dan reverse proxy seperti `caddy` atau `nginx`. Website utama `lyvaindonesia.com` bisa tetap memakai frontend yang sudah ada, lalu memanggil backend chatbot ini dari domain yang sama atau subdomain seperti `bot.lyvaindonesia.com`.
