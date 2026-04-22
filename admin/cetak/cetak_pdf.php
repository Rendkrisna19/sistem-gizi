<?php
session_start();
require_once '../../database/config.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Rekapitulasi Gizi Keseluruhan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        .kop-surat { text-align: center; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .kop-surat h1 { margin: 5px 0; font-size: 20px; color: #047857; }
        .kop-surat p { margin: 0; font-size: 11px; }
        
        .info-laporan { margin-bottom: 15px; text-align: right; font-weight: bold; }
        
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th, table.data td { border: 1px solid #999; padding: 6px; text-align: center; }
        table.data th { background-color: #047857; color: #fff; font-weight: bold; }
        table.data tr:nth-child(even) { background-color: #f9f9f9; }
        
        .ttd { width: 100%; margin-top: 40px; }
        .ttd td { width: 33%; text-align: center; }
    </style>
</head>
<body>

    <div class="kop-surat">
        <h2>Pemerintah Provinsi Lampung</h2>
        <h1>DATA REKAPITULASI GIZI BALITA (K-NN)</h1>
        <p>Laporan Keseluruhan Hasil Pemeriksaan Posyandu</p>
    </div>

    <div class="info-laporan">
        Tanggal Cetak: ' . date('d F Y') . '
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Ukur</th>
                <th>Nama Balita</th>
                <th>L/P</th>
                <th>Umur</th>
                <th>BB / TB</th>
                <th>Status Gizi</th>
                <th>Petugas (Bidan)</th>
            </tr>
        </thead>
        <tbody>';

        $no = 1;
        if(count($data) > 0){
            foreach ($data as $row) {
                $jk = $row['jenis_kelamin'] == 1 ? 'L' : 'P';
                $html .= '<tr>
                    <td>'.$no++.'</td>
                    <td>'.date('d/m/Y', strtotime($row['tanggal_ukur'])).'</td>
                    <td style="text-align:left;"><b>'.$row['nama_balita'].'</b></td>
                    <td>'.$jk.'</td>
                    <td>'.$row['umur_saat_ukur'].' bln</td>
                    <td>'.$row['berat_badan'].'kg / '.$row['tinggi_badan'].'cm</td>
                    <td><strong>'.strtoupper($row['hasil_klasifikasi']).'</strong></td>
                    <td style="text-align:left;">'.$row['nama_petugas'].'</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8">Belum ada data riwayat pemeriksaan.</td></tr>';
        }

$html .= '</tbody>
    </table>

    <table class="ttd">
        <tr>
            <td></td>
            <td></td>
            <td>
                Lampung, ' . date('d F Y') . '<br>
                Administrator Sistem,<br><br><br><br>
                <b><u>' . $_SESSION['nama_lengkap'] . '</u></b>
            </td>
        </tr>
    </table>

</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
// Diubah ke Landscape karena kolomnya banyak (ada kolom Petugas)
$dompdf->setPaper('A4', 'landscape'); 
$dompdf->render();

$dompdf->stream("Rekap_Gizi_Admin_SIPEGIZI.pdf", array("Attachment" => 0));
?>