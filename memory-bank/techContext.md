# Technical Context: Fiszki

## 1. Core Technologies

*   **PHP:** 8.3.4 (as per `.cursor/rules/tech-stack.mdc`)
*   **Symfony:** 6.4.21 (LTS) (as per `.cursor/rules/tech-stack.mdc`)
*   **MySQL:** 8.0.39 (as per `.cursor/rules/tech-stack.mdc`)
*   **Nginx:** (Version specified in `docker/nginx/conf.d/default.conf` if needed)
*   **Docker / Docker Compose:** Used for environment orchestration (`docker-compose.yml`).

## 2. Key Libraries & Frameworks

*   **Symfony Components:** Security, FrameworkBundle, TwigBundle, DoctrineBundle, WebpackEncoreBundle, etc. (See `composer.json`)
*   **Doctrine:** ORM, Migrations
*   **Twig:** Templating Engine
*   **Bootstrap:** CSS Framework (v5.0.2 via CDN in `base.html.twig`)
*   **Bootstrap Icons:** (v1.11.3 via CDN in `login.html.twig`)
*   **Webpack Encore:** Frontend asset management (`webpack.config.js`, `package.json`)

## 3. Development Setup

*   Managed via Docker Compose (`docker-compose up -d`).
*   Requires Docker and Docker Compose installed locally.
*   Symfony CLI might be used for commands (`bin/console`).
*   Frontend assets built using `npm run watch` or `npm run build` (defined in `package.json`).

## 4. Technical Constraints

*   Must adhere to the versions specified in `.cursor/rules/tech-stack.mdc`.
*   Database interactions should primarily use Doctrine ORM.
*   Frontend styling should leverage Bootstrap.

## 5. Dependencies

*   **PHP Dependencies:** Managed by Composer (`composer.json`).
*   **Frontend Dependencies:** Managed by npm/yarn (`package.json`).
*   **System Dependencies:** Docker, Docker Compose.

*(This context is based on `.cursor/rules/tech-stack.mdc`, `composer.json`, `package.json`, `docker-compose.yml`, and observed template includes.)*
