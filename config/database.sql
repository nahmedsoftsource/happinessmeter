-- =============================================
-- Josh Warner Portfolio - Database Schema
-- =============================================

-- Create database
CREATE DATABASE IF NOT EXISTS portfolio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portfolio_db;

-- =============================================
-- Admin Users Table
-- =============================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123 - change this!)
INSERT INTO admin_users (username, email, password, full_name) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- =============================================
-- Site Settings Table
-- =============================================
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default settings
INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES
('site_name', 'Josh Warner', 'text'),
('site_title', 'Product Designer & Artist', 'text'),
('hero_headline', 'Product', 'text'),
('hero_subline', 'Art by night.', 'text'),
('hero_description', 'Product designer and artist working independently from Redding, CA.', 'textarea'),
('available_for_hire', '1', 'boolean'),
('contact_email', 'hello@narrators.co', 'text'),
('location', 'Redding, California', 'text'),
('instagram_url', 'https://instagram.com/joshwarner.art', 'text'),
('twitter_url', 'https://twitter.com/iamjoshwarner', 'text'),
('dribbble_url', 'https://dribbble.com/joshwarner', 'text'),
('linkedin_url', 'https://linkedin.com', 'text');

-- =============================================
-- Projects Table
-- =============================================
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50) DEFAULT 'Product Design',
    tags JSON,
    featured_image VARCHAR(255),
    pdf_path VARCHAR(255),
    status ENUM('published', 'draft', 'coming_soon') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Project Images Table (for carousel)
-- =============================================
CREATE TABLE IF NOT EXISTS project_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Art Gallery Table
-- =============================================
CREATE TABLE IF NOT EXISTS art_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    category ENUM('3d', 'typography', 'abstract', 'experimental') DEFAULT '3d',
    image_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    is_large BOOLEAN DEFAULT FALSE,
    status ENUM('published', 'draft') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Skills Table
-- =============================================
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default skills
INSERT INTO skills (title, description, icon, sort_order) VALUES
('Product Design', 'Using Figma and Framer to explore and create user flows, wireframes, and high-fidelity screens for web and device apps.', 'grid', 1),
('Graphic Design', 'Using Photoshop, Illustrator, and InDesign to design everything from albums, singles and merch to full corporate brands.', 'palette', 2),
('Type Design', 'Using Illustrator and Glyphs app to create custom wordmarks, titles, and typography as well as full retail typefaces.', 'type', 3),
('3D Art', 'Creating a variety of personal and commercial artwork using Cinema 4D with Redshift.', 'box', 4);

-- =============================================
-- Sample Projects Data
-- =============================================
INSERT INTO projects (title, slug, description, category, tags, featured_image, pdf_path, status, is_featured, sort_order) VALUES
('Libra', 'libra', 'A modern public library catalog app integrating physical book checkout, e-readers, audiobooks, and more. Designed to make discovering and borrowing books seamless across all formats.', 'Product Design', '["Product Design", "Mobile App", "UX/UI"]', 'uploads/projects/1.jpg', 'uploads/pdfs/libra-casestudy.pdf', 'coming_soon', TRUE, 1),
('Energy Dashboard', 'energy-dashboard', 'A comprehensive dashboard for tracking energy from orbital solar satellites with real-time monitoring, analytics, and predictive insights for sustainable energy management.', 'Product Design', '["Product Design", "Dashboard", "Data Visualization"]', 'uploads/projects/2.jpg', 'uploads/pdfs/energy-dashboard.pdf', 'coming_soon', TRUE, 2),
('Solar Company Branding', 'solar-branding', 'Complete branding and product design for a home solar energy company. Including logo design, brand guidelines, marketing materials, and digital presence.', 'Branding', '["Branding", "Graphic Design", "Identity"]', 'uploads/projects/3.jpg', 'uploads/pdfs/solar-branding.pdf', 'coming_soon', TRUE, 3),
('Protocol', 'protocol', 'A platform that makes it easy for traditional art dealers and galleries to buy and sell art with crypto. Bridging the gap between traditional art market and Web3.', 'Product Design', '["Product Design", "Web3", "Marketplace"]', 'uploads/projects/4.jpg', 'uploads/pdfs/protocol.pdf', 'coming_soon', TRUE, 4);

-- =============================================
-- Sample Art Data
-- =============================================
INSERT INTO art_gallery (title, slug, description, category, image_path, is_large, status, sort_order) VALUES
('Luminescence', 'luminescence', '3D abstract sculpture exploring light and form', '3d', 'uploads/art/art-1.jpg', FALSE, 'published', 1),
('Light Study #03', 'light-study-03', 'Abstract exploration of light and shadow', 'abstract', 'uploads/art/art-2.jpg', TRUE, 'published', 2),
('Type Experiment', 'type-experiment', 'Custom typography exploration', 'typography', 'uploads/art/art-3.jpg', FALSE, 'published', 3),
('Geometric Dreams', 'geometric-dreams', '3D geometric forms and patterns', '3d', 'uploads/art/art-4.jpg', FALSE, 'published', 4),
('Digital Erosion', 'digital-erosion', 'Experimental digital art piece', 'experimental', 'uploads/art/art-5.jpg', FALSE, 'published', 5),
('Ethereal Space', 'ethereal-space', '3D environment design', '3d', 'uploads/art/art-6.jpg', TRUE, 'published', 6),
('Motion Type', 'motion-type', 'Kinetic typography design', 'typography', 'uploads/art/art-7.jpg', FALSE, 'published', 7),
('Fragments', 'fragments', 'Abstract composition study', 'abstract', 'uploads/art/art-8.jpg', FALSE, 'published', 8),
('Signal Decay', 'signal-decay', 'Glitch art exploration', 'experimental', 'uploads/art/art-9.jpg', FALSE, 'published', 9),
('Digital Being', 'digital-being', '3D character study', '3d', 'uploads/art/art-10.jpg', TRUE, 'published', 10),
('Chromatic Shift', 'chromatic-shift', 'Color study and abstract art', 'abstract', 'uploads/art/art-11.jpg', FALSE, 'published', 11),
('Dimensional Type', 'dimensional-type', '3D typography artwork', 'typography', 'uploads/art/art-12.jpg', FALSE, 'published', 12);

-- =============================================
-- Indexes for better performance
-- =============================================
CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_projects_featured ON projects(is_featured);
CREATE INDEX idx_art_category ON art_gallery(category);
CREATE INDEX idx_art_status ON art_gallery(status);
