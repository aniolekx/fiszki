# Progress: Fiszki (2025-05-05 16:56)

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

## 2. What's Left to Build (Core Features from Project Brief)

*   **Registration:** No registration form or controller action exists yet.
*   **Deck CRUD:** Functionality to create, read, update, delete decks.
*   **Flashcard CRUD:** Functionality to create, read, update, delete flashcards within decks.
*   **Study Mode:** Interface for reviewing flashcards.
*   **UI Navigation:** Consistent navigation/header across pages (partially addressed by the current task).
*   **User Feedback:** Flash messages for actions (e.g., successful login/logout, deck creation).

## 3. Current Status

*   Basic user authentication infrastructure is in place.
*   Login/Logout links are now displayed conditionally in the header navbar (`base.html.twig`) based on authentication status.

## 4. Known Issues / TODOs

*   Logged-in users can still access the `/login` page (optional fix planned).
*   No homepage or dashboard exists to redirect users to after login (defaults to `/`). A simple brand link to `/` was added in the navbar.
*   Frontend assets (`app.js`, `app.css`) are basic and might need building (`npm run watch/build`).
*   Registration functionality is missing.
*   Core flashcard/deck CRUD functionality is missing.
*   Study mode is missing.
