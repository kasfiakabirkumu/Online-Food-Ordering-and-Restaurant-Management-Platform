# FoodHub – Online Food Ordering and Restaurant Management Platform

PHP 8 (MVC), MySQL (PDO), HTML, CSS and JavaScript. Payment options in this assignment are simulations only.

## Windows: easiest way to run

1. Extract this ZIP completely.
2. Open the extracted `foodhub` folder in VS Code.
3. Open XAMPP Control Panel and click **Start** beside **MySQL**. Apache is not required for this project's built-in PHP server.
4. Double-click `start.bat` (or run `.\start.bat` from the `foodhub` folder in the VS Code terminal).
5. The browser opens the local diagnostic page. Check that **PDO MySQL** and **Database connection** both say Available/Connected.
6. If connected, click `setup.php` on that page (or visit http://localhost:8000/setup.php). It creates the database, tables, sample menu and demo accounts.
7. Open http://localhost:8000/ to use FoodHub.

Keep the terminal window open while using the site. Press **Ctrl+C** in that terminal to stop the server.

## PHP configuration

The launcher prefers `C:\\php\\php.exe` if present, then `C:\\xampp\\php\\php.exe`, then PHP on PATH. It checks the PDO MySQL driver before starting the site. If it reports the driver is missing, run the selected PHP's `--ini` command and enable `extension=pdo_mysql` in that exact `php.ini` file.

A warning saying `mysqli is already loaded` means mysqli is configured more than once. It is not the PDO MySQL driver. Remove/disable the duplicate `mysqli` line in the selected `php.ini`; keep only one active `extension=mysqli` line. Do not add PHP extension lines to PowerShell.

## Database defaults

`config/config.php` uses host `127.0.0.1`, port `3306`, database `foodhub`, user `root`, and an empty password (fresh XAMPP default). If your MySQL root account has a password, edit that file.

## Demo accounts

- Admin: `admin@foodhub.test` / `Admin@123`
- Customer: `customer@foodhub.test` / `Customer@123`

Reset sample data: http://localhost:8000/setup.php?reset=1

## Troubleshooting

- If the browser shows a port error, close any older FoodHub PHP server terminal with Ctrl+C, then run `start.bat` again.
- If the diagnostic page says database connection failed, make sure MySQL is running in XAMPP and check the database username/password in `config/config.php`.
- The diagnostic page is local-only and does not display the database password.
- Assignment material: `docs/ASSIGNMENT_NOTES.md`.
