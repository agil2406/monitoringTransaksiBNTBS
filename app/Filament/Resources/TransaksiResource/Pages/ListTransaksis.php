<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransaksis extends ListRecords
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data'),
        ];
    }

    protected function getTableEmptyStateHeading(): string
    {
        return 'Tidak ada data transaksi';
    }
    protected function getTableEmptyStateDescription(): string
    {
        return 'Silakan tambahkan data transaksi baru.';
    }
}
