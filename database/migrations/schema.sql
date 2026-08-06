CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prompts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    prompt MEDIUMTEXT NULL,
    negative_prompt MEDIUMTEXT NULL,
    thumbnail_prompt TEXT NULL,
    thumbnail_path VARCHAR(1024) NULL,
    reference_image_path VARCHAR(1024) NULL,
    generation_mode VARCHAR(32) NOT NULL DEFAULT 'imported',
    source_idea TEXT NULL,
    source_site VARCHAR(255) NULL,
    source_slug VARCHAR(255) NULL,
    source_url VARCHAR(2048) NULL,
    source_thumbnail_url VARCHAR(2048) NULL,
    source_published_at DATETIME NULL,
    source_modified_at DATETIME NULL,
    category VARCHAR(32) NOT NULL DEFAULT 'other',
    style_notes JSON NULL,
    ai_provider VARCHAR(100) NULL,
    ai_model VARCHAR(150) NULL,
    tested_models VARCHAR(500) NULL,
    reviewed_at DATETIME NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    copy_count INT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    generated_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY prompts_source_slug_unique (source_slug),
    KEY prompts_public_index (status, category, generated_at),
    KEY prompts_copy_index (copy_count),
    KEY prompts_mode_index (generation_mode),
    KEY prompts_source_index (source_site),
    FULLTEXT KEY prompts_search_fulltext (title, prompt),
    CONSTRAINT prompts_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prompt_generation_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prompt_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    payload JSON NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY jobs_status_available_index (status, available_at),
    KEY jobs_prompt_index (prompt_id),
    CONSTRAINT jobs_prompt_id_fk FOREIGN KEY (prompt_id) REFERENCES prompts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(190) NOT NULL UNIQUE,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY rate_limits_expires_at_index (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
