-- ApexOps PostgreSQL schema
-- Run this once against your RDS instance after it's available

CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS incidents (
    id          SERIAL PRIMARY KEY,
    service     VARCHAR(100) NOT NULL,
    severity    VARCHAR(20) CHECK (severity IN ('low','medium','high','critical')) DEFAULT 'medium',
    description TEXT NOT NULL,
    status      VARCHAR(20) CHECK (status IN ('open','resolved')) DEFAULT 'open',
    created_at  TIMESTAMP DEFAULT NOW(),
    resolved_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS estimates (
    id           SERIAL PRIMARY KEY,
    label        VARCHAR(150) DEFAULT 'My Estimate',
    ec2_type     VARCHAR(50),
    rds_tier     VARCHAR(50),
    s3_gb        DECIMAL(10,2) DEFAULT 0,
    monthly_cost DECIMAL(10,2) DEFAULT 0,
    created_at   TIMESTAMP DEFAULT NOW()
);

-- Demo admin user (password: Admin@123)
INSERT INTO users (name, email, password_hash)
VALUES (
    'Admin User',
    'admin@apexops.io',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
) ON CONFLICT (email) DO NOTHING;

-- Seed incidents
INSERT INTO incidents (service, severity, description, status) VALUES
    ('EC2',         'high',     'Instance unreachable in af-south-1 for 10 minutes',              'open'),
    ('RDS',         'critical', 'Database failover triggered — primary unavailable',               'resolved'),
    ('API Gateway', 'medium',   'Elevated 5xx error rate on /api/v2/orders endpoint',             'open'),
    ('S3',          'low',      'Bucket policy misconfiguration flagged by Security Hub',          'resolved'),
    ('Lambda',      'high',     'Function timeout errors spiking — memory limit may be too low',  'open');
