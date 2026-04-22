<?php
session_start();
require_once '../../database/config.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

$sql = "SELECT rk.*, b.nama_balita, b.nik, b.jenis_kelamin, u.nama_lengkap as nama_petugas 
        FROM riwayat_klasifikasi rk 
        JOIN data_balita b ON rk.id_balita = b.id 
        JOIN users u ON rk.id_user = u.id 
        ORDER BY rk.tanggal_ukur DESC";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set Judul
$sheet->setCellValue('A1', 'REKAPITULASI KESELURUHAN STATUS GIZI BALITA (ADMIN)');
$sheet->setCellValue('A2', 'Tanggal Cetak: ' . date('d-m-Y'));

// Merge Cells untuk Judul
$sheet->mergeCells('A1:J1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header Tabel
$sheet->setCellValue('A4', 'NO');
$sheet->setCellValue('B4', 'TANGGAL UKUR');
$sheet->setCellValue('C4', 'PETUGAS (BIDAN)');
$sheet->setCellValue('D4', 'NIK BALITA');
$sheet->setCellValue('E4', 'NAMA BALITA');
$sheet->setCellValue('F4', 'KELAMIN');
$sheet->setCellValue('G4', 'UMUR (BLN)');
$sheet->setCellValue('H4', 'BERAT (KG)');
$sheet->setCellValue('I4', 'TINGGI (CM)');
$sheet->setCellValue('J4', 'STATUS GIZI');

// Styling Header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF047857']]
];
$sheet->getStyle('A4:J4')->applyFromArray($headerStyle);

// Isi Data
$row_num = 5;
$no = 1;
foreach ($data as $row) {
    $jk = $row['jenis_kelamin'] == 1 ? 'Laki-laki' : 'Perempuan';
    
    $sheet->setCellValue('A' . $row_num, $no++);
    $sheet->setCellValue('B' . $row_num, date('d-m-Y', strtotime($row['tanggal_ukur'])));
    $sheet->setCellValue('C' . $row_num, $row['nama_petugas']);
    $sheet->setCellValue('D' . $row_num, "'" . $row['nik']); 
    $sheet->setCellValue('E' . $row_num, $row['nama_balita']);
    $sheet->setCellValue('F' . $row_num, $jk);
    $sheet->setCellValue('G' . $row_num, $row['umur_saat_ukur']);
    $sheet->setCellValue('H' . $row_num, $row['berat_badan']);
    $sheet->setCellValue('I' . $row_num, $row['tinggi_badan']);
    $sheet->setCellValue('J' . $row_num, strtoupper($row['hasil_klasifikasi']));
    
    $row_num++;
}

// Auto size kolom
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Border
$styleArray = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$sheet->getStyle('A4:J' . ($row_num - 1))->applyFromArray($styleArray);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Rekap_Master_Gizi_KNN.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>