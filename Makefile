.PHONY: help install install-dev install-prod update update-dev update-prod start start-prod stop stop-prod restart restart-prod logs logs-prod logs-backend logs-backend-prod logs-frontend logs-frontend-prod logs-database logs-database-prod sync-ams sync-ams-prod fixtures-dev fixtures-prod assets-install assets-install-prod cli-backend cli-backend-prod admin-user admin-user-prod quick-setup quick-setup-prod build-dev build-prod ps ps-prod clean clean-prod clean-all clean-all-prod install-staging

# Variables
DOCKER_COMPOSE := docker compose
DOCKER_COMPOSE_DEV := $(DOCKER_COMPOSE) -f docker-compose.yml -f docker-compose.dev.yml
DOCKER_COMPOSE_PROD := $(DOCKER_COMPOSE) -f docker-compose.yml -f docker-compose.prod.yml
ENV_FILE := .env.docker
BACKEND_SERVICE := backend
DATABASE_SERVICE := database
FRONTEND_SERVICE := frontend

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
NC := \033[0m # No Color

help: ## Display help
	@echo "$(BLUE)╔════════════════════════════════════════════════════════╗$(NC)"
	@echo "$(BLUE)║     AMS APPS HUB - Makefile Commands                   ║$(NC)"
	@echo "$(BLUE)╚════════════════════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo "$(GREEN)INSTALLATION$(NC)"
	@echo "  $(YELLOW)make install-dev$(NC)          Install project in development mode"
	@echo "  $(YELLOW)make install-prod$(NC)         Install project in production mode"
	@echo "  $(YELLOW)make install$(NC)              Alias for install-dev
	@echo "  $(YELLOW)make install-staging$(NC)      Install project in staging mode"
	@echo ""
	@echo "$(GREEN)UPDATES$(NC)"
	@echo "  $(YELLOW)make update-dev$(NC)           Update project in development mode"
	@echo "  $(YELLOW)make update-prod$(NC)          Update project in production mode"
	@echo "  $(YELLOW)make update$(NC)               Alias for update-dev"
	@echo ""
	@echo "$(GREEN)CONTAINERS$(NC)"
	@echo "  $(YELLOW)make start$(NC)                Start development containers"
	@echo "  $(YELLOW)make start-prod$(NC)           Start production containers"
	@echo "  $(YELLOW)make stop$(NC)                 Stop development containers"
	@echo "  $(YELLOW)make stop-prod$(NC)            Stop production containers"
	@echo "  $(YELLOW)make restart$(NC)              Restart development containers"
	@echo "  $(YELLOW)make restart-prod$(NC)         Restart production containers"
	@echo "  $(YELLOW)make build-dev$(NC)            Build development images"
	@echo "  $(YELLOW)make build-prod$(NC)           Build production images"
	@echo "  $(YELLOW)make ps$(NC)                   Display development container status"
	@echo "  $(YELLOW)make ps-prod$(NC)              Display production container status"
	@echo ""
	@echo "$(GREEN)LOGS$(NC)"
	@echo "  $(YELLOW)make logs$(NC)                 Display development logs (all services)"
	@echo "  $(YELLOW)make logs-prod$(NC)            Display production logs (all services)"
	@echo "  $(YELLOW)make logs-backend$(NC)         Display development backend logs"
	@echo "  $(YELLOW)make logs-backend-prod$(NC)    Display production backend logs"
	@echo "  $(YELLOW)make logs-frontend$(NC)        Display development frontend logs"
	@echo "  $(YELLOW)make logs-frontend-prod$(NC)   Display production frontend logs"
	@echo "  $(YELLOW)make logs-database$(NC)        Display development database logs"
	@echo "  $(YELLOW)make logs-database-prod$(NC)   Display production database logs"
	@echo ""
	@echo "$(GREEN)QUICK SETUP$(NC)"
	@echo "  $(YELLOW)make quick-setup$(NC)          Create default admin + apps + sync AMS (dev)"
	@echo "  $(YELLOW)make quick-setup-prod$(NC)     Create default admin + apps + sync AMS (prod)"
	@echo ""
	@echo "$(GREEN)USERS$(NC)"
	@echo "  $(YELLOW)make admin-user$(NC)           Create administrator (dev)"
	@echo "  $(YELLOW)make admin-user-prod$(NC)      Create administrator (prod)"
	@echo ""
	@echo "$(GREEN)SYNC$(NC)"
	@echo "  $(YELLOW)make sync-ams$(NC)             Sync AMS data (dev)"
	@echo "  $(YELLOW)make sync-ams-prod$(NC)        Sync AMS data (prod)"
	@echo ""
	@echo "$(GREEN)DATA$(NC)"
	@echo "  $(YELLOW)make fixtures-dev$(NC)         Load development fixtures (dev)"
	@echo "  $(YELLOW)make fixtures-prod$(NC)        Load development fixtures (prod)"
	@echo ""
	@echo "$(GREEN)ASSETS$(NC)"
	@echo "  $(YELLOW)make assets-install$(NC)       Install Symfony assets (CSS/JS) (dev)"
	@echo "  $(YELLOW)make assets-install-prod$(NC)  Install Symfony assets (CSS/JS) (prod)"
	@echo ""
	@echo "$(GREEN)CLI$(NC)"
	@echo "  $(YELLOW)make cli-backend$(NC)          Open shell in backend (dev)"
	@echo "  $(YELLOW)make cli-backend-prod$(NC)     Open shell in backend (prod)"
	@echo ""
	@echo "$(GREEN)MAINTENANCE$(NC)"
	@echo "  $(YELLOW)make clean$(NC)                Stop and clean dev containers"
	@echo "  $(YELLOW)make clean-prod$(NC)           Stop and clean prod containers"
	@echo "  $(YELLOW)make clean-all$(NC)            Delete dev containers AND data"
	@echo "  $(YELLOW)make clean-all-prod$(NC)       Delete prod containers AND data"
	@echo ""

# ════════════════════════════════════════════════════════════════════════════════
# INSTALLATION
# ════════════════════════════════════════════════════════════════════════════════

install-dev: ## Install project in development mode
	@echo "$(GREEN)▶ Installing project in development mode...$(NC)"
	@echo "$(YELLOW)  • Starting containers...$(NC)"
	$(DOCKER_COMPOSE_DEV) up -d --build
	@echo "$(YELLOW)  • Waiting for database startup...$(NC)"
	sleep 10
	@echo "$(YELLOW)  • Installing PHP dependencies...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend composer install
	@echo "$(YELLOW)  • Running migrations...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(YELLOW)  • Loading fixtures (dev)...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend php bin/console doctrine:fixtures:load --group=dev --no-interaction
	@echo "$(YELLOW)  • Installing assets (CSS/JS)...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend php bin/console assets:install --symlink --relative
	@echo "$(YELLOW)  • Installing npm dependencies (frontend)...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T frontend npm install
	@echo "$(YELLOW)  • Loading applications...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend php bin/console app:load-applications
	@echo "$(YELLOW)  • Syncing AMS data...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend php bin/console app:sync-ams || \
		(echo "$(YELLOW)  ⚠ AMS sync failed — check AMS credentials$(NC)")
	@echo "$(GREEN)✓ Installation completed!$(NC)"
	@echo ""
	@echo "$(GREEN)Available services:$(NC)"
	@if [ -f .env ] && grep -q "DOMAIN=" .env; then \
		DOMAIN=$$(grep "^DOMAIN=" .env | cut -d'=' -f2); \
		if [ "$$DOMAIN" = "localhost" ]; then \
			echo "  • Frontend & Admin : $(YELLOW)http://localhost$(NC)"; \
			echo "  • Backend API : $(YELLOW)http://localhost/api$(NC)"; \
			echo "  • EasyAdmin : $(YELLOW)http://localhost/admin$(NC)"; \
		else \
			echo "  • Frontend & Admin : $(YELLOW)https://$$DOMAIN$(NC)"; \
			echo "  • Backend API : $(YELLOW)https://$$DOMAIN/api$(NC)"; \
			echo "  • EasyAdmin : $(YELLOW)https://$$DOMAIN/admin$(NC)"; \
		fi; \
	else \
		echo "  • Frontend & Admin : $(YELLOW)http://localhost$(NC)"; \
		echo "  • Backend API : $(YELLOW)http://localhost/api$(NC)"; \
		echo "  • EasyAdmin : $(YELLOW)http://localhost/admin$(NC)"; \
	fi
	@echo "  • Database : $(YELLOW)localhost:5432$(NC)"

install-prod: ## Install project in production mode
	@echo "$(GREEN)▶ Installing project in production mode...$(NC)"
	@echo "$(YELLOW)  • Starting containers (prod)...$(NC)"
	$(DOCKER_COMPOSE_PROD) up -d --build
	@echo "$(YELLOW)  • Waiting for database startup...$(NC)"
	sleep 10
	@echo "$(YELLOW)  • Installing PHP dependencies (optimized)...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T backend composer install --no-dev --optimize-autoloader
	@echo "$(YELLOW)  • Running migrations...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(YELLOW)  • Clearing cache and warmup...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T backend php bin/console cache:clear --env=prod
	@echo "$(YELLOW)  • Installing assets (CSS/JS)...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T backend php bin/console assets:install
	@echo "$(YELLOW)  • Building frontend (optimized)...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T frontend npm ci
	$(DOCKER_COMPOSE_PROD) exec -T frontend npm run build
	@echo "$(YELLOW)  • Loading applications...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T backend php bin/console app:load-applications
	@echo "$(YELLOW)  • Syncing AMS data...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T backend php bin/console app:sync-ams || \
		(echo "$(YELLOW)  ⚠ AMS sync failed — check AMS credentials$(NC)")
	@echo "$(GREEN)✓ Production installation completed!$(NC)"
	@echo ""
	@echo "$(GREEN)Available services:$(NC)"
	@if [ -f .env ] && grep -q "DOMAIN=" .env; then \
		DOMAIN=$$(grep "^DOMAIN=" .env | cut -d'=' -f2); \
		if [ "$$DOMAIN" = "localhost" ]; then \
			echo "  • Frontend & Admin : $(YELLOW)http://localhost$(NC)"; \
			echo "  • Backend API : $(YELLOW)http://localhost/api$(NC)"; \
			echo "  • EasyAdmin : $(YELLOW)http://localhost/admin$(NC)"; \
		else \
			echo "  • Frontend & Admin : $(YELLOW)https://$$DOMAIN$(NC)"; \
			echo "  • Backend API : $(YELLOW)https://$$DOMAIN/api$(NC)"; \
			echo "  • EasyAdmin : $(YELLOW)https://$$DOMAIN/admin$(NC)"; \
		fi; \
	else \
		echo "  • Frontend & Admin : $(YELLOW)http://localhost$(NC)"; \
		echo "  • Backend API : $(YELLOW)http://localhost/api$(NC)"; \
		echo "  • EasyAdmin : $(YELLOW)http://localhost/admin$(NC)"; \
	fi

install-staging: ## Install project in staging mode (first-time setup on staging server)
	@echo "$(GREEN)▶ Installing project in staging mode...$(NC)"
	@[ -f .env.staging ] || (echo "$(RED)✗ .env.staging not found. Copy .env.staging.example and fill it in.$(NC)"; exit 1)
	@echo "$(YELLOW)  • Starting containers (staging)...$(NC)"
	$(DOCKER_COMPOSE_STAGING) up -d --build
	@echo "$(YELLOW)  • Waiting for database startup...$(NC)"
	sleep 10
	@echo "$(YELLOW)  • Running migrations...$(NC)"
	$(DOCKER_COMPOSE_STAGING) exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(YELLOW)  • Clearing cache and warmup...$(NC)"
	$(DOCKER_COMPOSE_STAGING) exec -T backend php bin/console cache:clear --env=prod
	@echo "$(YELLOW)  • Installing assets (CSS/JS)...$(NC)"
	$(DOCKER_COMPOSE_STAGING) exec -T backend php bin/console assets:install
	@echo "$(YELLOW)  • Loading applications...$(NC)"
	$(DOCKER_COMPOSE_STAGING) exec -T backend php bin/console app:load-applications
	@echo "$(YELLOW)  • Syncing AMS data...$(NC)"
	$(DOCKER_COMPOSE_STAGING) exec -T backend php bin/console app:sync-ams || \
		(echo "$(YELLOW)  ⚠ AMS sync failed — check AMS credentials in .env.staging$(NC)")
	@echo "$(GREEN)✓ Staging installation completed!$(NC)"
	@echo ""
	@echo "$(GREEN)  • App : $(YELLOW)https://staging-ihub.wingleetdev.com$(NC)"

install: install-dev ## Alias for install-dev

# ════════════════════════════════════════════════════════════════════════════════
# UPDATES
# ════════════════════════════════════════════════════════════════════════════════

update-dev: ## Update project in development mode
	@echo "$(GREEN)▶ Updating project in development mode...$(NC)"
	@echo "$(YELLOW)  • Updating PHP dependencies...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend composer update
	@echo "$(YELLOW)  • Running migrations...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(YELLOW)  • Loading fixtures (dev)...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend php bin/console doctrine:fixtures:load --group=dev --no-interaction
	@echo "$(YELLOW)  • Updating npm dependencies (frontend)...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T frontend npm update
	@echo "$(YELLOW)  • Clearing cache...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T backend php bin/console cache:clear
	@echo "$(GREEN)✓ Development update completed!$(NC)"

update-prod: ## Update project in production mode
	@echo "$(RED)⚠ WARNING: Update in PRODUCTION$(NC)"
	@echo ""
	@echo "$(YELLOW)  • Installing PHP dependencies (optimized)...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T backend composer update --no-dev --optimize-autoloader
	@echo "$(YELLOW)  • Running migrations...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(YELLOW)  • Warming up cache...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T backend php bin/console cache:clear --env=prod
	@echo "$(YELLOW)  • Updating npm dependencies...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T frontend npm ci
	$(DOCKER_COMPOSE_PROD) exec -T frontend npm run build
	@echo "$(GREEN)✓ Production update completed!$(NC)"

update: update-dev ## Alias for update-dev

# ════════════════════════════════════════════════════════════════════════════════
# CONTAINERS
# ════════════════════════════════════════════════════════════════════════════════

start: ## Start containers (dev)
	@echo "$(GREEN)▶ Starting development containers...$(NC)"
	$(DOCKER_COMPOSE_DEV) up -d
	@echo "$(GREEN)✓ Containers started!$(NC)"
	@sleep 3
	@$(DOCKER_COMPOSE_DEV) ps

start-prod: ## Start containers (prod)
	@echo "$(GREEN)▶ Starting production containers...$(NC)"
	$(DOCKER_COMPOSE_PROD) up -d
	@echo "$(GREEN)✓ Containers started!$(NC)"
	@sleep 3
	@$(DOCKER_COMPOSE_PROD) ps

stop: ## Stop containers (dev)
	@echo "$(GREEN)▶ Stopping containers...$(NC)"
	$(DOCKER_COMPOSE_DEV) down
	@echo "$(GREEN)✓ Containers stopped!$(NC)"

stop-prod: ## Stop containers (prod)
	@echo "$(GREEN)▶ Stopping production containers...$(NC)"
	$(DOCKER_COMPOSE_PROD) down
	@echo "$(GREEN)✓ Containers stopped!$(NC)"

restart: stop start ## Restart containers (dev)

restart-prod: stop-prod start-prod ## Restart containers (prod)

build-dev: ## Build images in development mode
	@echo "$(GREEN)▶ Building images (dev)...$(NC)"
	$(DOCKER_COMPOSE_DEV) build
	@echo "$(GREEN)✓ Build completed!$(NC)"

build-prod: ## Build images in production mode
	@echo "$(GREEN)▶ Building images (prod)...$(NC)"
	$(DOCKER_COMPOSE_PROD) build --no-cache
	@echo "$(GREEN)✓ Production build completed!$(NC)"

ps: ## Display container status (dev)
	@echo "$(BLUE)Container status:$(NC)"
	@$(DOCKER_COMPOSE_DEV) ps

ps-prod: ## Display container status (prod)
	@echo "$(BLUE)Production container status:$(NC)"
	@$(DOCKER_COMPOSE_PROD) ps

# ════════════════════════════════════════════════════════════════════════════════
# LOGS
# ════════════════════════════════════════════════════════════════════════════════

logs: ## Display logs for all services (dev) - Ctrl+C to quit
	@echo "$(BLUE)Development logs ($(YELLOW)Ctrl+C to quit$(BLUE))...$(NC)"
	$(DOCKER_COMPOSE_DEV) logs -f

logs-prod: ## Display logs for all services (prod) - Ctrl+C to quit
	@echo "$(BLUE)Production logs ($(YELLOW)Ctrl+C to quit$(BLUE))...$(NC)"
	$(DOCKER_COMPOSE_PROD) logs -f

logs-backend: ## Display backend logs (dev) - Ctrl+C to quit
	@echo "$(BLUE)Backend logs ($(YELLOW)Ctrl+C to quit$(BLUE))...$(NC)"
	$(DOCKER_COMPOSE_DEV) logs -f $(BACKEND_SERVICE)

logs-backend-prod: ## Display backend logs (prod) - Ctrl+C to quit
	@echo "$(BLUE)Production backend logs ($(YELLOW)Ctrl+C to quit$(BLUE))...$(NC)"
	$(DOCKER_COMPOSE_PROD) logs -f $(BACKEND_SERVICE)

logs-frontend: ## Display frontend logs (dev) - Ctrl+C to quit
	@echo "$(BLUE)Frontend logs ($(YELLOW)Ctrl+C to quit$(BLUE))...$(NC)"
	$(DOCKER_COMPOSE_DEV) logs -f $(FRONTEND_SERVICE)

logs-frontend-prod: ## Display frontend logs (prod) - Ctrl+C to quit
	@echo "$(BLUE)Production frontend logs ($(YELLOW)Ctrl+C to quit$(BLUE))...$(NC)"
	$(DOCKER_COMPOSE_PROD) logs -f $(FRONTEND_SERVICE)

logs-database: ## Display database logs (dev) - Ctrl+C to quit
	@echo "$(BLUE)Database logs ($(YELLOW)Ctrl+C to quit$(BLUE))...$(NC)"
	$(DOCKER_COMPOSE_DEV) logs -f $(DATABASE_SERVICE)

logs-database-prod: ## Display database logs (prod) - Ctrl+C to quit
	@echo "$(BLUE)Production database logs ($(YELLOW)Ctrl+C to quit$(BLUE))...$(NC)"
	$(DOCKER_COMPOSE_PROD) logs -f $(DATABASE_SERVICE)

# ════════════════════════════════════════════════════════════════════════════════
# SYNC
# ════════════════════════════════════════════════════════════════════════════════

sync-ams: ## Sync AMS data (dev)
	@echo "$(GREEN)▶ Syncing AMS data...$(NC)"
	@echo "$(YELLOW)  ℹ Configure AMS credentials in backend/.env.local if needed$(NC)"
	@$(DOCKER_COMPOSE_DEV) exec -T $(BACKEND_SERVICE) php bin/console app:sync-ams || \
		(echo "$(RED)✗ AMS sync failed. Check credentials in backend/.env.local:$(NC)"; \
		 echo "$(YELLOW)  AMS_API_URL=https://your-ams-instance.com$(NC)"; \
		 echo "$(YELLOW)  AMS_API_USERNAME=your_username$(NC)"; \
		 echo "$(YELLOW)  AMS_API_PASSWORD=your_password$(NC)"; \
		 exit 1)
	@echo "$(GREEN)✓ AMS sync completed!$(NC)"

sync-ams-prod: ## Sync AMS data (prod)
	@echo "$(GREEN)▶ Syncing AMS data (production)...$(NC)"
	@echo "$(YELLOW)  ℹ Configure AMS credentials in backend/.env.local if needed$(NC)"
	@$(DOCKER_COMPOSE_PROD) exec -T $(BACKEND_SERVICE) php bin/console app:sync-ams || \
		(echo "$(RED)✗ AMS sync failed. Check credentials in backend/.env.local:$(NC)"; \
		 echo "$(YELLOW)  AMS_API_URL=https://your-ams-instance.com$(NC)"; \
		 echo "$(YELLOW)  AMS_API_USERNAME=your_username$(NC)"; \
		 echo "$(YELLOW)  AMS_API_PASSWORD=your_password$(NC)"; \
		 exit 1)
	@echo "$(GREEN)✓ AMS sync completed!$(NC)"

# ════════════════════════════════════════════════════════════════════════════════
# QUICK SETUP
# ════════════════════════════════════════════════════════════════════════════════

quick-setup: ## Quick setup: default admin + apps + sync AMS (dev)
	@echo "$(GREEN)▶ Quick setup in progress...$(NC)"
	@echo ""
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)  STEP 1/3: Creating default admin user$(NC)"
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@echo "$(YELLOW)  • Email: admin@test.com$(NC)"
	@echo "$(YELLOW)  • Password: password123$(NC)"
	@$(DOCKER_COMPOSE_DEV) exec -T $(BACKEND_SERVICE) php bin/console app:create-user admin@test.com Admin Admin password123 --admin || \
		(echo "$(YELLOW)⚠ Admin user may already exist, continuing...$(NC)")
	@echo ""
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)  STEP 2/3: Loading applications (11 apps)$(NC)"
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@$(DOCKER_COMPOSE_DEV) exec -T $(BACKEND_SERVICE) php bin/console app:load-applications
	@echo ""
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)  STEP 3/3: Syncing AMS data$(NC)"
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@$(DOCKER_COMPOSE_DEV) exec -T $(BACKEND_SERVICE) php bin/console app:sync-ams || \
		(echo "$(YELLOW)⚠ AMS sync failed, you may need to configure credentials$(NC)")
	@echo ""
	@echo "$(GREEN)✓ Quick setup completed!$(NC)"
	@echo ""
	@echo "$(GREEN)Default admin credentials:$(NC)"
	@echo "  • Email: $(YELLOW)admin@test.com$(NC)"
	@echo "  • Password: $(YELLOW)password123$(NC)"

quick-setup-prod: ## Quick setup: default admin + apps + sync AMS (prod)
	@echo "$(GREEN)▶ Quick setup in progress (PRODUCTION)...$(NC)"
	@echo ""
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)  STEP 1/3: Creating default admin user$(NC)"
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@echo "$(YELLOW)  • Email: admin@test.com$(NC)"
	@echo "$(YELLOW)  • Password: password123$(NC)"
	@$(DOCKER_COMPOSE_PROD) exec -T $(BACKEND_SERVICE) php bin/console app:create-user admin@test.com Admin Admin password123 --admin || \
		(echo "$(YELLOW)⚠ Admin user may already exist, continuing...$(NC)")
	@echo ""
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)  STEP 2/3: Loading applications (11 apps)$(NC)"
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@$(DOCKER_COMPOSE_PROD) exec -T $(BACKEND_SERVICE) php bin/console app:load-applications
	@echo ""
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)  STEP 3/3: Syncing AMS data$(NC)"
	@echo "$(BLUE)═══════════════════════════════════════════════$(NC)"
	@$(DOCKER_COMPOSE_PROD) exec -T $(BACKEND_SERVICE) php bin/console app:sync-ams || \
		(echo "$(YELLOW)⚠ AMS sync failed, you may need to configure credentials$(NC)")
	@echo ""
	@echo "$(GREEN)✓ Quick setup completed!$(NC)"
	@echo ""
	@echo "$(GREEN)Default admin credentials:$(NC)"
	@echo "  • Email: $(YELLOW)admin@test.com$(NC)"
	@echo "  • Password: $(YELLOW)password123$(NC)"

# ════════════════════════════════════════════════════════════════════════════════
# DATA
# ════════════════════════════════════════════════════════════════════════════════

fixtures-dev: ## Load development fixtures (dev)
	@echo "$(GREEN)▶ Loading development fixtures...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T $(BACKEND_SERVICE) php bin/console doctrine:fixtures:load --group=dev --no-interaction
	@echo "$(GREEN)✓ Development fixtures loaded!$(NC)"

fixtures-prod: ## Load development fixtures (prod)
	@echo "$(GREEN)▶ Loading development fixtures (production)...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T $(BACKEND_SERVICE) php bin/console doctrine:fixtures:load --group=dev --no-interaction
	@echo "$(GREEN)✓ Development fixtures loaded!$(NC)"

# ════════════════════════════════════════════════════════════════════════════════
# USERS
# ════════════════════════════════════════════════════════════════════════════════

admin-user: ## Create an administrator user (dev)
	@echo "$(GREEN)▶ Creating an administrator user...$(NC)"
	@read -p "$(YELLOW)Email : $(NC)" email; \
	read -p "$(YELLOW)First name : $(NC)" firstname; \
	read -p "$(YELLOW)Last name : $(NC)" lastname; \
	printf "$(YELLOW)Password : $(NC)"; \
	stty -echo; \
	read password; \
	stty echo; \
	echo ""; \
	echo "$(YELLOW)  • Creating user...$(NC)"; \
	$(DOCKER_COMPOSE_DEV) exec -T $(BACKEND_SERVICE) php bin/console app:create-user $$email $$firstname $$lastname $$password --admin; \
	if [ $$? -eq 0 ]; then \
		echo "$(GREEN)✓ Admin user created successfully!$(NC)"; \
		echo "$(YELLOW)Email : $$email$(NC)"; \
	else \
		echo "$(RED)✗ Error while creating user$(NC)"; \
	fi

admin-user-prod: ## Create an administrator user (prod)
	@echo "$(GREEN)▶ Creating an administrator user...$(NC)"
	@read -p "$(YELLOW)Email : $(NC)" email; \
	read -p "$(YELLOW)First name : $(NC)" firstname; \
	read -p "$(YELLOW)Last name : $(NC)" lastname; \
	printf "$(YELLOW)Password : $(NC)"; \
	stty -echo; \
	read password; \
	stty echo; \
	echo ""; \
	echo "$(YELLOW)  • Creating user...$(NC)"; \
	$(DOCKER_COMPOSE_PROD) exec -T $(BACKEND_SERVICE) php bin/console app:create-user $$email $$firstname $$lastname $$password --admin; \
	if [ $$? -eq 0 ]; then \
		echo "$(GREEN)✓ Admin user created successfully!$(NC)"; \
		echo "$(YELLOW)Email : $$email$(NC)"; \
	else \
		echo "$(RED)✗ Error while creating user$(NC)"; \
	fi

# ════════════════════════════════════════════════════════════════════════════════
# ASSETS
# ════════════════════════════════════════════════════════════════════════════════

assets-install: ## Install Symfony assets (CSS/JS) (dev)
	@echo "$(GREEN)▶ Installing assets...$(NC)"
	$(DOCKER_COMPOSE_DEV) exec -T $(BACKEND_SERVICE) php bin/console assets:install --symlink --relative
	@echo "$(GREEN)✓ Assets installed!$(NC)"

assets-install-prod: ## Install Symfony assets (CSS/JS) (prod)
	@echo "$(GREEN)▶ Installing assets (production)...$(NC)"
	$(DOCKER_COMPOSE_PROD) exec -T $(BACKEND_SERVICE) php bin/console assets:install
	@echo "$(GREEN)✓ Assets installed!$(NC)"

# ════════════════════════════════════════════════════════════════════════════════
# CLI
# ════════════════════════════════════════════════════════════════════════════════

cli-backend: ## Open a shell in the backend container (dev)
	@echo "$(BLUE)Connecting to backend container...$(NC)"
	@echo "$(YELLOW)Type 'exit' to quit$(NC)"
	@$(DOCKER_COMPOSE_DEV) exec $(BACKEND_SERVICE) sh

cli-backend-prod: ## Open a shell in the backend container (prod)
	@echo "$(BLUE)Connecting to production backend container...$(NC)"
	@echo "$(YELLOW)Type 'exit' to quit$(NC)"
	@$(DOCKER_COMPOSE_PROD) exec $(BACKEND_SERVICE) sh

# ════════════════════════════════════════════════════════════════════════════════
# MAINTENANCE
# ════════════════════════════════════════════════════════════════════════════════

clean: ## Stop and clean containers (dev)
	@echo "$(RED)▶ Cleanup in progress...$(NC)"
	@echo "$(YELLOW)  • Stopping containers...$(NC)"
	$(DOCKER_COMPOSE_DEV) down --remove-orphans
	@echo "$(RED)⚠ To also remove volumes (data): docker compose down -v$(NC)"
	@echo "$(GREEN)✓ Cleanup completed!$(NC)"

clean-prod: ## Stop and clean containers (prod)
	@echo "$(RED)▶ Production cleanup in progress...$(NC)"
	@echo "$(YELLOW)  • Stopping containers...$(NC)"
	$(DOCKER_COMPOSE_PROD) down --remove-orphans
	@echo "$(RED)⚠ To also remove volumes (data): docker compose -f docker-compose.yml -f docker-compose.prod.yml down -v$(NC)"
	@echo "$(GREEN)✓ Cleanup completed!$(NC)"

clean-all: ## ⚠️  Delete containers AND data (dev)
	@echo "$(RED)⚠️  WARNING: This action will delete all containers AND data!$(NC)"
	@read -p "$(RED)Are you sure ? (yes/no) : $(NC)" confirm; \
	if [ "$$confirm" = "yes" ]; then \
		echo "$(RED)▶ Complete deletion in progress...$(NC)"; \
		$(DOCKER_COMPOSE_DEV) down -v --remove-orphans; \
		echo "$(RED)✓ All containers and data have been deleted!$(NC)"; \
	else \
		echo "$(YELLOW)Operation cancelled$(NC)"; \
	fi

clean-all-prod: ## ⚠️  Delete production containers AND data
	@echo "$(RED)⚠️  WARNING: This action will delete all production containers AND data!$(NC)"
	@read -p "$(RED)Are you sure ? (yes/no) : $(NC)" confirm; \
	if [ "$$confirm" = "yes" ]; then \
		echo "$(RED)▶ Complete deletion in progress...$(NC)"; \
		$(DOCKER_COMPOSE_PROD) down -v --remove-orphans; \
		echo "$(RED)✓ All containers and data have been deleted!$(NC)"; \
	else \
		echo "$(YELLOW)Operation cancelled$(NC)"; \
	fi

# ════════════════════════════════════════════════════════════════════════════════
# DEFAULT
# ════════════════════════════════════════════════════════════════════════════════

.DEFAULT_GOAL := help
# ════════════════════════════════════════════════════════════════════════════════
# STAGING
# ════════════════════════════════════════════════════════════════════════════════

DOCKER_COMPOSE_STAGING := docker compose -f docker-compose.yml -f docker-compose.staging.yml --env-file .env.staging

.PHONY: deploy-staging staging-rebuild staging-restart staging-down staging-logs staging-status staging-shell staging-migrate staging-quick-setup

deploy-staging: ## git pull + build images + start in staging mode
	@echo "$(GREEN)▶ Deploying staging (ams-hub)...$(NC)"
	@[ -f .env.staging ] || (echo "$(RED)✗ .env.staging not found. Copy .env.staging.example and fill it in.$(NC)"; exit 1)
	@echo "$(YELLOW)  • Pulling latest code...$(NC)"
	git pull
	@echo "$(YELLOW)  • Building and starting containers...$(NC)"
	$(DOCKER_COMPOSE_STAGING) up -d --build
	@echo "$(YELLOW)  • Waiting for database...$(NC)"
	sleep 10
	@echo "$(YELLOW)  • Running migrations...$(NC)"
	$(DOCKER_COMPOSE_STAGING) exec -T $(BACKEND_SERVICE) php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(YELLOW)  • Clearing cache...$(NC)"
	$(DOCKER_COMPOSE_STAGING) exec -T $(BACKEND_SERVICE) php bin/console cache:clear --env=prod
	@echo "$(YELLOW)  • Installing assets...$(NC)"
	$(DOCKER_COMPOSE_STAGING) exec -T $(BACKEND_SERVICE) php bin/console assets:install
	@echo "$(GREEN)✓ Staging deployment complete! → https://staging-ihub.wingleetdev.com$(NC)"

staging-rebuild: ## Rebuild staging images + restart
	@echo "$(GREEN)▶ Rebuilding staging images...$(NC)"
	@[ -f .env.staging ] || (echo "$(RED)✗ .env.staging not found.$(NC)"; exit 1)
	$(DOCKER_COMPOSE_STAGING) up -d --build --force-recreate
	@echo "$(GREEN)✓ Staging containers rebuilt and restarted!$(NC)"

staging-restart: ## Restart staging containers without rebuild
	@echo "$(GREEN)▶ Restarting staging containers...$(NC)"
	$(DOCKER_COMPOSE_STAGING) restart
	@echo "$(GREEN)✓ Staging containers restarted!$(NC)"

staging-down: ## Stop staging containers
	@echo "$(YELLOW)▶ Stopping staging containers...$(NC)"
	$(DOCKER_COMPOSE_STAGING) down
	@echo "$(GREEN)✓ Staging containers stopped!$(NC)"

staging-logs: ## View staging logs (Ctrl+C to quit)
	@echo "$(BLUE)Staging logs ($(YELLOW)Ctrl+C to quit$(BLUE))...$(NC)"
	$(DOCKER_COMPOSE_STAGING) logs -f

staging-status: ## Display staging container status
	@echo "$(BLUE)Staging container status:$(NC)"
	@$(DOCKER_COMPOSE_STAGING) ps

staging-shell: ## Open shell in staging backend container
	@echo "$(BLUE)Connecting to staging backend...$(NC)"
	@$(DOCKER_COMPOSE_STAGING) exec $(BACKEND_SERVICE) sh

staging-migrate: ## Run DB migrations in staging
	@echo "$(GREEN)▶ Running migrations (staging)...$(NC)"
	$(DOCKER_COMPOSE_STAGING) exec -T $(BACKEND_SERVICE) php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)✓ Migrations applied!$(NC)"

staging-quick-setup: ## Create admin + load apps + sync AMS (staging)
	@echo "$(GREEN)▶ Quick setup (staging)...$(NC)"
	@$(DOCKER_COMPOSE_STAGING) exec -T $(BACKEND_SERVICE) php bin/console app:create-user admin@test.com Admin Admin password123 --admin || \
		(echo "$(YELLOW)⚠ Admin user may already exist, continuing...$(NC)")
	@$(DOCKER_COMPOSE_STAGING) exec -T $(BACKEND_SERVICE) php bin/console app:load-applications
	@$(DOCKER_COMPOSE_STAGING) exec -T $(BACKEND_SERVICE) php bin/console app:sync-ams || \
		(echo "$(YELLOW)⚠ AMS sync failed — check credentials in .env.staging$(NC)")
	@echo "$(GREEN)✓ Staging quick setup complete!$(NC)"
