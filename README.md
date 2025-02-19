# Agaciro Saving Group Management System

A web-based management system for tracking savings group transactions, built with PHP and MySQL.

## Features

- Member management
- Transaction tracking (deposits and withdrawals)
- Automatic fee calculation
- Transaction history
- Balance reporting
- Individual member reports

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/Agaciro-alpha.git
```

2. Import the database schema:
- Create a new MySQL database
- Import the database structure from `config/database.php`

3. Configure the database connection:
- Update the database credentials in `config/database.php`

4. Set up your web server:
- Point your web server to the project directory
- Ensure PHP has write permissions to the project directory

## Usage

1. Access the application through your web browser
2. Add members through the "Abanyamuryango" section
3. Record deposits using "Bitsa"
4. Record withdrawals using "Bikuza"
5. View reports in "Raporo"

## Security

- All database queries use prepared statements to prevent SQL injection
- Input validation and sanitization implemented
- Secure password hashing for user authentication

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 