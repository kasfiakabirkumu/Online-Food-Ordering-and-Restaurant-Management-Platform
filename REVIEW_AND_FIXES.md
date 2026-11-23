# FoodHub review and fixes

## Files reviewed
The PHP MVC application, database schema/seed files, setup and diagnostic pages, and browser JavaScript were reviewed.

## Fixes applied
1. **AJAX menu fallback CSRF failure** — menu cards created by live search did not include the hidden `csrf_token`. If the AJAX add-to-cart request failed and JavaScript submitted the form normally, the server rejected it with HTTP 419. Dynamically generated forms now include the same CSRF token used by the API request.
2. **Stale cart entries** — if a menu item was deleted after being added to a session cart, its ID could remain in the cart and the cart badge could show an item that could not be checked out. `Cart::lines()` now removes missing/unavailable items and clamps stored quantities to the supported range.

## Validation performed
- All PHP files passed `php -l` syntax checks.
- `public/assets/js/app.js` passed `node --check`.

## Important limitations / environment checks
- A live MySQL-backed end-to-end test was not possible in this review environment. Run the project with MySQL and PDO MySQL enabled, then use `diagnostic.php` and `setup.php`.
- Card and bKash payments are **simulations only**. The application does not contact a bank/payment gateway or actually charge/verify a payment.
- The shared ChatGPT conversation URL supplied with the project did not expose its conversation text to this review, so this review is based on the uploaded ZIP rather than the contents of that shared chat.
