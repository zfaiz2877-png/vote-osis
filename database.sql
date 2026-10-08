-- PERINGATAN: Skrip ini menghapus seluruh tabel dan data voting yang lama.
-- Jalankan hanya setelah membuat backup database.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS tokens;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS admins;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE candidates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    no_urut TINYINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    photo_url VARCHAR(255) NOT NULL DEFAULT 'default.jpg',
    visi TEXT NOT NULL,
    misi TEXT NOT NULL,
    proker TEXT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_candidates_no_urut (no_urut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    token_code VARCHAR(10) NOT NULL,
    status ENUM('tersedia', 'terpakai') NOT NULL DEFAULT 'tersedia',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tokens_token_code (token_code),
    KEY idx_tokens_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE votes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    candidate_id INT UNSIGNED NOT NULL,
    token_used VARCHAR(10) NOT NULL,
    voted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_votes_token_used (token_used),
    KEY idx_votes_candidate_id (candidate_id),
    CONSTRAINT fk_votes_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidates (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_votes_token
        FOREIGN KEY (token_used) REFERENCES tokens (token_code)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admins (username, password_hash)
VALUES ('admin', '$2y$10$5d3eyG/gOicycRfVN1ZHAedCx.K5xPRyhJuuhJkdP6EMrtqYcBt.6');
