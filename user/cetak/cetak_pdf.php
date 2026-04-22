<?php
session_start();
// Mundur 2 folder (../..) karena posisi file ini ada di user/cetak/
require_once '../../database/config.php';
require_once '../../vendor/autoload.php'; // Panggil library dari Composer

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    die("Akses ditolak!");
}

// Ambil data riwayat
$sql = "SELECT rk.*, b.nama_balita, b.nik, b.jenis_kelamin 
        FROM riwayat_klasifikasi rk 
        JOIN data_balita b ON rk.id_balita = b.id 
        WHERE rk.id_user = ? 
        ORDER BY rk.tanggal_ukur DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$data = $stmt->fetchAll();

// ==========================================
// DESAIN HTML UNTUK PDF
// ==========================================
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Riwayat Gizi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .kop-surat { text-align: center; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .kop-surat h1 { margin: 5px 0; font-size: 22px; color: #047857; }
        .kop-surat p { margin: 0; font-size: 12px; }
        
        .info-laporan { margin-bottom: 15px; }
        .info-laporan table { width: 100%; }
        .info-laporan td { padding: 2px 0; }
        
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th, table.data td { border: 1px solid #999; padding: 8px; text-align: center; }
        table.data th { background-color: #047857; color: #fff; font-weight: bold; }
        table.data tr:nth-child(even) { background-color: #f9f9f9; }
        
        .ttd { width: 100%; margin-top: 50px; }
        .ttd td { width: 50%; text-align: center; }
    </style>
</head>
<body>

    <div class="kop-surat">
        <h2>Pemerintah Provinsi Lampung</h2>
        <h1>SIPEGIZI POSYANDU</h1>
        <p>Sistem Klasifikasi Status Gizi Balita Metode K-Nearest Neighbor</p>
    </div>

    <div class="info-laporan">
        <table>
            <tr>
                <td width="15%"><strong>Petugas Bidan</strong></td>
                <td width="2%">:</td>
                <td>' . $_SESSION['nama_lengkap'] . '</td>
                <td width="15%" align="right"><strong>Tanggal Cetak</strong></td>
                <td width="2%">:</td>
                <td width="20%">' . date('d F Y') . '</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Balita (NIK)</th>
                <th>L/P</th>
                <th>Umur</th>
                <th>BB / TB</th>
                <th>Status Gizi</th>
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
                    <td style="text-align:left;"><b>'.$row['nama_balita'].'</b><br><small>'.$row['nik'].'</small></td>
                    <td>'.$jk.'</td>
                    <td>'.$row['umur_saat_ukur'].' bln</td>
                    <td>'.$row['berat_badan'].'kg / '.$row['tinggi_badan'].'cm</td>
                    <td><strong>'.strtoupper($row['hasil_klasifikasi']).'</strong></td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="7">Belum ada data riwayat pemeriksaan.</td></tr>';
        }

$html .= '</tbody>
    </table>

    <table class="ttd">
        <tr>
            <td></td>
            <td>
                Lampung, ' . date('d F Y') . '<br>
                Petugas Pemeriksa,<br><br><br><br>
                <b><u>' . $_SESSION['nama_lengkap'] . '</u></b>
            </td>
        </tr>
    </table>

</body>
</html>';

// ==========================================
// SETUP DOMPDF
// ==========================================
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Tampilkan PDF di browser (Attachment 0 = View, 1 = Otomatis Download)
$dompdf->stream("Laporan_Riwayat_Gizi.pdf", array("Attachment" => 0));
?>