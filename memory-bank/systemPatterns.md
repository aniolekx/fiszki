# System Patterns: Fiszki

## 1. Architecture

*   **Monolithic Web Application:** Standard Symfony application structure.
*   **MVC (Model-View-Controller):** Follows Symfony's implementation of MVC.
    *   **Models:** Doctrine Entities (`src/Domain/User/Entity/User`, potentially others for Decks/Cards later). Repositories (`src/Repository/DoctrineUserRepository`).
    *   **Views:** Twig Templates (`templates/`).
    *   **Controllers:** Symfony Controllers (`src/Controller/`).

## 2. Key Technical Decisions

*   **Symfony Framework:** Chosen as the core application framework.
*   **Doctrine ORM:** Used for database interaction and entity management.
*   **Twig:** Templating engine for rendering HTML.
*   **Symfony Security Component:** Handles authentication and authorization.
    *   Custom Authenticator (`App\Security\AppAuthenticator`) used for login logic.
    *   Password hashing handled by the component.
*   **Webpack Encore:** Manages frontend assets (CSS, JS).
*   **Docker:** Used for local development environment setup (PHP, Nginx, MySQL).

## 3. Design Patterns

*   **Repository Pattern:** Used for abstracting data persistence logic (e.g., `DoctrineUserRepository`).
*   **Dependency Injection:** Heavily utilized via Symfony's service container.
*   **Front Controller Pattern:** Implemented by `public/index.php` and the Symfony Kernel.

## 4. Component Relationships (Initial)

*   `SecurityController` handles login/logout routes.
*   `AppAuthenticator` implements the core authentication logic.
*   `LoginType` defines the login form structure.
*   `User` entity represents the user data model.
*   `UserRepository` fetches user data for authentication.
*   Twig templates render the UI, including the login form.

*(This document reflects the patterns observed in the existing codebase, primarily related to authentication.)*
