# language: pl

Właściwość: Panel administracyjny
  W celu zarządzania systemem i użytkownikami
  Jako administrator
  Chcę mieć dostęp do panelu administracyjnego

  Założenia:
    Dodany użytkownik "admin@example.com" z hasłem "admin123" z rolą "ROLE_ADMIN"
    Dodany użytkownik "user@example.com" z hasłem "user123" z rolą "ROLE_USER"
    Ustawienia systemu "default_credits" wynosi "500"
    Ustawienia systemu "ai_generation_cost" wynosi "100"

  Scenariusz: Dostęp do panelu admina wymaga uprawnień administratora
    Zakładając jestem zalogowany jako "user@example.com" z hasłem "user123"
    Kiedy wchodzę na "/admin/"
    Wtedy powinienem zobaczyć błąd "403"

  Scenariusz: Administrator może zobaczyć dashboard
    Zakładając jestem zalogowany jako "admin@example.com" z hasłem "admin123"
    Kiedy wchodzę na "/admin/"
    Wtedy powinienem zobaczyć "Panel Administracyjny"
    I powinienem zobaczyć "Użytkowników"
    I powinienem zobaczyć "Kredytów w systemie"
    I powinienem zobaczyć "Tokenów użytych"

  Scenariusz: Administrator może zarządzać użytkownikami
    Zakładając jestem zalogowany jako "admin@example.com" z hasłem "admin123"
    Kiedy wchodzę na "/admin/users/"
    Wtedy powinienem zobaczyć "Zarządzanie Użytkownikami"
    I powinienem zobaczyć "user@example.com"
    I powinienem zobaczyć "admin@example.com"

  Scenariusz: Administrator może dodać kredyty użytkownikowi
    Zakładając jestem zalogowany jako "admin@example.com" z hasłem "admin123"
    I użytkownik "user@example.com" ma "100" kredytów
    Kiedy wchodzę na "/admin/users/"
    I klikam "Szczegóły" przy użytkowniku "user@example.com"
    I klikam "Dodaj kredyty"
    I wypełniam "amount" wartością "500"
    I wypełniam "description" wartością "Bonus testowy"
    I klikam przycisk "Dodaj kredyty"
    Wtedy powinienem zobaczyć "Dodano 500 kredytów"
    I użytkownik "user@example.com" powinien mieć "600" kredytów

  Scenariusz: Administrator może nadać uprawnienia admina innemu użytkownikowi
    Zakładając jestem zalogowany jako "admin@example.com" z hasłem "admin123"
    Kiedy wchodzę na szczegóły użytkownika "user@example.com"
    I klikam "Nadaj admina"
    Wtedy powinienem zobaczyć "Nadano uprawnienia administratora"
    I użytkownik "user@example.com" powinien mieć rolę "ROLE_ADMIN"

  Scenariusz: Administrator nie może usunąć sobie uprawnień admina
    Zakładając jestem zalogowany jako "admin@example.com" z hasłem "admin123"
    Kiedy wchodzę na szczegóły użytkownika "admin@example.com"
    I klikam "Usuń admina"
    Wtedy powinienem zobaczyć "Nie możesz zmienić własnych uprawnień"
    I użytkownik "admin@example.com" powinien nadal mieć rolę "ROLE_ADMIN"

  Scenariusz: Administrator może zmienić ustawienia systemu
    Zakładając jestem zalogowany jako "admin@example.com" z hasłem "admin123"
    Kiedy wchodzę na "/admin/settings/"
    I wypełniam "default_credits" wartością "1000"
    I wypełniam "ai_generation_cost" wartością "50"
    I klikam przycisk "Zapisz ustawienia"
    Wtedy powinienem zobaczyć "Ustawienia zostały zaktualizowane"
    I ustawienie "default_credits" powinno wynosić "1000"
    I ustawienie "ai_generation_cost" powinno wynosić "50"

  Scenariusz: Dashboard pokazuje użytkowników z niskim stanem kredytów
    Zakładając jestem zalogowany jako "admin@example.com" z hasłem "admin123"
    I dodany użytkownik "lowcredit@example.com" z "50" kredytami
    Kiedy wchodzę na "/admin/"
    Wtedy powinienem zobaczyć "Użytkownicy z niskim stanem kredytów"
    I powinienem zobaczyć "lowcredit@example.com"
    I powinienem zobaczyć "50 kredytów"

  Scenariusz: Historia transakcji jest widoczna w panelu admina
    Zakładając jestem zalogowany jako "admin@example.com" z hasłem "admin123"
    I użytkownik "user@example.com" wydał "100" kredytów na "Generacja AI"
    Kiedy wchodzę na "/admin/"
    Wtedy powinienem zobaczyć "Ostatnie transakcje kredytów"
    I powinienem zobaczyć "user@example.com"
    I powinienem zobaczyć "-100"
    I powinienem zobaczyć "AI"

  Scenariusz: Administrator widzi link do panelu w nawigacji
    Zakładając jestem zalogowany jako "admin@example.com" z hasłem "admin123"
    Kiedy wchodzę na "/dashboard"
    Wtedy powinienem zobaczyć link "Panel Admin" w nawigacji

  Scenariusz: Zwykły użytkownik nie widzi linku do panelu admina
    Zakładając jestem zalogowany jako "user@example.com" z hasłem "user123"
    Kiedy wchodzę na "/dashboard"
    Wtedy nie powinienem zobaczyć "Panel Admin" w nawigacji