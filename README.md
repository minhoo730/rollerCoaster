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

## Architecture Direction

이 프로젝트는 그누보드7의 회원, 권한, 게시판, 관리자, 확장 시스템을 기반으로 사용하고,
RollerCoaster의 주식 도메인 기능을 그 위에 추가합니다.

- 그누보드7 코어 기능은 `backend/`의 기존 구조를 최대한 그대로 사용합니다.
- 주식 시세, 관심종목, 종목별 토론, 투자 알림 같은 RollerCoaster 전용 기능은 별도 도메인으로 관리합니다.
- 현재 주식 API는 `backend/app/Services/Kis`와 `backend/routes/apis/stock.php`에 얇게 연결되어 있습니다.
- 기능이 커지면 주식 도메인은 `backend/modules/_bundled/rollercoaster-stock` 같은 모듈로 분리하는 것을 기준으로 합니다.

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
