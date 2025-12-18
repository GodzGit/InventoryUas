-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 18, 2025 at 12:26 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventori`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_lat1` ()   BEGIN
  SELECT CONCAT(first_name, ' ', last_name) AS Nama_Pegawai, job_id AS Pekerjaan, salary AS Gaji
  FROM employees
  WHERE job_id IN ('IT_PROG', 'FI_ACCOUNT')
  AND salary NOT IN (4800, 6000, 9000);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_detail_penerimaan_insert` (IN `p_idpenerimaan` INT, IN `p_idbarang` INT, IN `p_jumlah` INT)   BEGIN
    DECLARE v_harga INT DEFAULT 0;
    DECLARE v_sub INT DEFAULT 0;
    
    -- Ambil harga
    SELECT IFNULL(harga, 0) INTO v_harga FROM barang WHERE idbarang = p_idbarang;
    SET v_sub = v_harga * p_jumlah;

    -- Insert ke Detail Penerimaan
    INSERT INTO detail_penerimaan (idpenerimaan, barang_idbarang, jumlah_terima, harga_satuan_terima, sub_total_terima)
    VALUES (p_idpenerimaan, p_idbarang, p_jumlah, v_harga, v_sub);

    -- Insert ke Kartu Stok (INPUT LANGSUNG)
    -- Kita tidak hitung saldo di sini, biarkan View yang menghitung totalnya nanti
    INSERT INTO kartu_stok (jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, idbarang)
    VALUES ('M', p_jumlah, 0, 0, NOW(), p_idpenerimaan, p_idbarang); 
    -- Catatan: Kolom 'stock' diisi 0 tidak masalah, karena kita pakai View untuk laporan.
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_detail_pengadaan_insert` (IN `p_idpengadaan` INT, IN `p_idbarang` INT, IN `p_jumlah` INT)   BEGIN
    DECLARE v_harga INT;
    DECLARE v_subtotal INT;

    SELECT harga INTO v_harga
    FROM barang
    WHERE idbarang = p_idbarang;

    SET v_subtotal = v_harga * p_jumlah;

    INSERT INTO detail_pengadaan (idpengadaan, idbarang, jumlah, harga_satuan, sub_total)
    VALUES (p_idpengadaan, p_idbarang, p_jumlah, v_harga, v_subtotal);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_detail_penjualan_insert` (IN `p_idpenjualan` INT, IN `p_idbarang` INT, IN `p_jumlah` INT)   BEGIN
    DECLARE v_harga_modal INT DEFAULT 0;
    DECLARE v_persen_margin DOUBLE DEFAULT 0;
    DECLARE v_harga_jual INT DEFAULT 0;
    DECLARE v_subtotal INT DEFAULT 0;
    
    -- 1. Ambil Harga Modal dari Master Barang
    SELECT IFNULL(harga, 0) INTO v_harga_modal 
    FROM barang WHERE idbarang = p_idbarang;

    -- 2. Ambil Persen Margin dari Header Penjualan ini
    -- (Kenapa ambil dari header? Supaya konsisten, kalau admin ubah margin di tengah jalan, transaksi yg sedang jalan tidak berubah)
    SELECT IFNULL(m.persen, 0) INTO v_persen_margin
    FROM penjualan p
    LEFT JOIN margin_penjualan m ON p.idmargin_penjualan = m.idmargin_penjualan
    WHERE p.idpenjualan = p_idpenjualan;

    -- 3. HITUNG HARGA JUAL (Modal + Margin)
    SET v_harga_jual = v_harga_modal + (v_harga_modal * v_persen_margin / 100);
    SET v_subtotal = v_harga_jual * p_jumlah;

    -- 4. Insert Detail dengan HARGA JUAL (Bukan Harga Modal)
    INSERT INTO detail_penjualan (penjualan_idpenjualan, idbarang, harga_satuan, jumlah, subtotal)
    VALUES (p_idpenjualan, p_idbarang, v_harga_jual, p_jumlah, v_subtotal);

    -- 5. Insert Kartu Stok (Tetap pakai logika Keluar)
    INSERT INTO kartu_stok (jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, idbarang)
    VALUES ('K', 0, p_jumlah, 0, NOW(), p_idpenjualan, p_idbarang);

    -- 6. Update Header Penjualan
    UPDATE penjualan 
    SET subtotal_nilai = subtotal_nilai + v_subtotal,
        total_nilai = total_nilai + v_subtotal 
    WHERE idpenjualan = p_idpenjualan;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tambah_penerimaan` (IN `p_idpengadaan` INT, IN `p_user_id` INT)   BEGIN
    INSERT INTO penerimaan (idpengadaan, iduser, created_at, status)
    VALUES (p_idpengadaan, p_user_id, NOW(), 'P');  -- 'P' = Proses
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tambah_pengadaan` (IN `p_user_id` INT, IN `p_vendor_id` INT, IN `p_subtotal` INT)   BEGIN
    DECLARE v_ppn INT;
    DECLARE v_total INT;

    SET v_ppn = p_subtotal * 0.1;
    SET v_total = p_subtotal + v_ppn;

    INSERT INTO pengadaan (timestamp, user_iduser, vendor_idvendor, subtotal_nilai, ppn, total_nilai, status)
    VALUES (NOW(), p_user_id, p_vendor_id, p_subtotal, v_ppn, v_total, 'A');
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tambah_penjualan` (IN `p_iduser` INT, OUT `p_id_baru` INT)   BEGIN
    DECLARE v_idmargin INT;

    -- 1. Ambil ID Margin yang sedang AKTIF (status = 1)
    -- Ambil satu saja (LIMIT 1) untuk jaga-jaga kalau ada double aktif
    SELECT idmargin_penjualan INTO v_idmargin
    FROM margin_penjualan 
    WHERE status = 1 
    ORDER BY idmargin_penjualan DESC LIMIT 1;

    -- Jika tidak ada margin aktif, set default NULL atau tangani error
    -- Di sini kita biarkan NULL jika tidak ada setingan margin

    -- 2. Buat header nota baru dengan mencatat ID Margin-nya
    INSERT INTO penjualan (created_at, subtotal_nilai, ppn, total_nilai, iduser, idmargin_penjualan)
    VALUES (NOW(), 0, 0, 0, p_iduser, v_idmargin);

    -- 3. Kembalikan ID nota yang baru dibuat
    SET p_id_baru = LAST_INSERT_ID();
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_stok_akhir` (`p_idbarang` INT) RETURNS INT DETERMINISTIC BEGIN
    DECLARE v_stok INT;

    SELECT IFNULL(SUM(masuk) - SUM(keluar), 0) INTO v_stok
    FROM kartu_stok
    WHERE idbarang = p_idbarang;

    RETURN v_stok;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_total_penjualan` (`p_idpenjualan` INT) RETURNS INT DETERMINISTIC BEGIN
    DECLARE v_total INT;

    SELECT SUM(subtotal) INTO v_total
    FROM detail_penjualan
    WHERE penjualan_idpenjualan = p_idpenjualan;

    RETURN IFNULL(v_total, 0);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `idbarang` int NOT NULL,
  `jenis` char(1) DEFAULT NULL,
  `nama` varchar(45) NOT NULL,
  `idsatuan` int DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  `harga` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`idbarang`, `jenis`, `nama`, `idsatuan`, `status`, `harga`) VALUES
(1, 'A', 'Beras Gulag', 4, 1, 50000),
(2, 'B', 'Minyak Goreng Suki ', 3, 1, 18000),
(3, 'C', 'Gula Djawa', 4, 1, 9000),
(4, 'D', 'Kopi Tubruk', 5, 1, 7000),
(5, 'E', 'Teh Celup', 5, 1, 5000),
(6, 'F', 'Sabun Cuci Cair', 3, 1, 5000),
(14, 'A', 'Garam Sajiku', 5, 1, 3000),
(15, 'A', 'Micin Sasa', 5, 1, 2500),
(16, 'B', 'Sushi Kaleng', 1, 0, 15000),
(17, 'B', 'Kornet Ayam', 1, 1, 25000),
(18, 'C', 'Mentega', 1, 1, 8000),
(19, 'C', 'Boncabe', 1, 1, 12000),
(20, 'D', 'Kopi 123', 5, 1, 4000),
(21, 'D', 'Gula Pasir Putih', 4, 1, 10000),
(22, 'E', 'Shampoo 123', 3, 1, 7000),
(23, 'E', 'Sabun Div', 1, 1, 15000);

-- --------------------------------------------------------

--
-- Table structure for table `detail_penerimaan`
--

CREATE TABLE `detail_penerimaan` (
  `iddetail_penerimaan` bigint NOT NULL,
  `idpenerimaan` bigint DEFAULT NULL,
  `barang_idbarang` int DEFAULT NULL,
  `jumlah_terima` int DEFAULT NULL,
  `harga_satuan_terima` int DEFAULT NULL,
  `sub_total_terima` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_penerimaan`
--

INSERT INTO `detail_penerimaan` (`iddetail_penerimaan`, `idpenerimaan`, `barang_idbarang`, `jumlah_terima`, `harga_satuan_terima`, `sub_total_terima`) VALUES
(1, 1, 1, 20, 50000, 1000000),
(2, 2, 1, 2, 50000, 100000),
(3, 2, 1, 2, 50000, 100000),
(4, 2, 1, 2, 50000, 100000),
(5, 2, 1, 2, 50000, 100000),
(6, 3, 3, 2, 9000, 18000),
(7, 3, 3, 1, 9000, 9000),
(8, 3, 3, 1, 9000, 9000),
(9, 3, 3, 16, 9000, 144000),
(10, 4, 5, 5, 5000, 25000),
(11, 4, 6, 5, 5000, 25000),
(12, 5, 2, 10, 18000, 180000),
(13, 6, 2, 30, 18000, 540000),
(14, 6, 5, 100, 5000, 500000),
(15, 7, 2, 20, 18000, 360000),
(16, 7, 3, 40, 9000, 360000);

-- --------------------------------------------------------

--
-- Table structure for table `detail_pengadaan`
--

CREATE TABLE `detail_pengadaan` (
  `iddetail_pengadaan` bigint NOT NULL,
  `harga_satuan` int DEFAULT NULL,
  `jumlah` int DEFAULT NULL,
  `sub_total` int DEFAULT NULL,
  `idbarang` int DEFAULT NULL,
  `idpengadaan` bigint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_pengadaan`
--

INSERT INTO `detail_pengadaan` (`iddetail_pengadaan`, `harga_satuan`, `jumlah`, `sub_total`, `idbarang`, `idpengadaan`) VALUES
(1, 25000, 20, 500000, 1, 1),
(2, 30000, 10, 300000, 2, 2),
(3, 20000, 20, 400000, 3, 3),
(4, 25000, 10, 250000, 4, 4),
(5, 70000, 5, 350000, 5, 5),
(8, 8000, 8, 64000, 18, 7),
(9, 25000, 5, 125000, 17, 6),
(10, 3000, 200, 600000, 14, 9),
(11, 5000, 5, 25000, 6, 5),
(12, 18000, 50, 900000, 2, 10),
(13, 18000, 50, 900000, 2, 11),
(14, 5000, 100, 500000, 5, 11),
(15, 9000, 40, 360000, 3, 11);

--
-- Triggers `detail_pengadaan`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_detail_pengadaan` AFTER INSERT ON `detail_pengadaan` FOR EACH ROW BEGIN
    DECLARE v_subtotal DECIMAL(15,2);

    -- Hitung ulang subtotal (total semua detail_pengadaan untuk pengadaan itu)
    SELECT SUM(sub_total) INTO v_subtotal
    FROM detail_pengadaan
    WHERE idpengadaan = NEW.idpengadaan;

    -- Update subtotal, ppn, dan total di tabel pengadaan
    UPDATE pengadaan
    SET subtotal_nilai = v_subtotal,
        ppn = v_subtotal * 0.1,
        total_nilai = v_subtotal * 1.1
    WHERE idpengadaan = NEW.idpengadaan;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `detail_penjualan`
--

CREATE TABLE `detail_penjualan` (
  `iddetail_penjualan` bigint NOT NULL,
  `harga_satuan` int DEFAULT NULL,
  `jumlah` int DEFAULT NULL,
  `subtotal` int DEFAULT NULL,
  `penjualan_idpenjualan` int DEFAULT NULL,
  `idbarang` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_penjualan`
--

INSERT INTO `detail_penjualan` (`iddetail_penjualan`, `harga_satuan`, `jumlah`, `subtotal`, `penjualan_idpenjualan`, `idbarang`) VALUES
(1, 25000, 5, 125000, 1, 1),
(2, 30000, 3, 90000, 2, 2),
(3, 20000, 10, 200000, 3, 3),
(4, 25000, 6, 150000, 4, 4),
(5, 70000, 1, 70000, 5, 5),
(6, 9000, 9, 81000, 10, 3),
(7, 50000, 3, 150000, 10, 1),
(8, 19800, 4, 79200, 11, 2),
(9, 19800, 5, 99000, 12, 2);

--
-- Triggers `detail_penjualan`
--
DELIMITER $$
CREATE TRIGGER `trg_validasi_stok_jual` BEFORE INSERT ON `detail_penjualan` FOR EACH ROW BEGIN
    DECLARE v_stok_sekarang INT;

    -- 1. Panggil Function untuk intip stok saat ini
    SET v_stok_sekarang = fn_stok_akhir(NEW.idbarang);

    -- 2. Bandingkan. Jika stok < permintaan, Stop!
    IF v_stok_sekarang < NEW.jumlah THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'TRANSAKSI GAGAL: Stok barang tidak mencukupi!';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `detail_retur`
--

CREATE TABLE `detail_retur` (
  `iddetail_retur` int NOT NULL,
  `jumlah` int DEFAULT NULL,
  `alasan` varchar(200) DEFAULT NULL,
  `idretur` bigint DEFAULT NULL,
  `iddetail_penerimaan` bigint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_retur`
--

INSERT INTO `detail_retur` (`iddetail_retur`, `jumlah`, `alasan`, `idretur`, `iddetail_penerimaan`) VALUES
(1, 2, 'Kemasan rusak', 1, 1),
(2, 1, 'Kualitas tidak sesuai', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `kartu_stok`
--

CREATE TABLE `kartu_stok` (
  `idkartu_stok` bigint NOT NULL,
  `jenis_transaksi` char(1) DEFAULT NULL,
  `masuk` int DEFAULT NULL,
  `keluar` int DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `idtransaksi` int DEFAULT NULL,
  `idbarang` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kartu_stok`
--

INSERT INTO `kartu_stok` (`idkartu_stok`, `jenis_transaksi`, `masuk`, `keluar`, `stock`, `created_at`, `idtransaksi`, `idbarang`) VALUES
(1, 'M', 6, 0, 6, '2025-11-26 04:07:47', 1, 1),
(3, 'M', 2, 0, NULL, '2025-11-26 09:58:11', 2, 1),
(4, 'M', 2, 0, NULL, '2025-11-26 10:01:02', 3, 3),
(5, 'M', 1, 0, NULL, '2025-11-26 10:05:21', 3, 3),
(6, 'M', 1, 0, 0, '2025-11-26 10:18:01', 3, 3),
(7, 'M', 16, 0, 0, '2025-11-26 10:20:27', 3, 3),
(8, 'M', 5, 0, 0, '2025-11-26 13:01:30', 4, 5),
(9, 'M', 5, 0, 0, '2025-11-26 13:01:58', 4, 6),
(10, 'K', 0, 9, 0, '2025-11-26 13:53:12', 10, 3),
(11, 'K', 0, 3, 0, '2025-11-26 14:39:02', 10, 1),
(12, 'M', 10, 0, 0, '2025-11-26 15:13:57', 5, 2),
(13, 'K', 0, 4, 0, '2025-11-26 15:14:49', 11, 2),
(14, 'M', 30, 0, 0, '2025-11-27 01:24:36', 6, 2),
(15, 'M', 100, 0, 0, '2025-11-27 01:24:45', 6, 5),
(16, 'M', 20, 0, 0, '2025-11-27 01:25:25', 7, 2),
(17, 'M', 40, 0, 0, '2025-11-27 01:25:31', 7, 3),
(18, 'K', 0, 5, 0, '2025-11-27 01:32:37', 12, 2);

-- --------------------------------------------------------

--
-- Table structure for table `margin_penjualan`
--

CREATE TABLE `margin_penjualan` (
  `idmargin_penjualan` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `persen` double DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  `iduser` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `margin_penjualan`
--

INSERT INTO `margin_penjualan` (`idmargin_penjualan`, `created_at`, `persen`, `status`, `iduser`, `updated_at`) VALUES
(1, '2025-09-30 01:15:33', 10, 1, 4, '2025-09-30 01:15:33'),
(2, '2025-09-30 01:15:33', 12.5, 0, 5, '2025-11-13 03:03:17');

-- --------------------------------------------------------

--
-- Table structure for table `penerimaan`
--

CREATE TABLE `penerimaan` (
  `idpenerimaan` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` char(1) DEFAULT NULL,
  `idpengadaan` bigint DEFAULT NULL,
  `iduser` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penerimaan`
--

INSERT INTO `penerimaan` (`idpenerimaan`, `created_at`, `status`, `idpengadaan`, `iduser`) VALUES
(1, '2025-11-26 04:07:27', 'P', 1, 1),
(2, '2025-11-26 09:36:36', 'P', 1, 1),
(3, '2025-11-26 10:00:57', 'S', 3, 1),
(4, '2025-11-26 13:01:12', 'S', 5, 1),
(5, '2025-11-26 15:13:46', 'P', 10, 1),
(6, '2025-11-27 01:24:21', 'P', 11, 1),
(7, '2025-11-27 01:25:13', 'S', 11, 1);

-- --------------------------------------------------------

--
-- Table structure for table `pengadaan`
--

CREATE TABLE `pengadaan` (
  `idpengadaan` bigint NOT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_iduser` int DEFAULT NULL,
  `vendor_idvendor` int DEFAULT NULL,
  `subtotal_nilai` int DEFAULT NULL,
  `ppn` int DEFAULT NULL,
  `total_nilai` int DEFAULT NULL,
  `status` char(1) DEFAULT 'P'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengadaan`
--

INSERT INTO `pengadaan` (`idpengadaan`, `timestamp`, `user_iduser`, `vendor_idvendor`, `subtotal_nilai`, `ppn`, `total_nilai`, `status`) VALUES
(1, '2025-09-30 00:59:54', 1, 1, 500000, 50000, 550000, 'P'),
(2, '2025-09-30 00:59:54', 2, 2, 300000, 30000, 330000, 'P'),
(3, '2025-09-30 00:59:54', 3, 3, 400000, 40000, 440000, 'P'),
(4, '2025-09-30 00:59:54', 1, 4, 250000, 25000, 275000, 'P'),
(5, '2025-09-30 00:59:54', 2, 5, 375000, 37500, 412500, 'S'),
(6, '2025-11-05 21:42:51', 1, 3, 125000, 12500, 137500, 'P'),
(7, '2025-11-06 00:56:05', 1, 1, 64000, 6400, 70400, 'P'),
(9, '2025-11-13 02:12:56', 1, 3, 600000, 60000, 660000, 'P'),
(10, '2025-11-26 14:59:53', 1, 5, 900000, 90000, 990000, 'P'),
(11, '2025-11-27 01:19:41', 1, 3, 1760000, 176000, 1936000, 'S'),
(12, '2025-11-27 01:36:52', 1, 2, 0, 0, 0, 'A');

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `idpenjualan` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `subtotal_nilai` int DEFAULT NULL,
  `ppn` int DEFAULT NULL,
  `total_nilai` int DEFAULT NULL,
  `iduser` int DEFAULT NULL,
  `idmargin_penjualan` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penjualan`
--

INSERT INTO `penjualan` (`idpenjualan`, `created_at`, `subtotal_nilai`, `ppn`, `total_nilai`, `iduser`, `idmargin_penjualan`) VALUES
(1, '2025-09-30 01:17:41', 125000, 12500, 137500, 4, 1),
(2, '2025-09-30 01:17:41', 90000, 9000, 99000, 5, 2),
(3, '2025-09-30 01:17:41', 200000, 20000, 220000, 4, 1),
(4, '2025-09-30 01:17:41', 150000, 15000, 165000, 5, 2),
(5, '2025-09-30 01:17:41', 70000, 7000, 77000, 4, 1),
(10, '2025-11-26 13:52:33', 231000, 0, 231000, 5, NULL),
(11, '2025-11-26 15:14:38', 79200, 0, 79200, 5, 1),
(12, '2025-11-27 01:32:08', 99000, 0, 99000, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `retur`
--

CREATE TABLE `retur` (
  `idretur` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `idpenerimaan` bigint DEFAULT NULL,
  `iduser` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `retur`
--

INSERT INTO `retur` (`idretur`, `created_at`, `idpenerimaan`, `iduser`) VALUES
(1, '2025-09-30 01:12:46', 1, 1),
(2, '2025-09-30 01:12:46', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `idrole` int NOT NULL,
  `nama_role` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`idrole`, `nama_role`) VALUES
(1, 'superadmin'),
(2, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `satuan`
--

CREATE TABLE `satuan` (
  `idsatuan` int NOT NULL,
  `nama_satuan` varchar(45) NOT NULL,
  `status` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `satuan`
--

INSERT INTO `satuan` (`idsatuan`, `nama_satuan`, `status`) VALUES
(1, 'pcs', 1),
(2, 'dus', 1),
(3, 'liter', 1),
(4, 'kg', 1),
(5, 'pack', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `iduser` int NOT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(100) NOT NULL,
  `idrole` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`iduser`, `username`, `password`, `idrole`) VALUES
(1, 'KG_Bagusaurus', '123456', 1),
(2, 'Artix_SPV', '123456', 1),
(3, 'King_Bob', '123456', 1),
(4, 'Safiria_Kasir', '123456', 2),
(5, 'Lae_Gudang', '123456', 2);

-- --------------------------------------------------------

--
-- Table structure for table `vendor`
--

CREATE TABLE `vendor` (
  `idvendor` int NOT NULL,
  `nama_vendor` varchar(100) NOT NULL,
  `badan_hukum` char(1) DEFAULT NULL,
  `status` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vendor`
--

INSERT INTO `vendor` (`idvendor`, `nama_vendor`, `badan_hukum`, `status`) VALUES
(1, 'PT Sumber Makmur', 'Y', '1'),
(2, 'CV Jaya Abadi', 'N', '1'),
(3, 'PT Pandai Tani Sentosa', 'Y', '1'),
(4, 'CV Tani Jaya', 'N', '1'),
(5, 'PT Makmur Abadi Raja FF', 'Y', '1');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_barang_aktif`
-- (See below for the actual view)
--
CREATE TABLE `v_barang_aktif` (
`idbarang` int
,`jenis` char(1)
,`nama` varchar(45)
,`idsatuan` int
,`status` tinyint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_pengadaan`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_pengadaan` (
`idpengadaan` bigint
,`tanggal_pengadaan` timestamp
,`nama_vendor` varchar(100)
,`petugas` varchar(45)
,`nama_barang` varchar(45)
,`jumlah` int
,`harga_satuan` int
,`sub_total` int
,`total_nilai` int
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_penjualan`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_penjualan` (
`idpenjualan` int
,`tanggal_penjualan` timestamp
,`kasir` varchar(45)
,`nama_barang` varchar(45)
,`jumlah` int
,`harga_satuan` int
,`subtotal` int
,`nilai_margin` decimal(12,1)
,`total_per_item` decimal(13,1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_retur`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_retur` (
`idretur` bigint
,`tanggal_retur` timestamp
,`petugas` varchar(45)
,`nama_barang` varchar(45)
,`jumlah` int
,`alasan` varchar(200)
,`idpenerimaan` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_stok`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_stok` (
`idbarang` int
,`nama_barang` varchar(45)
,`nama_satuan` varchar(45)
,`stok_akhir` decimal(33,0)
,`terakhir_update` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_margin_penjualan_aktif`
-- (See below for the actual view)
--
CREATE TABLE `v_margin_penjualan_aktif` (
`idmargin_penjualan` int
,`created_at` timestamp
,`persen` double
,`status` tinyint
,`iduser` int
,`nama_user` varchar(45)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_master_barang`
-- (See below for the actual view)
--
CREATE TABLE `v_master_barang` (
`jenis` char(1)
,`nama` varchar(45)
,`nama_satuan` varchar(45)
,`harga` int
,`status` varchar(13)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_master_margin_penjualan`
-- (See below for the actual view)
--
CREATE TABLE `v_master_margin_penjualan` (
`idmargin_penjualan` int
,`created_at` timestamp
,`persen` double
,`status` varchar(13)
,`username` varchar(45)
,`nama_user` varchar(45)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_master_role`
-- (See below for the actual view)
--
CREATE TABLE `v_master_role` (
`idrole` int
,`nama_role` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_master_satuan`
-- (See below for the actual view)
--
CREATE TABLE `v_master_satuan` (
`idsatuan` int
,`nama_satuan` varchar(45)
,`status` varchar(13)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_master_user`
-- (See below for the actual view)
--
CREATE TABLE `v_master_user` (
`iduser` int
,`username` varchar(45)
,`password` varchar(100)
,`idrole` int
,`nama_role` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_master_vendor`
-- (See below for the actual view)
--
CREATE TABLE `v_master_vendor` (
`idvendor` int
,`nama_vendor` varchar(100)
,`badan_hukum` char(1)
,`status` varchar(13)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_satuan_aktif`
-- (See below for the actual view)
--
CREATE TABLE `v_satuan_aktif` (
`idsatuan` int
,`nama_satuan` varchar(45)
,`status` tinyint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_vendor_aktif`
-- (See below for the actual view)
--
CREATE TABLE `v_vendor_aktif` (
`idvendor` int
,`nama_vendor` varchar(100)
,`badan_hukum` char(1)
,`status` char(1)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`idbarang`),
  ADD KEY `idsatuan` (`idsatuan`);

--
-- Indexes for table `detail_penerimaan`
--
ALTER TABLE `detail_penerimaan`
  ADD PRIMARY KEY (`iddetail_penerimaan`),
  ADD KEY `idpenerimaan` (`idpenerimaan`),
  ADD KEY `barang_idbarang` (`barang_idbarang`);

--
-- Indexes for table `detail_pengadaan`
--
ALTER TABLE `detail_pengadaan`
  ADD PRIMARY KEY (`iddetail_pengadaan`),
  ADD KEY `idbarang` (`idbarang`),
  ADD KEY `idpengadaan` (`idpengadaan`);

--
-- Indexes for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD PRIMARY KEY (`iddetail_penjualan`),
  ADD KEY `penjualan_idpenjualan` (`penjualan_idpenjualan`),
  ADD KEY `idbarang` (`idbarang`);

--
-- Indexes for table `detail_retur`
--
ALTER TABLE `detail_retur`
  ADD PRIMARY KEY (`iddetail_retur`),
  ADD KEY `idretur` (`idretur`),
  ADD KEY `iddetail_penerimaan` (`iddetail_penerimaan`);

--
-- Indexes for table `kartu_stok`
--
ALTER TABLE `kartu_stok`
  ADD PRIMARY KEY (`idkartu_stok`),
  ADD KEY `idbarang` (`idbarang`);

--
-- Indexes for table `margin_penjualan`
--
ALTER TABLE `margin_penjualan`
  ADD PRIMARY KEY (`idmargin_penjualan`),
  ADD KEY `iduser` (`iduser`);

--
-- Indexes for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD PRIMARY KEY (`idpenerimaan`),
  ADD KEY `idpengadaan` (`idpengadaan`),
  ADD KEY `iduser` (`iduser`);

--
-- Indexes for table `pengadaan`
--
ALTER TABLE `pengadaan`
  ADD PRIMARY KEY (`idpengadaan`),
  ADD KEY `user_iduser` (`user_iduser`),
  ADD KEY `vendor_idvendor` (`vendor_idvendor`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`idpenjualan`),
  ADD KEY `iduser` (`iduser`),
  ADD KEY `idmargin_penjualan` (`idmargin_penjualan`);

--
-- Indexes for table `retur`
--
ALTER TABLE `retur`
  ADD PRIMARY KEY (`idretur`),
  ADD KEY `idpenerimaan` (`idpenerimaan`),
  ADD KEY `iduser` (`iduser`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`idrole`);

--
-- Indexes for table `satuan`
--
ALTER TABLE `satuan`
  ADD PRIMARY KEY (`idsatuan`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`iduser`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idrole` (`idrole`);

--
-- Indexes for table `vendor`
--
ALTER TABLE `vendor`
  ADD PRIMARY KEY (`idvendor`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `idbarang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `detail_penerimaan`
--
ALTER TABLE `detail_penerimaan`
  MODIFY `iddetail_penerimaan` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `detail_pengadaan`
--
ALTER TABLE `detail_pengadaan`
  MODIFY `iddetail_pengadaan` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `iddetail_penjualan` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `detail_retur`
--
ALTER TABLE `detail_retur`
  MODIFY `iddetail_retur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kartu_stok`
--
ALTER TABLE `kartu_stok`
  MODIFY `idkartu_stok` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `margin_penjualan`
--
ALTER TABLE `margin_penjualan`
  MODIFY `idmargin_penjualan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penerimaan`
--
ALTER TABLE `penerimaan`
  MODIFY `idpenerimaan` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pengadaan`
--
ALTER TABLE `pengadaan`
  MODIFY `idpengadaan` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `idpenjualan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `retur`
--
ALTER TABLE `retur`
  MODIFY `idretur` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `idrole` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `satuan`
--
ALTER TABLE `satuan`
  MODIFY `idsatuan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `iduser` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vendor`
--
ALTER TABLE `vendor`
  MODIFY `idvendor` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- --------------------------------------------------------

--
-- Structure for view `v_barang_aktif`
--
DROP TABLE IF EXISTS `v_barang_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_barang_aktif`  AS SELECT `barang`.`idbarang` AS `idbarang`, `barang`.`jenis` AS `jenis`, `barang`.`nama` AS `nama`, `barang`.`idsatuan` AS `idsatuan`, `barang`.`status` AS `status` FROM `barang` WHERE (`barang`.`status` = 1) ;

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_pengadaan`
--
DROP TABLE IF EXISTS `v_laporan_pengadaan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_pengadaan`  AS SELECT `pg`.`idpengadaan` AS `idpengadaan`, `pg`.`timestamp` AS `tanggal_pengadaan`, `v`.`nama_vendor` AS `nama_vendor`, `u`.`username` AS `petugas`, `b`.`nama` AS `nama_barang`, `dp`.`jumlah` AS `jumlah`, `dp`.`harga_satuan` AS `harga_satuan`, `dp`.`sub_total` AS `sub_total`, `pg`.`total_nilai` AS `total_nilai` FROM ((((`pengadaan` `pg` join `detail_pengadaan` `dp` on((`pg`.`idpengadaan` = `dp`.`idpengadaan`))) join `barang` `b` on((`dp`.`idbarang` = `b`.`idbarang`))) join `vendor` `v` on((`pg`.`vendor_idvendor` = `v`.`idvendor`))) join `user` `u` on((`pg`.`user_iduser` = `u`.`iduser`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_penjualan`
--
DROP TABLE IF EXISTS `v_laporan_penjualan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_penjualan`  AS SELECT `p`.`idpenjualan` AS `idpenjualan`, `p`.`created_at` AS `tanggal_penjualan`, `u`.`username` AS `kasir`, `b`.`nama` AS `nama_barang`, `dp`.`jumlah` AS `jumlah`, `dp`.`harga_satuan` AS `harga_satuan`, `dp`.`subtotal` AS `subtotal`, (`dp`.`subtotal` * 0.1) AS `nilai_margin`, (`dp`.`subtotal` + (`dp`.`subtotal` * 0.1)) AS `total_per_item` FROM (((`penjualan` `p` join `detail_penjualan` `dp` on((`p`.`idpenjualan` = `dp`.`penjualan_idpenjualan`))) join `barang` `b` on((`dp`.`idbarang` = `b`.`idbarang`))) join `user` `u` on((`p`.`iduser` = `u`.`iduser`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_retur`
--
DROP TABLE IF EXISTS `v_laporan_retur`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_retur`  AS SELECT `r`.`idretur` AS `idretur`, `r`.`created_at` AS `tanggal_retur`, `u`.`username` AS `petugas`, `b`.`nama` AS `nama_barang`, `dr`.`jumlah` AS `jumlah`, `dr`.`alasan` AS `alasan`, `p`.`idpenerimaan` AS `idpenerimaan` FROM (((((`retur` `r` join `detail_retur` `dr` on((`r`.`idretur` = `dr`.`idretur`))) join `detail_penerimaan` `dp` on((`dr`.`iddetail_penerimaan` = `dp`.`iddetail_penerimaan`))) join `barang` `b` on((`dp`.`barang_idbarang` = `b`.`idbarang`))) join `penerimaan` `p` on((`r`.`idpenerimaan` = `p`.`idpenerimaan`))) join `user` `u` on((`r`.`iduser` = `u`.`iduser`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_stok`
--
DROP TABLE IF EXISTS `v_laporan_stok`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_stok`  AS SELECT `b`.`idbarang` AS `idbarang`, `b`.`nama` AS `nama_barang`, `s`.`nama_satuan` AS `nama_satuan`, (coalesce(sum(`k`.`masuk`),0) - coalesce(sum(`k`.`keluar`),0)) AS `stok_akhir`, max(`k`.`created_at`) AS `terakhir_update` FROM ((`barang` `b` left join `satuan` `s` on((`b`.`idsatuan` = `s`.`idsatuan`))) left join `kartu_stok` `k` on((`b`.`idbarang` = `k`.`idbarang`))) GROUP BY `b`.`idbarang`, `b`.`nama`, `s`.`nama_satuan` ;

-- --------------------------------------------------------

--
-- Structure for view `v_margin_penjualan_aktif`
--
DROP TABLE IF EXISTS `v_margin_penjualan_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_margin_penjualan_aktif`  AS SELECT `mp`.`idmargin_penjualan` AS `idmargin_penjualan`, `mp`.`created_at` AS `created_at`, `mp`.`persen` AS `persen`, `mp`.`status` AS `status`, `u`.`iduser` AS `iduser`, `u`.`username` AS `nama_user` FROM (`margin_penjualan` `mp` join `user` `u` on((`mp`.`iduser` = `u`.`iduser`))) WHERE (`mp`.`status` = 1) ;

-- --------------------------------------------------------

--
-- Structure for view `v_master_barang`
--
DROP TABLE IF EXISTS `v_master_barang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_master_barang`  AS SELECT `b`.`jenis` AS `jenis`, `b`.`nama` AS `nama`, `s`.`nama_satuan` AS `nama_satuan`, `b`.`harga` AS `harga`, (case when (`b`.`status` = 0) then '❎ Tidak Aktif' else '✅ Aktif' end) AS `status` FROM (`barang` `b` left join `satuan` `s` on((`b`.`idsatuan` = `s`.`idsatuan`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_master_margin_penjualan`
--
DROP TABLE IF EXISTS `v_master_margin_penjualan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_master_margin_penjualan`  AS SELECT `mp`.`idmargin_penjualan` AS `idmargin_penjualan`, `mp`.`created_at` AS `created_at`, `mp`.`persen` AS `persen`, (case when (`mp`.`status` = 0) then '❎ Tidak Aktif' else '✅ Aktif' end) AS `status`, `u`.`username` AS `username`, `u`.`username` AS `nama_user` FROM (`margin_penjualan` `mp` left join `user` `u` on((`mp`.`iduser` = `u`.`iduser`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_master_role`
--
DROP TABLE IF EXISTS `v_master_role`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_master_role`  AS SELECT `role`.`idrole` AS `idrole`, `role`.`nama_role` AS `nama_role` FROM `role` ;

-- --------------------------------------------------------

--
-- Structure for view `v_master_satuan`
--
DROP TABLE IF EXISTS `v_master_satuan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_master_satuan`  AS SELECT `s`.`idsatuan` AS `idsatuan`, `s`.`nama_satuan` AS `nama_satuan`, (case when (`s`.`status` = 0) then '❎ Tidak Aktif' else '✅ Aktif' end) AS `status` FROM `satuan` AS `s` ;

-- --------------------------------------------------------

--
-- Structure for view `v_master_user`
--
DROP TABLE IF EXISTS `v_master_user`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_master_user`  AS SELECT `u`.`iduser` AS `iduser`, `u`.`username` AS `username`, `u`.`password` AS `password`, `r`.`idrole` AS `idrole`, `r`.`nama_role` AS `nama_role` FROM (`user` `u` join `role` `r` on((`u`.`idrole` = `r`.`idrole`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_master_vendor`
--
DROP TABLE IF EXISTS `v_master_vendor`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_master_vendor`  AS SELECT `v`.`idvendor` AS `idvendor`, `v`.`nama_vendor` AS `nama_vendor`, `v`.`badan_hukum` AS `badan_hukum`, (case when (`v`.`status` = 0) then '❎ Tidak Aktif' else '✅ Aktif' end) AS `status` FROM `vendor` AS `v` ;

-- --------------------------------------------------------

--
-- Structure for view `v_satuan_aktif`
--
DROP TABLE IF EXISTS `v_satuan_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_satuan_aktif`  AS SELECT `satuan`.`idsatuan` AS `idsatuan`, `satuan`.`nama_satuan` AS `nama_satuan`, `satuan`.`status` AS `status` FROM `satuan` WHERE (`satuan`.`status` = 1) ;

-- --------------------------------------------------------

--
-- Structure for view `v_vendor_aktif`
--
DROP TABLE IF EXISTS `v_vendor_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_vendor_aktif`  AS SELECT `vendor`.`idvendor` AS `idvendor`, `vendor`.`nama_vendor` AS `nama_vendor`, `vendor`.`badan_hukum` AS `badan_hukum`, `vendor`.`status` AS `status` FROM `vendor` WHERE (`vendor`.`status` = 'A') ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`idsatuan`) REFERENCES `satuan` (`idsatuan`);

--
-- Constraints for table `detail_penerimaan`
--
ALTER TABLE `detail_penerimaan`
  ADD CONSTRAINT `detail_penerimaan_ibfk_1` FOREIGN KEY (`idpenerimaan`) REFERENCES `penerimaan` (`idpenerimaan`),
  ADD CONSTRAINT `detail_penerimaan_ibfk_2` FOREIGN KEY (`barang_idbarang`) REFERENCES `barang` (`idbarang`);

--
-- Constraints for table `detail_pengadaan`
--
ALTER TABLE `detail_pengadaan`
  ADD CONSTRAINT `detail_pengadaan_ibfk_1` FOREIGN KEY (`idbarang`) REFERENCES `barang` (`idbarang`),
  ADD CONSTRAINT `detail_pengadaan_ibfk_2` FOREIGN KEY (`idpengadaan`) REFERENCES `pengadaan` (`idpengadaan`);

--
-- Constraints for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`penjualan_idpenjualan`) REFERENCES `penjualan` (`idpenjualan`),
  ADD CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`idbarang`) REFERENCES `barang` (`idbarang`);

--
-- Constraints for table `detail_retur`
--
ALTER TABLE `detail_retur`
  ADD CONSTRAINT `detail_retur_ibfk_1` FOREIGN KEY (`idretur`) REFERENCES `retur` (`idretur`),
  ADD CONSTRAINT `detail_retur_ibfk_2` FOREIGN KEY (`iddetail_penerimaan`) REFERENCES `detail_penerimaan` (`iddetail_penerimaan`);

--
-- Constraints for table `kartu_stok`
--
ALTER TABLE `kartu_stok`
  ADD CONSTRAINT `kartu_stok_ibfk_1` FOREIGN KEY (`idbarang`) REFERENCES `barang` (`idbarang`);

--
-- Constraints for table `margin_penjualan`
--
ALTER TABLE `margin_penjualan`
  ADD CONSTRAINT `margin_penjualan_ibfk_1` FOREIGN KEY (`iduser`) REFERENCES `user` (`iduser`);

--
-- Constraints for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD CONSTRAINT `penerimaan_ibfk_1` FOREIGN KEY (`idpengadaan`) REFERENCES `pengadaan` (`idpengadaan`),
  ADD CONSTRAINT `penerimaan_ibfk_2` FOREIGN KEY (`iduser`) REFERENCES `user` (`iduser`);

--
-- Constraints for table `pengadaan`
--
ALTER TABLE `pengadaan`
  ADD CONSTRAINT `pengadaan_ibfk_1` FOREIGN KEY (`user_iduser`) REFERENCES `user` (`iduser`),
  ADD CONSTRAINT `pengadaan_ibfk_2` FOREIGN KEY (`vendor_idvendor`) REFERENCES `vendor` (`idvendor`);

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`iduser`) REFERENCES `user` (`iduser`),
  ADD CONSTRAINT `penjualan_ibfk_2` FOREIGN KEY (`idmargin_penjualan`) REFERENCES `margin_penjualan` (`idmargin_penjualan`);

--
-- Constraints for table `retur`
--
ALTER TABLE `retur`
  ADD CONSTRAINT `retur_ibfk_1` FOREIGN KEY (`idpenerimaan`) REFERENCES `penerimaan` (`idpenerimaan`),
  ADD CONSTRAINT `retur_ibfk_2` FOREIGN KEY (`iduser`) REFERENCES `user` (`iduser`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`idrole`) REFERENCES `role` (`idrole`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
