CREATE TABLE IF NOT EXISTS public_interactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador da interacao publica enviada pelo visitante',
    visitor_name VARCHAR(120) NOT NULL COMMENT 'Nome informado no formulario publico',
    visitor_email VARCHAR(160) NOT NULL COMMENT 'E-mail informado no formulario publico',
    message TEXT NOT NULL COMMENT 'Mensagem enviada pelo visitante',
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'Status editorial da interacao',
    source_page VARCHAR(255) NULL COMMENT 'URL da pagina publica de origem',
    submitted_ip VARCHAR(45) NULL COMMENT 'IP de origem do envio',
    user_agent VARCHAR(255) NULL COMMENT 'User agent capturado no momento do envio',
    reviewed_by_user_id BIGINT UNSIGNED NULL COMMENT 'Usuario SaaS que revisou a interacao',
    reviewed_at DATETIME NULL DEFAULT NULL COMMENT 'Data/hora da ultima revisao editorial',
    published_at DATETIME NULL DEFAULT NULL COMMENT 'Data/hora de publicacao na landing',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data/hora do envio',
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data/hora da ultima atualizacao',
    CONSTRAINT fk_public_interactions_reviewed_by_user
        FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_public_interactions_status CHECK (
        status IN ('pending', 'published', 'rejected')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Interacoes publicas moderadas da pagina institucional';

CREATE INDEX idx_public_interactions_status ON public_interactions(status);
CREATE INDEX idx_public_interactions_published_at ON public_interactions(published_at);
CREATE INDEX idx_public_interactions_created_at ON public_interactions(created_at);
