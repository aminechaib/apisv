<p align="center">
  <img src="https://raw.githubusercontent.com/gyfm/logos/main/business-card.svg" width="200" alt="Business Card OCR API">
  <h1>Business Card OCR API</h1>
  <img src="https://img.shields.io/badge/Laravel-12-brightgreen.svg" alt="Laravel 12">
  <img src="https://img.shields.io/badge/OCR-Tesseract-blue.svg" alt="Tesseract OCR">
  <img src="https://img.shields.io/badge/AI-Mistral%20Tiny-orange.svg" alt="Mistral AI">
  <img src="https://img.shields.io/badge/Queue-Jobs-red.svg" alt="Queue Jobs">
  <img src="https://img.shields.io/badge/License-MIT-brightgreen.svg" alt="MIT License">
</p>

## 📋 Overview

**Business Card OCR API** is a robust Laravel 12 RESTful API that automates business card processing. Upload a card image, extract text via OCR, structure data using AI, and manage contacts with export capabilities.

### ✨ Key Features
- **Image Upload & OCR**: Tesseract OCR extracts text from business cards
- **AI Data Structuring**: Mistral AI (tiny model) parses into JSON: `name`, `email`, `phone`, `company`, `activity`, `address`, `website`, `confidence_score`
- **Queue Processing**: Asynchronous jobs with retries and fallback regex parsing
- **Multi-value Support**: Emails/phones/addresses joined with `/`
- **API Endpoints**: CRUD for contacts, image serving, Excel export
- **Review Workflow**: Flag low-confidence (`<0.85`) extractions for manual review
- **Tested**: Pest feature tests for core upload/processing flow

## 🛠 Architecture
```
Upload Image → OCR Service → Queue Job (ProcessBusinessCard)
             ↓
     Contact Model ← Mistral AI / Fallback Parser
             ↓
   API: List / Update / Delete / Export Excel
```

## 🚀 Quick Start

1. **Clone & Install**
   ```bash
   git clone <repo> apisv
   cd apisv
   composer install
   npm ci
   cp .env.example .env
   php artisan key:generate
   ```

2. **Environment Setup**
   ```
   DB_CONNECTION=sqlite  # or mysql/postgres
   QUEUE_CONNECTION=database  # or redis
   MISTRAL_API_KEY=your_key_here  # Optional: fallback works without
   ```

3. **Database & Run**
   ```bash
   php artisan migrate
   php artisan db:seed  # Optional
   php artisan queue:work &
   php artisan serve
   npm run dev  # For Tailwind
   ```

4. **Test**
   ```bash
   php artisan test
   ```

## 📖 API Documentation

| Method | Endpoint | Description | Example |
|--------|----------|-------------|---------|
| `POST` | `/api/process-card` | Upload card image (multipart: `card_image`, optional `text`) | `curl -F "card_image=@card.jpg" http://localhost:8000/api/process-card` |
| `POST` | `/api/process-text` | Submit extracted text | `{"text": "...", "image_url": "..."}` |
| `GET` | `/api/contacts` | List validated contacts (paginated) | - |
| `GET` | `/api/export-contacts` | Download Excel | - |
| `GET` | `/api/cards/{id}/image` | Serve contact image | - |
| `PUT` | `/api/contacts/{id}` | Update contact | `{"name": "...", "email": "..."}` |
| `DELETE` | `/api/contacts/{id}` | Delete contact | - |

**Response Example** (processing):
```json
{
  "message": "Card image received. Processing has started.",
  "contact_id": 1,
  "image_url": "http://localhost:8000/api/cards/1/image",
  "status": "processing"
}
```

## 🔧 Requirements
- PHP 8.3+
- Laravel 12
- Tesseract OCR (system install: `brew install tesseract` / `apt install tesseract-ocr`)
- Composer, Node.js/NPM
- Database: SQLite/MySQL/PostgreSQL
- Queue driver (database/redis)
- Optional: Mistral AI API key

## 🧪 Testing
```bash
php artisan test --coverage  # Coverage report
```

## 📈 Deployment
- Forge/Vapor/Laravel Herd
- Queue workers: Supervisor/Horizon
- Storage: Public disk for images
- OCR: Docker with Tesseract if serverless

## 🤝 Contributing
1. Fork & PR
2. Follow Laravel Pint: `composer run pint`
3. Add tests: `php artisan make:test --pest`
4. See [Laravel Boost Guidelines](AGENTS.md)

## 📄 License
MIT License - see [LICENSE](LICENSE) (add if missing).

**Built with ❤️ using Laravel 12**


