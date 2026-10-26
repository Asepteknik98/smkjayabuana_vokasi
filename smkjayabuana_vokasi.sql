-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 03:48 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smkjayabuana_vokasi`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$4XVvd4niAdxrrflBc4P5POpVYJuG8iBY6F5Xm/mr0htmQJd0aFhhW', '2026-10-22 11:19:12');

-- --------------------------------------------------------

--
-- Table structure for table `destinasi`
--

CREATE TABLE `destinasi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kategori_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nama` varchar(180) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `wilayah` varchar(120) NOT NULL,
  `alamat` text DEFAULT NULL,
  `deskripsi` text NOT NULL,
  `harga_tiket` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gambar` varchar(255) DEFAULT NULL,
  `jam_buka` varchar(100) DEFAULT NULL,
  `jam_tutup` varchar(100) DEFAULT NULL,
  `status` enum('draft','aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinasi`
--

INSERT INTO `destinasi` (`id`, `kategori_id`, `nama`, `slug`, `wilayah`, `alamat`, `deskripsi`, `harga_tiket`, `gambar`, `jam_buka`, `jam_tutup`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Pantai Anyer', 'pantai-anyer', 'Serang', 'Kawasan Anyer, Kabupaten Serang, Banten', 'Nikmati pemandangan laut dan suasana pantai\r\n    bersama keluarga.', 20000.00, 'anyer.jpg', '07:00', '18:00', 'aktif', '2026-10-20 12:10:21', '2026-10-20 12:10:21'),
(2, 2, 'Taman Nasional Ujung Kulon', 'taman-nasional-ujung-kulon', 'Pandeglang', 'Kabupaten Pandeglang, Banten', 'Jelajahi keindahan alam dan kekayaan ekosistem\r\n    kawasan konservasi.', 50000.00, 'ujung-kulon.jpg', '08:00', '16:00', 'aktif', '2026-10-20 12:10:21', '2026-10-20 12:10:21'),
(3, 1, 'Tanjung Lesung', 'tanjung-lesung', 'Pandeglang', 'Kawasan Tanjung Lesung, Pandeglang, Banten', 'Destinasi pesisir untuk menikmati panorama laut\r\n    dan kegiatan wisata.', 25000.00, 'tanjung-lesung.jpg', '08:00', '17:00', 'aktif', '2026-10-20 12:10:21', '2026-10-20 12:10:21');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `slug`, `deskripsi`, `created_at`) VALUES
(1, 'Pantai', 'pantai', 'Wisata pantai di Banten', '2026-10-20 12:10:21'),
(2, 'Alam', 'alam', 'Wisata alam dan konservasi', '2026-10-20 12:10:21'),
(3, 'Budaya', 'budaya', 'Wisata budaya dan tradisi', '2026-10-20 12:10:21'),
(4, 'Keluarga', 'keluarga', 'Wisata rekreasi keluarga', '2026-10-20 12:10:21');

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pengelola','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wisata`
--

CREATE TABLE `wisata` (
  `id` int(11) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `lokasi` varchar(200) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `foto` varchar(255) NOT NULL,
  `aktivitas` text DEFAULT NULL,
  `harga_tiket` varchar(100) DEFAULT NULL,
  `jam_buka` varchar(100) DEFAULT NULL,
  `fasilitas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wisata`
--

INSERT INTO `wisata` (`id`, `nama`, `lokasi`, `kategori`, `deskripsi`, `foto`, `aktivitas`, `harga_tiket`, `jam_buka`, `fasilitas`, `created_at`) VALUES
(1, 'Pantai Anyer', 'Serang, Banten', 'Pantai', 'Pantai indah dengan pemandangan laut dan pasir.', 'pantai_anyer.webp', 'Berenang, bermain pasir, menikmati matahari terbenam', 'Rp10.000/orang tanpa kendaraan. Motor + orang Rp25.000. Sedan/Kijang + orang Rp100.000. Harga kendar', 'Jam operasional mengikuti pengelola pantai. Konfirmasi jam buka sebelum berkunjung.', 'Area parkir, warung makan, toilet, tempat bilas, penyewaan ban, dan tempat duduk (ketersediaan mengikuti lokasi).', '2026-10-22 10:57:31'),
(2, 'Pantai Carita', 'Pandeglang, Banten', 'Pantai', 'Pantai populer untuk berenang dan wisata keluarga.', 'pantai_carita.jpg', 'Berenang, bermain pasir, fotografi', 'Estimasi Rp15.000-Rp25.000/orang. Tiket motor dan mobil dapat berbeda menurut lokasi.', 'Konfirmasi jam operasional lokasi', 'Area parkir, warung, toilet (konfirmasi ketersediaan)', '2026-10-22 10:57:31'),
(3, 'Tanjung Lesung', 'Pandeglang, Banten', 'Pantai', 'Destinasi pantai dengan panorama laut yang menarik.', 'tanjung_lesung.webp', 'Snorkeling, berenang, menikmati pantai', 'Hari biasa sekitar Rp25.000/orang; akhir pekan sekitar Rp40.000/orang. Pantai tertentu memiliki tike', 'Periksa jam operasional pengelola', 'Fasilitas wisata dan penginapan (konfirmasi ketersediaan)', '2026-10-22 10:57:31'),
(4, 'Pantai Sawarna', 'Lebak, Banten', 'Pantai', 'Pantai dengan pasir putih, ombak, dan tebing.', 'pantai_sawarna.webp', 'Berselancar, fotografi, menikmati pantai', 'Sekitar Rp10.000/orang untuk tiket masuk kawasan. Objek wisata dan aktivitas tertentu dapat dikenaka', 'Konfirmasi jam kunjungan', 'Warung dan penginapan sekitar (konfirmasi ketersediaan)', '2026-10-22 10:57:31'),
(5, 'Pulau Cangkir', 'Kronjo, Tangerang, Banten', 'Pulau', 'Wisata pesisir dengan suasana alam dan wisata religi.', 'pulau_cangkir.jpg', 'Wisata religi, berjalan-jalan, menikmati pesisir', 'Tarif masuk terbaru belum terverifikasi. Konfirmasi biaya masuk dan parkir kepada pengelola.', 'Periksa aturan kunjungan setempat', 'Fasilitas dasar sesuai kondisi lokasi', '2026-10-22 10:57:31'),
(6, 'Pulau Sangiang', 'Serang, Banten', 'Pulau', 'Pulau dengan panorama laut dan alam tropis.', 'pulau_sangiang.webp', 'Snorkeling, menyelam, berkemah', 'Paket one day trip sekitar Rp400.000; paket 2 hari 1 malam sekitar Rp550.000. Konfirmasi isi paket d', 'Atur kunjungan dan kapal terlebih dahulu', 'Fasilitas sesuai pengelola dan area kunjungan', '2026-10-22 10:57:31'),
(7, 'Pulau Peucang', 'Pandeglang, Banten', 'Pulau', 'Pulau alami dengan pasir putih dan hutan asri.', 'pulau_peucang.webp', 'Menjelajahi alam, snorkeling, fotografi', 'Tiket kawasan untuk wisatawan domestik sekitar Rp10.000/orang/hari pada hari biasa. Kapal, pemandu, ', 'Ikuti jadwal kunjungan kawasan', 'Fasilitas sesuai aturan kawasan', '2026-10-22 10:57:31'),
(8, 'Taman Nasional Ujung Kulon', 'Pandeglang, Banten', 'Taman Nasional', 'Kawasan konservasi dengan hutan tropis dan satwa liar.', 'ujung_kulon.webp', 'Wisata alam, pengamatan satwa, trekking', 'Tiket kawasan wisatawan domestik sekitar Rp10.000/orang/hari pada hari biasa. Biaya lain dan tarif h', 'Ikuti jam dan aturan kawasan', 'Fasilitas sesuai ketentuan kawasan konservasi', '2026-10-22 10:57:31'),
(9, 'Kampung Baduy', 'Lebak, Banten', 'Budaya', 'Wisata budaya untuk mengenal tradisi masyarakat Baduy.', 'kampung_baduy.webp', 'Wisata budaya, mengenal tradisi lokal', 'Biaya kunjungan atau paket lokal bervariasi. Paket perjalanan yang ditemukan mulai sekitar Rp200.000', 'Ikuti aturan kunjungan masyarakat setempat', 'Layanan lokal sesuai aturan masyarakat', '2026-10-22 10:57:31'),
(10, 'Banten Lama', 'Kota Serang, Banten', 'Sejarah', 'Kawasan bersejarah peninggalan Kesultanan Banten.', 'banten_lama.png', 'Wisata sejarah, fotografi, wisata religi', 'Biaya masuk bergantung pada objek yang dikunjungi. Konfirmasi biaya parkir dan objek tertentu di lok', 'Periksa jam tiap objek', 'Fasilitas umum di sekitar kawasan', '2026-10-22 10:57:31'),
(11, 'Keraton Surosowan', 'Kota Serang, Banten', 'Sejarah', 'Situs peninggalan keraton Kesultanan Banten.', 'kreatonsurosowan.webp', 'Wisata sejarah, belajar budaya', 'Tiket masuk dilaporkan gratis. Siapkan biaya parkir atau biaya lain jika berlaku.', 'Konfirmasi akses situs', 'Fasilitas sesuai kondisi situs', '2026-10-22 10:57:31'),
(12, 'Tanara', 'Kabupaten Serang, Banten', 'Wisata Religi', 'Kawasan yang dikenal dengan sejarah dan wisata religi.', 'tanara.webp', 'Wisata religi, mengenal sejarah lokal', 'Tarif masuk khusus belum terverifikasi. Periksa biaya pada lokasi wisata religi yang dikunjungi.', 'Hormati jam kegiatan tempat religi', 'Fasilitas sesuai lokasi kunjungan', '2026-10-22 10:57:31'),
(13, 'Gunung Pulosari', 'Pandeglang, Banten', 'Gunung', 'Gunung dengan jalur pendakian dan pemandangan alam.', 'gunung_pulosari.webp', 'Mendaki, menikmati pemandangan', 'Sekitar Rp30.000/orang untuk pendakian, termasuk asuransi menurut informasi yang tersedia. Konfirmas', 'Periksa aturan pendakian', 'Fasilitas pendakian sesuai pengelola', '2026-10-22 10:57:31'),
(14, 'Curug Putri', 'Pandeglang, Banten', 'Air Terjun', 'Wisata alam dengan aliran air dan suasana hijau.', 'curug_putri.webp', 'Menikmati air terjun, fotografi alam', 'Paket wisata yang tercantum mulai sekitar Rp75.000. Pastikan apakah harga sudah termasuk tiket masuk', 'Konfirmasi jam operasional', 'Fasilitas sesuai kondisi lokasi', '2026-10-22 10:57:31'),
(15, 'Rawa Dano', 'Serang, Banten', 'Danau', 'Kawasan rawa dengan lingkungan alami.', 'rawa_dano.webp', 'Fotografi, menikmati alam, pengamatan lingkungan', 'Tarif masuk terbaru belum terverifikasi. Konfirmasi akses dan biaya kepada pengelola kawasan.', 'Konfirmasi akses kawasan', 'Fasilitas sesuai akses kawasan', '2026-10-22 10:57:31'),
(16, 'Arung Jeram Sungai Ciberang', 'Kabupaten Lebak, Banten', 'Sungai', 'Arung Jeram Sungai Ciberang merupakan wisata petualangan alam yang menawarkan pengalaman menyusuri aliran sungai menggunakan perahu karet. Pengunjung dapat merasakan sensasi melewati jeram, menikmati pemandangan alam yang hijau, serta melatih kekompakan dan kerja sama tim. Aktivitas ini cocok untuk wisata bersama teman, keluarga, maupun rombongan sekolah dengan tetap mengikuti arahan pemandu dan prosedur keselamatan.', 'arungjeramsungaiciberang.webp', 'Arung jeram, mendayung perahu karet, melewati jeram, menikmati pemandangan alam, fotografi.', 'Rp 50.000 – Rp 350.000 per orang', '24 Jam', 'Area parkir, toilet, musala, warung makan, tempat istirahat, ruang bilas, perlengkapan arung jeram, pemandu, dan penginapan.', '2026-10-22 12:45:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `destinasi`
--
ALTER TABLE `destinasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_destinasi_kategori` (`kategori_id`),
  ADD KEY `idx_destinasi_status` (`status`),
  ADD KEY `idx_destinasi_wilayah` (`wilayah`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wisata`
--
ALTER TABLE `wisata`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `destinasi`
--
ALTER TABLE `destinasi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wisata`
--
ALTER TABLE `wisata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `destinasi`
--
ALTER TABLE `destinasi`
  ADD CONSTRAINT `fk_destinasi_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
