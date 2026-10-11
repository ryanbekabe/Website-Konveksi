-- Skema database Website Konveksi (MySQL 5.7+ / MariaDB 10.3+)
-- Impor lewat phpMyAdmin pada database kosong, lalu impor seed.sql.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama          VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL,
    no_hp         VARCHAR(20)  NOT NULL DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','produksi','keuangan','pelanggan') NOT NULL DEFAULT 'pelanggan',
    status        ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE login_attempts (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip         VARCHAR(45)  NOT NULL,
    email      VARCHAR(100) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_ip (ip, created_at),
    KEY idx_attempts_email (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE addresses (
    id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id  INT UNSIGNED NOT NULL,
    penerima VARCHAR(100) NOT NULL,
    no_hp    VARCHAR(20)  NOT NULL,
    alamat   TEXT NOT NULL,
    kota     VARCHAR(100) NOT NULL,
    provinsi VARCHAR(100) NOT NULL,
    kode_pos VARCHAR(10)  NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    CONSTRAINT fk_addr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama      VARCHAR(100) NOT NULL,
    slug      VARCHAR(120) NOT NULL,
    deskripsi TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NULL,
    nama        VARCHAR(150) NOT NULL,
    slug        VARCHAR(170) NOT NULL,
    deskripsi   TEXT NULL,
    bahan       VARCHAR(100) NOT NULL DEFAULT '',
    harga_dasar DECIMAL(12,2) NOT NULL DEFAULT 0,
    tipe        ENUM('ready','custom') NOT NULL DEFAULT 'custom',
    aktif       TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_products_slug (slug),
    KEY idx_products_category (category_id),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_variants (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    ukuran     VARCHAR(20) NOT NULL DEFAULT '',
    warna      VARCHAR(30) NOT NULL DEFAULT '',
    sku        VARCHAR(50) NULL,
    stok       INT NOT NULL DEFAULT 0,
    harga      DECIMAL(12,2) NULL,
    PRIMARY KEY (id),
    KEY idx_variants_product (product_id),
    CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE price_tiers (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    min_qty    INT UNSIGNED NOT NULL,
    max_qty    INT UNSIGNED NULL,
    harga      DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_tiers_product (product_id),
    CONSTRAINT fk_tiers_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    file       VARCHAR(100) NOT NULL,
    urutan     INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_images_product (product_id),
    CONSTRAINT fk_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_order       VARCHAR(30) NOT NULL,
    user_id          INT UNSIGNED NOT NULL,
    tipe             ENUM('ready','custom') NOT NULL DEFAULT 'custom',
    status           ENUM('menunggu_pembayaran','dp_dibayar','desain_mockup','menunggu_persetujuan','produksi','qc','menunggu_pelunasan','siap_kirim','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran',
    subtotal         DECIMAL(12,2) NOT NULL DEFAULT 0,
    ongkir           DECIMAL(12,2) NOT NULL DEFAULT 0,
    diskon           DECIMAL(12,2) NOT NULL DEFAULT 0,
    total            DECIMAL(12,2) NOT NULL DEFAULT 0,
    catatan          TEXT NULL,
    estimasi_selesai DATE NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_orders_kode (kode_order),
    KEY idx_orders_user (user_id),
    KEY idx_orders_status (status),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NOT NULL,
    variant_id   INT UNSIGNED NULL,
    qty          INT UNSIGNED NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    spesifikasi  TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_items_order (order_id),
    CONSTRAINT fk_items_order   FOREIGN KEY (order_id)   REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_designs (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id        INT UNSIGNED NOT NULL,
    file            VARCHAR(150) NOT NULL,
    tipe            ENUM('desain','mockup') NOT NULL DEFAULT 'desain',
    status_approval ENUM('menunggu','disetujui','revisi') NOT NULL DEFAULT 'menunggu',
    catatan         TEXT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_designs_order (order_id),
    CONSTRAINT fk_designs_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id    INT UNSIGNED NOT NULL,
    jenis       ENUM('dp','pelunasan') NOT NULL,
    metode      VARCHAR(30) NOT NULL DEFAULT 'transfer',
    jumlah      DECIMAL(12,2) NOT NULL,
    bukti       VARCHAR(150) NULL,
    status      ENUM('menunggu','terverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
    verified_by INT UNSIGNED NULL,
    dibayar_pada DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_order (order_id),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE shipments (
    id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    kurir    VARCHAR(50) NOT NULL DEFAULT '',
    layanan  VARCHAR(50) NOT NULL DEFAULT '',
    resi     VARCHAR(80) NULL,
    biaya    DECIMAL(12,2) NOT NULL DEFAULT 0,
    status   VARCHAR(30) NOT NULL DEFAULT 'diproses',
    PRIMARY KEY (id),
    UNIQUE KEY uk_shipments_order (order_id),
    CONSTRAINT fk_shipments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE production_stages (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id   INT UNSIGNED NOT NULL,
    tahap      ENUM('pemotongan','penjahitan','sablon_bordir','finishing','qc','packing') NOT NULL,
    petugas_id INT UNSIGNED NULL,
    status     ENUM('menunggu','berjalan','selesai') NOT NULL DEFAULT 'menunggu',
    mulai      DATETIME NULL,
    selesai    DATETIME NULL,
    catatan    TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_stages_order (order_id),
    CONSTRAINT fk_stages_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE suppliers (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama   VARCHAR(100) NOT NULL,
    kontak VARCHAR(100) NOT NULL DEFAULT '',
    alamat TEXT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE materials (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama         VARCHAR(100) NOT NULL,
    satuan       VARCHAR(20) NOT NULL DEFAULT 'pcs',
    stok         DECIMAL(12,2) NOT NULL DEFAULT 0,
    stok_minimum DECIMAL(12,2) NOT NULL DEFAULT 0,
    supplier_id  INT UNSIGNED NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_materials_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE material_movements (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    material_id INT UNSIGNED NOT NULL,
    tipe        ENUM('masuk','keluar') NOT NULL,
    qty         DECIMAL(12,2) NOT NULL,
    order_id    INT UNSIGNED NULL,
    keterangan  VARCHAR(200) NOT NULL DEFAULT '',
    tanggal     DATE NOT NULL,
    PRIMARY KEY (id),
    KEY idx_movements_material (material_id),
    CONSTRAINT fk_movements_material FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    order_id   INT UNSIGNED NULL,
    rating     TINYINT UNSIGNED NOT NULL,
    komentar   TEXT NULL,
    tampil     TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reviews_product (product_id),
    CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)    REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE portfolios (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    judul     VARCHAR(150) NOT NULL,
    deskripsi TEXT NULL,
    gambar    VARCHAR(100) NULL,
    kategori  VARCHAR(100) NOT NULL DEFAULT '',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE posts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    judul        VARCHAR(200) NOT NULL,
    slug         VARCHAR(220) NOT NULL,
    konten       MEDIUMTEXT NOT NULL,
    status       ENUM('draft','terbit') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_posts_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
    `key`   VARCHAR(60) NOT NULL,
    `value` TEXT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    pesan      VARCHAR(255) NOT NULL,
    dibaca     TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notif_user (user_id, dibaca),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_logs (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NULL,
    aksi       VARCHAR(50) NOT NULL,
    objek      VARCHAR(50) NOT NULL DEFAULT '',
    detail     TEXT NULL,
    ip         VARCHAR(45) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logs_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
