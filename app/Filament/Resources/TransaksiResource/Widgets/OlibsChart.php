<?php

namespace App\Filament\Resources\TransaksiResource\Widgets;

use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class OlibsChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Perbandingan Transaksi 5 Jam Terakhir';
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '5s';

    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '400px';

    public ?string $filter = 'today';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hari ini',
            'yesterday' => 'Kemarin',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;

        // Mulai query dari semua transaksi
        $query = Transaksi::query();
        
        // Sesuaikan berdasarkan filter
        if ($filter === 'today') {
            $query->whereDate('waktu_transaksi', now()->toDateString());
        } elseif ($filter === 'yesterday') {
            $query->whereDate('waktu_transaksi', now()->subDay()->toDateString());
        } 

        // Ambil 10 data terbaru dari hasil query
        $data = $query->latest('waktu_transaksi')->take(10)->get();
        
        // Kelompokkan berdasarkan jam (format: Y-m-d H)
        $grouped = $data->groupBy(function ($item) {
            return Carbon::parse($item->waktu_transaksi)->format('Y-m-d H');
        });
        
        // Ambil data terbaru dari setiap kelompok jam
        $latestPerHour = $grouped->map(function (Collection $items) {
            return $items->sortByDesc('waktu_transaksi')->first();
        });
        
        // Ambil 5 kelompok terbaru berdasarkan jam
        $latest5 = $latestPerHour->sortByDesc(function ($item) {
            return $item->waktu_transaksi;
        })->take(5)->reverse(); // reverse untuk urutan dari lama ke baru
        
        // Ubah format untuk chart
        $groupedData = $latest5->map(function ($item) {
            return [
                'tanggal' => Carbon::parse($item->waktu_transaksi)->format('d M H:i'),
                'saldo' => $item->kewajiban_olibs,
                'outgoing' => $item->outgoing_ossw,
                'incoming' => $item->incoming_ossw,
                'selisih' => $item->selisih,
            ];
        })->values();
        
        
        return [
            'datasets' => [
                [
                    'label' => "Kewajiban",
                    'data' => $groupedData->pluck('saldo'),
                    'backgroundColor' => 'rgba(0, 123, 255, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => "Outgoing",
                    'data' => $groupedData->pluck('outgoing'),
                    'backgroundColor' => 'rgba(255, 159, 64, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => "Selisih",
                    'data' => $groupedData->pluck('selisih'),
                    'backgroundColor' => 'rgba(128, 128, 128, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => "Incoming",
                    'data' => $groupedData->pluck('incoming'),
                    'backgroundColor' => 'rgba(0, 255, 0, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $groupedData->pluck('tanggal')->map(function($tanggal) {
                // Parse the date and set the time to 19:00 (remove minutes and seconds)
                return \Carbon\Carbon::createFromFormat('d M H:i', $tanggal)->setMinute(0)->setSecond(0)->format('d M H:i');
            }),
        ];
        
    }



    protected function getType(): string
    {
        return 'bar'; // atau 'bar'
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 2000000000, // Tanpa IDR
                    ],
                ],
            ],
        ];
    }

}
