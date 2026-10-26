<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ArsipSurat;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SuratChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Statistik Surat per Bulan';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $tahun = (int) ($this->filters['tahun'] ?? date('Y'));

        // 1. Ambil data dalam 1 KALI QUERY menggunakan GROUP BY
        $rawData = ArsipSurat::query()
            ->selectRaw('MONTH(tanggal_surat) as bulan, jenis, COUNT(*) as total')
            ->whereYear('tanggal_surat', $tahun)
            ->groupBy('bulan', 'jenis')
            ->get();

        // 2. Siapkan array kosong untuk 12 bulan (default: 0)
        $dataMasuk = array_fill(1, 12, 0);
        $dataKeluar = array_fill(1, 12, 0);

        // 3. Masukkan hasil query ke dalam array berdasarkan bulannya
        foreach ($rawData as $row) {
            if ($row->jenis === 'masuk') {
                $dataMasuk[$row->bulan] = $row->total;
            } else {
                $dataKeluar[$row->bulan] = $row->total;
            }
        }

        // 4. Buat label bulan (Jan, Feb, Mar, dst)
        $bulanLabels = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanLabels[] = Carbon::create($tahun, $i, 1)->isoFormat('MMM');
        }

        // 5. Ubah key array 1-12 menjadi 0-11 (karena Chart.js mulai dari index 0)
        return [
            'datasets' => [
                [
                    'label' => 'Surat Masuk',
                    'data' => array_values($dataMasuk), // ini akan menghasilkan array 0-11
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Surat Keluar',
                    'data' => array_values($dataKeluar), // ini akan menghasilkan array 0-11
                    'backgroundColor' => 'rgba(245, 158, 11, 0.7)',
                    'borderColor' => 'rgb(245, 158, 11)',
                ],
            ],
            'labels' => $bulanLabels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
