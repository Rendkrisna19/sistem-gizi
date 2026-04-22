<?php
session_start();
require_once '../../database/config.php';
require_once '../../vendor/autoload.php'; // Panggil library Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    die("Akses ditolak!");
}

// Ambil Data
$sql = "SELECT rk.*, b.nama_balita, b.nik, b.jenis_kelamin 
        FROM riwayat_klasifikasi rk 
        JOIN data_balita b ON rk.id_balita = b.id 
        WHERE rk.id_user = ? 
        ORDER BY rk.tanggal_ukur DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$data = $stmt->fetchAll();

// Inisialisasi Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set Judul
$sheet->setCellValue('A1', 'REKAPITULASI STATUS GIZI BALITA (METODE K-NN)');
$sheet->setCellValue('A2', 'Petugas: ' . $_SESSION['nama_lengkap']);
$sheet->setCellValue('A3', 'Tanggal Cetak: ' . date('d-m-Y'));

// Merge Cells untuk Judul
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header Tabel
$sheet->setCellValue('A5', 'NO');
$sheet->setCellValue('B5', 'TANGGAL UKUR');
$sheet->setCellValue('C5', 'NIK BALITA');
$sheet->setCellValue('D5', 'NAMA BALITA');
$sheet->setCellValue('E5', 'KELAMIN');
$sheet->setCellValue('F5', 'UMUR (BLN)');
$sheet->setCellValue('G5', 'BERAT (KG)');
$sheet->setCellValue('H5', 'TINGGI (CM)');
$sheet->setCellValue('I5', 'STATUS GIZI (KNN)');

// Styling Header (Bold + Center)
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF047857']]
];
$sheet->getStyle('A5:I5')->applyFromArray($headerStyle);

// Isi Data
$row_num = 6;
$no = 1;
foreach ($data as $row) {
    $jk = $row['jenis_kelamin'] == 1 ? 'Laki-laki' : 'Perempuan';
    
    $sheet->setCellValue('A' . $row_num, $no++);
    $sheet->setCellValue('B' . $row_num, date('d-m-Y', strtotime($row['tanggal_ukur'])));
    $sheet->setCellValue('C' . $row_num, "'" . $row['nik']); // Tambah petik agar NIK tidak jadi format exponensial
    $sheet->setCellValue('D' . $row_num, $row['nama_balita']);
    $sheet->setCellValue('E' . $row_num, $jk);
    $sheet->setCellValue('F' . $row_num, $row['umur_saat_ukur']);
    $sheet->setCellValue('G' . $row_num, $row['berat_badan']);
    $sheet->setCellValue('H' . $row_num, $row['tinggi_badan']);
    $sheet->setCellValue('I' . $row_num, strtoupper($row['hasil_klasifikasi']));
    
    $row_num++;
}

// Auto size kolom biar rapi
foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Berikan Border
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyle('A5:I' . ($row_num - 1))->applyFromArray($styleArray);

// Set Header HTTP agar otomatis Download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Rekap_Gizi_KNN.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>