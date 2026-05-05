<p align="center">
  <img src="https://raw.githubusercontent.com/aminechaib/apisv/main/business-card-icon.png" width="200" alt="Business Card OCR API">
  <h1>Business Card OCR API</h1>
  <img src="https://img.shields.io/badge/Laravel-12-brightgreen.svg?style=for-the-badge" alt="Laravel 12">
  <img src="https://img.shields.io/badge/OCR-Tesseract-blue.svg?style=for-the-badge" alt="Tesseract OCR">
  <img src="https://img.shields.io/badge/AI-Mistral_Tiny-orange.svg?style=for-the-badge" alt="Mistral AI">
  <img src="https://img.shields.io/badge/Queue-Jobs-red.svg?style=for-the-badge" alt="Queue Jobs">
  <img src="https://img.shields.io/badge/License-MIT-brightgreen.svg?style=for-the-badge" alt="MIT License">
</p>

<p align="center">
  <img src="https://raw.githubusercontent.com/aminechaib/apisv/main/Flag_map_of_Algeria.svg" width="30" alt="Algeria Flag"> 
  <strong><a href="https://github.com/aminechaib">Amine Chaib</a></strong><br>
  🇩🇿 DZ Fullstack Developer | Laravel Expert
</p>

## 📋 Overview

**Business Card OCR API** is a production-ready Laravel 12 REST API for automating business card digitization. 

**Flow**:
1. Upload image → Tesseract OCR extracts text
2. Queue job → Mistral AI structures data (name/email/phone/company/activity/address/website + confidence)
3. Store in DB → Review low-confidence → Export Excel

Supports multi-values (e.g., phones: "+213/0555") and fallback parsing.

### ✨ Features
- ✅ AI-powered extraction (Mistral Tiny / Regex fallback)
- ✅ Async queue processing (3 retries)
- ✅ Image serving & pagination
- ✅ Excel export (maatwebsite/excel)
- ✅ Review workflow (confidence < 0.85)
- ✅ Pest tested
- ✅ Sanctum ready

## 🛠 Tech Stack
```
Laravel 12 | PHP 8.3 | MySQL/SQLite | Redis/Database Queue
Tesseract OCR | Mistral AI API | Tailwind | Vite | Pest
```

## 🚀 Quickstart (5min)

```bash
git clone <repo> && cd apisv
composer install --no-dev
npm ci
cp .env.example .env
php artisan key:generate

# DB
php artisan migrate --seed

# Run (Terminal 1)
php artisan queue:work --sleep=3 --tries=3

# Run (Terminal 2)
php artisan serve
npm run dev
```

**Test Upload**:
```bash
curl -X POST -F 'card_image=@your_card.jpg' http://127.0.0.1:8000/api/process-card
```

## 📖 API Endpoints

| Endpoint | Method | Auth | Desc |
|----------|--------|------|------|
| `/api/process-card` | POST | - | Upload image (`multipart/form-data`) |
| `/api/process-text` | POST | - | Submit OCR text |
| `/api/contacts` | GET | - | Paginated validated contacts |
| `/api/export-contacts` | GET | - | Excel download |
| `/api/cards/{id}/image` | GET | - | Image CDN |
| `/api/contacts/{id}` | PUT | - | Edit contact |
| `/api/contacts/{id}` | DELETE | - | Delete |

**Sample Response**:
```json
{
  "contact_id": 1,
  "status": "processing",
  "image_url": "/api/cards/1/image"
}
```

**Contact JSON**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1-555-1234",
  "company": "ABC Corp",
  "confidence_score": 0.95
}
```

## 🧪 Testing & Quality

```bash
php artisan test --coverage
# 100% pass rate expected
```

Pint formatted, migration-ready.

## 🔧 Requirements
| Tool | Install |
|------|---------|
| PHP 8.3+ | - |
| Tesseract | `choco install tesseract` (Win) / `apt install tesseract-ocr` |
| Composer | `composer install` |
| Node | `npm ci && npm run dev` |
| Mistral Key | `.env` (optional) |

## 📈 Production Deployment
```
Forge/Vapor | Supervisor queues | Horizon dashboard
S3/Cloudinary images | Redis queue | MySQL
Docker: Tesseract layer
```

## 🤝 Contribute
1. ⭐ Star & Fork
2. `composer run pint`
3. `git pr` with tests
4. `@aminechaib` for features

See [AGENTS.md](AGENTS.md) for Boost rules.

## 📄 License
MIT © 2026 [Amine Chaib](https://github.com/aminechaib)

<div align="center">
  <img src="https://komarev.com/ghpvc/?username=aminechaib&label=Profile%20views&color=0e75b6&style=flat" alt="Profile views">

</div>
