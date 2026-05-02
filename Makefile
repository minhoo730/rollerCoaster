COMPOSE=docker compose -f backend/docker-compose.yml
FRONT_URL=http://localhost:12000

up:
	$(COMPOSE) up -d

build:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

restart:
	$(COMPOSE) down
	$(COMPOSE) up -d

# open:
# 	powershell.exe -NoProfile -Command "Start-Process '$(FRONT_URL)'"

logs:
	$(COMPOSE) logs -f

logs-front:
	$(COMPOSE) logs -f frontend

logs-web:
	$(COMPOSE) logs -f web

logs-db:
	$(COMPOSE) logs -f db

dev:
	$(COMPOSE) up -d
	sleep 3
	$(COMPOSE) logs -f

rebuild:
	$(COMPOSE) down
	$(COMPOSE) up -d --build
	sleep 3
	$(COMPOSE) logs -f

dev-log:
	$(COMPOSE) up -d
	sleep 3
	$(COMPOSE) logs -f