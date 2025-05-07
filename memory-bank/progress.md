# Progress: Fiszki (2025-05-07 09:08)

## 1. What Works

*   **User Entity:** `src/Entity/User.php` exists.
*   **User Repository:** `src/Repository/DoctrineUserRepository.php` exists.
*   **Database Schema:** Initial migration `migrations/Version20250505103002.php` likely created the `user` table.
*   **User Creation Command:** `src/Command/CreateUserCommand.php` allows creating users via CLI.
*   **Basic Login Form:** `templates/security/login.html.twig` renders a login form.
*   **Custom Authenticator:** `src/Security/AppAuthenticator.php` handles the login process.
*   **Security Configuration:** `config/packages/security.yaml` defines firewall, user provider, password hasher, and logout path.
*   **Logout Route:** Defined in `src/Controller/SecurityController.php` (`app_logout`).
*   **Docker Environment:** `docker-compose.yml` sets up PHP, Nginx, and MySQL services.
*   **Base Template Navigation:** `templates/base.html.twig` includes a navbar with conditional Login/Logout links.
*   **User Registration (Basic):** New users can submit the registration form via the `/register` route.
    *   Uses `RegistrationType` form.
    *   Handles form submission and validation.
    *   Dispatches a Messenger command (`RegisterUserCommand`) to handle registration logic.
    *   *Note: The post-registration flow (auto-login) is being replaced by email confirmation.*

## 2. What's Left to Build (Core Features from Project Brief)

*   **User Registration Email Confirmation:** Implement email confirmation flow after registration, including token generation, email sending, and a confirmation action.
*   **Deck CRUD:** Functionality to create, read, update, delete decks.
*   **Flashcard CRUD:** Functionality to create, read, update, delete flashcards within decks.
*   **Study Mode:** Interface for reviewing flashcards.
*   **UI Navigation:** Consistent navigation/header across pages (partially addressed by the current task).
*   **User Feedback:** Flash messages for actions (e.g., successful login/logout, deck creation).

## 3. Current Status

*   Basic user authentication infrastructure is in place, including login and the initial registration form submission handling.
*   The focus has shifted to implementing the email confirmation flow for user registration.
*   Login/Logout links are now displayed conditionally in the header navbar (`base.html.twig`).

## 4. Known Issues / TODOs

*   Logged-in users can still access the `/login` and `/register` pages (optional fix planned).
*   No homepage or dashboard exists to redirect users to after login (defaults to `/`). A simple brand link to `/` was added in the navbar.
*   Frontend assets (`app.js`, `app.css`) are basic and might need building (`npm run watch/build`).
*   Core flashcard/deck CRUD functionality is missing.
*   Study mode is missing.
*   Unconfirmed users can currently log in (will be addressed as part of the email confirmation implementation).
