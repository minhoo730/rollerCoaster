# RollerCoaster

주식 커뮤니티와 거래 정보를 함께 보여주는 웹 애플리케이션입니다.

## Stack

- Backend: Laravel
- Frontend: Vue 3, Vite, Tailwind CSS
- Market data: 한국투자증권 KIS API

## Project Structure

```text
backend/   Laravel API server
frontend/  Vue client
```

## Development

Backend:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Frontend:

```bash
cd frontend
npm install
npm run dev
```

## Notes

- `backend/.env`에는 KIS API 키와 앱 설정이 들어가므로 Git에 올리지 않습니다.
- 프론트엔드는 `/api` 요청을 Vite proxy로 백엔드에 전달합니다.
