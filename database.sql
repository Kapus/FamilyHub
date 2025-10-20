-- Familyhub databasstruktur för MySQL (schema: test)
-- Körs via phpMyAdmin eller motsvarande verktyg innan applikationen används

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    namn VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    losenord VARCHAR(255) NOT NULL,
    roll ENUM('USER', 'ADMIN') NOT NULL DEFAULT 'USER',
    skapad_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(150) NOT NULL,
    datum DATE NOT NULL,
    slut_datum DATE NULL,
    anvandar_id INT NULL,
    farg VARCHAR(20) DEFAULT '#0d6efd',
    FOREIGN KEY (anvandar_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(150) NOT NULL,
    deadline DATE NULL,
    status VARCHAR(50) DEFAULT 'Pågående',
    tilldelad_till INT NULL,
    FOREIGN KEY (tilldelad_till) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dag VARCHAR(20) NOT NULL,
    ratt VARCHAR(150) NOT NULL,
    recept_url VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    avsandare INT NOT NULL,
    mottagare INT NULL,
    meddelande TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (avsandare) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mottagare) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filnamn VARCHAR(255) NOT NULL,
    album VARCHAR(100) NULL,
    kommentar TEXT NULL,
    uppladdad_av INT NULL,
    FOREIGN KEY (uppladdad_av) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Exempel på hur man kan lägga till en första admin-användare:
-- Ersätt HASHEN med resultatet från PHP-funktionen password_hash('DittLosenord', PASSWORD_DEFAULT)
-- INSERT INTO users (namn, email, losenord, roll) VALUES ('Admin', 'admin@example.com', 'HASHEN', 'ADMIN');
