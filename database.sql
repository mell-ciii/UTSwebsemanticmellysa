-- =====================================================================
--  APLIKASI WEB MARKETPLACE TRADISIONAL
--  Skema Database Relasional (MySQL / MariaDB)
--  Dibuat untuk: SOAL 11 - Perancangan & Pembangunan Aplikasi Marketplace
-- =====================================================================
--
--  CATATAN PENTING SAAT DEPLOY KE INFINITYFREE:
--  InfinityFree sudah menyediakan database, jadi baris CREATE DATABASE
--  di bawah cukup di-COMMENT. Cukup import file ini lewat phpMyAdmin
--  ke database yang sudah dibuat di panel InfinityFree.
--
-- =====================================================================

-- CREATE DATABASE IF NOT EXISTS marketplace_db
--   DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE marketplace_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. TABEL users  (Pengguna: Penjual & Pembeli)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100)  NOT NULL,
  email         VARCHAR(150)  NOT NULL UNIQUE,
  password      VARCHAR(255)  NOT NULL,
  role          ENUM('penjual','pembeli') NOT NULL DEFAULT 'pembeli',
  phone         VARCHAR(20)   DEFAULT NULL,
  address       TEXT          DEFAULT NULL,
  store_name    VARCHAR(150)  DEFAULT NULL,      -- nama toko untuk penjual
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. TABEL categories  (Kategori Produk)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100)  NOT NULL,
  slug          VARCHAR(120)  NOT NULL UNIQUE,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. TABEL products  (Produk yang ditawarkan Penjual)
--    FK: seller_id -> users.id , category_id -> categories.id
-- ---------------------------------------------------------------------
CREATE TABLE products (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  seller_id     INT            NOT NULL,
  category_id   INT            DEFAULT NULL,
  name          VARCHAR(150)   NOT NULL,
  slug          VARCHAR(180)   NOT NULL UNIQUE,
  description   TEXT           DEFAULT NULL,
  price         DECIMAL(12,2)  NOT NULL DEFAULT 0,
  stock         INT            NOT NULL DEFAULT 0,
  image         VARCHAR(255)   DEFAULT NULL,
  status        ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_seller
      FOREIGN KEY (seller_id)   REFERENCES users(id)      ON DELETE CASCADE,
  CONSTRAINT fk_products_category
      FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. TABEL carts  (Keranjang Belanja Pembeli)
--    FK: user_id -> users.id , product_id -> products.id
--    UNIQUE(user_id, product_id) agar 1 produk hanya 1 baris per user
-- ---------------------------------------------------------------------
CREATE TABLE carts (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT       NOT NULL,
  product_id    INT       NOT NULL,
  quantity      INT       NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_carts_user
      FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_carts_product
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT uq_cart_user_product UNIQUE (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. TABEL orders  (Header Transaksi Pemesanan)
--    FK: buyer_id -> users.id
-- ---------------------------------------------------------------------
CREATE TABLE orders (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  order_code        VARCHAR(30)   NOT NULL UNIQUE,
  buyer_id          INT           NOT NULL,
  recipient_name    VARCHAR(100)  NOT NULL,
  phone             VARCHAR(20)   NOT NULL,
  shipping_address  TEXT          NOT NULL,
  note              TEXT          DEFAULT NULL,
  total_amount      DECIMAL(12,2) NOT NULL DEFAULT 0,
  status            ENUM('pending','diproses','dikirim','selesai','dibatalkan')
                    NOT NULL DEFAULT 'pending',
  created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_buyer
      FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. TABEL order_items  (Detail item pada tiap pesanan)
--    FK: order_id -> orders.id , product_id -> products.id
-- ---------------------------------------------------------------------
CREATE TABLE order_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  order_id      INT           NOT NULL,
  product_id    INT           NOT NULL,
  product_name  VARCHAR(150)  NOT NULL,          -- snapshot nama saat membeli
  price         DECIMAL(12,2) NOT NULL,          -- snapshot harga saat membeli
  quantity      INT           NOT NULL DEFAULT 1,
  subtotal      DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_orderitems_order
      FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  CONSTRAINT fk_orderitems_product
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  DATA CONTOH (SEED)
-- Password semua akun contoh: password123
-- =====================================================================

INSERT INTO users (name, email, password, role, phone, address, store_name) VALUES
('Toko Sinar Jaya', 'penjual@market.com',
 '$2y$10$5.O8enzQxOTq5t6ULIg57.F0lNF7AOp2Ud6FLzeqHBngKqnBcMDwi',
 'penjual', '081200000001', 'Jl. Merdeka No. 1, Jakarta', 'Sinar Jaya Store'),
('Budi Pembeli', 'pembeli@market.com',
 '$2y$10$5.O8enzQxOTq5t6ULIg57.F0lNF7AOp2Ud6FLzeqHBngKqnBcMDwi',
 'pembeli', '081200000002', 'Jl. Kenanga No. 10, Bandung', NULL),
('Admin Mellysa', 'adminmellysa@market.com',
 '$2y$10$5.O8enzQxOTq5t6ULIg57.F0lNF7AOp2Ud6FLzeqHBngKqnBcMDwi',
 'penjual', '081200000003', 'Jl. Melati No. 5, Surabaya', 'Florist Mellysa');

INSERT INTO categories (name, slug) VALUES
('Bunga & Tanaman', 'bunga-tanaman');

INSERT INTO products (seller_id, category_id, name, slug, description, price, stock, image, status) VALUES
-- Produk milik penjual "Florist Mellysa" (kategori: Bunga & Tanaman)
(3, 1, 'Buket Mawar Merah 12 Tangkai', 'buket-mawar-merah-12-tangkai',
 'Buket 12 tangkai mawar merah segar dengan wrapping elegan, cocok untuk hadiah romantis.',
 250000.00, 30, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=red%20roses%20bouquet%20with%20elegant%20wrapping&image_size=square', 'aktif'),
(3, 1, 'Buket Bunga Matahari', 'buket-bunga-matahari',
 'Buket bunga matahari cerah, memberi kesan hangat dan ceria untuk ucapan selamat.',
 180000.00, 25, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=sunflower%20bouquet%20cheerful&image_size=square', 'aktif'),
(3, 1, 'Buket Mawar Putih Premium', 'buket-mawar-putih-premium',
 'Buket mawar putih premium dengan pita satin, simbol ketulusan dan kesucian.',
 230000.00, 20, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=white%20roses%20bouquet%20with%20satin%20ribbon&image_size=square', 'aktif'),
(3, 1, 'Rangkaian Bunga Meja', 'rangkaian-bunga-meja',
 'Rangkaian bunga segar dalam vas untuk hiasan meja ruangan.',
 350000.00, 15, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=table%20flower%20arrangement%20in%20vase&image_size=square', 'aktif'),
(3, 1, 'Standing Flower Ucapan Selamat', 'standing-flower-ucapan-selamat',
 'Bunga standing besar untuk ucapan selamat pembukaan usaha atau acara resmi.',
 850000.00, 10, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=tall%20standing%20flower%20board%20congratulation&image_size=square', 'aktif'),
(3, 1, 'Bunga Tulip Import', 'bunga-tulip-import',
 'Tulip import segar pilihan, tersedia berbagai warna, tampil mewah dan elegan.',
 420000.00, 12, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=fresh%20imported%20tulips%20bouquet&image_size=square', 'aktif'),
(3, 1, 'Bouquet Bunga Kering', 'bouquet-bunga-kering',
 'Bouquet bunga kering (dried flower) tahan lama, dekorasi estetik tanpa perawatan.',
 165000.00, 40, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=dried%20flower%20bouquet%20aesthetic&image_size=square', 'aktif'),
(3, 1, 'Tanaman Monstera', 'tanaman-monstera',
 'Tanaman monstera daun lebar, cocok untuk dekorasi indoor dan penyegar ruangan.',
 145000.00, 35, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=monstera%20deliciosa%20potted%20plant&image_size=square', 'aktif'),
(3, 1, 'Tanaman Lidah Mertua', 'tanaman-lidah-mertua',
 'Sansevieria penyerap polutan, perawatan mudah, cocok di dalam maupun luar ruangan.',
 95000.00, 50, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=sansevieria%20snake%20plant%20in%20pot&image_size=square', 'aktif'),
(3, 1, 'Kaktus Mini Pot', 'kaktus-mini-pot',
 'Kaktus mini dalam pot mungil, cocok untuk meja kerja dan hadiah kecil.',
 35000.00, 80, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=small%20cactus%20in%20mini%20pot&image_size=square', 'aktif'),
(3, 1, 'Anggrek Bulan Pot', 'anggrek-bulan-pot',
 'Anggrek bulan berbunga indah dalam pot, elegan untuk hiasan rumah.',
 275000.00, 18, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=phalaenopsis%20orchid%20in%20pot&image_size=square', 'aktif'),
(3, 1, 'Pot Keramik Minimalis', 'pot-keramik-minimalis',
 'Pot keramik minimalis berbagai ukuran, mempercantik tampilan tanaman Anda.',
 65000.00, 60, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=minimalist%20ceramic%20plant%20pot&image_size=square', 'aktif'),
(3, 1, 'Buket Bunga Wisuda', 'buket-bunga-wisuda',
 'Buket bunga wisuda lengkap dengan boneka, siap untuk momen kelulusan.',
 210000.00, 22, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=graduation%20flower%20bouquet%20with%20teddy%20bear&image_size=square', 'aktif'),
(3, 1, 'Karangan Bunga Duka Cita', 'karangan-bunga-duka-cita',
 'Karangan bunga duka cita dengan papan ucapan, disusun rapi dan sopan.',
 750000.00, 8, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=condolence%20wreath%20flower%20arrangement&image_size=square', 'aktif'),
(3, 1, 'Bunga Papan Happy Wedding', 'bunga-papan-happy-wedding',
 'Bunga papan ucapan pernikahan, desain elegan dengan bunga segar.',
 900000.00, 6, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=wedding%20congratulation%20flower%20board&image_size=square', 'aktif'),
(3, 1, 'Mawar Tangkai Segar', 'mawar-tangkai-segar',
 'Mawar segar dijual per tangkai, tersedia warna merah, putih, dan pink.',
 15000.00, 200, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=single%20fresh%20rose%20stem&image_size=square', 'aktif'),
(3, 1, 'Bouquet Snack dan Bunga', 'bouquet-snack-dan-bunga',
 'Bouquet kombinasi snack dan bunga, hadiah unik untuk ulang tahun.',
 195000.00, 28, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=snack%20and%20flower%20bouquet%20gift&image_size=square', 'aktif'),
(3, 1, 'Tanaman Sukulen Assorted', 'tanaman-sukulen-assorted',
 'Sukulen aneka jenis dalam pot kecil, tahan lama dan mudah dirawat.',
 45000.00, 70, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=assorted%20succulent%20plants%20in%20small%20pots&image_size=square', 'aktif'),
(3, 1, 'Vas Bunga Kaca', 'vas-bunga-kaca',
 'Vas bunga dari kaca bening, cocok untuk rangkaian bunga segar maupun kering.',
 85000.00, 45, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=clear%20glass%20flower%20vase&image_size=square', 'aktif'),
(3, 1, 'Buket Uang Hias', 'buket-uang-hias',
 'Jasa rangkai buket uang hias, cocok untuk hadiah spesial dan kejutan.',
 150000.00, 30, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=decorative%20money%20bouquet%20gift%20wrapping&image_size=square', 'aktif'),
(3, 1, 'Buket Bunga Lily Putih', 'buket-bunga-lily-putih',
 'Buket bunga lily putih segar dengan sentuhan baby breath, elegan untuk berbagai ucapan.',
 280000.00, 18, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=white%20lily%20bouquet%20with%20baby%20breath&image_size=square', 'aktif'),
(3, 1, 'Buket Bunga Peony Pink', 'buket-bunga-peony-pink',
 'Buket bunga peony pink import yang cantik dan romantis untuk momen spesial.',
 320000.00, 15, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=pink%20peony%20bouquet&image_size=square', 'aktif'),
(3, 1, 'Buket Lavender Kering', 'buket-lavender-kering',
 'Buket lavender kering beraroma menenangkan, cocok untuk dekorasi kamar.',
 175000.00, 30, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=dried%20lavender%20bouquet&image_size=square', 'aktif'),
(3, 1, 'Rangkaian Bunga Duka Cita Mini', 'rangkaian-bunga-duka-cita-mini',
 'Rangkaian bunga duka cita ukuran mini, praktis untuk ucapan belasungkawa.',
 450000.00, 12, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=small%20condolence%20flower%20arrangement&image_size=square', 'aktif'),
(3, 1, 'Bunga Meja Kantor', 'bunga-meja-kantor',
 'Rangkaian bunga meja untuk mempercantik ruang kantor dan meja kerja.',
 300000.00, 20, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=office%20desk%20flower%20arrangement&image_size=square', 'aktif'),
(3, 1, 'Tanaman Calathea', 'tanaman-calathea',
 'Tanaman calathea berdaun corak unik, cocok untuk dekorasi indoor.',
 165000.00, 25, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=calathea%20potted%20plant%20indoor&image_size=square', 'aktif'),
(3, 1, 'Tanaman Peace Lily', 'tanaman-peace-lily',
 'Tanaman peace lily berbunga putih, penyejuk ruangan dan pembersih udara.',
 135000.00, 30, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=peace%20lily%20plant%20in%20pot&image_size=square', 'aktif'),
(3, 1, 'Pot Gantung Bunga', 'pot-gantung-bunga',
 'Pot gantung berisi bunga gantung, mempercantik teras dan balkon rumah.',
 55000.00, 50, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=hanging%20flower%20pot&image_size=square', 'aktif'),
(3, 1, 'Paket Bunga Anniversary', 'paket-bunga-anniversary',
 'Paket bunga anniversary lengkap dengan kartu ucapan dan cokelat.',
 375000.00, 15, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=anniversary%20flower%20gift%20package%20with%20chocolate&image_size=square', 'aktif'),
(3, 1, 'Bunga Kering Baby Breath', 'bunga-kering-baby-breath',
 'Bunga kering baby breath, tahan lama dan cocok sebagai pelengkap buket.',
 120000.00, 40, 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=dried%20baby%20breath%20flowers&image_size=square', 'aktif');
